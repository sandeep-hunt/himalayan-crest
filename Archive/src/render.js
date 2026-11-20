// Puts CMS content into the page's fixed sections (hero, why, team, support, stories, footer...).
// The HTML in index.html is the built-in English content; it is only replaced when the CMS supplies a value.
import { CMS, t, sec, esc, img, setLang } from "./cms.js";
import { TREKS } from "./data.js";
import { footerColumnsHtml, navShows, waHref } from "./menu.js";

const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];

const CAT_ICON = {
  up: '<path d="M12 20V4M7 9l5-5 5 5"/>',
  peak: '<path d="M12 4 21 20H3z"/>',
  circle: '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4.5"/>',
  star: '<path d="M12 3c.6 5 3 7.4 8 8-5 .6-7.4 3-8 8-.6-5-3-7.4-8-8 5-.6 7.4-3 8-8z"/>',
};
const initials = (name) => String(name).split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0]).join("").toUpperCase();

export function renderCms() {
  applyUi();
  if (!CMS.live) return;
  renderLanguageSwitch();
  renderTextHooks();
  renderHero();
  renderCategories();
  renderCraft();
  renderWhy();
  renderTeam();
  renderSupport();
  renderStories();
  renderContacts();
  renderChrome();
}

/** Brand texts, SEO title/description, footer link columns, association badges. */
function renderChrome() {
  const s = CMS.settings || {};
  if (s.brand_name) $$("[data-brand-name]").forEach((el) => { el.textContent = s.brand_name; });
  if (s.brand_sub) $$("[data-brand-sub]").forEach((el) => { el.textContent = s.brand_sub; });
  if (s.seo_title) document.title = s.seo_title;
  if (s.seo_description) {
    document.querySelector('meta[name="description"]')?.setAttribute("content", s.seo_description);
    document.querySelector('meta[property="og:description"]')?.setAttribute("content", s.seo_description);
  }
  if (s.seo_title) document.querySelector('meta[property="og:title"]')?.setAttribute("content", s.seo_title);

  // footer link columns (Admin -> Menu, location "Footer")
  if (CMS.menu?.footer) {
    const html = footerColumnsHtml();
    const holder = $("#footer-menus");
    if (holder) holder.outerHTML = html || "";
    // "Popular treks" is now a footer menu in the CMS, so the automatic column is not needed
    $("#footer-popular")?.remove();
  }
  // "Registered & associated with" badges
  if (Array.isArray(s.associations) && s.associations.length) {
    $("#assoc-badges").innerHTML = s.associations.filter((a) => a.short || a.name).map((a) => {
      const inner = `<strong>${esc(a.short)}</strong>${a.name ? `<small>${esc(a.name)}</small>` : ""}`;
      return `<li>${/^https?:\/\//i.test(a.url) ? `<a class="badge badge--assoc" href="${esc(a.url)}" target="_blank" rel="noopener">${inner}</a>` : `<span class="badge badge--assoc">${inner}</span>`}</li>`;
    }).join("");
  }
  // navbar buttons (Admin -> Homepage Sections -> Navbar)
  const navWa = $("#nav-wa"), navPlan = $("#nav-plan");
  if (navWa) { navWa.hidden = !navShows("whatsapp"); navWa.href = waHref(); navWa.title = t("nav.whatsapp", "WhatsApp us"); }
  if (navPlan) navPlan.hidden = !navShows("plan");
  // plan panel WhatsApp link text
  if (s.phone && $("#plan-wa-link")) $("#plan-wa-link").textContent = `${t("contact.whatsapp", "WhatsApp")} ${s.phone}`;
}

/** data-i18n="key" -> text, data-i18n-aria="key" -> aria-label (English in the HTML is the fallback). */
export function applyUi(root = document) {
  if (!CMS.live) return;
  $$("[data-i18n]", root).forEach((el) => { el.textContent = t(el.dataset.i18n, el.textContent); });
  $$("[data-i18n-aria]", root).forEach((el) => el.setAttribute("aria-label", t(el.dataset.i18nAria, el.getAttribute("aria-label"))));
  $$("[data-i18n-ph]", root).forEach((el) => el.setAttribute("placeholder", t(el.dataset.i18nPh, el.getAttribute("placeholder"))));
}

function renderLanguageSwitch() {
  if (CMS.languages.length < 2) return;
  const wrap = $("#lang-switch"), select = $("#lang-select");
  // The header shows a compact code (EN); the invisible select on top opens the full list of languages.
  select.innerHTML = CMS.languages.map((l) => `<option value="${l.code}" ${l.code === CMS.locale ? "selected" : ""} lang="${l.code}">${esc(l.native)}${l.native !== l.name ? ` (${esc(l.name)})` : ""}</option>`).join("");
  wrap.insertAdjacentHTML("afterbegin", `<span class="lang-switch__code" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 3 2.5 15 0 18M12 3c-2.5 3-2.5 15 0 18"/></svg>${CMS.locale.toUpperCase()}</span>`);
  select.setAttribute("aria-label", t("nav.language", "Language"));
  select.addEventListener("change", () => setLang(select.value));
  wrap.hidden = false;
}

function renderTextHooks() {
  $$("[data-s]").forEach((el) => { const v = sec(el.dataset.s); if (typeof v === "string") el.textContent = v; });
  $$("[data-s-src]").forEach((el) => { const v = sec(el.dataset.sSrc); if (v) el.src = img(v); });
  $$("[data-s-bg]").forEach((el) => { const v = sec(el.dataset.sBg); if (v) el.style.backgroundImage = `url('${img(v)}')`; });
}

function renderHero() {
  const title = sec("hero.title");
  if (title) $("#hero-title").innerHTML = title.map(esc).join("<br />");
  const sub = sec("hero.subtitle");
  if (sub) { $("#hero-sub").textContent = sub; $("#hero-sub").hidden = false; }
  const values = sec("hero.values");
  if (values) $$("#hero-values li").forEach((li, i) => {
    if (!values[i]) { li.remove(); return; }
    const svg = li.querySelector("svg")?.outerHTML || "";
    li.innerHTML = svg + esc(values[i]);
  });
  const slides = (sec("hero.slides") || []).filter((s) => s.image);
  if (slides.length) {
    $("#hero-slides").innerHTML = slides.map((s, i) =>
      `<figure class="hero__slide ${i === 0 ? "is-active" : ""}" data-caption="${esc(s.caption || "")}"><img src="${esc(img(s.image))}" alt="" ${i === 0 ? 'fetchpriority="high"' : 'decoding="async"'} /></figure>`).join("");
    $("#hero-caption span").textContent = slides[0].caption || "";
  }
}

function renderCategories() {
  if (!CMS.categories.length) return;
  $("#find-cards").innerHTML = CMS.categories.map((c) => {
    const body = `<img src="${esc(img(c.img))}" alt="" loading="lazy" /><span class="cat__body">
      <svg class="cat__ico" viewBox="0 0 24 24" aria-hidden="true">${CAT_ICON[c.icon] || CAT_ICON.up}</svg>
      <span class="cat__t">${esc(c.title)}</span>${c.description ? `<span class="cat__d">${esc(c.description)}</span>` : ""}
      ${c.link_label ? `<span class="cat__link">${esc(c.link_label)}</span>` : ""}</span>`;
    return c.action === "plan" ? `<a class="cat" href="#plan" data-plan>${body}</a>` : `<button class="cat" data-filter="${esc(c.action)}">${body}</button>`;
  }).join("");
}

function renderCraft() {
  const tags = sec("craft.tags");
  if (tags) $("#craft-tags").innerHTML = tags.map((x) => `<li>${esc(x)}</li>`).join("");
}

function renderWhy() {
  const photo = $("#why-bento .bento__item--photo");
  if (sec("why.image")) photo.style.backgroundImage = `url('${img(sec("why.image"))}')`;
  if (sec("why.quote")) photo.querySelector("h3").textContent = `“${sec("why.quote")}”`;
  if (sec("why.quote_by")) photo.querySelector(".bento__sign").textContent = sec("why.quote_by");
  const items = (sec("why.items") || []).filter((x) => x.title);
  if (items.length) {
    $$("#why-bento .bento__item:not(.bento__item--photo)").forEach((el) => el.remove());
    photo.insertAdjacentHTML("afterend", items.map((it, i) =>
      `<article class="bento__item"><span class="bento__n">${String(i + 1).padStart(2, "0")}</span><h3>${esc(it.title)}</h3><p>${esc(it.text || "")}</p></article>`).join(""));
  }
}

function renderTeam() {
  if (!CMS.team.length) return;
  const fallbackBg = ["ebc-trekkers.jpg", "namche.jpg", "tengboche.jpg"];
  $("#crew-track").innerHTML = CMS.team.map((m, i) => `
    <article class="crew-card" data-bg="${esc(img(m.background || m.photo || fallbackBg[i % 3]))}">
      ${m.photo ? `<img class="crew-card__photo" src="${esc(img(m.photo))}" alt="${esc(m.name)}" loading="lazy" />` : `<div class="crew-card__monogram" aria-hidden="true">${esc(initials(m.name))}</div>`}
      ${m.designation ? `<p class="crew-card__role">${esc(m.designation)}</p>` : ""}
      <h3>${esc(m.name)}</h3>
      ${m.experience ? `<p class="crew-card__xp">${esc(m.experience)}</p>` : ""}
      ${m.regions ? `<p>${esc(m.regions)}</p>` : ""}
      ${m.bio ? `<p class="crew-card__bio">${esc(m.bio)}</p>` : ""}
    </article>`).join("");
  $("#crew-n").textContent = String(CMS.team.length).padStart(2, "0");
}

function renderSupport() {
  const cols = (sec("support.columns") || []).filter((c) => c.title);
  if (!cols.length) return;
  $("#support-grid").innerHTML = cols.map((c, i) => `
    <article class="support__col">
      <span class="support__step">${String(i + 1).padStart(2, "0")}</span>
      <h3>${esc(c.title)}</h3>
      <ul>${(c.items || []).map((x) => `<li>${esc(x)}</li>`).join("")}</ul>
    </article>`).join("");
}

function renderStories() {
  if (!CMS.reviews.length) return;
  $("#quotes").innerHTML = CMS.reviews.map((r, i) => {
    const sample = /^sample/i.test(r.name || "");
    const rating = Math.max(0, Math.min(5, Number(r.rating) || 0));
    const meta = [r.country, r.trek].filter(Boolean).join(" · ");
    return `<figure class="quote ${i === 0 ? "is-active" : ""}">
      ${rating ? `<p class="quote__stars" aria-label="${rating} / 5">${"★".repeat(rating)}<span>${"★".repeat(5 - rating)}</span></p>` : ""}
      <blockquote>“${esc(r.text)}”</blockquote>
      <figcaption>
        ${r.photo ? `<img class="quote__avatar" src="${esc(img(r.photo))}" alt="" loading="lazy" />` : `<span class="quote__avatar quote__avatar--initials" aria-hidden="true">${esc(initials(r.name || "?"))}</span>`}
        <span class="quote__who"><strong>${esc(r.name)}</strong>${meta ? `<small>${esc(meta)}</small>` : ""}</span>
        ${sample ? `<span class="sample-tag">${esc(t("stories.sample", "Sample review"))}</span>` : ""}
      </figcaption>
    </figure>`;
  }).join("");
}

function renderContacts() {
  const s = CMS.settings;
  if (!s) return;
  const wa = s.whatsapp, social = s.social || {};
  if (wa) $$('a[href*="wa.me/"]').forEach((a) => { a.href = a.href.replace(/wa\.me\/\d+/, `wa.me/${wa}`); });
  if (s.email) $$('a[href^="mailto:"]').forEach((a) => { a.href = `mailto:${s.email}`; if (a.textContent.includes("@")) a.textContent = s.email; });
  const setHref = (sel, url) => { const a = $(sel); if (a && url) a.href = url; };
  setHref("#link-tripadvisor", social.tripadvisor);
  setHref("#link-google", social.google_reviews);
  setHref("#link-youtube", social.youtube);
  for (const [label, url] of [["Instagram", social.instagram], ["Facebook", social.facebook], ["YouTube", social.youtube], ["TikTok", social.tiktok]]) {
    const a = $(`#socials a[aria-label="${label}"]`);
    if (!a) continue;
    if (url) a.href = url; else a.remove();
  }

  const tel = (p) => "tel:" + String(p).replace(/[^\d+]/g, "");
  const city = s.address || t("contact.city", "Kathmandu, Nepal");
  $("#closing-contacts").innerHTML = [
    wa && s.phone ? `<li><span>${esc(t("contact.whatsapp", "WhatsApp"))}</span><a href="https://wa.me/${esc(wa)}" target="_blank" rel="noopener">${esc(s.phone)}</a></li>` : "",
    s.email ? `<li><span>${esc(t("contact.email", "Email"))}</span><a href="mailto:${esc(s.email)}">${esc(s.email)}</a></li>` : "",
    `<li><span>${esc(t("contact.city", "Kathmandu"))}</span>${esc(city)}</li>`,
  ].join("");
  $("#footer-contact").innerHTML = `<h4>${esc(t("footer.contact", "Contact"))}</h4>` + [
    s.phone && `<a href="${tel(s.phone)}">${esc(s.phone)}</a>`,
    s.phone2 && `<a href="${tel(s.phone2)}">${esc(s.phone2)}</a>`,
    s.email && `<a href="mailto:${esc(s.email)}">${esc(s.email)}</a>`,
    `<span>${esc(city)}</span>`,
  ].filter(Boolean).join("");
  if (s.footer_tagline) $("#footer-tag").textContent = s.footer_tagline;
  if (s.copyright) $("#copyright").textContent = s.copyright;
  if (s.payment_methods?.length) {
    const cls = { VISA: "badge--visa", MASTERCARD: "badge--mc", AMEX: "badge--amex", PAYPAL: "badge--paypal" };
    $("#pay-badges").innerHTML = s.payment_methods.map((p) => `<li class="badge badge--pay ${cls[p.toUpperCase()] || ""}">${esc(p)}</li>`).join("");
  }
  // footer: popular treks = featured treks (only used when the CMS has no footer menus)
  const popular = TREKS.filter((x) => x.featured).slice(0, 5);
  if (popular.length && $("#footer-popular")) $("#footer-popular").innerHTML = `<h4>${esc(t("footer.popular", "Popular treks"))}</h4>` +
    popular.map((x) => `<a href="#" data-open-trek="${esc(x.id)}">${esc(x.name)}</a>`).join("");
  if (s.email) $("#form-note").textContent = t("plan.note", "Sends through your email app to {email}.", { email: s.email });
}
