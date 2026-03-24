/**
 * dp-import.js – Wizard-Import (neu)
 * Unterstützt: dienstplan, bereiche, taetigkeiten, vereine, veranstaltungen, dienste
 */
(function ($) {
    'use strict';

    /* ------------------------------------------------------------------ */
    /* Feld-Definitionen                                                    */
    /* ------------------------------------------------------------------ */
    const fieldDefs = {
        dienstplan: [
            { key: 'datum',                 label: 'Datum',                    required: true  },
            { key: 'von_zeit',              label: 'Von Zeit (HH:MM)',         required: false },
            { key: 'bis_zeit',              label: 'Bis Zeit (HH:MM)',         required: false },
            { key: 'bereich_name',          label: 'Bereich Name',             required: false },
            { key: 'bereich_farbe',         label: 'Bereich Farbe (#hex)',     required: false },
            { key: 'bereich_admin_only',    label: 'Bereich Admin-Only (1/0)', required: false },
            { key: 'taetigkeit_name',       label: 'Tätigkeit Name',           required: false },
            { key: 'taetigkeit_admin_only', label: 'Tätigkeit Admin-Only (1/0)', required: false },
            { key: 'verein_kuerzel',        label: 'Verein Kürzel',            required: false },
            { key: 'verein_name',           label: 'Verein Name',              required: false },
            { key: 'anzahl_personen',       label: 'Anzahl Personen',          required: false },
            { key: 'splittbar',             label: 'Splittbar (1/0)',          required: false },
            { key: 'besonderheiten',        label: 'Besonderheiten',           required: false }
        ],
        bereiche: [
            { key: 'name',       label: 'Name',              required: true  },
            { key: 'farbe',      label: 'Farbe (#hex)',       required: false },
            { key: 'aktiv',      label: 'Aktiv (1/0)',        required: false },
            { key: 'sortierung', label: 'Sortierung',         required: false },
            { key: 'admin_only', label: 'Admin-Only (1/0)',   required: false }
        ],
        taetigkeiten: [
            { key: 'bereich_name', label: 'Bereich Name',       required: true  },
            { key: 'bereich_id',   label: 'Bereich ID',         required: false },
            { key: 'name',         label: 'Name',               required: true  },
            { key: 'beschreibung', label: 'Beschreibung',       required: false },
            { key: 'aktiv',        label: 'Aktiv (1/0)',        required: false },
            { key: 'sortierung',   label: 'Sortierung',         required: false },
            { key: 'admin_only',   label: 'Admin-Only (1/0)',   required: false }
        ],
        vereine: [
            { key: 'name',            label: 'Name',               required: true  },
            { key: 'kuerzel',         label: 'Kürzel',             required: true  },
            { key: 'beschreibung',    label: 'Beschreibung',       required: false },
            { key: 'kontakt_name',    label: 'Kontakt Name',       required: false },
            { key: 'kontakt_email',   label: 'Kontakt E-Mail',     required: false },
            { key: 'kontakt_telefon', label: 'Kontakt Telefon',    required: false }
        ],
        veranstaltungen: [
            { key: 'name',           label: 'Name',                        required: true  },
            { key: 'start_datum',    label: 'Start-Datum (YYYY-MM-DD)',    required: true  },
            { key: 'end_datum',      label: 'End-Datum (YYYY-MM-DD)',      required: true  },
            { key: 'beschreibung',   label: 'Beschreibung',                required: false },
            { key: 'dienst_von_zeit',label: 'Dienst Von (HH:MM)',          required: false },
            { key: 'dienst_bis_zeit',label: 'Dienst Bis (HH:MM)',          required: false }
        ],
        dienste: [
            { key: 'datum',           label: 'Datum',               required: true  },
            { key: 'von_zeit',        label: 'Von Zeit (HH:MM)',    required: false },
            { key: 'bis_zeit',        label: 'Bis Zeit (HH:MM)',    required: false },
            { key: 'verein_kuerzel',  label: 'Verein Kürzel',       required: false },
            { key: 'bereich_name',    label: 'Bereich Name',        required: false },
            { key: 'taetigkeit_name', label: 'Tätigkeit Name',      required: false },
            { key: 'anzahl_personen', label: 'Anzahl Personen',     required: false },
            { key: 'splittbar',       label: 'Splittbar (1/0)',     required: false },
            { key: 'besonderheiten',  label: 'Besonderheiten',      required: false }
        ]
    };

    const typeHints = {
        dienstplan:    'Eine Zeile = ein Dienst aus einem alten Excel-Dienstplan. Bereiche, Tätigkeiten und fehlende Vereine werden automatisch angelegt.',
        bereiche:      'Importiert Bereiche mit Name, Farbe und Admin-only-Flag. Vorhandene Bereiche werden anhand des Namens erkannt.',
        taetigkeiten:  'Importiert Tätigkeiten. Angabe von bereich_name oder bereich_id erforderlich. Fehlende Bereiche werden angelegt.',
        vereine:       'Importiert Vereine. Erkennung über Kürzel. Vorhandene Vereine werden nur bei Modus "Aktualisieren" überschrieben.',
        veranstaltungen: 'Importiert Veranstaltungen. Erkennung über Name.',
        dienste:       'Importiert einzelne Dienste für eine ausgewählte Veranstaltung.'
    };

    /* ------------------------------------------------------------------ */
    /* Zustand                                                              */
    /* ------------------------------------------------------------------ */
    let state = {
        type:       null,
        csvRaw:     null,
        csvHeaders: null,
        csvData:    null,
        mapping:    {}
    };

    const templateHeaders = [
        'datum', 'von_zeit', 'bis_zeit', 'bereich_name', 'bereich_farbe',
        'bereich_admin_only', 'taetigkeit_name', 'taetigkeit_admin_only',
        'verein_kuerzel', 'verein_name', 'anzahl_personen', 'splittbar', 'besonderheiten'
    ];

    const templatePresets = {
        dienstplan: {
            datum: 'datum',
            von_zeit: 'von_zeit',
            bis_zeit: 'bis_zeit',
            bereich_name: 'bereich_name',
            bereich_farbe: 'bereich_farbe',
            bereich_admin_only: 'bereich_admin_only',
            taetigkeit_name: 'taetigkeit_name',
            taetigkeit_admin_only: 'taetigkeit_admin_only',
            verein_kuerzel: 'verein_kuerzel',
            verein_name: 'verein_name',
            anzahl_personen: 'anzahl_personen',
            splittbar: 'splittbar',
            besonderheiten: 'besonderheiten'
        },
        bereiche: {
            bereich_name: 'name',
            bereich_farbe: 'farbe',
            bereich_admin_only: 'admin_only'
        },
        taetigkeiten: {
            bereich_name: 'bereich_name',
            taetigkeit_name: 'name',
            taetigkeit_admin_only: 'admin_only'
        },
        vereine: {
            verein_kuerzel: 'kuerzel',
            verein_name: 'name'
        },
        dienste: {
            datum: 'datum',
            von_zeit: 'von_zeit',
            bis_zeit: 'bis_zeit',
            verein_kuerzel: 'verein_kuerzel',
            bereich_name: 'bereich_name',
            taetigkeit_name: 'taetigkeit_name',
            anzahl_personen: 'anzahl_personen',
            splittbar: 'splittbar',
            besonderheiten: 'besonderheiten'
        }
    };

    /* ------------------------------------------------------------------ */
    /* Initialisierung                                                      */
    /* ------------------------------------------------------------------ */
    $(function () {
        bindTypeCards();
        bindFileHandlers();
        bindWizardNav();
    });

    /* ------------------------------------------------------------------ */
    /* Typ-Karten                                                           */
    /* ------------------------------------------------------------------ */
    function bindTypeCards() {
        $(document).on('click', '.dp-type-card', function () {
            $('.dp-type-card').removeClass('selected');
            $(this).addClass('selected');
            state.type = $(this).data('type');
            $('#dp_import_type').val(state.type);

            // Hinweis anzeigen
            const hint = typeHints[state.type] || '';
            if (hint) {
                $('#dp-type-hint-text').text(hint);
                $('#dp-type-hint').show();
            } else {
                $('#dp-type-hint').hide();
            }

            // Optionale Zeilen in Schritt 3 vorbereiten
            const needsVeranstaltung = (state.type === 'dienste' || state.type === 'dienstplan');
            $('#dp-veranstaltung-row').toggle(needsVeranstaltung);
            $('#dp-create-vereine-row').toggle(state.type === 'dienstplan');
            $('#dp-bereich-farbe-row').toggle(state.type === 'dienstplan' || state.type === 'bereiche' || state.type === 'taetigkeiten');

            // Datei-Sektion einblenden
            $('#dp-file-section').show();
            checkAnalyzeBtn();
        });
    }

    /* ------------------------------------------------------------------ */
    /* Datei-Handling                                                       */
    /* ------------------------------------------------------------------ */
    function bindFileHandlers() {
        // Browse-Button
        $('#dp-browse-btn').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $('#dp_import_file')[0].click();
        });

        // Drop Zone Click
        $('#dp-file-drop-zone').on('click', function (e) {
            // Vermeidet Rekursion: Klicks auf interaktive Elemente nicht erneut auf Input umleiten.
            if ($(e.target).closest('button, input, a, label').length === 0) {
                $('#dp_import_file')[0].click();
            }
        });

        // Drag & Drop
        $('#dp-file-drop-zone').on('dragover', function (e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        }).on('dragleave drop', function () {
            $(this).removeClass('drag-over');
        }).on('drop', function (e) {
            e.preventDefault();
            const files = e.originalEvent.dataTransfer.files;
            if (files.length) handleFileSelected(files[0]);
        });

        // File Input
        $('#dp_import_file').on('change', function () {
            if (this.files.length) handleFileSelected(this.files[0]);
        });

        // Entfernen
        $('#dp-file-reset').on('click', function (e) {
            e.stopPropagation();
            resetFile();
        });
    }

    function handleFileSelected(file) {
        if (!file || !file.name.match(/\.(csv|txt)$/i)) {
            alert('Bitte eine CSV-Datei auswählen.');
            return;
        }
        state.csvRaw = file;
        const size = file.size < 1024 ? file.size + ' B'
                   : file.size < 1048576 ? Math.round(file.size / 1024) + ' KB'
                   : (file.size / 1048576).toFixed(1) + ' MB';
        $('#dp-file-name').text(file.name);
        $('#dp-file-size').text(size);
        $('#dp-file-info').show();
        checkAnalyzeBtn();
    }

    function resetFile() {
        state.csvRaw = null;
        $('#dp_import_file').val('');
        $('#dp-file-info').hide();
        checkAnalyzeBtn();
    }

    function checkAnalyzeBtn() {
        $('#dp-analyze-btn').prop('disabled', !(state.type && state.csvRaw));
    }

    /* ------------------------------------------------------------------ */
    /* Wizard-Navigation                                                    */
    /* ------------------------------------------------------------------ */
    function bindWizardNav() {
        $('#dp-analyze-btn').on('click', readAndAnalyzeCSV);
        $('#dp-back-btn-1').on('click', () => showStep(1));
        $('#dp-to-step3-btn').on('click', goToStep3);
        $('#dp-back-btn-2').on('click', () => showStep(2));
        $('#dp-start-import-btn').on('click', startImport);
    }

    function showStep(n) {
        $('.dp-wizard-panel').hide();
        $('#dp-step-' + n).show();

        // Schritt-Indikatoren aktualisieren
        $('.dp-step').removeClass('active done');
        for (let i = 1; i <= 4; i++) {
            const $s = $('[data-step="' + i + '"]');
            if (i < n)       $s.addClass('done');
            else if (i === n) $s.addClass('active');
        }
    }

    /* ------------------------------------------------------------------ */
    /* CSV lesen & Mapping-Tabelle aufbauen                                */
    /* ------------------------------------------------------------------ */
    function readAndAnalyzeCSV() {
        const $analyzeBtn = $('#dp-analyze-btn');
        $analyzeBtn.prop('disabled', true).text('Analysiere...');

        const reader = new FileReader();
        reader.onload = function (e) {
            try {
                const bytes = new Uint8Array(e.target.result);
                const text = decodeCsvBytes(bytes);

                const lines = text.split(/\r?\n/).filter(l => l.trim());
                if (lines.length < 2) {
                    alert('Die CSV-Datei enthält keine Datenzeilen.');
                    return;
                }

                state.csvHeaders = parseCSVLine(lines[0]);
                const firstRow = parseCSVLine(lines[1]);
                state.csvData = lines.slice(1).map(parseCSVLine);

                buildMappingTable(state.csvHeaders, firstRow);

                const presetApplied = applyTemplatePresetIfDetected();
                updatePreviewInfo(lines.length - 1);

                if (presetApplied) {
                    const missingRequired = getMissingRequiredFields();
                    if (!missingRequired.length) {
                        goToStep3(true);
                        return;
                    }
                }

                showStep(2);
            } catch (err) {
                console.error('CSV-Analyse fehlgeschlagen:', err);
                alert('CSV konnte nicht analysiert werden. Bitte Datei/Format prüfen.\n\nDetails: ' + (err && err.message ? err.message : err));
            } finally {
                $analyzeBtn.prop('disabled', false).text('CSV analysieren → Schritt 2');
            }
        };

        reader.onerror = function (err) {
            console.error('Datei-Lesefehler:', err);
            alert('Datei konnte nicht gelesen werden. Bitte erneut auswählen.');
            $analyzeBtn.prop('disabled', false).text('CSV analysieren → Schritt 2');
        };

        reader.readAsArrayBuffer(state.csvRaw);
    }

    function decodeCsvBytes(bytes) {
        // UTF-8 BOM
        if (bytes.length >= 3 && bytes[0] === 0xEF && bytes[1] === 0xBB && bytes[2] === 0xBF) {
            return new TextDecoder('utf-8').decode(bytes);
        }

        // Primär: Windows/Excel-typisch
        try {
            const latin = new TextDecoder('iso-8859-1').decode(bytes);
            if (!/\ufffd/.test(latin)) {
                return latin;
            }
        } catch (e) {
            // Fallback unten
        }

        // Fallback: UTF-8
        return new TextDecoder('utf-8').decode(bytes);
    }

    function normalizeHeader(value) {
        return String(value || '')
            .toLowerCase()
            .trim()
            .replace(/[\s-]+/g, '_');
    }

    function isTemplateHeaderSet(headers) {
        if (!headers || !headers.length) return false;
        const normalized = new Set(headers.map(normalizeHeader));
        return templateHeaders.every(h => normalized.has(h));
    }

    function applyTemplatePresetIfDetected() {
        if (!isTemplateHeaderSet(state.csvHeaders)) {
            return false;
        }

        const preset = templatePresets[state.type];
        if (!preset) {
            return false;
        }

        const headerIndex = {};
        state.csvHeaders.forEach((header, idx) => {
            headerIndex[normalizeHeader(header)] = idx;
        });

        Object.keys(preset).forEach(templateHeader => {
            const idx = headerIndex[templateHeader];
            if (typeof idx === 'undefined') return;

            const targetField = preset[templateHeader];
            const $select = $('.dp-map-select[data-csv-idx="' + idx + '"]');
            if ($select.length && $select.find('option[value="' + targetField + '"]').length) {
                $select.val(targetField).trigger('change');
                $select.closest('tr').addClass('dp-auto-matched');
            }
        });

        $('#dp-preview-info').append(
            '<br><strong>Vorlage erkannt:</strong> Zuordnung wurde automatisch gesetzt.'
        );

        return true;
    }

    function parseCSVLine(line) {
        // Unterstützt Semikolon und Komma, sowie Anführungszeichen
        const sep = line.includes(';') ? ';' : ',';
        const result = [];
        let cur = '', inQ = false;
        for (let i = 0; i < line.length; i++) {
            const c = line[i];
            if (c === '"') {
                if (inQ && line[i + 1] === '"') { cur += '"'; i++; }
                else inQ = !inQ;
            } else if (c === sep && !inQ) {
                result.push(cur.trim());
                cur = '';
            } else {
                cur += c;
            }
        }
        result.push(cur.trim());
        return result;
    }

    function updatePreviewInfo(count) {
        const fields = fieldDefs[state.type] || [];
        const required = fields.filter(f => f.required).map(f => f.label).join(', ');
        $('#dp-preview-info').html(
            '<strong>' + count + ' Datenzeilen</strong> gefunden. ' +
            (required ? 'Pflichtfelder: <em>' + required + '</em>.' : '')
        );
        $('#dp-mapping-desc').text(
            'Ordnen Sie jede CSV-Spalte dem passenden Datenbankfeld zu. ' +
            'Grün hinterlegte Zeilen wurden automatisch erkannt.'
        );
    }

    function buildMappingTable(headers, sampleRow) {
        const fields = fieldDefs[state.type] || [];
        const allFields = getAllFieldOptions(state.type);
        const $tbody = $('#dp-mapping-body').empty();

        headers.forEach((header, idx) => {
            const sample = sampleRow[idx] || '';
            const $tr = $('<tr>');

            // Spaltenname
            $tr.append($('<td>').text(header));

            // Beispielwert
            const $sample = $('<td>').css('color', sample ? '#374151' : '#9ca3af')
                .text(sample ? sample.substring(0, 40) : '(leer)');
            $tr.append($sample);

            // Dropdown
            const $sel = $('<select>').addClass('dp-map-select').attr('data-csv-idx', idx);
            $sel.append($('<option>').val('').text('-- nicht importieren --'));
            let autoMatched = false;

            allFields.forEach(f => {
                const $opt = $('<option>').val(f.key).text(f.label + (f.required ? ' *' : ''));
                if (f.required) $opt.css('color', '#dc2626');

                // Auto-Match: Spaltenname enthält Feldname oder umgekehrt
                const h = header.toLowerCase().replace(/[\s_-]/g, '');
                const k = f.key.toLowerCase().replace(/[\s_-]/g, '');
                if (!autoMatched && (h.includes(k) || k.includes(h))) {
                    $opt.prop('selected', true);
                    autoMatched = true;
                    $tr.addClass('dp-auto-matched');
                }
                $sel.append($opt);
            });

            // Pflichtfeld-Highlight
            const isRequired = fields.some(f => f.required && $sel.val() === f.key);
            if (isRequired) $tr.addClass('dp-required');

            $sel.on('change', function () {
                const val = $(this).val();
                const req = fields.some(f => f.required && f.key === val);
                $tr.toggleClass('dp-required', req);
                $tr.toggleClass('dp-auto-matched', false);
            });

            $tr.append($('<td>').append($sel));
            $tbody.append($tr);
        });
    }

    function getAllFieldOptions(currentType) {
        const preferred = fieldDefs[currentType] || [];
        const mergedByKey = {};

        // Aktuellen Typ zuerst übernehmen (Priorität für Label/required)
        preferred.forEach(f => {
            mergedByKey[f.key] = { key: f.key, label: f.label, required: !!f.required };
        });

        // Danach alle restlichen Typen ergänzen (nur fehlende Keys)
        Object.keys(fieldDefs).forEach(type => {
            (fieldDefs[type] || []).forEach(f => {
                if (!mergedByKey[f.key]) {
                    mergedByKey[f.key] = { key: f.key, label: f.label, required: false };
                }
            });
        });

        return Object.values(mergedByKey);
    }

    /* ------------------------------------------------------------------ */
    /* Schritt 3: Zusammenfassung aufbauen                                 */
    /* ------------------------------------------------------------------ */
    function goToStep3() {
        state.mapping = collectMapping();
        const missingRequired = getMissingRequiredFields();
        if (missingRequired.length) {
            alert('Bitte ordnen Sie alle Pflichtfelder zu:\n' + missingRequired.map(f => '• ' + f.label).join('\n'));
            return;
        }

        buildSummary();
        showStep(3);
    }

    function collectMapping() {
        const mapping = {};
        $('.dp-map-select').each(function () {
            const field = $(this).val();
            const idx   = parseInt($(this).attr('data-csv-idx'), 10);
            if (field) mapping[field] = idx;
        });
        return mapping;
    }

    function getMissingRequiredFields() {
        const fields = fieldDefs[state.type] || [];
        const mapping = collectMapping();
        return fields.filter(f => f.required && !(f.key in mapping));
    }

    function buildSummary() {
        const items = [
            '<li><strong>Typ:</strong> ' + state.type + '</li>',
            '<li><strong>Datei:</strong> ' + state.csvRaw.name + '</li>',
            '<li><strong>Zeilen:</strong> ' + state.csvData.length + '</li>',
            '<li><strong>Zugeordnete Felder:</strong> ' + Object.keys(state.mapping).length + ' / ' + (fieldDefs[state.type] || []).length + '</li>'
        ];

        if (state.type === 'dienstplan' || state.type === 'dienste') {
            items.push('<li><strong>Veranstaltung:</strong> ' + ($('#dp_import_veranstaltung option:selected').text() || '(keine)') + '</li>');
        }
        if (state.type === 'dienstplan') {
            items.push('<li><strong>Vereine anlegen:</strong> ' + ($('#dp_auto_create_vereine').is(':checked') ? 'Ja' : 'Nein') + '</li>');
        }
        $('#dp-summary-list').html(items.join(''));
    }

    /* ------------------------------------------------------------------ */
    /* Import starten                                                       */
    /* ------------------------------------------------------------------ */
    function startImport() {
        const mode = $('#dp_import_mode').val();
        const defaultFarbe = $('#dp_default_bereich_farbe').val() || '#3b82f6';
        const autoCreateVereine = $('#dp_auto_create_vereine').is(':checked') ? 1 : 0;

        showStep(4);
        $('#dp-progress-wrap').show();
        $('#dp-result-box').empty();
        setProgress(5, 'Daten werden übertragen…');

        const payload = {
            action:              'dp_import_csv',
            nonce:               dpImportData.nonce,
            import_type:         state.type,
            import_mode:         mode,
            default_bereich_farbe: defaultFarbe,
            auto_create_vereine: autoCreateVereine,
            csv_data:            JSON.stringify(state.csvData),
            mapping:             JSON.stringify(state.mapping)
        };

        if (state.type === 'dienste' || state.type === 'dienstplan') {
            payload.veranstaltung_id    = $('#dp_import_veranstaltung').val();
            const selOpt = $('#dp_import_veranstaltung option:selected');
            payload.veranstaltung_start = selOpt.data('start');
            payload.veranstaltung_ende  = selOpt.data('ende');
        }

        $.ajax({
            url:  dpImportData.ajaxurl,
            type: 'POST',
            data: payload,
            xhr: function () {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        const pct = Math.round((e.loaded / e.total) * 60);
                        setProgress(5 + pct, 'Hochladen…');
                    }
                });
                return xhr;
            },
            success: function (resp) {
                setProgress(100, '');
                showResult(resp);
                $('#dp-result-actions').show();
            },
            error: function (xhr, status, err) {
                setProgress(100, '');
                showResultError(xhr.responseJSON || null, 'AJAX-Fehler: ' + err);
                $('#dp-result-actions').show();
            }
        });
    }

    function setProgress(pct, msg) {
        $('#dp-progress-bar').css('width', pct + '%');
        if (msg) $('#dp-progress-status').text(msg);
    }

    function showResult(resp) {
        if (resp.success && resp.data) {
            const d = resp.data;
            const detailItems = Array.isArray(d.error_details) ? d.error_details : [];
            const infoItems = detailItems.filter(item => {
                const msg = (typeof item === 'object' && item.message) ? item.message : String(item);
                return typeof item !== 'object' && /:\s*Info\s*-/.test(msg);
            });
            const warningItems = detailItems.filter(item => {
                if (typeof item === 'object' && item.type === 'warning') {
                    return true;
                }

                const msg = (typeof item === 'object' && item.message) ? item.message : String(item);
                return typeof item !== 'object' && !/:\s*Info\s*-/.test(msg) && /⚠️|unvollständig|nicht gefunden/i.test(msg);
            });
            const errorItems = detailItems.filter(item => {
                const msg = (typeof item === 'object' && item.message) ? item.message : String(item);
                if (typeof item === 'object' && item.type === 'warning') {
                    return false;
                }
                if (typeof item !== 'object' && /:\s*Info\s*-/.test(msg)) {
                    return false;
                }
                if (typeof item !== 'object' && /⚠️|unvollständig|nicht gefunden/i.test(msg)) {
                    return false;
                }
                return true;
            });
            let html = '<div style="padding:1rem; background:#d1fae5; border-radius:6px; border:1px solid #6ee7b7;">';
            html += '<p style="margin:0 0 0.5rem; font-weight:600; color:#065f46;">✓ Import abgeschlossen</p>';
            html += '<ul style="margin:0; padding-left:1.2rem;">';
            html += '<li>Erstellt: <strong>' + d.created + '</strong></li>';
            html += '<li>Aktualisiert: <strong>' + d.updated + '</strong></li>';
            html += '<li>Übersprungen: <strong>' + d.skipped + '</strong></li>';
            if (d.errors > 0) html += '<li style="color:#dc2626;">Fehler: <strong>' + d.errors + '</strong></li>';
            if (warningItems.length > 0) html += '<li style="color:#9a3412;">Warnungen: <strong>' + warningItems.length + '</strong></li>';
            if (infoItems.length > 0) html += '<li style="color:#075985;">Infos: <strong>' + infoItems.length + '</strong></li>';
            html += '</ul>';

            if (errorItems.length > 0) {
                html += renderImportMessagesPanel('Fehlerdetails', errorItems, '#fee2e2', '#fecaca', '#991b1b');
            }

            if (warningItems.length > 0) {
                html += renderImportMessagesPanel('Warnungen und unvollständige Zuordnungen', warningItems, '#fff7ed', '#fdba74', '#9a3412');
            }

            if (infoItems.length > 0) {
                html += renderImportMessagesPanel('Automatische Zuordnungen und Infos', infoItems, '#eff6ff', '#93c5fd', '#075985');
            }

            if (Array.isArray(d.unresolved_vereine) && d.unresolved_vereine.length > 0) {
                html += '<div id="dp-unresolved-hint" style="margin-top:0.75rem; padding:0.85rem 1rem; background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; color:#1d4ed8;">';
                html += '<strong>Vereinszuordnung erforderlich:</strong> ' + d.unresolved_vereine.length + ' nicht gefundene Vereine können jetzt direkt zugeordnet werden.';
                html += '</div>';
            }
            html += '</div>';
            $('#dp-result-box').html(html);

            if (Array.isArray(d.unresolved_vereine) && d.unresolved_vereine.length > 0) {
                showVereinAssignmentModal(d.unresolved_vereine);
            }
        } else {
            showResultError(resp, 'Unbekannter Fehler');
        }
    }

    function showVereinAssignmentModal(unresolved) {
        const vereine = Array.isArray(dpImportData.vereine) ? dpImportData.vereine : [];
        if (!vereine.length) {
            $('#dp-result-box').append(
                '<div style="margin-top:0.75rem; padding:0.85rem 1rem; background:#fff7ed; border:1px solid #fdba74; border-radius:6px; color:#9a3412;">' +
                '<strong>Vereine konnten nicht direkt zugeordnet werden:</strong> Es sind keine Vereine für die Auswahl geladen.' +
                '</div>'
            );
            return;
        }

        const rows = unresolved.map((entry, idx) => {
            const key = entry.lookup_key || entry.input_kuerzel || entry.input_name || ('eintrag_' + idx);
            const label = entry.display || key;
            const rowInfo = Array.isArray(entry.rows) && entry.rows.length
                ? 'Zeilen: ' + entry.rows.join(', ')
                : (entry.row ? 'Zeile: ' + entry.row : '');

            const options = ['<option value="">-- bitte Verein wählen --</option>']
                .concat(vereine.map(v => {
                    const kuerzel = (v.kuerzel || '').toUpperCase();
                    const name = v.name || '';
                    const text = kuerzel ? (kuerzel + ' - ' + name) : name;
                    return '<option value="' + escHtml(kuerzel) + '">' + escHtml(text) + '</option>';
                }))
                .join('');

            return '' +
                '<tr>' +
                    '<td style="padding:6px 8px;">' +
                        '<strong>' + escHtml(String(label)) + '</strong>' +
                        (rowInfo ? '<div style="margin-top:0.2rem; font-size:0.82em; color:#64748b;">' + escHtml(rowInfo) + '</div>' : '') +
                    '</td>' +
                    '<td style="padding:6px 8px;">' +
                        '<select class="dp-verein-alias-select" data-lookup-key="' + escHtml(String(key).toUpperCase()) + '" style="width:100%;">' +
                            options +
                        '</select>' +
                    '</td>' +
                '</tr>';
        }).join('');

        const modalHtml = '' +
            '<div id="dp-verein-alias-modal" style="position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:100000; display:flex; align-items:center; justify-content:center;">' +
                '<div style="background:#fff; width:min(760px,95vw); max-height:85vh; overflow:auto; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,0.25);">' +
                    '<div style="padding:14px 16px; border-bottom:1px solid #e5e7eb;">' +
                        '<h3 style="margin:0;">Vereine zuordnen</h3>' +
                        '<p style="margin:6px 0 0; color:#4b5563;">Einige Vereine konnten nicht zugewiesen werden. Bitte Zuordnung wählen und als Alias speichern.</p>' +
                    '</div>' +
                    '<div style="padding:14px 16px;">' +
                        '<table class="widefat striped" style="margin:0;">' +
                            '<thead><tr><th>Nicht gefunden</th><th>Zuordnen zu</th></tr></thead>' +
                            '<tbody>' + rows + '</tbody>' +
                        '</table>' +
                    '</div>' +
                    '<div style="padding:12px 16px; border-top:1px solid #e5e7eb; text-align:right;">' +
                        '<button type="button" class="button" id="dp-verein-alias-skip">Später</button> ' +
                        '<button type="button" class="button button-primary" id="dp-verein-alias-save">Zuordnung speichern</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $('body').append(modalHtml);

        $('#dp-verein-alias-skip').on('click', function () {
            $('#dp-verein-alias-modal').remove();
        });

        $('#dp-verein-alias-save').on('click', function () {
            const aliases = {};
            const assignments = [];
            $('.dp-verein-alias-select').each(function () {
                const from = String($(this).data('lookup-key') || '').trim().toUpperCase();
                const to = String($(this).val() || '').trim().toUpperCase();
                if (from && to) {
                    aliases[from] = to;
                    const unresolvedEntry = unresolved.find(entry => String(entry.lookup_key || '').trim().toUpperCase() === from);
                    assignments.push({
                        lookup_key: from,
                        target_kuerzel: to,
                        dienst_ids: unresolvedEntry && Array.isArray(unresolvedEntry.dienst_ids) ? unresolvedEntry.dienst_ids : []
                    });
                }
            });

            if (!Object.keys(aliases).length) {
                alert('Bitte mindestens eine Zuordnung auswählen oder Später klicken.');
                return;
            }

            $.ajax({
                url: dpImportData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dp_save_verein_aliases',
                    nonce: dpImportData.nonce,
                    aliases: JSON.stringify(aliases),
                    assignments: JSON.stringify(assignments)
                },
                success: function (resp) {
                    if (resp && resp.success) {
                        $('#dp-verein-alias-modal').remove();
                        const applied = resp.data && typeof resp.data.applied !== 'undefined' ? resp.data.applied : 0;
                        $('#dp-unresolved-hint').remove();
                        $('#dp-result-box').append(
                            '<div style="margin-top:0.75rem; padding:0.85rem 1rem; background:#ecfdf5; border:1px solid #86efac; border-radius:6px; color:#166534;">' +
                            '<strong>Vereinszuordnung gespeichert:</strong> ' + applied + ' importierte Dienste wurden direkt aktualisiert. Die Zuordnung wird auch für künftige Importe verwendet.' +
                            '</div>'
                        );
                    } else {
                        alert('Zuordnung konnte nicht gespeichert werden.');
                    }
                },
                error: function () {
                    alert('Fehler beim Speichern der Zuordnung.');
                }
            });
        });
    }

    function showResultError(payload, fallbackMessage) {
        const responseData = payload && payload.data ? payload.data : null;
        const message = responseData && responseData.message
            ? responseData.message
            : (typeof payload === 'string' ? payload : (fallbackMessage || 'Unbekannter Fehler'));
        let html = '' +
            '<div style="padding:1rem; background:#fee2e2; border-radius:6px; border:1px solid #fca5a5;">' +
            '<p style="margin:0; font-weight:600; color:#7f1d1d;">✗ Fehler beim Import</p>' +
            '<p style="margin:0.5rem 0 0;">' + escHtml(message) + '</p>';

        if (responseData && Array.isArray(responseData.error_details) && responseData.error_details.length) {
            html += renderImportMessagesPanel('Fehlerdetails', responseData.error_details, '#fff1f2', '#fda4af', '#9f1239');
        }

        if (responseData && responseData.debug) {
            html += '<pre style="margin-top:0.75rem; padding:0.75rem; background:#fff; border:1px solid #fecaca; border-radius:6px; overflow:auto; font-size:0.8em;">' + escHtml(JSON.stringify(responseData.debug, null, 2)) + '</pre>';
        }

        html += '</div>';
        $('#dp-result-box').html(html);
    }

    function renderImportMessagesPanel(title, items, bgColor, borderColor, textColor) {
        if (!Array.isArray(items) || !items.length) {
            return '';
        }

        const listItems = items.map(function (entry) {
            const msg = (typeof entry === 'object' && entry.message) ? entry.message : String(entry);
            return '<li style="margin:0.2rem 0;">' + escHtml(msg) + '</li>';
        }).join('');

        return '' +
            '<div style="margin-top:0.75rem; padding:0.85rem 1rem; background:' + bgColor + '; border:1px solid ' + borderColor + '; border-radius:6px; color:' + textColor + ';">' +
            '<strong>' + escHtml(title) + '</strong>' +
            '<ul style="margin:0.5rem 0 0; padding-left:1.25rem; max-height:260px; overflow:auto;">' + listItems + '</ul>' +
            '</div>';
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /* ------------------------------------------------------------------ */
    /* Reset                                                                */
    /* ------------------------------------------------------------------ */
    window.dpImportReset = function () {
        state = { type: null, csvRaw: null, csvHeaders: null, csvData: null, mapping: {} };
        $('.dp-type-card').removeClass('selected');
        $('#dp_import_type').val('');
        resetFile();
        $('#dp-file-section').hide();
        $('#dp-type-hint').hide();
        $('#dp-progress-bar').css('width', '0%');
        $('#dp-result-box').empty();
        $('#dp-result-actions').hide();
        $('#dp-progress-wrap').hide();
        showStep(1);
    };

})(jQuery);
