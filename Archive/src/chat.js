// Crest Himalaya Assistant: a rule-based helper that runs in the browser (no AI service, no API key).
// It answers buttons and typed questions from the website content (treks, departures, FAQs, trip info),
// which comes from the CMS, so editing that content in the admin updates the assistant too.
// Every text and button name is editable in Admin -> Buttons & Labels -> "Chat assistant" (keys "chat.*").
// The assistant always speaks English (client decision): when the website is shown in another
// language, the CMS also sends the English treks and FAQs for it (CHAT_DATA), and the chat.* texts are English.
import { TREKS as SITE_TREKS, FAQS as SITE_FAQS, CHAT_DATA, CHAT_INFO as INFO } from "./data.js";
import { fmt, upcoming } from "./treks.js";
import { esc, img, t as tx } from "./cms.js";

// C(key, englishDefault, vars) -> the CMS text for "chat.<key>", else the English default
const C = (key, def, vars) => tx("chat." + key, def, vars);
const H = (key, def, vars) => esc(C(key, def, vars)); // same, HTML-escaped

let TREKS = SITE_TREKS, FAQS = SITE_FAQS;
const trekById = (id) => TREKS.find((x) => x.id === id);
const fmtDate = (iso, opts = { day: "numeric", month: "short", year: "numeric" }) => new Date(iso + "T00:00:00").toLocaleDateString("en-GB", opts);

const SEASONS = { spring: [3, 4, 5], autumn: [9, 10, 11], winter: [12, 1, 2], monsoon: [6, 7, 8] };
const MONTHS = ["january", "february", "march", "april", "may", "june", "july", "august", "september", "october", "november", "december"];

// words people use for each trek
const ALIASES = {
  ebc: ["everest base camp", "ebc", "base camp trek", "everest"], acircuit: ["annapurna circuit", "circuit", "thorong"], abc: ["annapurna base camp", "abc", "annapurna sanctuary"],
  gokyo: ["gokyo", "renjo"], mera: ["mera"], manaslu: ["manaslu", "larkya"], langtang: ["langtang"], island: ["island peak", "imja tse", "island"],
  mustang: ["mustang", "lo manthang"], poonhill: ["poon hill", "poonhill", "ghorepani"], kanchenjunga: ["kanchenjunga", "kangchenjunga", "pangpema"],
};

export function initChat(api) {
  TREKS = CHAT_DATA.treks || SITE_TREKS;
  FAQS = CHAT_DATA.faqs || SITE_FAQS;
  const MAIN = [
    ["find", C("btn_find", "🥾 Find a Trek")], ["expeditions", C("btn_expeditions", "🏔️ Explore Expeditions")],
    ["availability", C("btn_availability", "📅 Check Availability")], ["prices", C("btn_prices", "💰 Prices")],
    ["packing", C("btn_packing", "🎒 Packing Guide")], ["faqs", C("btn_faqs", "❓ FAQs")],
    ["book", C("btn_book", "📝 Book Now")], ["team", C("btn_team", "👨‍💼 Talk to Team")],
  ];
  const MORE = [["travel", C("btn_travel", "📍 Travel info")], ["safety", C("btn_safety", "🚨 Safety & emergency")], ["fitness", C("btn_fitness", "💪 Fitness")]];

  document.body.insertAdjacentHTML("beforeend", `
    <button class="chat-launch" id="chat-launch" lang="en" aria-controls="chat" aria-expanded="false">
      <span class="chat-launch__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5h16v11H9l-5 4z"/><path d="M8 10h8M8 13h5"/></svg></span>
      <span class="chat-launch__label">${H("launcher", "Ask our trek assistant")}</span>
    </button>
    <section class="chat" id="chat" lang="en" dir="ltr" role="dialog" aria-modal="false" aria-labelledby="chat-title" hidden>
      <header class="chat__head">
        <svg class="chat__mark" viewBox="0 0 100 110" aria-hidden="true"><use href="#logo-mark" /></svg>
        <div><h2 id="chat-title">${H("title", "Crest Himalaya Assistant")}</h2><p><i aria-hidden="true"></i>${H("status", "Online · Here to help")}</p></div>
        <button class="chat__icon-btn" data-chat-act="menu" aria-label="${H("menu_aria", "Main menu")}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
        <button class="chat__icon-btn" id="chat-close" aria-label="${H("close_aria", "Close assistant")}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
      </header>
      <div class="chat__log" id="chat-log" role="log" aria-live="polite"></div>
      <form class="chat__input" id="chat-form" autocomplete="off">
        <label class="sr-only" for="chat-text">${H("placeholder", "Type your message...")}</label>
        <input id="chat-text" type="text" placeholder="${H("placeholder", "Type your message...")}" maxlength="300" />
        <button type="submit" aria-label="${H("send", "Send")}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12 20 4l-6 16-3-7z"/></svg></button>
      </form>
    </section>`);

  const launch = document.getElementById("chat-launch"), box = document.getElementById("chat"), log = document.getElementById("chat-log");
  const input = document.getElementById("chat-text"), REDUCED = matchMedia("(prefers-reduced-motion: reduce)").matches;
  let started = false, find = {}, lastTrek = null;
  const history = []; // short transcript for the hand-off to a human

  // ---------- rendering ----------
  const scroll = () => { log.scrollTop = log.scrollHeight; };
  function user(text) {
    history.push("Me: " + text);
    log.insertAdjacentHTML("beforeend", `<div class="msg msg--user">${esc(text)}</div>`);
    scroll();
  }
  // bot(html, chips): chips are [[action, label], ...]; html is built here from our own data only
  function typingDots() {
    const typing = document.createElement("div");
    typing.className = "msg msg--bot msg--typing"; typing.innerHTML = "<span></span><span></span><span></span>";
    log.append(typing); scroll();
    return typing;
  }
  function botNow(html, chips = []) {
    history.push("Assistant: " + html.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim().slice(0, 140));
    log.insertAdjacentHTML("beforeend", `<div class="msg msg--bot">${html}</div>${chips.length ? `<div class="chips-row">${chips.map(([a, l]) => `<button class="chip-btn" data-chat-act="${esc(a)}">${esc(l)}</button>`).join("")}</div>` : ""}`);
    scroll();
  }
  function bot(html, chips = []) {
    const typing = typingDots();
    setTimeout(() => { typing.remove(); botNow(html, chips); }, REDUCED ? 0 : 420);
  }
  const p = (key, def, vars) => `<p>${H(key, def, vars)}</p>`;

  const back = [["menu", C("btn_menu", "↩ Main menu")]];
  const short = (name) => name.replace(/ Trek$| Climb$/, "");
  const trekChips = (filterFn = () => true, prefix = "trek") => TREKS.filter(filterFn).map((x) => [`${prefix}:${x.id}`, short(x.name)]);

  function card(x) {
    const next = upcoming(x.id)[0];
    return `<div class="tcard"><img src="${esc(img(x.img))}" alt="" loading="lazy" /><div>
      <strong>${esc(x.name)}</strong>
      <span>${H("card_days", "{n} days", { n: x.days })} · ${esc(x.alt)} · ${esc(x.level)}</span>
      <span>${H("card_from", "From")} <b>${esc(tx("trek.currency", "USD"))} ${fmt(x.price)}</b>${next ? ` · ${H("card_next", "next {date}", { date: fmtDate(next.date, { day: "numeric", month: "short" }) })}` : ""}</span></div></div>`;
  }

  // ---------- actions ----------
  function act(a) {
    const [k, v] = a.split(":");
    switch (k) {
      case "menu": return bot(H("q_menu", "What can I help you with?"), [...MAIN, ...MORE]);
      case "more": return bot(H("q_more", "Here's more I can help with:"), [...MORE, ...back]);

      // 1-2. trek discovery
      case "find": find = {}; return bot(H("q_level", "Let's find your trek. How challenging would you like it?"), [["lvl:easy", C("opt_easy", "Easy")], ["lvl:moderate", C("opt_moderate", "Moderate")], ["lvl:challenging", C("opt_challenging", "Challenging")], ["lvl:any", C("opt_not_sure", "Not sure")]]);
      case "lvl": find.level = v; return bot(H("q_days", "How many days do you have?"), [["dur:short", C("opt_short", "Up to 10 days")], ["dur:mid", C("opt_mid", "11 to 15 days")], ["dur:long", C("opt_long", "16+ days")], ["dur:any", C("opt_flexible", "Flexible")]]);
      case "dur": find.dur = v; return bot(H("q_season", "When would you like to go?"), [["sea:spring", C("opt_spring", "Spring (Mar–May)")], ["sea:autumn", C("opt_autumn", "Autumn (Sep–Nov)")], ["sea:winter", C("opt_winter", "Winter (Dec–Feb)")], ["sea:monsoon", C("opt_monsoon", "Monsoon (Jun–Aug)")], ["sea:any", C("opt_not_sure", "Not sure")]]);
      case "sea": find.season = v; return bot(H("q_budget", "And your budget per person, before flights?"), [["bud:low", C("opt_bud_low", "Under USD 1,000")], ["bud:mid", C("opt_bud_mid", "USD 1,000–2,000")], ["bud:high", C("opt_bud_high", "USD 2,000+")], ["bud:any", C("opt_flexible", "Flexible")]]);
      case "bud": find.budget = v; return recommend(find);

      // 3. details
      case "trek": { const x = trekById(v); if (!x) return act("menu"); lastTrek = x.id; return details(x); }
      case "open": api.openTrek(v); return bot(H("opened", "I've opened the full trek page for you. Anything else?"), [[`dates:${v}`, C("btn_dates", "Dates")], [`book:${v}`, C("btn_book_short", "Book")], ...back]);
      case "show": api.setFilter(v, true); close(); return;

      // 4. availability
      case "availability": return bot(H("q_dates", "Which trek would you like dates for?"), [["dates:all", C("btn_all_dates", "All upcoming dates")], ...trekChips(undefined, "dates")]);
      case "dates": return dates(v === "all" ? null : v);
      case "dep": { const [id, date] = v.split("@"); close(); api.prefill(id, date.slice(0, 7), "Join a group"); return; }

      // 5. pricing
      case "prices": return prices();

      // 6. preparation
      case "packing": return bot(`${p("packing_intro", "Here are the essentials for a Himalayan trek:")}<ul class="msg__list">${INFO.packing.map(([h, d]) => `<li>${h ? `<b>${esc(h)}:</b> ` : ""}${esc(d)}</li>`).join("")}</ul>${p("packing_outro", "Most gear can be rented or bought in Kathmandu. We send a full personal list after booking.")}`, [["fitness", C("btn_fitness_tips", "Fitness tips")], ["faq-pick", C("btn_more_faqs", "More FAQs")], ...back]);
      case "fitness": return bot(esc(INFO.fitness), [["find", C("btn_find_me", "Find a trek for me")], ["packing", C("btn_packing_guide", "Packing guide")], ...back]);

      // 7. booking
      case "book": return v ? bookTrek(trekById(v)) : bot(`${p("book_intro", "Booking takes three steps:")}<ol class="msg__list"><li>${H("book_step1", "Tell us your trek, dates and group size.")}</li><li>${H("book_step2", "We confirm availability and price within 24 hours.")}</li><li>${esc(INFO.deposit)}</li></ol>${p("book_which", "Which trek would you like to book?")}`, [["form:", C("btn_not_sure_help", "Not sure yet, help me")], ...trekChips(undefined, "book")]);
      case "form": close(); api.prefill(v || "", null, null); return;

      // 8. FAQs
      case "faqs": case "faq-pick": return bot(H("q_faq", "Pick a question:"), [...FAQS.map((f, i) => [`faq:${i}`, f.q]), ...back]);
      case "faq": return FAQS[Number(v)] ? bot(esc(FAQS[Number(v)].a), [["faq-pick", C("btn_another", "Another question")], ["team", C("btn_ask_team", "Ask the team")], ...back]) : act("faqs");

      // 9. travel info
      case "travel": return bot(`<ul class="msg__list"><li><b>${H("lbl_pickup", "Pickup")}:</b> ${esc(INFO.meeting)}</li><li><b>${H("lbl_briefing", "Briefing")}:</b> ${esc(INFO.briefing)}</li><li><b>${H("lbl_reporting", "Reporting time")}:</b> ${esc(INFO.reporting)}</li><li><b>${H("lbl_hotel", "Accommodation")}:</b> ${esc(INFO.hotel)}</li></ul>`, [["faq:2", C("btn_insurance", "Insurance")], ["safety", C("btn_safety_info", "Safety info")], ...back]);

      // 10. safety
      case "safety": return bot(`<ul class="msg__list">${INFO.emergency.map((e) => `<li>${esc(e)}</li>`).join("")}</ul><p>${H("safety_urgent", "For anything urgent before or during your trip, call or WhatsApp us on")} <a href="tel:+${esc(INFO.whatsapp)}">${esc(INFO.phone)}</a>.</p>`, [["faq:3", C("btn_altitude", "Altitude sickness")], ["team", C("btn_talk_team", "Talk to the team")], ...back]);

      // expeditions
      case "expeditions": return bot(`${p("exp_intro", "Our peak climbs are guided, non-technical to moderately technical summits with glacier training on the trip:")}${TREKS.filter((x) => x.cat === "peak").map(card).join("")}${p("exp_outro", "For 7,000 and 8,000 m expeditions, our team plans each one personally.")}`, [...trekChips((x) => x.cat === "peak"), ["team", C("btn_ask_exp", "Ask about expeditions")], ...back]);

      // 11. human support
      case "team": return team();
      default: return act("menu");
    }
  }

  function details(x) {
    const next = upcoming(x.id)[0];
    const included = x.inclusions?.length ? x.inclusions.join(", ") : "";
    bot(`${card(x)}<p>${esc(x.summary)}</p>
      <ul class="msg__list"><li><b>${H("lbl_max_alt", "Max altitude")}:</b> ${esc(x.alt)}</li><li><b>${H("lbl_grade", "Grade")}:</b> ${esc(x.grade)}</li><li><b>${H("lbl_region", "Region")}:</b> ${esc(x.region)}${x.start ? `, ${H("starts_in", "starts in")} ${esc(x.start)}` : ""}</li><li><b>${H("lbl_season", "Best season")}:</b> ${esc(x.season)}</li>
      ${x.highlights.length ? `<li><b>${H("lbl_highlights", "Highlights")}:</b> ${esc(x.highlights.join("; "))}</li>` : ""}</ul>
      ${x.itinerary.length ? `<p><b>${H("lbl_itinerary", "Itinerary")}:</b></p><ul class="msg__list msg__list--it">${x.itinerary.map((d) => `<li>${esc(d)}</li>`).join("")}</ul>` : ""}
      ${included ? `<p><b>${H("lbl_included", "Included")}:</b> ${esc(included)}.</p>` : ""}
      ${next ? p("next_dep", "Next group departure: {date} ({n} seats).", { date: fmtDate(next.date), n: next.seats }) : ""}`,
    [[`open:${x.id}`, C("btn_open_page", "Open trek page")], [`dates:${x.id}`, C("btn_all_dates2", "All dates")], [`book:${x.id}`, C("btn_book_this", "Book this trek")], ...back]);
  }

  function dates(id) {
    const list = upcoming(id).slice(0, id ? 6 : 8);
    if (!list.length) return bot(H("no_dates", "There are no fixed group dates for {trek} right now, but every trek runs privately on any date you choose.", { trek: id ? trekById(id)?.name || "" : C("this_trek", "this trek") }), [[`book:${id || ""}`, C("btn_private", "Plan a private trip")], ["team", C("btn_ask_team", "Ask the team")], ...back]);
    bot(`${id ? p("dates_for", "Upcoming group departures for {trek}:", { trek: trekById(id).name }) : p("dates_all", "Upcoming group departures:")}
      <ul class="msg__list msg__deps">${list.map((d) => `<li><b>${esc(fmtDate(d.date))}</b> · ${id ? "" : esc(trekById(d.trek).name) + " · "}<span class="${d.seats <= 3 ? "low" : ""}">${H("seats_left", "{n} seats left", { n: d.seats })}</span></li>`).join("")}</ul>
      ${p("dates_private", "Can't make these? Every trek also runs privately on your dates.")}`,
    [...list.slice(0, 4).map((d) => [`dep:${d.trek}@${d.date}`, C("btn_book_date", "Book {date}", { date: fmtDate(d.date, { day: "numeric", month: "short" }) }) + (id ? "" : " · " + short(trekById(d.trek).name))]), ...back]);
  }

  function prices() {
    const rows = [...TREKS].sort((a, b) => a.price - b.price).map((x) => `<li><span>${esc(x.name)}</span><b>${esc(tx("trek.currency", "USD"))} ${fmt(x.price)}</b></li>`).join("");
    bot(`${p("prices_intro", "Prices are per person, from:")}<ul class="msg__prices">${rows}</ul>
      ${INFO.offers?.length ? `<p><b>${H("lbl_offers", "Offers")}:</b> ${esc(INFO.offers.join(" · "))}.</p>` : ""}<p><b>${H("lbl_payment", "Payment")}:</b> ${esc(INFO.deposit)} ${H("we_accept", "We accept")} ${esc(INFO.payments)}</p>
      ${p("prices_note", "Prices include permits, guide, porters, teahouses, meals on trail and Kathmandu hotel. International flights, visa, insurance and tips are extra.")}`,
    [["find", C("btn_budget", "Find a trek in my budget")], ["book", C("btn_book_now", "Book now")], ...back]);
  }

  function bookTrek(x) {
    if (!x) return act("book");
    const deps = upcoming(x.id).slice(0, 3);
    bot(`${p("great_choice", "Great choice: {trek}, from USD {price}.", { trek: x.name, price: fmt(x.price) })}<p>${deps.length ? H("book_has_dates", "Join a group date below, or open the booking form to choose your own dates.") : H("book_private", "It runs privately on any date. Open the booking form to choose yours.")} ${esc(INFO.deposit)}</p>`,
      [...deps.map((d) => [`dep:${x.id}@${d.date}`, C("btn_join", "Join {date}", { date: fmtDate(d.date, { day: "numeric", month: "short" }) })]), [`form:${x.id}`, C("btn_open_form", "Open booking form")], ["team", C("btn_wa_book", "Book on WhatsApp")], ...back]);
  }

  function team() {
    const asked = history.filter((h) => h.startsWith("Me: ")).slice(-5).map((h) => "- " + h.slice(4));
    const msg = C("wa_message", "Namaste Crest Himalaya, I was chatting with your website assistant.") + (asked.length ? `\n\n${C("wa_asked", "What I asked about:")}\n` + asked.join("\n") : "");
    bot(`<p>${H("team_intro", "Our trek planners are happy to help.")} ${esc(INFO.hours)}</p>
      <div class="msg__contact">
        <a class="btn btn--primary" href="https://wa.me/${esc(INFO.whatsapp)}?text=${encodeURIComponent(msg)}" target="_blank" rel="noopener">${H("btn_wa", "WhatsApp the team")}</a>
        <a class="btn btn--outline" href="mailto:${esc(INFO.email)}?subject=${encodeURIComponent(C("email_subject", "Question from the website"))}&body=${encodeURIComponent(msg)}">${H("btn_email", "Email us")}</a>
      </div>
      <p class="msg__small">${H("lbl_call", "Call")}: <a href="tel:+${esc(INFO.whatsapp)}">${esc(INFO.phone)}</a>${INFO.phone2 ? ` · <a href="tel:${esc(INFO.phone2.replace(/\s/g, ""))}">${esc(INFO.phone2)}</a>` : ""}</p>`, back);
  }

  // ---------- recommendation ----------
  function recommend(f) {
    const tests = {
      level: (x) => ({ easy: /easy/i.test(x.level), moderate: /moderate/i.test(x.level), challenging: /challenging|strenuous/i.test(x.level) || x.cat === "peak" })[f.level] ?? true,
      dur: (x) => ({ short: x.days <= 10, mid: x.days >= 11 && x.days <= 15, long: x.days >= 16 })[f.dur] ?? true,
      season: (x) => (SEASONS[f.season] ? SEASONS[f.season].some((m) => x.months.includes(m)) : f.month ? x.months.includes(f.month) : true),
      budget: (x) => ({ low: x.price < 1000, mid: x.price >= 1000 && x.price <= 2000, high: x.price > 2000 })[f.budget] ?? (f.max ? x.price <= f.max : true),
    };
    let keys = ["level", "dur", "season", "budget"], results = [], relaxed = [];
    // drop the least important wishes until something fits
    for (const drop of [null, "budget", "dur", "level"]) {
      if (drop) { keys = keys.filter((k) => k !== drop); relaxed.push(drop); }
      results = TREKS.filter((x) => keys.every((k) => tests[k](x)));
      if (results.length) break;
    }
    results = results.slice(0, 3);
    const words = { budget: C("w_budget", "budget"), dur: C("w_duration", "duration"), level: C("w_difficulty", "difficulty") };
    const note = relaxed.length ? `<p class="msg__small">${H("rec_relaxed", "Nothing matched every wish, so I relaxed your {list}.", { list: relaxed.map((r) => words[r]).join(` ${C("w_and", "and")} `) })}</p>` : "";
    const monsoon = f.season === "monsoon" || [6, 7, 8].includes(f.month) ? p("rec_monsoon", "In the monsoon, rain-shadow treks like Upper Mustang are the best choice.") : "";
    bot(`${results.length > 1 ? p("rec_picks", "Here are my top picks for you:") : p("rec_pick", "Here is my top pick for you:")}${note}${monsoon}${results.map(card).join("")}`,
      [...results.map((x) => [`trek:${x.id}`, C("btn_trek_details", "{trek} details", { trek: short(x.name) })]), ["find", C("btn_start_again", "Start again")], ["team", C("btn_ask_planner", "Ask a trek planner")], ...back]);
  }

  // ---------- free text ----------
  // a trek mentioned in the text: known nicknames first, then any CMS trek name
  function detectTrek(s) {
    const alias = Object.entries(ALIASES).find(([id, words]) => trekById(id) && words.some((w) => s.includes(w)))?.[0];
    if (alias) return alias;
    return TREKS.find((x) => s.includes(x.name.toLowerCase().replace(/ (trek|climb)$/, "")))?.id;
  }

  function understand(raw) {
    const s = raw.toLowerCase();
    const trekId = detectTrek(s);
    if (trekId) lastTrek = trekId;
    const id = trekId || lastTrek;
    const has = (re) => re.test(s);

    if (has(/\b(human|agent|person|staff|someone|talk|call|phone|whatsapp|contact)\b/)) return act("team");
    if (has(/emergenc|rescue|evacuat|safe|danger|accident|injur|police/)) return act("safety");
    if (has(/altitude sick|ams\b|acclimati/)) return act("faq:3");
    if (has(/insurance/)) return act("faq:2");
    if (has(/pick ?up|airport|meet|reporting|hotel|accommodation|teahouse|lodge|where do we|getting to nepal|arrive/)) return act("travel");
    if (has(/pack|gear|equipment|kit|clothes|boots|sleeping bag/)) return act("packing");
    if (has(/fit|train|experience|beginner|first time/)) return act("fitness");
    if (has(/expedition|peak|climb|summit|6,?000|7,?000|8,?000/) && !trekId) return act("expeditions");
    if (has(/book|reserve|sign ?up|register|deposit/)) return id ? act(`book:${id}`) : act("book");
    if (has(/date|availab|depart|seat|when .*(start|leave)|fixed/)) return act(`dates:${trekId || "all"}`);
    if (has(/price|cost|how much|usd|\$|cheap|expensive|offer|discount|pay/) && !has(/under|below|less than|budget/)) {
      if (!trekId) return act("prices");
      const x = trekById(trekId);
      return bot(`${H("price_for", "{trek} is from USD {price} per person.", { trek: x.name, price: fmt(x.price) })} ${esc(INFO.deposit)}`, [[`trek:${trekId}`, C("btn_details", "Details")], [`book:${trekId}`, C("btn_book_short", "Book")], ["prices", C("btn_all_prices", "All prices")], ...back]);
    }
    if (has(/itinerar|detail|altitude|how high|how long|days|distance|include|route/) && trekId) return act(`trek:${trekId}`);
    if (trekId) return act(`trek:${trekId}`);

    // a description of the trek they want -> recommend
    const f = {};
    if (has(/easy|gentle|beginner|family|kids/)) f.level = "easy"; else if (has(/moderate|medium/)) f.level = "moderate"; else if (has(/hard|challeng|tough|strenuous|difficult/)) f.level = "challenging";
    const days = s.match(/(\d{1,2})\s*(?:-|to)?\s*(?:\d{1,2}\s*)?days?/);
    if (days) { const n = +days[1]; f.dur = n <= 10 ? "short" : n <= 15 ? "mid" : "long"; } else if (has(/short|quick|week\b/)) f.dur = "short";
    const season = Object.keys(SEASONS).find((k) => s.includes(k)) || (has(/\bfall\b/) ? "autumn" : null);
    if (season) f.season = season;
    const mi = MONTHS.findIndex((m) => s.includes(m) || new RegExp(`\\b${m.slice(0, 3)}\\b`).test(s));
    if (!season && mi >= 0) f.month = mi + 1;
    const money = s.match(/(?:under|below|less than|max|budget|up to)[^\d]*(\d[\d,]*)/);
    if (money) f.max = Number(money[1].replace(/,/g, ""));
    if (Object.keys(f).length || has(/recommend|suggest|which trek|find|looking for|want to trek|best trek/)) {
      if (!Object.keys(f).length) return act("find");
      return recommend(f);
    }
    if (has(/faq|question/)) return act("faqs");
    if (has(/visa|permit/)) return bot(H("visa", "Most nationalities get a Nepal visa on arrival at Kathmandu airport (bring USD cash and a passport photo). We arrange all trekking permits for you, including restricted-area permits for Manaslu, Mustang and Kanchenjunga."), [["travel", C("btn_travel_info", "Travel info")], ...back]);
    if (has(/^(hi|hello|hey|namaste|good (morning|evening|afternoon))/)) return bot(H("hello", "Namaste! 🙏 How can I help you plan your Himalayan journey?"), MAIN);
    if (has(/thank/)) return bot(H("thanks", "You're welcome! Anything else I can help with?"), MAIN);
    return bot(H("not_understood", "I'm not sure I understood that. Here's what I can help with, or I can pass you to a trek planner."), [...MAIN, ...MORE]);
  }

  // ---------- open / close ----------
  function open(topic) {
    box.hidden = false; launch.setAttribute("aria-expanded", "true"); document.body.classList.add("chat-open");
    if (!started) {
      started = true;
      bot(`<p><b>${H("greeting_title", "Namaste! 👋")}</b></p>${p("greeting", "I'm your virtual trekking assistant. I can find a trek for you, check dates and prices, or help you book. How can I help today?")}`, topic ? [] : MAIN);
    } else if (!topic && !log.lastElementChild?.querySelector?.(".chip-btn:not(:disabled)")) act("menu"); // returning visitor: offer the menu again
    if (topic) setTimeout(() => act(topic), REDUCED ? 0 : 450);
    setTimeout(() => input.focus({ preventScroll: true }), 60);
  }
  function close() { box.hidden = true; launch.setAttribute("aria-expanded", "false"); document.body.classList.remove("chat-open"); launch.focus({ preventScroll: true }); }

  launch.addEventListener("click", () => (box.hidden ? open() : close()));
  document.getElementById("chat-close").addEventListener("click", close);
  box.addEventListener("keydown", (e) => { if (e.key === "Escape") { e.stopPropagation(); close(); } });
  box.addEventListener("click", (e) => {
    const b = e.target.closest("[data-chat-act]");
    if (!b) return;
    if (b.classList.contains("chip-btn")) { user(b.textContent); b.parentElement.querySelectorAll("button").forEach((x) => (x.disabled = true)); }
    act(b.dataset.chatAct);
  });
  document.getElementById("chat-form").addEventListener("submit", (e) => {
    e.preventDefault();
    const text = input.value.trim();
    if (!text) return;
    input.value = "";
    user(text);
    understand(text);
  });
  // stop the page's smooth scroll from hijacking wheel events inside the chat
  log.setAttribute("data-lenis-prevent", "");

  return { open, close };
}
