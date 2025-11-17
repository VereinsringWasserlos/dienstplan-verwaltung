/**
 * Dienstplan Verwaltung - Admin Modal Functions
 * Alle CRUD-Funktionen für Modals (Vereine, Veranstaltungen, Dienste, Bereiche, Tätigkeiten)
 * @version 1.0.0
 */

console.log('dp-admin-modals.js geladen');

// ============================================================================
// VEREINE MODAL FUNCTIONS
// ============================================================================

window.openVereinModal = function(vereinId = null) {
    const modal = document.getElementById('verein-modal');
    const form = document.getElementById('verein-form');
    const title = document.getElementById('modal-title');
    
    if (!modal || !form) {
        console.error('Verein-Modal oder Form nicht gefunden');
        return;
    }
    
    // Reset form
    form.reset();
    document.getElementById('verein_id').value = '';
    
    if (vereinId) {
        // Bearbeiten-Modus
        title.textContent = 'Verein bearbeiten';
        
        // Lade Verein-Daten via AJAX
        jQuery.post(ajaxurl, {
            action: 'dp_get_verein',
            verein_id: vereinId,
            nonce: dpAjax.nonce
        }, function(response) {
            if (response.success && response.data) {
                document.getElementById('verein_id').value = response.data.id || '';
                document.getElementById('name').value = response.data.name || '';
                document.getElementById('kuerzel').value = response.data.kuerzel || '';
                document.getElementById('beschreibung').value = response.data.beschreibung || '';
                document.getElementById('logo_id').value = response.data.logo_id || '';
                document.getElementById('kontakt_name').value = response.data.kontakt_name || '';
                document.getElementById('kontakt_email').value = response.data.kontakt_email || '';
                document.getElementById('kontakt_telefon').value = response.data.kontakt_telefon || '';
                
                // Logo Preview
                if (response.data.logo_url) {
                    document.getElementById('logo-preview').innerHTML = '<img src="' + response.data.logo_url + '" style="max-width: 200px; max-height: 200px;">';
                    document.getElementById('remove-logo-btn').style.display = 'inline-block';
                }
            } else {
                alert('Fehler beim Laden des Vereins: ' + (response.data?.message || 'Unbekannter Fehler'));
            }
        }).fail(function() {
            alert('Fehler beim Laden des Vereins');
        });
    } else {
        // Neu-Modus
        title.textContent = 'Neuer Verein';
    }
    
    modal.style.display = 'block';
};

window.closeVereinModal = function() {
    const modal = document.getElementById('verein-modal');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.editVerein = function(vereinId) {
    openVereinModal(vereinId);
};

window.saveVerein = function() {
    const form = document.getElementById('verein-form');
    if (!form) {
        console.error('Verein-Form nicht gefunden');
        return;
    }
    
    // Validierung
    const name = document.getElementById('name').value.trim();
    const kuerzel = document.getElementById('kuerzel').value.trim();
    
    if (!name || !kuerzel) {
        alert('Bitte füllen Sie alle Pflichtfelder aus (Name, Kürzel)');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'dp_save_verein');
    formData.append('nonce', dpAjax.nonce);
    
    // AJAX Request
    jQuery.post(ajaxurl, Object.fromEntries(formData), function(response) {
        if (response.success) {
            alert('Verein erfolgreich gespeichert');
            closeVereinModal();
            if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
        } else {
            alert('Fehler beim Speichern: ' + (response.data?.message || 'Unbekannter Fehler'));
        }
    }).fail(function() {
        alert('Fehler beim Speichern des Vereins');
    });
};

window.deleteVerein = function(vereinId) {
    if (!confirm('Möchten Sie diesen Verein wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')) {
        return;
    }
    
    jQuery.post(ajaxurl, {
        action: 'dp_delete_verein',
        verein_id: vereinId,
        nonce: dpAjax.nonce
    }, function(response) {
        if (response.success) {
            alert('Verein erfolgreich gelöscht');
            if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
        } else {
            alert('Fehler beim Löschen: ' + (response.data?.message || 'Unbekannter Fehler'));
        }
    }).fail(function() {
        alert('Fehler beim Löschen des Vereins');
    });
};

// Kontakt Modal (Vereine)
window.openNewContactModal = function() {
    const modal = document.getElementById('new-contact-modal');
    if (modal) {
        modal.style.display = 'block';
    }
};

window.closeNewContactModal = function() {
    const modal = document.getElementById('new-contact-modal');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.saveNewContact = function() {
    // Kopiere Werte aus Kontakt-Modal in Haupt-Form
    const newName = document.getElementById('new_kontakt_name').value;
    const newEmail = document.getElementById('new_kontakt_email').value;
    const newTelefon = document.getElementById('new_kontakt_telefon').value;
    
    document.getElementById('kontakt_name').value = newName;
    document.getElementById('kontakt_email').value = newEmail;
    document.getElementById('kontakt_telefon').value = newTelefon;
    
    closeNewContactModal();
};

// ============================================================================
// VERANSTALTUNGEN MODAL FUNCTIONS
// ============================================================================

window.openVeranstaltungModal = function(veranstaltungId = null) {
    const modal = document.getElementById('veranstaltung-modal');
    const form = document.getElementById('veranstaltung-form');
    const title = document.getElementById('veranstaltung-modal-title');
    
    if (!modal || !form) {
        console.error('Veranstaltung-Modal oder Form nicht gefunden');
        return;
    }
    
    // Reset form
    form.reset();
    document.getElementById('veranstaltung_id').value = '';
    document.getElementById('tags-container').innerHTML = '';
    
    if (veranstaltungId) {
        // Bearbeiten-Modus
        title.textContent = 'Veranstaltung bearbeiten';
        
        // Lade Veranstaltung-Daten via AJAX
        jQuery.post(ajaxurl, {
            action: 'dp_get_veranstaltung',
            veranstaltung_id: veranstaltungId,
            nonce: dpAjax.nonce
        }, function(response) {
            if (response.success && response.data) {
                document.getElementById('veranstaltung_id').value = response.data.id || '';
                document.getElementById('veranstaltung_name').value = response.data.name || '';
                document.getElementById('veranstaltung_beschreibung').value = response.data.beschreibung || '';
                document.getElementById('veranstaltung_typ').value = response.data.typ || 'eintaegig';
                document.getElementById('veranstaltung_status').value = response.data.status || 'geplant';
                document.getElementById('veranstaltung_start_datum').value = response.data.start_datum || '';
                document.getElementById('veranstaltung_end_datum').value = response.data.end_datum || '';
                
                // Lade Tags
                if (response.data.tage && response.data.tage.length > 0) {
                    response.data.tage.forEach(function(tag) {
                        addTagToUI(tag);
                    });
                }
            } else {
                alert('Fehler beim Laden der Veranstaltung: ' + (response.data?.message || 'Unbekannter Fehler'));
            }
        }).fail(function() {
            alert('Fehler beim Laden der Veranstaltung');
        });
    } else {
        // Neu-Modus
        title.textContent = 'Neue Veranstaltung';
    }
    
    modal.style.display = 'block';
};

window.closeVeranstaltungModal = function() {
    const modal = document.getElementById('veranstaltung-modal');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.editVeranstaltung = function(veranstaltungId) {
    openVeranstaltungModal(veranstaltungId);
};

window.saveVeranstaltung = function() {
    const form = document.getElementById('veranstaltung-form');
    if (!form) {
        console.error('Veranstaltung-Form nicht gefunden');
        return;
    }
    
    // Validierung
    const name = document.getElementById('veranstaltung_name').value.trim();
    const startDatum = document.getElementById('veranstaltung_start_datum').value;
    
    if (!name || !startDatum) {
        alert('Bitte füllen Sie alle Pflichtfelder aus (Name, Startdatum)');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'dp_save_veranstaltung');
    formData.append('nonce', dpAjax.nonce);
    
    // AJAX Request
    jQuery.post(ajaxurl, Object.fromEntries(formData), function(response) {
        if (response.success) {
            alert('Veranstaltung erfolgreich gespeichert');
            closeVeranstaltungModal();
            if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
        } else {
            alert('Fehler beim Speichern: ' + (response.data?.message || 'Unbekannter Fehler'));
        }
    }).fail(function() {
        alert('Fehler beim Speichern der Veranstaltung');
    });
};

window.deleteVeranstaltung = function(veranstaltungId) {
    if (!confirm('Möchten Sie diese Veranstaltung wirklich löschen? Alle zugehörigen Dienste werden ebenfalls gelöscht. Diese Aktion kann nicht rückgängig gemacht werden.')) {
        return;
    }
    
    jQuery.post(ajaxurl, {
        action: 'dp_delete_veranstaltung',
        veranstaltung_id: veranstaltungId,
        nonce: dpAjax.nonce
    }, function(response) {
        if (response.success) {
            alert('Veranstaltung erfolgreich gelöscht');
            if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
        } else {
            alert('Fehler beim Löschen: ' + (response.data?.message || 'Unbekannter Fehler'));
        }
    }).fail(function() {
        alert('Fehler beim Löschen der Veranstaltung');
    });
};

// Tag-Funktionen (Veranstaltungen)
window.addTag = function() {
    const container = document.getElementById('tags-container');
    if (!container) return;
    
    const tagIndex = container.children.length;
    const tagHTML = `
        <div class="tag-item" data-tag-index="${tagIndex}">
            <input type="hidden" name="tags[${tagIndex}][tag_nummer]" value="${tagIndex + 1}">
            <label>Tag ${tagIndex + 1}:</label>
            <input type="date" name="tags[${tagIndex}][tag_datum]" required>
            <input type="time" name="tags[${tagIndex}][von_zeit]">
            <input type="time" name="tags[${tagIndex}][bis_zeit]">
            <button type="button" class="button" onclick="removeTag(this)">Entfernen</button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', tagHTML);
};

window.removeTag = function(button) {
    const tagItem = button.closest('.tag-item');
    if (tagItem) {
        tagItem.remove();
    }
};

function addTagToUI(tag) {
    const container = document.getElementById('tags-container');
    if (!container) return;
    
    const tagIndex = container.children.length;
    const tagHTML = `
        <div class="tag-item" data-tag-index="${tagIndex}" data-tag-id="${tag.id}">
            <input type="hidden" name="tags[${tagIndex}][id]" value="${tag.id}">
            <input type="hidden" name="tags[${tagIndex}][tag_nummer]" value="${tag.tag_nummer}">
            <label>Tag ${tag.tag_nummer}:</label>
            <input type="date" name="tags[${tagIndex}][tag_datum]" value="${tag.tag_datum}" required>
            <input type="time" name="tags[${tagIndex}][von_zeit]" value="${tag.von_zeit || ''}">
            <input type="time" name="tags[${tagIndex}][bis_zeit]" value="${tag.bis_zeit || ''}">
            <button type="button" class="button" onclick="removeTag(this)">Entfernen</button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', tagHTML);
}

// Kontakt Modal (Veranstaltungen)
window.openNewContactModalVeranstaltung = function() {
    const modal = document.getElementById('new-contact-modal-veranstaltung');
    if (modal) {
        modal.style.display = 'block';
    }
};

window.closeNewContactModalVeranstaltung = function() {
    const modal = document.getElementById('new-contact-modal-veranstaltung');
    if (modal) {
        modal.style.display = 'none';
    }
};

// Seiten-Funktionen (Veranstaltungen)
window.createPageForEvent = function(veranstaltungId) {
    if (!confirm('Möchten Sie eine neue WordPress-Seite für diese Veranstaltung erstellen?')) {
        return;
    }
    
    jQuery.post(ajaxurl, {
        action: 'dp_create_event_page',
        veranstaltung_id: veranstaltungId,
        nonce: dpAjax.nonce
    }, function(response) {
        if (response.success) {
            alert('Seite erfolgreich erstellt');
            if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
        } else {
            alert('Fehler beim Erstellen der Seite: ' + (response.data?.message || 'Unbekannter Fehler'));
        }
    }).fail(function() {
        alert('Fehler beim Erstellen der Seite');
    });
};

window.updatePageForEvent = function(veranstaltungId) {
    if (!confirm('Möchten Sie die verknüpfte Seite aktualisieren?')) {
        return;
    }
    
    jQuery.post(ajaxurl, {
        action: 'dp_update_event_page',
        veranstaltung_id: veranstaltungId,
        nonce: dpAjax.nonce
    }, function(response) {
        if (response.success) {
            alert('Seite erfolgreich aktualisiert');
            if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
        } else {
            alert('Fehler beim Aktualisieren der Seite: ' + (response.data?.message || 'Unbekannter Fehler'));
        }
    }).fail(function() {
        alert('Fehler beim Aktualisieren der Seite');
    });
};

// ============================================================================
// DIENSTE MODAL FUNCTIONS
// ============================================================================

window.openDienstModal = function(dienstId = null) {
    const modal = document.getElementById('dienst-modal');
    const form = document.getElementById('dienst-form');
    const title = document.getElementById('dienst-modal-title');
    
    if (!modal || !form) {
        console.error('Dienst-Modal oder Form nicht gefunden');
        return;
    }
    
    // Reset form
    form.reset();
    document.getElementById('dienst_id').value = '';
    
    if (dienstId) {
        // Bearbeiten-Modus
        title.textContent = 'Dienst bearbeiten';
        
        // Lade Dienst-Daten via AJAX
        jQuery.post(ajaxurl, {
            action: 'dp_get_dienst',
            dienst_id: dienstId,
            nonce: dpAjax.nonce
        }, function(response) {
            if (response.success && response.data) {
                document.getElementById('dienst_id').value = response.data.id || '';
                document.getElementById('dienst_veranstaltung_id').value = response.data.veranstaltung_id || '';
                document.getElementById('dienst_tag_id').value = response.data.tag_id || '';
                document.getElementById('dienst_verein_id').value = response.data.verein_id || '';
                document.getElementById('dienst_bereich_id').value = response.data.bereich_id || '';
                document.getElementById('dienst_taetigkeit_id').value = response.data.taetigkeit_id || '';
                document.getElementById('dienst_von_zeit').value = response.data.von_zeit || '';
                document.getElementById('dienst_bis_zeit').value = response.data.bis_zeit || '';
                document.getElementById('dienst_anzahl_personen').value = response.data.anzahl_personen || '';
                document.getElementById('dienst_besonderheiten').value = response.data.besonderheiten || '';
            } else {
                alert('Fehler beim Laden des Dienstes: ' + (response.data?.message || 'Unbekannter Fehler'));
            }
        }).fail(function() {
            alert('Fehler beim Laden des Dienstes');
        });
    } else {
        // Neu-Modus
        title.textContent = 'Neuer Dienst';
    }
    
    modal.style.display = 'block';
};

window.closeDienstModal = function() {
    const modal = document.getElementById('dienst-modal');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.editDienst = function(dienstId) {
    openDienstModal(dienstId);
};

window.saveDienst = function() {
    const form = document.getElementById('dienst-form');
    if (!form) {
        console.error('Dienst-Form nicht gefunden');
        return;
    }
    
    // Validierung
    const veranstaltungId = document.getElementById('dienst_veranstaltung_id').value;
    const vereinId = document.getElementById('dienst_verein_id').value;
    const bereichId = document.getElementById('dienst_bereich_id').value;
    const taetigkeitId = document.getElementById('dienst_taetigkeit_id').value;
    
    if (!veranstaltungId || !vereinId || !bereichId || !taetigkeitId) {
        alert('Bitte füllen Sie alle Pflichtfelder aus');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'dp_save_dienst');
    formData.append('nonce', dpAjax.nonce);
    
    // AJAX Request
    jQuery.post(ajaxurl, Object.fromEntries(formData), function(response) {
        if (response.success) {
            alert('Dienst erfolgreich gespeichert');
            closeDienstModal();
            if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
        } else {
            alert('Fehler beim Speichern: ' + (response.data?.message || 'Unbekannter Fehler'));
        }
    }).fail(function() {
        alert('Fehler beim Speichern des Dienstes');
    });
};

window.deleteDienst = function(dienstId) {
    if (!confirm('Möchten Sie diesen Dienst wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')) {
        return;
    }
    
    jQuery.post(ajaxurl, {
        action: 'dp_delete_dienst',
        dienst_id: dienstId,
        nonce: dpAjax.nonce
    }, function(response) {
        if (response.success) {
            alert('Dienst erfolgreich gelöscht');
            if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
        } else {
            alert('Fehler beim Löschen: ' + (response.data?.message || 'Unbekannter Fehler'));
        }
    }).fail(function() {
        alert('Fehler beim Löschen des Dienstes');
    });
};

// Nested Modals im Dienst-Modal
window.openNeuerVereinDialog = function() {
    const modal = document.getElementById('neuer-verein-modal');
    if (modal) {
        modal.style.display = 'block';
    }
};

window.closeNeuerVereinModal = function() {
    const modal = document.getElementById('neuer-verein-modal');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.saveNeuerVerein = function() {
    // Vereinfachte Version - ruft Haupt-Verein-Funktion auf
    alert('Bitte verwenden Sie die Vereine-Seite um neue Vereine anzulegen');
    closeNeuerVereinModal();
};

window.openNeuerBereichDialog = function() {
    const modal = document.getElementById('neuer-bereich-modal');
    if (modal) {
        modal.style.display = 'block';
    }
};

window.closeNeuerBereichModal = function() {
    const modal = document.getElementById('neuer-bereich-modal');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.saveNeuerBereich = function() {
    alert('Bitte verwenden Sie die Bereiche-Seite um neue Bereiche anzulegen');
    closeNeuerBereichModal();
};

window.openNeueTaetigkeitDialog = function() {
    const modal = document.getElementById('neue-taetigkeit-modal');
    if (modal) {
        modal.style.display = 'block';
    }
};

window.closeNeueTaetigkeitModal = function() {
    const modal = document.getElementById('neue-taetigkeit-modal');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.saveNeueTaetigkeit = function() {
    alert('Bitte verwenden Sie die Bereiche & Tätigkeiten-Seite um neue Tätigkeiten anzulegen');
    closeNeueTaetigkeitModal();
};

// ============================================================================
// BEREICHE & TÄTIGKEITEN MODAL FUNCTIONS
// ============================================================================

window.openBereichModal = function(bereichId = null) {
    const modal = document.getElementById('bereich-modal');
    const form = document.getElementById('bereich-form');
    const title = modal.querySelector('.dp-modal-header h2');
    
    if (!modal || !form) {
        console.error('Bereich-Modal oder Form nicht gefunden');
        return;
    }
    
    // Reset form
    form.reset();
    document.getElementById('bereich_id').value = '';
    
    if (bereichId) {
        // Bearbeiten-Modus
        title.textContent = 'Bereich bearbeiten';
        
        // Lade Bereich-Daten via AJAX
        jQuery.post(ajaxurl, {
            action: 'dp_get_bereich',
            bereich_id: bereichId,
            nonce: dpAjax.nonce
        }, function(response) {
            if (response.success && response.data) {
                document.getElementById('bereich_id').value = response.data.id || '';
                document.getElementById('bereich_name').value = response.data.name || '';
                document.getElementById('bereich_beschreibung').value = response.data.beschreibung || '';
                document.getElementById('bereich_farbe').value = response.data.farbe || '#3b82f6';
            } else {
                alert('Fehler beim Laden des Bereichs: ' + (response.data?.message || 'Unbekannter Fehler'));
            }
        }).fail(function() {
            alert('Fehler beim Laden des Bereichs');
        });
    } else {
        // Neu-Modus
        title.textContent = 'Neuer Bereich';
    }
    
    modal.style.display = 'block';
};

window.closeBereichModal = function() {
    const modal = document.getElementById('bereich-modal');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.saveBereich = function() {
    const form = document.getElementById('bereich-form');
    if (!form) {
        console.error('Bereich-Form nicht gefunden');
        return;
    }
    
    // Validierung
    const name = document.getElementById('bereich_name').value.trim();
    
    if (!name) {
        alert('Bitte geben Sie einen Namen ein');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'dp_save_bereich');
    formData.append('nonce', dpAjax.nonce);
    
    // AJAX Request
    jQuery.post(ajaxurl, Object.fromEntries(formData), function(response) {
        if (response.success) {
            alert('Bereich erfolgreich gespeichert');
            closeBereichModal();
            if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
        } else {
            alert('Fehler beim Speichern: ' + (response.data?.message || 'Unbekannter Fehler'));
        }
    }).fail(function() {
        alert('Fehler beim Speichern des Bereichs');
    });
};

window.openTaetigkeitModal = function(bereichId, taetigkeitId = null) {
    const modal = document.getElementById('taetigkeit-modal');
    const form = document.getElementById('taetigkeit-form');
    const title = modal.querySelector('.dp-modal-header h2');
    
    if (!modal || !form) {
        console.error('Tätigkeit-Modal oder Form nicht gefunden');
        return;
    }
    
    // Reset form
    form.reset();
    document.getElementById('taetigkeit_id').value = '';
    document.getElementById('taetigkeit_bereich_id').value = bereichId;
    
    if (taetigkeitId) {
        // Bearbeiten-Modus
        title.textContent = 'Tätigkeit bearbeiten';
        
        // Lade Tätigkeit-Daten via AJAX
        jQuery.post(ajaxurl, {
            action: 'dp_get_taetigkeit',
            taetigkeit_id: taetigkeitId,
            nonce: dpAjax.nonce
        }, function(response) {
            if (response.success && response.data) {
                document.getElementById('taetigkeit_id').value = response.data.id || '';
                document.getElementById('taetigkeit_bereich_id').value = response.data.bereich_id || '';
                document.getElementById('taetigkeit_name').value = response.data.name || '';
                document.getElementById('taetigkeit_beschreibung').value = response.data.beschreibung || '';
                document.getElementById('taetigkeit_default_dauer').value = response.data.default_dauer || '';
            } else {
                alert('Fehler beim Laden der Tätigkeit: ' + (response.data?.message || 'Unbekannter Fehler'));
            }
        }).fail(function() {
            alert('Fehler beim Laden der Tätigkeit');
        });
    } else {
        // Neu-Modus
        title.textContent = 'Neue Tätigkeit';
    }
    
    modal.style.display = 'block';
};

window.closeTaetigkeitModal = function() {
    const modal = document.getElementById('taetigkeit-modal');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.saveTaetigkeit = function() {
    const form = document.getElementById('taetigkeit-form');
    if (!form) {
        console.error('Tätigkeit-Form nicht gefunden');
        return;
    }
    
    // Validierung
    const name = document.getElementById('taetigkeit_name').value.trim();
    const bereichId = document.getElementById('taetigkeit_bereich_id').value;
    
    if (!name || !bereichId) {
        alert('Bitte füllen Sie alle Pflichtfelder aus');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'dp_save_taetigkeit');
    formData.append('nonce', dpAjax.nonce);
    
    // AJAX Request
    jQuery.post(ajaxurl, Object.fromEntries(formData), function(response) {
        if (response.success) {
            alert('Tätigkeit erfolgreich gespeichert');
            closeTaetigkeitModal();
            if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
        } else {
            alert('Fehler beim Speichern: ' + (response.data?.message || 'Unbekannter Fehler'));
        }
    }).fail(function() {
        alert('Fehler beim Speichern der Tätigkeit');
    });
};

window.deleteTaetigkeit = function(taetigkeitId) {
    if (!confirm('Möchten Sie diese Tätigkeit wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')) {
        return;
    }
    
    jQuery.post(ajaxurl, {
        action: 'dp_delete_taetigkeit',
        taetigkeit_id: taetigkeitId,
        nonce: dpAjax.nonce
    }, function(response) {
        if (response.success) {
            alert('Tätigkeit erfolgreich gelöscht');
            if(typeof dpSafeReload === "function") { dpSafeReload(); } else { location.reload(); };
        } else {
            alert('Fehler beim Löschen: ' + (response.data?.message || 'Unbekannter Fehler'));
        }
    }).fail(function() {
        alert('Fehler beim Löschen der Tätigkeit');
    });
};

// ============================================================================
// BESETZUNG MODAL FUNCTIONS
// ============================================================================

window.openBesetzungModal = function(dienstId) {
    const modal = document.getElementById('besetzung-modal');
    const title = modal.querySelector('.dp-modal-header h2');
    
    if (!modal) {
        console.error('Besetzung-Modal nicht gefunden');
        return;
    }
    
    // Setze Dienst-ID
    window.currentBesetzungDienstId = dienstId;
    
    // Lade Besetzungen via AJAX
    jQuery.post(ajaxurl, {
        action: 'dp_get_besetzungen',
        dienst_id: dienstId,
        nonce: dpAjax.nonce
    }, function(response) {
        if (response.success && response.data) {
            title.textContent = 'Besetzung für ' + (response.data.dienst_name || 'Dienst');
            
            // Zeige Besetzungen an
            const container = document.getElementById('besetzung-list');
            if (container && response.data.besetzungen) {
                container.innerHTML = '';
                response.data.besetzungen.forEach(function(b) {
                    container.innerHTML += `
                        <div class="besetzung-item">
                            <strong>${b.mitarbeiter_name}</strong> (${b.status})
                            <button class="button button-small" onclick="removeBesetzung(${b.id})">Entfernen</button>
                        </div>
                    `;
                });
            }
        } else {
            alert('Fehler beim Laden der Besetzungen: ' + (response.data?.message || 'Unbekannter Fehler'));
        }
    }).fail(function() {
        alert('Fehler beim Laden der Besetzungen');
    });
    
    modal.style.display = 'block';
};

window.closeBesetzungModal = function() {
    const modal = document.getElementById('besetzung-modal');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.openNeuerMitarbeiterForm = function() {
    const form = document.getElementById('neuer-mitarbeiter-form');
    if (form) {
        form.style.display = 'block';
    }
};

window.closeNeuerMitarbeiterForm = function() {
    const form = document.getElementById('neuer-mitarbeiter-form');
    if (form) {
        form.style.display = 'none';
        document.getElementById('neuer-mitarbeiter-form').reset();
    }
};

window.saveNeuerMitarbeiter = function() {
    const vorname = document.getElementById('neuer_mitarbeiter_vorname').value.trim();
    const nachname = document.getElementById('neuer_mitarbeiter_nachname').value.trim();
    
    if (!vorname || !nachname) {
        alert('Bitte geben Sie Vor- und Nachname ein');
        return;
    }
    
    jQuery.post(ajaxurl, {
        action: 'dp_add_mitarbeiter_from_besetzung',
        vorname: vorname,
        nachname: nachname,
        email: document.getElementById('neuer_mitarbeiter_email').value,
        telefon: document.getElementById('neuer_mitarbeiter_telefon').value,
        dienst_id: window.currentBesetzungDienstId,
        nonce: dpAjax.nonce
    }, function(response) {
        if (response.success) {
            alert('Mitarbeiter erfolgreich hinzugefügt');
            closeNeuerMitarbeiterForm();
            openBesetzungModal(window.currentBesetzungDienstId); // Reload
        } else {
            alert('Fehler beim Hinzufügen: ' + (response.data?.message || 'Unbekannter Fehler'));
        }
    }).fail(function() {
        alert('Fehler beim Hinzufügen des Mitarbeiters');
    });
};

window.removeBesetzung = function(besetzungId) {
    if (!confirm('Möchten Sie diese Zuweisung wirklich entfernen?')) {
        return;
    }
    
    jQuery.post(ajaxurl, {
        action: 'dp_remove_besetzung',
        besetzung_id: besetzungId,
        nonce: dpAjax.nonce
    }, function(response) {
        if (response.success) {
            openBesetzungModal(window.currentBesetzungDienstId); // Reload
        } else {
            alert('Fehler beim Entfernen: ' + (response.data?.message || 'Unbekannter Fehler'));
        }
    }).fail(function() {
        alert('Fehler beim Entfernen der Zuweisung');
    });
};

// ============================================================================
// MITARBEITER DIENSTE MODAL
// ============================================================================

window.openMitarbeiterDiensteModal = function(mitarbeiterId) {
    const modal = document.getElementById('mitarbeiter-dienste-modal');
    const title = modal.querySelector('.dp-modal-header h2');
    
    if (!modal) {
        console.error('Mitarbeiter-Dienste-Modal nicht gefunden');
        return;
    }
    
    // Lade Dienste via AJAX
    jQuery.post(ajaxurl, {
        action: 'dp_get_mitarbeiter_dienste',
        mitarbeiter_id: mitarbeiterId,
        nonce: dpAjax.nonce
    }, function(response) {
        if (response.success && response.data) {
            title.textContent = 'Dienste von ' + (response.data.mitarbeiter_name || 'Mitarbeiter');
            
            // Zeige Dienste an
            const container = document.getElementById('mitarbeiter-dienste-list');
            if (container && response.data.dienste) {
                container.innerHTML = '';
                if (response.data.dienste.length === 0) {
                    container.innerHTML = '<p>Noch keine Dienste zugewiesen.</p>';
                } else {
                    response.data.dienste.forEach(function(d) {
                        container.innerHTML += `
                            <div class="dienst-item">
                                <strong>${d.veranstaltung_name}</strong> - ${d.taetigkeit_name}<br>
                                ${d.datum} ${d.von_zeit} - ${d.bis_zeit}
                            </div>
                        `;
                    });
                }
            }
        } else {
            alert('Fehler beim Laden der Dienste: ' + (response.data?.message || 'Unbekannter Fehler'));
        }
    }).fail(function() {
        alert('Fehler beim Laden der Dienste');
    });
    
    modal.style.display = 'block';
};

// ============================================================================
// BULK UPDATE MODALS
// ============================================================================

window.openBulkTimeModal = function() {
    const modal = document.getElementById('bulk-time-modal');
    if (modal) modal.style.display = 'block';
};

window.closeBulkTimeModal = function() {
    const modal = document.getElementById('bulk-time-modal');
    if (modal) modal.style.display = 'none';
};

window.saveBulkTime = function() {
    alert('Bulk-Zeit-Update wird implementiert');
    closeBulkTimeModal();
};

window.openBulkVereinModal = function() {
    const modal = document.getElementById('bulk-verein-modal');
    if (modal) modal.style.display = 'block';
};

window.closeBulkVereinModal = function() {
    const modal = document.getElementById('bulk-verein-modal');
    if (modal) modal.style.display = 'none';
};

window.saveBulkVerein = function() {
    alert('Bulk-Verein-Update wird implementiert');
    closeBulkVereinModal();
};

window.openBulkBereichModal = function() {
    const modal = document.getElementById('bulk-bereich-modal');
    if (modal) modal.style.display = 'block';
};

window.closeBulkBereichModal = function() {
    const modal = document.getElementById('bulk-bereich-modal');
    if (modal) modal.style.display = 'none';
};

window.saveBulkBereich = function() {
    alert('Bulk-Bereich-Update wird implementiert');
    closeBulkBereichModal();
};

window.openBulkTaetigkeitModal = function() {
    const modal = document.getElementById('bulk-taetigkeit-modal');
    if (modal) modal.style.display = 'block';
};

window.closeBulkTaetigkeitModal = function() {
    const modal = document.getElementById('bulk-taetigkeit-modal');
    if (modal) modal.style.display = 'none';
};

window.saveBulkTaetigkeit = function() {
    alert('Bulk-Tätigkeit-Update wird implementiert');
    closeBulkTaetigkeitModal();
};

window.openBulkStatusModal = function() {
    const modal = document.getElementById('bulk-status-modal');
    if (modal) modal.style.display = 'block';
};

window.closeBulkStatusModal = function() {
    const modal = document.getElementById('bulk-status-modal');
    if (modal) modal.style.display = 'none';
};

window.saveBulkStatus = function() {
    alert('Bulk-Status-Update wird implementiert');
    closeBulkStatusModal();
};

window.openBulkTagModal = function() {
    const modal = document.getElementById('bulk-tag-modal');
    if (modal) modal.style.display = 'block';
};

window.closeBulkTagModal = function() {
    const modal = document.getElementById('bulk-tag-modal');
    if (modal) modal.style.display = 'none';
};

window.saveBulkTag = function() {
    alert('Bulk-Tag-Update wird implementiert');
    closeBulkTagModal();
};

// ============================================================================
// DEBUG FUNCTIONS
// ============================================================================

window.checkDienstStatus = function() {
    alert('Dienst-Status-Check wird implementiert');
};

console.log('dp-admin-modals.js vollständig geladen - alle Funktionen registriert');
