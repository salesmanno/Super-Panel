/* Present the real website form as the approved settings workspace; keep every field. */
(() => {
    'use strict';
    window.PortalWebsiteSettings = {
        mount(pane) {
            const form = pane.querySelector('#websideForm');
            if (!form) return null;
            const data = JSON.parse(document.getElementById('pwp-data').textContent);
            const company = data.company || {};
            const original = document.createDocumentFragment();
            const fields = new Map([...form.querySelectorAll('[name]')].map(field => [field.name, field]));
            const records = [];
            const savedCompanyName = document.querySelector('#companyDataForm [name="name"]');
            const legacyHeadings = [...pane.querySelectorAll(':scope > .card > .card-body > .card-title, :scope > .card > .card-body > p.text-muted')]
                .map(element => ({element,hidden:element.hidden}));
            legacyHeadings.forEach(({element}) => { element.hidden = true; });
            original.append(...form.childNodes);
            const layout = document.createElement('div');
            layout.className = 'pwp-website-editor';
            layout.innerHTML = `<header class="pwp-settings-heading"><div><small>DITT NETTSTED</small><h2>Webside</h2><p>Utseende og innstillinger for kundesiden. Se endringene direkte i forhåndsvisningen.</p></div><button type="submit" class="pwp-settings-save">Lagre endringer <span aria-hidden="true">→</span></button></header>
                <div class="pwp-website-grid"><section class="pwp-settings-card" data-settings-group="profile"><h3>Profil og farger</h3></section><section class="pwp-settings-card" data-settings-group="navigation"><h3>Visning og navigasjon</h3></section></div>
                <section class="pwp-settings-card pwp-website-seo"><h3>Søkemotorer og deling</h3><p>Tittel og beskrivelse som brukes når nettsiden finnes og deles.</p><div class="pwp-website-grid" data-settings-group="seo"></div></section>
                <details class="pwp-website-advanced"><summary>Flere nettsideinnstillinger</summary><div data-settings-group="advanced"></div></details><p class="pwp-website-status" role="status" aria-live="polite"></p>`;
            form.appendChild(layout);
            const group = name => layout.querySelector(`[data-settings-group="${name}"]`);
            function move(name, target, labelText) {
                const field = fields.get(name);
                if (!field) return;
                const wrapper = field.closest('.mb-3') || field.parentElement;
                if (records.some(record => record.wrapper === wrapper)) return;
                const marker = document.createComment('website field position');
                wrapper.before(marker);
                const label = wrapper.querySelector('label');
                records.push({wrapper,marker,className:wrapper.className,field,fieldId:field.getAttribute('id'),label,labelClass:label?.className,labelFor:label?.getAttribute('for'),labelHtml:label?.innerHTML});
                wrapper.className = 'pwp-website-field';
                if (!field.id) field.id = `pwp-setting-${name}`;
                if (label) label.htmlFor = field.id;
                if (labelText && label) label.textContent = labelText;
                if (field.type === 'color') {
                    const row = document.createElement('span');
                    row.className = 'pwp-color-control';
                    const output = document.createElement('output');
                    output.textContent = field.value.toUpperCase();
                    field.before(row); row.append(field, output);
                }
                group(target).append(wrapper);
            }
            const nameLabel = document.createElement('label');
            nameLabel.className = 'pwp-website-field';
            nameLabel.textContent = 'Firmanavn';
            const nameInput = document.createElement('input');
            nameInput.name = 'company_name'; nameInput.required = true; nameInput.maxLength = 255;
            nameInput.value = savedCompanyName?.value || company.name || '';
            nameInput.autocomplete = 'off'; nameLabel.append(nameInput); group('profile').append(nameLabel);
            move('general_header_bg_color','profile','Headerfarge');
            move('general_body_bg_color','profile','Sidebakgrunn');
            move('general_primary_color','profile','Primærfarge');
            move('general_secondary_color','profile','Sekundærfarge');
            move('general_font_family','profile','Font');
            const font = fields.get('general_font_family');
            let fontSelect = null;
            if (font?.tagName === 'INPUT') {
                fontSelect = document.createElement('select');
                fontSelect.name = font.name;
                fontSelect.id = font.id;
                const fonts = new Map([[font.value,font.value],['Georgia, serif','Georgia'],['DM Sans, sans-serif','DM Sans'],['Inter, sans-serif','Inter'],['Trebuchet MS, sans-serif','Trebuchet']]);
                for (const [value,label] of fonts) fontSelect.add(new Option(label,value));
                fontSelect.value = font.value;
                font.replaceWith(fontSelect);
            }
            move('general_hero_enabled','navigation','Vis toppsak på Hjem');
            move('general_logo_position','navigation','Logoens plassering');
            move('general_nav_position','navigation','Navigasjon');
            move('general_header_mode','navigation','Headerens posisjon');
            move('general_footer_mode','navigation','Footerens posisjon');
            move('general_header_height','navigation','Headerhøyde');
            move('general_logo_height','navigation','Logohøyde');
            move('general_border_radius','navigation','Avrunding');
            const hero = fields.get('general_hero_enabled');
            let heroToggle = null;
            if (hero) {
                hero.hidden = true;
                heroToggle = document.createElement('input'); heroToggle.type = 'checkbox'; heroToggle.checked = hero.value === '1';
                const heroLabel = hero.parentElement.querySelector('label');
                heroLabel?.removeAttribute('for');
                heroLabel?.classList.add('pwp-website-toggle');
                heroLabel?.prepend(heroToggle);
            }
            const footerNote = document.createElement('p');
            footerNote.className = 'pwp-footer-calculation';
            group('navigation').append(footerNote);
            move('config_seo_title','seo','Søketittel');
            move('config_seo_description','seo','Søkebeskrivelse');
            move('general_site_title','seo','Sidetittel');
            move('general_site_description','seo','Sidebeskrivelse');
            group('advanced').append(original);
            // Keep every other named control, including URLs, contact data and existing integrations.
            group('advanced').querySelectorAll('button[type="submit"]').forEach(button => { button.hidden = true; });
            const saveButton = layout.querySelector('button[type="submit"]');
            const status = layout.querySelector('.pwp-website-status');
            let saving = false, destroyed = false;
            const snapshot = () => JSON.stringify([...new FormData(form)].filter(([,value]) => typeof value === 'string'));
            function theme() {
                const values = Object.fromEntries(new FormData(form));
                const result = {...company,name:nameInput.value};
                for (const key of ['header_bg_color','body_bg_color','primary_color','secondary_color','font_family','header_height','logo_height','logo_position','nav_position','header_mode','footer_mode','border_radius','hero_enabled']) {
                    if (`general_${key}` in values) result[key] = values[`general_${key}`];
                }
                if (values.general_logo_url) result.img_url = values.general_logo_url;
                return result;
            }
            let baseline = snapshot(), baselineTheme = theme();
            const preview = value => document.dispatchEvent(new CustomEvent('portal:website-preview',{detail:value}));
            function changed() {
                if (heroToggle) hero.value = heroToggle.checked ? '1' : '0';
                layout.querySelectorAll('input[type="color"]').forEach(input => {
                    const output = input.closest('.pwp-color-control')?.querySelector('output');
                    if (output) output.textContent = input.value.toUpperCase();
                });
                const height = form.elements.general_header_height?.value || '80px';
                footerNote.textContent = `Footerhøyde: ${height} × 0,6. Velg om footeren alltid skal være synlig eller følge siden.`;
                const dirty = snapshot() !== baseline;
                saveButton.disabled = saving || !dirty;
                status.textContent = dirty ? 'Endringene vises i previewen. Trykk «Lagre endringer» for å publisere innstillingene.' : 'Ingen ulagrede endringer.';
                status.dataset.error = 'false';
                preview(theme());
            }
            async function submit(event) {
                event.preventDefault(); event.stopImmediatePropagation();
                nameInput.value = nameInput.value.trim();
                if (saving || snapshot() === baseline || !form.reportValidity()) return;
                const payload = {config:{},general:{},company_name:nameInput.value.trim()};
                for (const [key,value] of new FormData(form)) {
                    if (typeof value !== 'string') continue;
                    if (key.startsWith('config_')) payload.config[key.slice(7)] = value;
                    if (key.startsWith('general_')) payload.general[key.slice(8)] = value;
                }
                const submittedSnapshot = snapshot(), submittedTheme = theme();
                saving = true; saveButton.disabled = true; status.textContent = 'Lagrer innstillinger …';
                // Prevent edits being incorrectly marked saved while the request is in flight.
                const controls = [...form.elements].filter(control => !control.disabled);
                controls.forEach(control => { control.disabled = true; });
                try {
                    const response = await fetch('/api/update_webside_data.php', {
                        method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.error || 'Innstillingene kunne ikke lagres.');
                    controls.forEach(control => { control.disabled = false; });
                    baseline = submittedSnapshot; baselineTheme = submittedTheme;
                    if (savedCompanyName) savedCompanyName.value = nameInput.value.trim();
                    status.textContent = 'Websideinnstillingene er lagret.';
                    status.dataset.error = 'false';
                    preview(baselineTheme);
                    document.dispatchEvent(new CustomEvent('portal:website-saved',{detail:baselineTheme}));
                } catch (error) {
                    status.textContent = error.message || 'Lagring feilet. Endringene er ikke lagret.';
                    status.dataset.error = 'true';
                } finally {
                    saving = false;
                    controls.forEach(control => { control.disabled = false; });
                    saveButton.disabled = snapshot() === baseline;
                }
            }
            const invalid = event => { const details = event.target.closest('details'); if (details) details.open = true; };
            const beforeUnload = event => { if (saving || snapshot() !== baseline) { event.preventDefault(); event.returnValue = ''; } };
            form.addEventListener('input',changed);
            form.addEventListener('change',changed);
            form.addEventListener('submit',submit,true);
            form.addEventListener('invalid',invalid,true);
            window.addEventListener('beforeunload',beforeUnload);
            changed();
            return {
                canClose: () => !saving && (snapshot() === baseline || window.confirm('Forkaste ulagrede websideinnstillinger?')),
                destroy() {
                    if (destroyed) return;
                    destroyed = true;
                    legacyHeadings.forEach(({element,hidden}) => { element.hidden = hidden; });
                    preview(baselineTheme);
                    form.removeEventListener('input',changed); form.removeEventListener('change',changed);
                    form.removeEventListener('submit',submit,true); form.removeEventListener('invalid',invalid,true);
                    window.removeEventListener('beforeunload',beforeUnload);
                    if (fontSelect) { font.value = fontSelect.value; fontSelect.replaceWith(font); }
                    heroToggle?.remove(); if (hero) hero.hidden = false;
                    for (const record of records) {
                        const color = record.wrapper.querySelector('.pwp-color-control');
                        if (color) color.replaceWith(color.querySelector('input'));
                        record.wrapper.className = record.className; record.marker.replaceWith(record.wrapper);
                        if (record.fieldId === null) record.field.removeAttribute('id'); else record.field.id = record.fieldId;
                        if (record.label) {
                            record.label.className = record.labelClass;
                            record.label.innerHTML = record.labelHtml;
                            if (record.labelFor === null) record.label.removeAttribute('for'); else record.label.htmlFor = record.labelFor;
                        }
                    }
                    const advanced = group('advanced');
                    advanced.querySelectorAll('button[type="submit"]').forEach(button => { button.hidden = false; });
                    const restored = document.createDocumentFragment(); restored.append(...advanced.childNodes);
                    form.replaceChildren(restored);
                }
            };
        }
    };
})();
