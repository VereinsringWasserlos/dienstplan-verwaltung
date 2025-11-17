(function($) {
    'use strict';
    
    let csvData = null;
    let csvHeaders = null;
    
    // Feld-Definitionen für jeden Import-Typ
    const fieldDefinitions = {
        'vereine': [
            { key: 'name', label: 'Name', required: true },
            { key: 'kuerzel', label: 'Kürzel', required: true },
            { key: 'beschreibung', label: 'Beschreibung', required: false },
            { key: 'kontakt_name', label: 'Kontakt Name', required: false },
            { key: 'kontakt_email', label: 'Kontakt E-Mail', required: false },
            { key: 'kontakt_telefon', label: 'Kontakt Telefon', required: false }
        ],
        'veranstaltungen': [
            { key: 'name', label: 'Name', required: true },
            { key: 'start_datum', label: 'Start-Datum (YYYY-MM-DD)', required: true },
            { key: 'ende_datum', label: 'Ende-Datum (YYYY-MM-DD)', required: true },
            { key: 'beschreibung', label: 'Beschreibung', required: false },
            { key: 'dienst_von_zeit', label: 'Dienst von Zeit (HH:MM)', required: false },
            { key: 'dienst_bis_zeit', label: 'Dienst bis Zeit (HH:MM)', required: false }
        ],
        'dienste': [
            { key: 'datum', label: 'Datum (YYYY-MM-DD)', required: true },
            { key: 'verein_kuerzel', label: 'Verein Kürzel', required: false },
            { key: 'bereich_name', label: 'Bereich Name', required: false },
            { key: 'taetigkeit_name', label: 'Tätigkeit Name', required: false },
            { key: 'von_zeit', label: 'Von Zeit (HH:MM)', required: true },
            { key: 'bis_zeit', label: 'Bis Zeit (HH:MM)', required: true },
            { key: 'anzahl_personen', label: 'Anzahl Personen', required: false },
            { key: 'splittbar', label: 'Splittbar (1/0)', required: false },
            { key: 'besonderheiten', label: 'Besonderheiten', required: false }
        ]
    };
    
    $(document).ready(function() {
        console.log('Import/Export Seite geladen');
        
        // CSV Analyse Button Click Handler
        $('#analyze-csv-btn').on('click', function(e) {
            e.preventDefault();
            console.log('CSV Analyse Button geklickt');
            analyzeCSV();
        });
        
        // Import-Typ Änderung
        $('#import_type').on('change', function() {
            const type = $(this).val();
            $('#column-mapping').hide();
            $('#import_mode_row').hide();
            $('#dienste_veranstaltung_row').hide();
            $('#import_timezone_row').hide();
            csvData = null;
            csvHeaders = null;
            
            // Zeige Veranstaltungs-Auswahl und Zeitzone für Dienste
            if (type === 'dienste') {
                $('#dienste_veranstaltung_row').show();
                $('#import_timezone_row').show();
            } else if (type === 'veranstaltungen') {
                // Zeitzone auch für Veranstaltungen bei Zeitfeldern
                $('#import_timezone_row').show();
            }
        });
    });
    
    // CSV analysieren
    window.analyzeCSV = function() {
        console.log('analyzeCSV aufgerufen');
        const type = $('#import_type').val();
        const fileInput = $('#import_file')[0];
        
        console.log('Import-Typ:', type);
        console.log('File Input Element:', fileInput);
        
        if (!type) {
            alert('Bitte wählen Sie zuerst einen Import-Typ!');
            return;
        }
        
        if (!fileInput.files || !fileInput.files[0]) {
            alert('Bitte wählen Sie eine CSV-Datei!');
            return;
        }
        
        const file = fileInput.files[0];
        console.log('Datei ausgewählt:', file.name, 'Größe:', file.size, 'Typ:', file.type);
        const reader = new FileReader();
        
        reader.onload = function(e) {
            let text;
            
            // ArrayBuffer in String konvertieren
            if (e.target.result instanceof ArrayBuffer) {
                // Versuche zuerst ISO-8859-1 (Windows-Encoding)
                try {
                    text = new TextDecoder('iso-8859-1').decode(new Uint8Array(e.target.result));
                    console.log('Datei als ISO-8859-1 dekodiert');
                } catch(err) {
                    console.warn('ISO-8859-1 Dekodierung fehlgeschlagen, versuche UTF-8:', err);
                    text = new TextDecoder('utf-8').decode(new Uint8Array(e.target.result));
                    console.log('Datei als UTF-8 dekodiert');
                }
            } else {
                // Falls bereits String (sollte nicht vorkommen)
                text = e.target.result;
                console.log('Datei als String gelesen');
            }
            
            // Versuche, Encoding-Probleme zu beheben
            const isBroken = /\ufffd/.test(text); // Zeichen für Encoding-Fehler
            
            if (isBroken) {
                console.warn('Encoding-Fehler erkannt, versuche UTF-8...');
                try {
                    const uint8Array = new Uint8Array(e.target.result);
                    text = new TextDecoder('utf-8').decode(uint8Array);
                    console.log('Erfolgreich zu UTF-8 gewechselt');
                } catch(err) {
                    console.warn('UTF-8 Konvertierung fehlgeschlagen:', err);
                }
            }
            
            const lines = text.split(/\r?\n/).filter(line => line.trim());
            
            console.log('CSV Zeilen:', lines.length);
            console.log('Erste Zeile:', lines[0]);
            console.log('Zweite Zeile:', lines[1]);
            
            if (lines.length < 2) {
                alert('CSV-Datei enthält keine Daten!');
                console.error('Zu wenige Zeilen:', lines.length);
                return;
            }
            
            // Parse Header und erste Datenzeile
            csvHeaders = parseCSVLine(lines[0]);
            const firstDataRow = parseCSVLine(lines[1]);
            csvData = lines.slice(1).map(line => parseCSVLine(line));
            
            console.log('CSV Headers:', csvHeaders);
            console.log('Erste Zeile:', firstDataRow);
            console.log('Gesamt Zeilen zum Import:', csvData.length);
            
            // Validierung: Prüfe ob Headers erkannt wurden
            if (csvHeaders.length === 0 || csvHeaders[0].trim() === '') {
                alert('Fehler: CSV-Header konnten nicht erkannt werden. Prüfen Sie das Dateiformat!');
                console.error('Keine Headers gefunden');
                return;
            }
            
            // Zeige Mapping-Tabelle
            buildMappingTable(type, csvHeaders, firstDataRow);
            $('#column-mapping').show();
            $('#import_mode_row').show();
        };
        
        reader.onerror = function(err) {
            console.error('Fehler beim Lesen der Datei:', err);
            alert('Fehler beim Lesen der CSV-Datei');
        };
        
        // Lese Datei als ArrayBuffer zuerst (für besseres Encoding-Handling)
        reader.readAsArrayBuffer(file);
    };
    
    // CSV-Zeile parsen (Semikolon oder Komma)
    function parseCSVLine(line) {
        const delimiter = line.includes(';') ? ';' : ',';
        return line.split(delimiter).map(cell => cell.trim().replace(/^["']|["']$/g, ''));
    }
    
    // Mapping-Tabelle erstellen
    function buildMappingTable(type, headers, sampleData) {
        const fields = fieldDefinitions[type];
        const tbody = $('#mapping-body');
        tbody.empty();
        
        headers.forEach((header, index) => {
            const sampleValue = sampleData[index] || '';
            const isEmpty = !sampleValue || sampleValue.trim() === '';
            const row = $('<tr>');
            
            // CSV-Spalte mit Beispielwert
            const headerCell = $('<td>');
            const headerContent = $('<div>');
            headerContent.text(header);
            
            if (sampleValue) {
                const exampleSpan = $('<span>')
                    .text(' (' + sampleValue.substring(0, 30) + ')')
                    .css('color', '#666; font-size: 0.9em');
                headerContent.append(exampleSpan);
            } else {
                const emptySpan = $('<span>')
                    .text(' (LEER - kein Beispielwert)')
                    .css({
                        'color': '#d32f2f',
                        'font-weight': 'bold',
                        'font-size': '0.9em'
                    });
                headerContent.append(emptySpan);
                row.css('background-color', '#ffebee');
            }
            
            headerCell.append(headerContent);
            
            // Dropdown für Zuordnung
            const selectCell = $('<td>');
            const select = $('<select>')
                .addClass('column-mapping-select')
                .attr('data-csv-index', index)
                .css('width', '100%');
            
            // Option: Nicht importieren
            select.append($('<option>').val('').text('-- Nicht importieren --'));
            
            // Optionen für Felder
            fields.forEach(field => {
                const option = $('<option>')
                    .val(field.key)
                    .text(field.label + (field.required ? ' *' : ''));
                
                // Erforderliche Felder rot markieren
                if (field.required) {
                    option.css('color', '#d32f2f');
                }
                
                // Auto-Match basierend auf Namen
                if (header.toLowerCase().includes(field.key.toLowerCase()) || 
                    field.key.toLowerCase().includes(header.toLowerCase())) {
                    option.prop('selected', true);
                }
                
                select.append(option);
            });
            
            selectCell.append(select);
            row.append(headerCell).append(selectCell);
            tbody.append(row);
        });
    }
    
    window.startImport = function(e) {
        if (e) e.preventDefault();
        const type = $('#import_type').val();
        const mode = $('#import_mode').val();
        
        if (!type) {
            alert('Bitte wählen Sie einen Import-Typ!');
            return false;
        }
        
        if (!csvData || !csvHeaders) {
            alert('Bitte analysieren Sie zuerst die CSV-Datei!');
            return false;
        }
        
        // Bei Diensten: Veranstaltung prüfen
        let veranstaltungId = null;
        let veranstaltungStart = null;
        let veranstaltungEnde = null;
        
        if (type === 'dienste') {
            veranstaltungId = $('#import_veranstaltung').val();
            if (!veranstaltungId) {
                alert('Bitte wählen Sie eine Veranstaltung!');
                return false;
            }
            
            const selectedOption = $('#import_veranstaltung option:selected');
            veranstaltungStart = selectedOption.data('start');
            veranstaltungEnde = selectedOption.data('ende');
        }
        
        // Sammle Mapping
        const mapping = {};
        $('.column-mapping-select').each(function() {
            const csvIndex = $(this).data('csv-index');
            const dbField = $(this).val();
            if (dbField) {
                mapping[dbField] = csvIndex;
            }
        });
        
        console.log('Mapping:', mapping);
        console.log('Start Import:', type, mode, csvData.length + ' Zeilen');
        
        $('#import-progress').show();
        $('#import-status').text('Lese CSV-Datei...');
        $('#progress-bar-fill').css('width', '10%');
        
        const importData = {
            action: 'dp_import_csv',
            nonce: dpAjax.nonce,
            import_type: type,
            import_mode: mode,
            csv_data: JSON.stringify(csvData),
            mapping: JSON.stringify(mapping),
            timezone: $('#import_timezone').val() || 'UTC'
        };
        
        // Veranstaltungs-Daten für Dienste hinzufügen
        if (type === 'dienste') {
            importData.veranstaltung_id = veranstaltungId;
            importData.veranstaltung_start = veranstaltungStart;
            importData.veranstaltung_ende = veranstaltungEnde;
        }
        
        console.log('Sende Import-Daten:', importData);
        
        $.ajax({
            url: dpAjax.ajaxurl,
            type: 'POST',
            data: importData,
            success: function(response) {
                console.log('Import Response:', response);
                $('#progress-bar-fill').css('width', '100%');
                
                if (response.success && response.data) {
                    $('#import-status').html('<span style="color: #059669;">✓ Import erfolgreich abgeschlossen!</span>');
                    
                    let html = '<div style="padding: 1rem; background: #d1fae5; border-radius: 4px;">';
                    html += '<strong>Ergebnis:</strong><ul style="margin: 0.5rem 0;">';
                    html += '<li>Erstellt: ' + response.data.created + '</li>';
                    html += '<li>Aktualisiert: ' + response.data.updated + '</li>';
                    html += '<li>Übersprungen: ' + response.data.skipped + '</li>';
                    if (response.data.errors > 0) {
                        html += '<li style="color: #dc2626;">Fehler: ' + response.data.errors + '</li>';
                    }
                    html += '</ul>';
                    
                    // Fehlerdetails anzeigen - BLEIBEN SICHTBAR!
                    if (response.data.error_details && response.data.error_details.length > 0) {
                        html += '<div style="margin-top: 1rem; padding: 0.5rem; background: #fee2e2; border-radius: 4px; max-height: 400px; overflow-y: auto; border: 1px solid #fca5a5;">';
                        html += '<strong style="color: #7f1d1d;">Fehlerdetails (' + response.data.error_details.length + '):</strong><ul style="margin: 0.5rem 0; font-size: 0.85rem;">';
                        response.data.error_details.forEach(function(error) {
                            html += '<li style="margin: 0.25rem 0;">' + error + '</li>';
                        });
                        html += '</ul></div>';
                    }
                    
                    // Button "Seite neu laden" deaktiviert - Auto-Reload über rate-limiting
                    // html += '<button type="button" class="button" onclick="location.reload();" style="margin-top: 1rem;">Seite neu laden</button>';
                    html += '</div>';
                    
                    $('#import-results').html(html);
                    // Zusätzlich: Meldung oben im Formular anzeigen und NICHT AUTO-SCHLIESSEN
                    $('#import-message').css('background', '#d1fae5').css('border', '1px solid #6ee7b7').css('color', '#065f46').html(html).show();
                    
                    // Scroll zu Fehlermeldung oben
                    $('html, body').animate({ scrollTop: $('#import-message').offset().top - 100 }, 500);
                } else {
                    $('#import-status').html('<span style="color: #dc2626;">✗ Fehler beim Import</span>');
                    let errorHtml = '<div style="padding: 1rem; background: #fee2e2; border-radius: 4px; border: 1px solid #fca5a5;">';
                    errorHtml += '<strong>Fehler:</strong><br>' + (response.data ? response.data.message : 'Unbekannter Fehler');
                    errorHtml += '</div>';
                    $('#import-results').html(errorHtml);
                    // Zusätzlich: Meldung oben im Formular anzeigen
                    $('#import-message').css('background', '#fee2e2').css('border', '1px solid #fca5a5').css('color', '#7f1d1d').html(errorHtml).show();
                }
                return false;
            },
            error: function(xhr, status, error) {
                console.error('AJAX Fehler:', error);
                $('#import-status').html('<span style="color: #dc2626;">✗ AJAX-Fehler</span>');
                let errorMsg = '<div style="padding: 1rem; background: #fee2e2; border-radius: 4px; border: 1px solid #fca5a5;">AJAX-Fehler: ' + error + '</div>';
                $('#import-results').html(errorMsg);
                $('#import-message').css('background', '#fee2e2').css('border', '1px solid #fca5a5').css('color', '#7f1d1d').html(errorMsg).show();
                return false;
            }
        });
        return false;
    };
    
    window.exportData = function(type, e) {
        if (e) e.preventDefault();
        console.log('Export:', type);
        
        // Download über versteckten Link
        const url = dpAjax.ajaxurl + '?action=dp_export_csv&type=' + type + '&nonce=' + dpAjax.nonce;
        const link = document.createElement('a');
        link.href = url;
        link.download = 'dienstplan-export-' + type + '-' + Date.now() + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        return false;
    };
    
})(jQuery);
