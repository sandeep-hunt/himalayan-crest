// Connection to the Crest Himalaya CMS (Laravel API).
// The website loads its content from the CMS when it starts. If the CMS can't be reached,
// the built-in content in data.js and index.html is used, so the site never breaks.
// Set the CMS address in .env:  VITE_CMS_URL=https://cms.cresthimalaya.com
import { TREKS, FAQS, GUIDES, POSTS, VIDEOS, REELS, DEPARTURES, CHAT_INFO, CHAT_DATA } from "./data.js";

export const CMS_URL = String(import.meta.env.VITE_CMS_URL || "").replace(/\/+$/, "");
const LANG_KEY = "crest-lang";

// Live CMS state (empty until loadContent() succeeds)
export const CMS = { live: false, locale: "en", dir: "ltr", languages: [], ui: {}, sections: {}, settings: null, categories: [], team: [], reviews: [], menu: null };

export const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);

/** Interface text: CMS translation, else the English fallback given in the code. {name} placeholders are filled from vars. */
export function t(key, fallback = key, vars) {
  let s = CMS.ui[key] ?? fallback;
  if (vars) s = s.replace(/\{(\w+)\}/g, (m, k) => (k in vars ? vars[k] : m));
  return s;
}

/** Image source: CMS images are absolute URLs; built-in ones are file names in public/img. */
export function img(src) {
  if (!src) return "";
  return /^(https?:|data:|\/)/.test(src) ? src : `img/${src}`;
}

/** Read a value from the CMS homepage sections, e.g. sec("why.title"). Empty strings count as missing. */
export function sec(path) {
  const v = path.split(".").reduce((o, k) => (o == null ? o : o[k]), CMS.sections);
  return v === "" || (Array.isArray(v) && !v.length) ? null : v;
}

function requestedLang() {
  const q = new URLSearchParams(location.search).get("lang");
  if (q) return q.toLowerCase();
  try { const s = localStorage.getItem(LANG_KEY); if (s) return s; } catch {}
  return (navigator.language || "en").slice(0, 2).toLowerCase();
}

export function setLang(code) {
  try { localStorage.setItem(LANG_KEY, code); } catch {}
  const url = new URL(location.href);
  url.searchParams.set("lang", code);
  location.href = url.toString();
}

const replace = (arr, items) => arr.splice(0, arr.length, ...items);

function apply(d) {
  Object.assign(CMS, {
    live: true, locale: d.locale, dir: d.dir, languages: d.languages || [], ui: d.ui || {}, sections: d.sections || {},
    settings: d.settings || null, categories: d.categories || [], team: d.team || [], reviews: d.reviews || [], menu: d.menu || null,
  });
  const trekRow = (t) => ({ ...t, months: t.months || [], tags: t.tags || [], highlights: t.highlights || [], itinerary: t.itinerary || [] });
  if (d.treks?.length) replace(TREKS, d.treks.map(trekRow));
  // the chat assistant is English only: English treks / FAQs when the page is in another language
  CHAT_DATA.treks = d.chat?.treks?.length ? d.chat.treks.map(trekRow) : null;
  CHAT_DATA.faqs = d.chat?.faqs ? d.chat.faqs : null;
  replace(DEPARTURES, (d.departures || []).filter((x) => TREKS.some((t) => t.id === x.trek)));
  replace(FAQS, d.faqs || []);
  replace(GUIDES, (d.guides || []).map((g) => ({ icon: g.icon, t: g.title, d: g.summary || "", href: "#", slug: g.slug, doc: g.has_content })));
  replace(POSTS, (d.posts || []).map((p) => ({ cat: p.cat || "", date: p.date, title: p.title, excerpt: p.excerpt || "", img: p.img, href: "#", slug: p.slug, doc: p.has_content })));
  replace(VIDEOS, (d.videos || []).filter((v) => v.type === "video"));
  replace(REELS, (d.videos || []).filter((v) => v.type === "reel"));
  const chat = d.settings?.chat || {};
  for (const [k, v] of Object.entries(chat)) if (v != null && v !== "" && !(Array.isArray(v) && !v.length)) CHAT_INFO[k] = v;

  document.documentElement.lang = d.locale;
  document.documentElement.dir = d.dir;
  // Joined / non-Latin scripts (Arabic, Hebrew, Devanagari, CJK…) must not get the wide brand letter-spacing
  const complex = d.dir === "rtl" || /^(ne|hi|mr|bn|pa|gu|ta|te|kn|ml|si|th|lo|km|my|bo|dz|zh|ja|ko|ka|hy|am)\b/.test(d.locale);
  document.documentElement.classList.toggle("script-complex", complex);
  try { localStorage.setItem(LANG_KEY, d.locale); } catch {}
}

/** Load all homepage content in one request. Resolves true when CMS content is in use. */
export async function loadContent() {
  if (!CMS_URL) return false;
  const ctrl = new AbortController();
  const timer = setTimeout(() => ctrl.abort(), 6000);
  try {
    const res = await fetch(`${CMS_URL}/api/v1/site?lang=${encodeURIComponent(requestedLang())}`, { signal: ctrl.signal, cache: "no-store", headers: { Accept: "application/json" } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    apply((await res.json()).data);
    return true;
  } catch (e) {
    console.warn("[Crest CMS] Content service unavailable, showing built-in content.", e);
    // During development make this impossible to miss (never shown on the live site)
    if (import.meta.env.DEV) {
      const bar = document.createElement("div");
      bar.setAttribute("role", "alert");
      bar.style.cssText = "position:fixed;left:0;right:0;bottom:0;z-index:9999;background:#b3261e;color:#fff;font:600 13px/1.4 system-ui,sans-serif;padding:10px 16px;text-align:center";
      bar.textContent = `Development notice: the website could not load content from the CMS at ${CMS_URL || "(VITE_CMS_URL is empty)"}, so it shows the built-in content. Is the backend running (php artisan serve)? See the browser console for details.`;
      document.body.append(bar);
    }
    return false;
  } finally {
    clearTimeout(timer);
  }
}

/** Full article or travel guide (type: "posts" | "guides"). */
export async function fetchDoc(type, slug) {
  const res = await fetch(`${CMS_URL}/api/v1/${type}/${encodeURIComponent(slug)}?lang=${CMS.locale}`, { cache: "no-store", headers: { Accept: "application/json" } });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return (await res.json()).data;
}

/** Minimal, safe Markdown for articles and guides: ## headings, - lists, **bold**, [links](https://...). HTML is escaped. */
export function md(text) {
  const inline = (s) => esc(s)
    .replace(/\*\*(.+?)\*\*/g, "<strong>$1</strong>")
    .replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
  // text typed in the admin uses Windows line breaks (\r\n); normalise them first
  return String(text || "").replace(/\r\n?/g, "\n").trim().split(/\n\s*\n/).map((block) => {
    const lines = block.split("\n");
    if (/^#{1,3}\s/.test(lines[0])) return `<h4>${inline(lines[0].replace(/^#+\s*/, ""))}</h4>` + (lines.length > 1 ? md(lines.slice(1).join("\n")) : "");
    if (lines.every((l) => /^\s*[-*]\s+/.test(l))) return `<ul>${lines.map((l) => `<li>${inline(l.replace(/^\s*[-*]\s+/, ""))}</li>`).join("")}</ul>`;
    return `<p>${lines.map(inline).join("<br />")}</p>`;
  }).join("");
}
