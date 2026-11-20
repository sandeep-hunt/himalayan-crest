import "./style.css";
import Lenis from "lenis";
import gsap from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import { TREKS, FAQS, GUIDES, POSTS, VIDEOS, REELS } from "./data.js";
import { fmt, trekById, matchesFilter, filterLabel, upcoming, fmtDate } from "./treks.js";
import { renderMenus } from "./menu.js";
import { initChat } from "./chat.js";
import { CMS, loadContent, t, img, esc, md, fetchDoc, setLang } from "./cms.js";
import { renderCms } from "./render.js";
import CREDITS from "./credits.json";

gsap.registerPlugin(ScrollTrigger);

const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const REDUCED = matchMedia("(prefers-reduced-motion: reduce)").matches;

// ---------- content: load from the CMS (falls back to the built-in content) ----------
await loadContent();
renderCms();

// ---------- smooth scroll ----------
const lenis = REDUCED ? null : new Lenis({ lerp: 0.1, smoothWheel: true });
if (lenis) {
  lenis.on("scroll", ScrollTrigger.update);
  gsap.ticker.add((time) => lenis.raf(time * 1000));
  gsap.ticker.lagSmoothing(0);
}
function scrollToEl(target) {
  const el = typeof target === "string" ? (target === "#top" ? 0 : $(target)) : target;
  if (el === null) return;
  if (lenis) lenis.scrollTo(el, { offset: el === 0 ? 0 : -60, duration: 1.4 });
  else if (el === 0) window.scrollTo({ top: 0 });
  else el.scrollIntoView({ behavior: REDUCED ? "auto" : "smooth" });
}
document.addEventListener("click", (e) => {
  const docBtn = e.target.closest("[data-doc]");
  if (docBtn) { e.preventDefault(); closeMenus(); const [type, slug] = docBtn.dataset.doc.split(":"); openDoc(type, slug); return; }
  const listBtn = e.target.closest("[data-doc-list]");
  if (listBtn && CMS.live) { e.preventDefault(); openDocList(listBtn.dataset.docList); return; }
  const teamBtn = e.target.closest("[data-team-all]");
  if (teamBtn && CMS.team.length) { e.preventDefault(); openTeam(); return; }
  const planBtn = e.target.closest("[data-plan]");
  if (planBtn) { e.preventDefault(); closeMenus(); prefill("", null, null, planBtn.dataset.planNote); return; }
  const filterBtn = e.target.closest("[data-filter-go]");
  if (filterBtn) { e.preventDefault(); closeMenus(); setFilter(filterBtn.dataset.filterGo, true); scrollToEl("#journeys"); return; }
  const chatBtn = e.target.closest("[data-chat]");
  if (chatBtn) { e.preventDefault(); closeMenus(); chat.open(chatBtn.dataset.chat); return; }
  const a = e.target.closest('a[href^="#"]');
  if (!a || a.hasAttribute("data-open-trek")) return;
  const href = a.getAttribute("href");
  if (href === "#") { e.preventDefault(); return; } // placeholder links: stay put until real pages exist
  e.preventDefault();
  closeMenus();
  scrollToEl(href);
});

// ---------- loader ----------
// (the page may already have finished loading while the CMS content was being fetched)
const started = performance.now();
function onLoaded() {
  const wait = Math.max(0, 1300 - (performance.now() - started));
  setTimeout(() => { document.body.classList.add("loaded"); ScrollTrigger.refresh(); }, REDUCED ? 0 : wait);
}
if (document.readyState === "complete") onLoaded();
else window.addEventListener("load", onLoaded);

// ---------- nav ----------
const nav = $("#nav");
let lastY = 0;
function onScrollNav() {
  const y = window.scrollY;
  nav.classList.toggle("is-solid", y > 80);
  nav.classList.toggle("is-hidden", y > 700 && y > lastY + 4 && $("#mobile-menu").hidden && !nav.classList.contains("has-dropdown"));
  if (y < lastY - 4) nav.classList.remove("is-hidden");
  lastY = y;
  $(".chat-launch")?.classList.toggle("is-on", y > $("#hero").offsetHeight * 0.5);
}
window.addEventListener("scroll", onScrollNav, { passive: true });
onScrollNav();

// ---------- header menus (dropdowns + mega menu, built from MENU in data.js) ----------
renderMenus($("#nav-links"), $("#mobile-menu"));
const tops = $$(".nav__top");
let hoverTimer;
function openDropdown(btn) {
  tops.forEach((b) => { const on = b === btn; b.setAttribute("aria-expanded", String(on)); $("#" + b.getAttribute("aria-controls")).hidden = !on; });
  nav.classList.toggle("has-dropdown", !!btn);
}
function closeMenus() { openDropdown(null); closeMenu(); }
tops.forEach((b) => {
  const item = b.parentElement;
  b.addEventListener("click", () => openDropdown(b.getAttribute("aria-expanded") === "true" ? null : b));
  item.addEventListener("mouseenter", () => { if (matchMedia("(hover: hover)").matches) { clearTimeout(hoverTimer); openDropdown(b); } });
  item.addEventListener("mouseleave", () => { if (matchMedia("(hover: hover)").matches) hoverTimer = setTimeout(() => openDropdown(null), 180); });
});
document.addEventListener("click", (e) => { if (!e.target.closest(".nav__item")) openDropdown(null); });
document.addEventListener("keydown", (e) => {
  if (e.key !== "Escape") return;
  const open = tops.find((b) => b.getAttribute("aria-expanded") === "true");
  if (open) { openDropdown(null); open.focus(); }
});
nav.addEventListener("focusout", (e) => { if (!nav.contains(e.relatedTarget)) openDropdown(null); });
$("#nav-links").addEventListener("click", (e) => { if (e.target.closest("a")) openDropdown(null); });

const burger = $(".nav__burger"), menu = $("#mobile-menu");
function closeMenu() { menu.hidden = true; burger.setAttribute("aria-expanded", "false"); lenis?.start(); }
burger.addEventListener("click", () => {
  const open = menu.hidden;
  menu.hidden = !open; burger.setAttribute("aria-expanded", String(open));
  open ? lenis?.stop() : lenis?.start();
});
menu.addEventListener("change", (e) => { if (e.target.matches("[data-lang-select]")) setLang(e.target.value); });


// ---------- 1. hero slideshow ----------
const SLIDE_MS = 6500;
const slides = $$(".hero__slide"), heroDots = $("#hero-dots"), heroCaption = $("#hero-caption span"), pauseBtn = $("#hero-pause");
heroDots.innerHTML = slides.map((s, i) => `<button aria-label="${esc(t("hero.photo_aria", "Photo {n}: {caption}", { n: i + 1, caption: s.dataset.caption || "" }))}" ${i === 0 ? 'aria-current="true"' : ""}><i></i></button>`).join("");
const dotBtns = $$("button", heroDots);
let slideI = 0, slideTimer = null, prevTimer = null, userPaused = REDUCED, heroVisible = true;
const FADE_MS = 1400; // keep in sync with .hero__slide transition in style.css
heroDots.style.setProperty("--slide-ms", SLIDE_MS + "ms");
// Load every slide photo up front (decoded off the main thread) so no slide fades in from black
const slideReady = slides.map((s) => {
  const im = s.querySelector("img");
  im.loading = "eager"; im.decoding = "async";
  return () => (im.complete && im.naturalWidth ? Promise.resolve() : im.decode().catch(() => {}));
});
// Smooth crossfade: the new photo fades in ON TOP of the old one, which stays fully visible
// (and keeps its slow zoom) until it is covered, so there is no dark dip and no zoom snap.
function showSlide(i) {
  const next = (i + slides.length) % slides.length;
  if (next !== slideI) {
    clearTimeout(prevTimer);
    slides.forEach((s) => s.classList.remove("is-prev"));
    slides[slideI].classList.add("is-prev");
    const old = slideI;
    prevTimer = setTimeout(() => slides[old].classList.remove("is-prev"), FADE_MS + 100);
  }
  slideI = next;
  slides.forEach((s, j) => s.classList.toggle("is-active", j === slideI));
  dotBtns.forEach((b, j) => {
    b.setAttribute("aria-current", String(j === slideI));
    b.classList.remove("is-running"); void b.offsetWidth; // restart the progress fill
  });
  heroCaption.textContent = slides[slideI].dataset.caption;
  schedule();
}
function schedule() {
  clearTimeout(slideTimer);
  const running = !userPaused && heroVisible && !document.hidden;
  $("#hero").classList.toggle("is-paused", !running);
  if (!running) return;
  dotBtns[slideI].classList.add("is-running");
  // wait for the next photo to be ready before changing (slow connections), then crossfade
  slideTimer = setTimeout(() => { const n = (slideI + 1) % slides.length; slideReady[n]().then(() => showSlide(n)); }, SLIDE_MS);
}
heroDots.addEventListener("click", (e) => { const b = e.target.closest("button"); if (b) showSlide(dotBtns.indexOf(b)); });
pauseBtn.addEventListener("click", () => {
  userPaused = !userPaused;
  pauseBtn.setAttribute("aria-pressed", String(userPaused));
  pauseBtn.setAttribute("aria-label", userPaused ? t("hero.play", "Play slideshow") : t("hero.pause", "Pause slideshow"));
  showSlide(slideI);
});
pauseBtn.setAttribute("aria-pressed", String(userPaused));
pauseBtn.setAttribute("aria-label", userPaused ? t("hero.play", "Play slideshow") : t("hero.pause", "Pause slideshow"));
new IntersectionObserver(([en]) => { heroVisible = en.isIntersecting; schedule(); }, { threshold: 0.15 }).observe($("#hero"));
showSlide(0);

// ---------- 2. find your next adventure: category cards filter the trek grid ----------
$$("button.cat").forEach((c) => c.addEventListener("click", () => { setFilter(c.dataset.filter); scrollToEl("#journeys"); }));

// ---------- 3. featured treks grid (Figma layout): featured treks (first 6 without the CMS) ----------
const FEATURED = (CMS.live && TREKS.filter((x) => x.featured).length) || 6;
const grid = $("#trek-grid");
const L = { days: t("trek.days", "Days"), from: t("trek.from", "From"), view: t("trek.view", "View trek") };
grid.innerHTML = TREKS.map((x, i) => `
  <article class="trek ${i >= FEATURED ? "is-hidden" : ""}" data-cat="${esc(x.cat)}" data-id="${esc(x.id)}" tabindex="0" role="button" aria-label="${esc(x.name)}, ${x.days} ${esc(L.days)}, ${esc(L.from)} ${esc(t("trek.currency", "USD"))} ${fmt(x.price)}">
    <div class="trek__media">
      <img src="${esc(img(x.img))}" alt="${esc(x.name)}" loading="lazy" />
      ${x.badge ? `<span class="trek__badge">${esc(x.badge)}</span>` : ""}
    </div>
    <div class="trek__body">
      <span class="trek__area">${esc(x.area)}</span>
      <h3 class="trek__name">${esc(x.name)}</h3>
      <ul class="trek__meta"><li>${x.days} ${esc(L.days)}</li><li>${esc(x.alt)}</li><li>${esc(x.level)}</li></ul>
      <div class="trek__foot">
        <span class="trek__price">${esc(L.from)} <strong>${esc(t("trek.currency", "USD"))} ${fmt(x.price)}</strong></span>
        <span class="trek__go">${esc(L.view)} <span aria-hidden="true">→</span></span>
      </div>
    </div>
  </article>`).join("");
const cards = $$(".trek", grid);
cards.forEach((el) => {
  el.addEventListener("click", () => openTrek(el.dataset.id));
  el.addEventListener("keydown", (e) => { if (e.key === "Enter" || e.key === " ") { e.preventDefault(); openTrek(el.dataset.id); } });
});
const pill = $("#filter-pill"), viewAll = $("#view-all");
// filter ids: "all", a category, "trek", "region:<name>" or "exp:<key>" (see treks.js)
function setFilter(id, showAll = false) {
  pill.hidden = id === "all";
  $("#filter-label").textContent = filterLabel(id);
  viewAll.hidden = showAll || id !== "all";
  gsap.to(cards, {
    opacity: 0, y: 12, duration: 0.2, onComplete: () => {
      cards.forEach((c, i) => c.classList.toggle("is-hidden", !matchesFilter(trekById(c.dataset.id), id) || (id === "all" && !showAll && i >= FEATURED)));
      grid.classList.toggle("is-empty", !cards.some((c) => !c.classList.contains("is-hidden")));
      gsap.fromTo(cards.filter((c) => !c.classList.contains("is-hidden")), { opacity: 0, y: 24 }, { opacity: 1, y: 0, duration: 0.6, stagger: 0.06, ease: "power3.out" });
      ScrollTrigger.refresh();
    },
  });
}
pill.addEventListener("click", () => setFilter("all"));
viewAll.addEventListener("click", () => setFilter("all", true));

// ---------- drawer ----------
const drawer = $("#drawer"), drawerBody = $("#drawer-body");
let lastFocus = null;
function openTrek(id) {
  const x = trekById(id);
  if (!x) return;
  const next = upcoming(id)[0]?.date;
  const list = (items) => items.map((h) => `<li>${esc(h)}</li>`).join("");
  const nextTxt = next
    ? `${esc(t("trek.next", "Next group departure:"))} <strong style="color:var(--ink)">${esc(fmtDate(next, { day: "numeric", month: "long", year: "numeric" }))}</strong>.`
    : esc(t("trek.private", "Runs privately on any date."));
  const included = x.inclusions?.length
    ? `<ul class="dr__inc">${list(x.inclusions)}</ul>`
    : `<p style="margin:0;color:var(--ink-muted);font-size:15px">${esc(t("trek.included_text", "Airport transfers, Kathmandu hotel, all permits, domestic flights on the route, licensed guide, porters, teahouse lodging and three meals a day on trail."))} ${nextTxt}</p>`;
  const MONTH_NAMES = Array.from({ length: 12 }, (_, i) => new Date(2026, i, 1).toLocaleDateString("en-GB", { month: "short" }));
  const deps = upcoming(id).slice(0, 6);
  const fact = (label, value) => (value || value === 0 ? `<div><span>${esc(label)}</span><strong>${esc(value)}</strong></div>` : "");
  const catLabel = { popular: t("cat.popular", "Popular treks"), peak: t("cat.peak", "Peak climbing"), cultural: t("cat.cultural", "Cultural tours"), short: t("cat.short", "Short treks") }[x.cat];
  drawerBody.innerHTML = `
    <div class="dr__hero"><img src="${esc(img(x.img))}" alt="" />
      <div class="dr__hero-text">
        <p class="dr__tags">${[x.badge && `<span class="dr__badge">${esc(x.badge)}</span>`, catLabel && `<span>${esc(catLabel)}</span>`, x.area && `<span>${esc(x.area)}</span>`].filter(Boolean).join("")}</p>
        <h3 id="drawer-title">${esc(x.name)}</h3>
      </div>
    </div>
    <div class="dr__body">
      <p style="margin:0;font-size:17px">${esc(x.summary)}</p>
      <div class="dr__facts">
        ${fact(t("trek.duration", "Duration"), `${x.days} ${t("trek.days_lower", "days")}`)}
        ${fact(t("trek.max_altitude", "Max altitude"), x.alt)}
        ${fact(t("trek.difficulty", "Difficulty"), x.level)}
        ${fact(t("trek.grade", "Grade"), x.grade !== x.level ? x.grade : "")}
        ${fact(t("trek.region", "Region"), x.region)}
        ${fact(t("trek.starts", "Starts in"), x.start)}
        ${fact(t("trek.season", "Best season"), x.season)}
        ${fact(t("trek.price", "Price from"), `${t("trek.currency", "USD")} ${fmt(x.price)}`)}
      </div>
      ${x.months?.length ? `<div><h4>${esc(t("trek.best_months", "Best months"))}</h4><ul class="dr__months">${MONTH_NAMES.map((m, i) => `<li class="${x.months.includes(i + 1) ? "is-on" : ""}">${esc(m)}</li>`).join("")}</ul></div>` : ""}
      ${x.highlights.length ? `<div><h4>${esc(t("trek.highlights", "Highlights"))}</h4><ul class="dr__hl">${list(x.highlights)}</ul></div>` : ""}
      ${x.gallery?.length ? `<div><h4>${esc(t("trek.gallery", "Gallery"))}</h4><div class="dr__gallery">${x.gallery.map((g) => `<a href="${esc(g)}" target="_blank" rel="noopener"><img src="${esc(g)}" alt="" loading="lazy" /></a>`).join("")}</div></div>` : ""}
      ${x.itinerary.length ? `<div><h4>${esc(t("trek.itinerary", "Itinerary at a glance"))}</h4><ul class="dr__it">${list(x.itinerary)}</ul></div>` : ""}
      <div><h4>${esc(t("trek.included", "Included"))}</h4>${included}</div>
      ${x.exclusions?.length ? `<div><h4>${esc(t("trek.excluded", "Not included"))}</h4><ul class="dr__exc">${list(x.exclusions)}</ul></div>` : ""}
      <div><h4>${esc(t("trek.departures", "Fixed group departures"))}</h4>
        ${deps.length ? `<ul class="dr__deps">${deps.map((d) => `<li>
          <strong>${esc(fmtDate(d.date, { weekday: "short", day: "numeric", month: "long", year: "numeric" }))}</strong>
          <span class="${d.seats <= 3 ? "low" : ""}">${esc(d.seats <= 3 ? t("menu.seats_low", "only {n} seats left", { n: d.seats }) : t("menu.seats_open", "{n} seats open", { n: d.seats }))}</span>
          <button class="btn btn--outline dr__join" data-join="${esc(x.id)}@${esc(d.date)}">${esc(t("trek.join", "Join"))}</button></li>`).join("")}</ul>`
          : `<p class="dr__note">${esc(t("trek.private", "Runs privately on any date."))}</p>`}
      </div>
    </div>
    <div class="dr__cta">
      <span class="trek__price">${esc(t("trek.from", "From"))} <strong>${esc(t("trek.currency", "USD"))} ${fmt(x.price)}</strong></span>
      <button class="btn btn--primary" data-enquire="${esc(x.id)}">${esc(t("trek.enquire", "Enquire about this trek"))} →</button>
    </div>`;
  showDrawer();
}
function showDrawer() {
  if (!drawer.classList.contains("is-open")) lastFocus = document.activeElement;
  drawer.classList.add("is-open"); drawer.setAttribute("aria-hidden", "false");
  lenis?.stop();
  $(".drawer__panel").scrollTop = 0;
  setTimeout(() => $(".drawer__panel").focus(), 50);
}

// ---------- articles, travel guides and the full team (shown in the side panel) ----------
const DOC_TYPES = { posts: () => POSTS, guides: () => GUIDES };
async function openDoc(type, slug) {
  if (!DOC_TYPES[type]) return;
  drawerBody.innerHTML = `<div class="dr__body doc"><p class="doc__loading">…</p></div>`;
  showDrawer();
  try {
    const d = await fetchDoc(type, slug);
    const meta = type === "posts" ? [d.cat, d.date && fmtDate(d.date, { day: "numeric", month: "long", year: "numeric" })].filter(Boolean).join(" · ") : "";
    drawerBody.innerHTML = `
      ${d.img ? `<div class="dr__hero"><img src="${esc(img(d.img))}" alt="" /><h3 id="drawer-title">${esc(d.title)}</h3></div>` : `<div class="doc__head"><h3 id="drawer-title">${esc(d.title)}</h3></div>`}
      <div class="dr__body doc">
        ${meta ? `<p class="doc__meta">${esc(meta)}</p>` : ""}
        ${d.excerpt || d.summary ? `<p class="doc__lede">${esc(d.excerpt || d.summary)}</p>` : ""}
        <div class="doc__content">${md(d.content)}</div>
        <p><button class="btn btn--outline" data-doc-list="${type}">← ${esc(type === "posts" ? t("blog.all", "All Articles") : t("prepare.all", "Visit Travel Guide"))}</button></p>
      </div>`;
  } catch {
    drawerBody.innerHTML = `<div class="dr__body doc"><p>${esc(t("doc.error", "Sorry, this page couldn't be loaded. Please try again."))}</p></div>`;
  }
}
function openDocList(type) {
  const items = DOC_TYPES[type]?.() || [];
  const title = type === "posts" ? (sectionTitle("blog") || t("blog.all", "All Articles")) : (sectionTitle("prepare") || t("prepare.all", "Visit Travel Guide"));
  drawerBody.innerHTML = `
    <div class="doc__head"><h3 id="drawer-title">${esc(title)}</h3></div>
    <div class="dr__body doc"><ul class="doc__list">${items.map((p) => `
      <li><a href="#" ${p.doc ? `data-doc="${type}:${esc(p.slug)}"` : ""}>
        ${p.img ? `<img src="${esc(img(p.img))}" alt="" loading="lazy" />` : ""}
        <span><strong>${esc(p.title || p.t)}</strong><small>${esc(p.excerpt || p.d || "")}</small></span>
      </a></li>`).join("")}</ul></div>`;
  showDrawer();
}
function sectionTitle(key) { return CMS.sections?.[key]?.title || ""; }
function openTeam() {
  drawerBody.innerHTML = `
    <div class="doc__head"><h3 id="drawer-title">${esc(CMS.sections?.team?.title || t("team.all", "Meet Our Full Team"))}</h3></div>
    <div class="dr__body doc"><ul class="team-list">${CMS.team.map((m) => `
      <li>${m.photo ? `<img src="${esc(img(m.photo))}" alt="" loading="lazy" />` : `<span class="team-list__mono">${esc(m.name.split(/\s+/).map((w) => w[0]).slice(0, 2).join(""))}</span>`}
        <div><strong>${esc(m.name)}</strong><small>${esc([m.designation, m.experience].filter(Boolean).join(" · "))}</small>
        ${m.regions ? `<small>${esc(m.regions)}</small>` : ""}${m.bio ? `<p>${esc(m.bio)}</p>` : ""}</div></li>`).join("")}</ul></div>`;
  showDrawer();
}
function closeDrawer() {
  drawer.classList.remove("is-open"); drawer.setAttribute("aria-hidden", "true");
  lenis?.start(); lastFocus?.focus?.();
}
drawer.addEventListener("click", (e) => {
  if (e.target.closest("[data-close]")) closeDrawer();
  const enq = e.target.closest("[data-enquire]");
  if (enq) { closeDrawer(); prefill(enq.dataset.enquire); }
  const join = e.target.closest("[data-join]");
  if (join) { const [trekId, date] = join.dataset.join.split("@"); closeDrawer(); prefill(trekId, date.slice(0, 7), "Join a group"); }
});
document.addEventListener("keydown", (e) => {
  if (e.key !== "Escape") return;
  if (drawer.classList.contains("is-open")) closeDrawer();
  if (planDrawer.classList.contains("is-open")) closePlan();
});

// ---------- plan-your-trip panel (holds the enquiry form) ----------
const planDrawer = $("#plan-drawer");
let planFocus = null;
function openPlan() {
  planFocus = document.activeElement;
  $("#form-success").hidden = true;
  planDrawer.classList.add("is-open"); planDrawer.setAttribute("aria-hidden", "false");
  lenis?.stop();
  $(".drawer__panel", planDrawer).scrollTop = 0;
  setTimeout(() => $(".drawer__panel", planDrawer).focus(), 50);
}
function closePlan() {
  planDrawer.classList.remove("is-open"); planDrawer.setAttribute("aria-hidden", "true");
  lenis?.start(); planFocus?.focus?.();
}
planDrawer.addEventListener("click", (e) => { if (e.target.closest("[data-close]")) closePlan(); });
document.addEventListener("click", (e) => {
  const a = e.target.closest("[data-open-trek]");
  if (a) { e.preventDefault(); closeMenus(); openTrek(a.dataset.openTrek); }
});

// ---------- 5b. team gallery ----------
const crewCards = $$(".crew-card"), crewBg = $(".crew__bg");
$("#crew-n").textContent = String(crewCards.length).padStart(2, "0");
let crewActive = -1;
function renderCrew(p) {
  const N = crewCards.length, pos = p * (N - 1);
  crewCards.forEach((el, i) => {
    const d = pos - i, ad = Math.abs(d);
    el.style.opacity = Math.max(0, 1 - ad / 0.6);
    el.style.transform = `translate(${-d * 140}px, -50%) scale(${1 - Math.min(ad, 1) * 0.06})`;
    el.style.zIndex = String(100 - Math.round(ad * 10));
    el.style.pointerEvents = ad < 0.4 ? "auto" : "none";
  });
  const active = Math.round(pos);
  if (active !== crewActive) {
    crewActive = active;
    crewBg.style.backgroundImage = `url('${crewCards[active].dataset.bg}')`;
    $("#crew-i").textContent = String(active + 1).padStart(2, "0");
  }
}

// desktop gets the pinned team scene, phones get a simple stacked list
const mm = gsap.matchMedia();
mm.add({ desktop: "(min-width: 901px) and (prefers-reduced-motion: no-preference)", mobile: "(max-width: 900px), (prefers-reduced-motion: reduce)" }, (ctx) => {
  if (ctx.conditions.desktop) {
    $(".crew").classList.remove("crew--static");
    renderCrew(0);
    ScrollTrigger.create({ trigger: "#crew", start: "top top", end: () => "+=" + (crewCards.length - 1) * innerHeight * 0.75, pin: ".crew__pin", scrub: 1, invalidateOnRefresh: true, onUpdate: (s) => renderCrew(s.progress) });
  } else {
    $(".crew").classList.add("crew--static");
    crewCards.forEach((el) => { el.style.cssText = ""; });
    crewBg.style.backgroundImage = `url('${crewCards[0].dataset.bg}')`;
  }
});

// ---------- 6. traveler stories ----------
// Card carousel: 3 cards on desktop, 1 on phones (swipe). Arrows/dots move one card; autoplay pauses on hover/focus.
const quoteTrack = $("#quotes"), quotes = $$(".quote", quoteTrack), qDots = $("#q-dots");
let qi = 0, qTimer, qHold = false, qScrollTimer;
const perView = () => Math.max(1, Math.round(quoteTrack.clientWidth / (quotes[0]?.offsetWidth || 1)));
// one dot per position the row can actually scroll to (cards - visible + 1)
function buildDots() {
  const n = Math.max(1, quotes.length - perView() + 1);
  qDots.innerHTML = Array.from({ length: n }, (_, i) => `<button aria-label="${esc(t("stories.dot_aria", "Story {n}", { n: i + 1 }))}"></button>`).join("");
  $(".quotes__nav").hidden = n < 2;
}
function markQuote(i) {
  qi = i;
  quotes.forEach((q, j) => q.classList.toggle("is-active", j === qi));
  const dots = $$("button", qDots);
  dots.forEach((b, j) => b.setAttribute("aria-current", String(j === Math.min(qi, dots.length - 1))));
}
function showQuote(i, user = false) {
  const last = Math.max(0, quotes.length - perView());
  const n = i > last ? 0 : i < 0 ? last : i; // wrap around at either end
  markQuote(n);
  quoteTrack.scrollTo({ left: quotes[n].offsetLeft - quotes[0].offsetLeft, behavior: REDUCED ? "auto" : "smooth" });
  if (user) restartQuotes();
}
function restartQuotes() {
  clearInterval(qTimer);
  if (!REDUCED && quotes.length > perView()) qTimer = setInterval(() => { if (!qHold && !document.hidden) showQuote(qi + 1); }, 6000);
}
// keep the dots in sync when the visitor swipes
quoteTrack.addEventListener("scroll", () => {
  clearTimeout(qScrollTimer);
  qScrollTimer = setTimeout(() => {
    const x = quoteTrack.scrollLeft;
    const near = quotes.reduce((best, q, j) => (Math.abs(q.offsetLeft - quotes[0].offsetLeft - x) < Math.abs(quotes[best].offsetLeft - quotes[0].offsetLeft - x) ? j : best), 0);
    markQuote(near);
  }, 120);
}, { passive: true });
["mouseenter", "focusin", "touchstart"].forEach((ev) => quoteTrack.addEventListener(ev, () => { qHold = true; }, { passive: true }));
["mouseleave", "focusout"].forEach((ev) => quoteTrack.addEventListener(ev, () => { qHold = false; }));
$("#q-prev").addEventListener("click", () => showQuote(qi - 1, true));
$("#q-next").addEventListener("click", () => showQuote(qi + 1, true));
qDots.addEventListener("click", (e) => { const b = e.target.closest("button"); if (b) showQuote($$("button", qDots).indexOf(b), true); });
if (quotes.length) { buildDots(); markQuote(0); restartQuotes(); }
addEventListener("resize", () => { if (!quotes.length) return; buildDots(); markQuote(Math.min(qi, Math.max(0, quotes.length - perView()))); restartQuotes(); });
gsap.fromTo(".stories__bg", { yPercent: -8 }, { yPercent: 8, ease: "none", scrollTrigger: { trigger: ".stories", start: "top bottom", end: "bottom top", scrub: true } });

// ---------- 7. prepare: before you trek ----------
const GUIDE_ICON = {
  plane: '<path d="M3 13l18-7-5 14-4-6zM12 14l9-8"/>',
  sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
  pack: '<rect x="5" y="7" width="14" height="14" rx="3"/><path d="M9 7V5a3 3 0 0 1 6 0v2M5 13h14"/>',
  peak: '<path d="M12 4 21 20H3z"/>',
  shield: '<path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6z"/>',
  permit: '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
  pin: '<path d="M12 21c-4-4-7-7.5-7-11a7 7 0 0 1 14 0c0 3.5-3 7-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
  card: '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h4"/>',
};
// CMS guides/articles with content open in the side panel (data-doc); others keep their link
$("#guides").innerHTML = GUIDES.map((g) => `
  <a class="guide" href="${esc(g.href)}" ${g.doc ? `data-doc="guides:${esc(g.slug)}"` : ""}>
    <svg class="ico" viewBox="0 0 24 24" aria-hidden="true">${GUIDE_ICON[g.icon] || GUIDE_ICON.pin}</svg>
    <h3>${esc(g.t)}</h3><p>${esc(g.d)}</p>
  </a>`).join("");

// ---------- 8. blog (latest 3 on the homepage) ----------
$("#blog-grid").innerHTML = POSTS.slice(0, 3).map((p) => `
  <a class="post" href="${esc(p.href)}" ${p.doc ? `data-doc="posts:${esc(p.slug)}"` : ""}>
    <div class="post__media"><img src="${esc(img(p.img))}" alt="" loading="lazy" /></div>
    <div class="post__body">
      <span class="post__cat">${esc([p.cat, p.date && fmtDate(p.date, { month: "short", year: "numeric" })].filter(Boolean).join(" · "))}</span>
      <h3>${esc(p.title)}</h3>
      <p>${esc(p.excerpt)}</p>
      <span class="post__more">${esc(t("blog.more", "Read more"))} <span aria-hidden="true">→</span></span>
    </div>
  </a>`).join("");

// ---------- 9. videos & reels ----------
const PLAY = '<span class="play" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 6 10 6-10 6z"/></svg></span>';
const vidCard = (v, cls) => `
  <a class="vid ${cls}" href="${esc(v.url)}" target="_blank" rel="noopener" aria-label="YouTube: ${esc(v.title)}">
    <img src="${esc(img(v.img))}" alt="" loading="lazy" />${PLAY}
    <span class="vid__t">${esc(v.title)}</span>
  </a>`;
$("#video-grid").innerHTML = VIDEOS.map((v) => vidCard(v, "vid--wide")).join("");
$("#reel-grid").innerHTML = REELS.map((v) => vidCard(v, "vid--reel")).join("");

// ---------- 10. faq ----------
$("#faq-list").innerHTML = FAQS.map((f, i) => `
  <div class="qa ${i === 0 ? "is-open" : ""}">
    <button aria-expanded="${i === 0}" aria-controls="qa-${i}">${esc(f.q)}<i aria-hidden="true"></i></button>
    <div class="qa__a" id="qa-${i}" role="region"><div><p>${esc(f.a)}</p></div></div>
  </div>`).join("");
$("#faq-list").addEventListener("click", (e) => {
  const b = e.target.closest(".qa button");
  if (!b) return;
  const qa = b.parentElement, open = !qa.classList.contains("is-open");
  qa.classList.toggle("is-open", open); b.setAttribute("aria-expanded", String(open));
  setTimeout(() => ScrollTrigger.refresh(), 550);
});

// ---------- 4. enquiry form (in the plan panel) ----------
const form = $("#plan-form"), fTrek = $("#f-trek"), fMonth = $("#f-month"), fPeople = $("#f-people");
fTrek.innerHTML = `<option value="">${esc(t("plan.not_sure", "Not sure yet, help me choose"))}</option>` + TREKS.map((x) => `<option value="${esc(x.id)}">${esc(x.name)} (${x.days} ${esc(t("trek.days_lower", "days"))})</option>`).join("");
const now = new Date();
fMonth.min = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
$$(".stepper button", form).forEach((b) => b.addEventListener("click", () => {
  fPeople.value = Math.min(12, Math.max(1, Number(fPeople.value || 1) + Number(b.dataset.step)));
}));
function prefill(trekId, month, style, note) {
  fTrek.value = trekId || "";
  form.elements.message.value = note || "";
  if (month) fMonth.value = month;
  if (style) form.elements.tripstyle.value = style;
  openPlan();
  setTimeout(() => form.elements.name.focus({ preventScroll: true }), 400);
}
form.addEventListener("submit", (e) => {
  e.preventDefault();
  const err = $("#form-error");
  const required = [fMonth, form.elements.name, form.elements.email];
  let bad = required.filter((el) => !el.value.trim());
  if (form.elements.email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.elements.email.value)) bad.push(form.elements.email);
  required.forEach((el) => el.classList.toggle("is-invalid", bad.includes(el)));
  if (bad.length) { err.textContent = t("plan.error", "Please add your start month, name and a valid email."); bad[0].focus(); return; }
  err.textContent = "";
  const fd = new FormData(form), f = Object.fromEntries(fd);
  const extras = fd.getAll("extras");
  const trek = trekById(f.trek)?.name || "Not sure yet";
  const body = [
    "Namaste Crest Himalaya,", "", "I'd like to craft a trek.", "",
    `Trek: ${trek}`, `Preferred start: ${f.month}`, `Travellers: ${f.people}`, `Trip style: ${f.tripstyle}`,
    `Comfort level: ${f.comfort}`, `Extras: ${extras.length ? extras.join(", ") : "-"}`,
    `Name: ${f.name}`, `Email: ${f.email}`, `WhatsApp: ${f.phone || "-"}`, "", f.message || "",
  ].join("\n");
  window.location.href = `mailto:${CMS.settings?.email || "info@cresthimalaya.com"}?subject=${encodeURIComponent(`Trek enquiry: ${trek}, ${f.month}`)}&body=${encodeURIComponent(body)}`;
  $("#form-success").hidden = false;
});

// ---------- assistant ----------
const chat = initChat({ openTrek, prefill, setFilter: (id, all) => { setFilter(id, all); scrollToEl("#journeys"); } });

// ---------- credits ----------
// from Admin → Homepage Sections → Photo credits (pop-up); the built-in list when the CMS is offline
const credits = CMS.settings?.photo_credits?.length ? CMS.settings.photo_credits : CREDITS;
$("#credits-list").innerHTML = credits.map((c) => {
  const lic = c.license ? (/^https?:\/\//i.test(c.source || "") ? `<a href="${esc(c.source)}" target="_blank" rel="noopener">${esc(c.license)}</a>` : esc(c.license)) : "";
  return `<li>${esc(String(c.title).replace(/\.jpe?g$/i, ""))}${c.artist ? `: ${esc(c.artist)}` : ""}${lic ? `, ${lic}` : ""}</li>`;
}).join("");
$("#open-credits").addEventListener("click", () => $("#credits").showModal());
$("#close-credits").addEventListener("click", () => $("#credits").close());

// ---------- reveals ----------
const revealSel = [".section--light .eyebrow", ".section--paper .eyebrow", ".why .eyebrow", ".videos .eyebrow", ".h2", ".lede", ".cat", ".trek", ".craft__media", ".bento__item", ".support__col", ".guide", ".post", ".vid", ".qa", ".footer__grid > div", ".footer__strip > div", ".section-head__aside"];
const revealEls = $$(revealSel.join(",")).filter((el) => !el.closest(".hero"));
revealEls.forEach((el) => {
  el.classList.add("rv");
  const sibs = [...el.parentElement.children].filter((c) => c.matches(revealSel.join(",")));
  el.style.setProperty("--d", `${Math.min(sibs.indexOf(el), 6) * 90}ms`);
});
const io = new IntersectionObserver((entries) => entries.forEach((en) => {
  if (!en.isIntersecting) return;
  en.target.classList.add("in");
  io.unobserve(en.target);
  setTimeout(() => en.target.style.setProperty("--d", "0ms"), 1400);
}), { threshold: 0.12, rootMargin: "0px 0px -6% 0px" });
revealEls.forEach((el) => io.observe(el));

document.addEventListener("visibilitychange", () => {
  document.body.classList.toggle("paused", document.hidden);
  schedule();
});

if (import.meta.env.DEV) { window.__lenis = lenis; window.__ST = ScrollTrigger; }
