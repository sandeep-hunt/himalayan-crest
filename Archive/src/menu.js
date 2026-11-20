// Header menus (desktop dropdowns / mega menu and the phone accordion) and the footer link columns.
// Built from the CMS "Menu" (Admin -> Menu); without the CMS, the built-in MENU in data.js is used.
import { MENU, TREKS, GUIDES } from "./data.js";
import { trekById, upcoming, fmtDate } from "./treks.js";
import { t, esc, CMS, sec } from "./cms.js";

/** Navbar buttons (Admin -> Homepage Sections -> Navbar): shown unless switched off. */
export const navShows = (name) => sec("navbar.show_" + name) !== false;
/** WhatsApp link with the pre-filled visitor message. */
export const waHref = () => `https://wa.me/${CMS.settings?.whatsapp || "9779862221697"}?text=${encodeURIComponent(t("nav.wa_text", "Namaste Crest Himalaya, I'd like to plan a trek."))}`;

// a link's action becomes data attributes that main.js handles
function attrs(l) {
  if (l.filter) return `href="#journeys" data-filter-go="${esc(l.filter)}"`;
  if (l.trek) return `href="#journeys" data-open-trek="${esc(l.trek)}"`;
  if (l.plan !== undefined) return `href="#plan" data-plan data-plan-note="${esc(l.plan)}"`;
  if (l.chat) return `href="#" data-chat="${esc(l.chat)}"`;
  if (l.doc) return `href="#" data-doc="${esc(l.doc)}"`;
  if (l.docList) return `href="#prepare" data-doc-list="${esc(l.docList)}"`;
  if (l.external) return `href="${esc(l.href)}" target="_blank" rel="noopener"`;
  return `href="${esc(l.href || "#")}"`;
}
const label = (l) => (l.k ? t(l.k, l.t) : l.t ?? trekById(l.trek)?.name ?? "");
const desc = (l) => (l.dk ? t(l.dk, l.d) : l.d);
// links to treks that no longer exist in the CMS are dropped
const valid = (l) => !l.trek || trekById(l.trek);
const link = (l, cls = "dd__link") => `<a class="${cls}" ${attrs(l)}><span>${esc(label(l))}</span>${desc(l) ? `<small>${esc(desc(l))}</small>` : ""}</a>`;

// ---------- CMS menu -> the structure used below ----------
function fromCmsLink(c) {
  const base = { t: c.title, d: c.subtitle };
  const target = (c.target || "").trim();
  switch (c.action) {
    case "filter": return { ...base, filter: target || "all" };
    case "trek": return { ...base, trek: target };
    case "plan": return { ...base, plan: target };
    case "chat": return { ...base, chat: target || "menu" };
    case "guides": return { ...base, docList: "guides" };
    case "articles": return { ...base, docList: "posts" };
    case "url": return { ...base, href: target || "#", external: /^https?:/i.test(target) };
    case "section": default: return { ...base, href: "#" + (target.replace(/^#/, "") || "top") };
  }
}
function fromCmsMenu(m) {
  const links = (m.links || []).map((c) => ({ ...fromCmsLink(c), column: c.column, button: c.button }));
  if (m.style === "mega") {
    const cols = [];
    for (const l of links) {
      const title = l.column || "";
      let col = cols.find((c) => c.title === title);
      if (!col) cols.push((col = { title, links: [] }));
      if (l.button) col.cta = l; else col.links.push(l);
    }
    return { label: m.title, mega: cols };
  }
  return { label: m.title, links, peaks: m.style === "peaks", departures: m.style === "departures", guides: m.style === "guides" };
}
const headerItems = () => (CMS.menu?.header?.length ? CMS.menu.header.map(fromCmsMenu) : MENU);

function peakLinks() {
  return TREKS.filter((x) => x.cat === "peak").map((x) => ({ t: x.name.replace(/ Climb$/, ""), d: `${x.alt} · ${x.days} ${t("trek.days_lower", "days")}`, trek: x.id }));
}

// Travel Guide menu: the CMS guides (each opens its guide), then the menu's own links.
function guideLinks(item) {
  const live = GUIDES.filter((g) => g.slug);
  if (!CMS.live || !live.length) return item.links;
  const extra = CMS.menu?.header?.length ? item.links : [
    { t: "Visit Travel Guide", k: "prepare.all", docList: "guides" },
    { t: "FAQs", k: "menu.guide.faqs", href: "#faq" },
  ];
  return [...live.map((g) => (g.doc ? { t: g.t, doc: `guides:${g.slug}` } : { t: g.t, href: "#prepare" })), ...extra];
}

function itemLinks(item) {
  if (item.departures) return departureLinks(item);
  if (item.guides) return guideLinks(item);
  return [...(item.peaks ? peakLinks() : []), ...(item.links || [])].filter(valid);
}

function departureLinks(item) {
  const deps = upcoming().slice(0, 5).map((d) => {
    const x = trekById(d.trek);
    const seats = d.seats <= 3 ? t("menu.seats_low", "only {n} seats left", { n: d.seats }) : t("menu.seats_open", "{n} seats open", { n: d.seats });
    return { t: x.name, d: `${fmtDate(d.date)} · ${seats}`, trek: d.trek };
  });
  const extra = CMS.menu?.header?.length ? (item.links || []) : [{ t: "See all dates & seats", k: "menu.dep_all", d: "Ask the assistant", dk: "menu.dep_ask", chat: "availability" }];
  return [...deps, ...extra];
}

function panel(item, i) {
  if (item.mega) {
    return `<div class="dd dd--mega" id="dd-${i}" hidden><div class="dd__mega wrap">${item.mega.map((col) => `
      <div class="dd__col">${col.title ? `<p class="dd__title">${esc(t(col.tk, col.title))}</p>` : ""}${col.links.filter(valid).map((l) => link(l)).join("")}
        ${col.cta ? `<a class="btn btn--primary dd__cta" ${attrs(col.cta)}>${esc(label(col.cta))} <span aria-hidden="true">→</span></a>` : ""}</div>`).join("")}
    </div></div>`;
  }
  return `<div class="dd" id="dd-${i}" hidden><div class="dd__list">${item.departures ? `<p class="dd__title">${esc(t("menu.dep_title", "Upcoming group departures"))}</p>` : ""}${itemLinks(item).map((l) => link(l)).join("")}</div></div>`;
}

export function renderMenus(navEl, mobileEl) {
  const items = headerItems();
  navEl.innerHTML = items.map((item, i) => `
    <div class="nav__item">
      <button class="nav__top" aria-expanded="false" aria-controls="dd-${i}">${esc(t(item.lk, item.label))}<svg viewBox="0 0 10 6" aria-hidden="true"><path d="M1 1l4 4 4-4"/></svg></button>
      ${panel(item, i)}
    </div>`).join("");

  mobileEl.innerHTML = items.map((item) => {
    const groups = item.mega
      ? item.mega.map((c) => ({ title: t(c.tk, c.title), links: (c.cta ? [...c.links, c.cta] : c.links).filter(valid) }))
      : [{ links: itemLinks(item) }];
    return `<details class="mm__group"><summary>${esc(t(item.lk, item.label))}</summary>${groups.map((g) => `${g.title ? `<p class="dd__title">${esc(g.title)}</p>` : ""}${g.links.map((l) => link(l, "mm__link")).join("")}`).join("")}</details>`;
  }).join("") + `<div class="mm__actions">${navShows("plan") ? `<a href="#plan" class="btn btn--primary" data-plan>${esc(t("nav.plan", "Plan Your Trip"))}</a>` : ""}${navShows("whatsapp") ? `<a class="btn btn--ghost" href="${esc(waHref())}" target="_blank" rel="noopener">${esc(t("nav.whatsapp", "WhatsApp us"))}</a>` : ""}${mobileLangSwitch()}</div>`;
}

/** Footer link columns from the CMS menu (location "footer"). Returns "" when there are none. */
export function footerColumnsHtml() {
  const cols = CMS.menu?.footer || [];
  return cols.map((m) => `<div><h4>${esc(m.title)}</h4>${(m.links || []).map(fromCmsLink).filter(valid).map((l) => `<a ${attrs(l)}>${esc(label(l))}</a>`).join("")}</div>`).join("");
}

function mobileLangSwitch() {
  if (CMS.languages.length < 2) return "";
  return `<label class="lang-switch lang-switch--mobile"><span>${esc(t("nav.language", "Language"))}</span><select data-lang-select>${CMS.languages.map((l) =>
    `<option value="${l.code}" ${l.code === CMS.locale ? "selected" : ""}>${esc(l.native)}</option>`).join("")}</select></label>`;
}
