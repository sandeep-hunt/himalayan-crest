// Crest Himalaya CMS admin helpers (no build step).
(() => {
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  // Mobile sidebar
  document.addEventListener("click", (e) => {
    if (e.target.closest("[data-toggle-side]")) { document.body.classList.toggle("side-open"); return; }
    if (document.body.classList.contains("side-open") && !e.target.closest(".side")) document.body.classList.remove("side-open");
  });

  // Confirm destructive actions
  document.addEventListener("submit", (e) => {
    const msg = e.target.dataset.confirm;
    if (msg && !window.confirm(msg)) e.preventDefault();
  });

  // Language tabs: show the inputs of one language at a time
  const tabs = document.querySelector("[data-lang-tabs]");
  if (tabs) {
    const buttons = $$("button[data-locale]", tabs);
    const panesFor = (loc) => $$(`.tr-pane[data-locale="${loc}"]`);
    const markFilled = () => buttons.forEach((b) => {
      const filled = panesFor(b.dataset.locale).some((p) => [...p.querySelectorAll("input,textarea")].some((i) => i.value.trim() !== ""));
      b.classList.toggle("has-content", filled);
    });
    const show = (loc) => {
      buttons.forEach((b) => b.setAttribute("aria-selected", String(b.dataset.locale === loc)));
      $$(".tr-pane").forEach((p) => { p.hidden = p.dataset.locale !== loc; });
      // other languages = view only: hide what is not translated and the Save / Cancel bar
      document.body.classList.toggle("is-viewing-translation", loc !== "en");
      const note = document.querySelector("[data-view-note]");
      if (note) {
        note.hidden = loc === "en";
        note.querySelector("b").textContent = buttons.find((b) => b.dataset.locale === loc)?.title.split(" (")[0] || loc;
      }
    };
    buttons.forEach((b) => b.addEventListener("click", () => show(b.dataset.locale)));
    document.addEventListener("click", (e) => { const go = e.target.closest("[data-locale-go]"); if (go) show(go.dataset.localeGo); });
    document.addEventListener("input", (e) => { if (e.target.closest(".tr-pane")) markFilled(); });
    markFilled();
    // Always open on English: it is the language that is edited (the others are view-only)
    show("en");
  }

  // Image preview before upload
  document.addEventListener("change", (e) => {
    const input = e.target.closest("input[type=file][data-preview]");
    if (!input || !input.files[0]) return;
    const box = document.querySelector(`[data-preview-for="${input.id}"]`);
    if (!box) return;
    const img = document.createElement("img");
    img.src = URL.createObjectURL(input.files[0]);
    box.replaceChildren(img);
  });

  // Client-side row filter (interface text)
  $$("[data-filter-rows]").forEach((input) => {
    const table = document.querySelector(input.dataset.filterRows);
    input.addEventListener("input", () => {
      const q = input.value.trim().toLowerCase();
      $$("tr[data-row]", table).forEach((tr) => {
        const text = (tr.textContent + " " + [...tr.querySelectorAll("input")].map((i) => i.value).join(" ")).toLowerCase();
        tr.hidden = q !== "" && !text.includes(q);
      });
    });
  });

  // Menu items: show "menu" fields for a menu and "link" fields for a link inside a menu
  const parentSelect = document.querySelector("[data-menu-parent]");
  if (parentSelect) {
    const apply = () => {
      const kind = parentSelect.value ? "link" : "top";
      $$("[data-only]").forEach((f) => { f.hidden = f.dataset.only !== kind; });
      // hide a two-column row or a whole section card when everything in it is hidden
      $$(".field-row, .form-card").forEach((box) => {
        const fields = $$(":scope > .field, :scope > .field-row", box);
        if (fields.length) box.hidden = fields.every((x) => x.hidden);
      });
    };
    parentSelect.addEventListener("change", apply);
    apply();
  }

  // Warn before leaving a form with unsaved changes
  $$("form.edit-form").forEach((form) => {
    let dirty = false;
    form.addEventListener("input", () => { dirty = true; });
    form.addEventListener("change", () => { dirty = true; });
    form.addEventListener("submit", () => { dirty = false; });
    form.addEventListener("cms:saving", () => { dirty = false; });
    window.addEventListener("beforeunload", (e) => { if (dirty) { e.preventDefault(); e.returnValue = ""; } });
  });

  // ---------- Progress popup (automatic translation) ----------
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
  let busy = false; // while translating: warn before closing / leaving the tab
  window.addEventListener("beforeunload", (e) => { if (busy) { e.preventDefault(); e.returnValue = ""; } });

  function progressPopup(title) {
    const box = document.createElement("div");
    box.className = "tr-modal";
    box.setAttribute("role", "dialog");
    box.setAttribute("aria-modal", "true");
    box.innerHTML = `
      <div class="tr-modal__card">
        <h2 class="tr-modal__title"></h2>
        <p class="tr-modal__step"></p>
        <div class="tr-modal__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><i></i></div>
        <p class="tr-modal__pct">0%</p>
        <p class="tr-modal__warn">Please keep this tab open and do not switch tabs until it has finished.</p>
        <p class="tr-modal__err" hidden></p>
        <div class="tr-modal__actions" hidden>
          <button type="button" class="btn" data-act="cancel">Close</button>
          <button type="button" class="btn btn--primary" data-act="retry">Retry</button>
        </div>
      </div>`;
    box.querySelector(".tr-modal__title").textContent = title;
    document.body.append(box);
    box.querySelector('[data-act="retry"]').focus?.();
    const bar = box.querySelector(".tr-modal__bar"), pct = box.querySelector(".tr-modal__pct");
    return {
      step(text) { box.querySelector(".tr-modal__step").textContent = text; },
      set(p) {
        const v = Math.max(0, Math.min(100, Math.round(p)));
        bar.querySelector("i").style.width = v + "%"; bar.setAttribute("aria-valuenow", v); pct.textContent = v + "%";
      },
      // show the error with Retry / Close; resolves "retry" or "cancel"
      fail(message) {
        busy = false;
        box.classList.add("is-error");
        const err = box.querySelector(".tr-modal__err");
        err.hidden = false; err.textContent = message;
        box.querySelector(".tr-modal__warn").hidden = true;
        const actions = box.querySelector(".tr-modal__actions");
        actions.hidden = false;
        actions.querySelector('[data-act="retry"]').focus();
        return new Promise((resolve) => {
          actions.onclick = (e) => {
            const act = e.target.closest("[data-act]")?.dataset.act;
            if (!act) return;
            actions.hidden = true; err.hidden = true; box.classList.remove("is-error");
            box.querySelector(".tr-modal__warn").hidden = false;
            if (act === "retry") busy = true;
            resolve(act);
          };
        });
      },
      close() { box.remove(); },
    };
  }

  async function postJson(url, body) {
    let res;
    try {
      res = await fetch(url, { method: "POST", headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": csrf }, body: JSON.stringify(body) });
    } catch {
      throw new Error("No connection to the server. Check the internet connection and try again.");
    }
    const data = await res.json().catch(() => ({}));
    if (res.status === 419) throw new Error("Your session has expired. Copy your text, reload the page and save again.");
    if (!res.ok) throw new Error(data.message || `The server answered with an error (${res.status}).`);
    return data;
  }
  // run jobs one by one; on failure ask Retry / Close. Resolves true when all succeeded.
  async function runJobs(ui, jobs) {
    busy = true;
    for (let i = 0; i < jobs.length; i++) {
      ui.step(jobs[i].label);
      ui.set((i / jobs.length) * 100);
      for (;;) {
        try { await jobs[i].run(); break; } catch (e) {
          if ((await ui.fail(`Translation failed: ${e.message}`)) !== "retry") return false;
        }
      }
    }
    ui.set(100);
    busy = false;
    return true;
  }
  window.cmsTranslate = { progressPopup, postJson, runJobs };

  // ---------- Save = translate English into every language, then save ----------
  const TRANSLATE_URL = document.querySelector('meta[name="cms-translate-url"]')?.content;
  const tabsEl = document.querySelector("[data-lang-tabs]");
  // the tabs list only the languages SHOWN on the website: those are translated on save (hidden ones are skipped)
  // (switching a language on in Languages re-translates all English content into it)
  const targets = tabsEl ? $$("button[data-locale]", tabsEl).map((b) => ({ code: b.dataset.locale, name: b.title.split(" (")[0] || b.dataset.locale })).filter((l) => l.code !== "en") : [];
  if (TRANSLATE_URL && targets.length) {
    $$("form.edit-form").forEach((form) => {
      const englishFields = () => $$('.tr-pane[data-locale="en"] input, .tr-pane[data-locale="en"] textarea', form).filter((el) => el.name.endsWith("[en]"));
      if (!englishFields().length) return;
      form.addEventListener("submit", async (e) => {
        if (form.dataset.translated === "1" || e.defaultPrevented) return;
        e.preventDefault();
        if (!form.reportValidity()) return;
        const units = englishFields().map((el) => ({ base: el.name.slice(0, -4), text: el.value, changed: el.value.trim() !== el.defaultValue.trim() }));
        const targetInput = (u, code) => {
          let el = form.elements.namedItem(`${u.base}[${code}]`);
          if (!el) { el = document.createElement("input"); el.type = "hidden"; el.name = `${u.base}[${code}]`; form.append(el); }
          return el;
        };
        // Only the fields whose English changed (or that have no translation yet) are translated;
        // every other translation is kept exactly as it is.
        const results = {}; // code -> { unit index: translation }
        const jobs = [];
        targets.forEach((t, ti) => {
          results[t.code] = {};
          const todo = [];
          units.forEach((u, i) => {
            if (u.text.trim() === "") { if (u.changed) results[t.code][i] = ""; return; } // English emptied: clear it
            if (u.changed || targetInput(u, t.code).value.trim() === "") todo.push(i);
          });
          // batches of at most 100 texts / ~20,000 characters per request
          const batches = [];
          let cur = [], size = 0;
          todo.forEach((i) => {
            if (cur.length && (cur.length >= 100 || size + units[i].text.length > 20000)) { batches.push(cur); cur = []; size = 0; }
            cur.push(i); size += units[i].text.length;
          });
          if (cur.length) batches.push(cur);
          batches.forEach((b, bi) => jobs.push({
            label: `English → ${t.name} (${ti + 1} of ${targets.length}) · ${b.length} ${b.length === 1 ? "text" : "texts"}${batches.length > 1 ? ` · part ${bi + 1} of ${batches.length}` : ""}`,
            run: async () => {
              const data = await postJson(TRANSLATE_URL, { target: t.code, texts: b.map((i) => units[i].text) });
              b.forEach((i, k) => { results[t.code][i] = data.translations[k] ?? ""; });
            },
          }));
        });
        // the progress popup is shown on every save
        const ui = progressPopup("Translating and saving…");
        if (jobs.length) {
          const ok = await runJobs(ui, jobs);
          if (!ok) { ui.close(); return; } // nothing saved, English included
        } else {
          ui.step("No text changed, so nothing needs translating.");
          ui.set(100);
          await new Promise((r) => setTimeout(r, 700)); // long enough to be seen
        }
        ui.set(100);
        ui.step("Saving…");
        targets.forEach((t) => Object.entries(results[t.code]).forEach(([i, text]) => { targetInput(units[i], t.code).value = text; }));
        form.dataset.translated = "1";
        form.dispatchEvent(new Event("cms:saving"));
        HTMLFormElement.prototype.submit.call(form);
      });
    });
  }
})();
