// Shared trek helpers for the trek grid, header menus and the assistant.
import { TREKS, CATEGORIES, DEPARTURES } from "./data.js";
import { t, CMS } from "./cms.js";

export const fmt = (n) => Number(n).toLocaleString("en-US");
export const trekById = (id) => TREKS.find((x) => x.id === id);
export const altM = (x) => x.alt_m ?? Number(String(x.alt).replace(/[^\d]/g, ""));

const EXP = {
  classic: { label: "Classic treks", test: (x) => x.tags.includes("classic") },
  short: { label: "Short treks", test: (x) => x.days <= 11 },
  high: { label: "High altitude", test: (x) => altM(x) >= 5000 },
  remote: { label: "Remote treks", test: (x) => x.tags.includes("remote") },
  family: { label: "Family treks", test: (x) => x.tags.includes("family") },
};

// filter ids: "all", a CATEGORIES id, "trek" (everything but peaks), "region:<name>", "exp:<key>"
// Region filters always compare against the English region/area (menu filters are language independent).
export function matchesFilter(x, id) {
  if (!id || id === "all") return true;
  if (id === "trek") return x.cat !== "peak";
  if (id.startsWith("region:")) {
    const r = id.slice(7).toLowerCase();
    return (x.region_en ?? x.region).toLowerCase().startsWith(r) || (x.area_en ?? x.area).toLowerCase().startsWith(r);
  }
  if (id.startsWith("exp:")) return EXP[id.slice(4)]?.test(x) ?? false;
  return x.cat === id;
}
export function filterLabel(id) {
  if (id.startsWith("region:")) return t("filter.region", "{name} region", { name: id.slice(7) });
  if (id.startsWith("exp:")) return EXP[id.slice(4)] ? t(`exp.${id.slice(4)}`, EXP[id.slice(4)].label) : id;
  const c = CATEGORIES.find((c) => c.id === id);
  return c ? t(`cat.${id}`, c.label) : id;
}

// upcoming departures, soonest first
export function upcoming(trekId) {
  const today = new Date().toISOString().slice(0, 10);
  return DEPARTURES.filter((d) => d.date >= today && (!trekId || d.trek === trekId)).sort((a, b) => a.date.localeCompare(b.date));
}
// Dates follow the visitor's chosen website language
// dates, months and years stay in English on every language (client decision)
export const fmtDate = (iso, opts = { day: "numeric", month: "short", year: "numeric" }) => new Date(iso + "T00:00:00").toLocaleDateString("en-GB", opts);
