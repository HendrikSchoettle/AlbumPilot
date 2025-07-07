<?php
/*
File: language/de_DE/plugin.lang.php – AlbumPilot Plugin for Piwigo
Author: Hendrik Schöttle
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
*/

// --- Frontend translations for JavaScript (used in template via L10N_JS) ---
$lang = array(
    'Start_sync'                        => 'Starte Synchronisation',
    'Reset_settings'                    => 'Einstellungen zurücksetzen',
    'progress_heading'                  => 'Fortschritt',
    'select_album_alert'                => 'Bitte wähle ein Album aus.',
    'select_step_alert'                 => 'Bitte wähle mindestens einen Schritt aus.',
    'sync_in_progress'                  => 'Synchronisation läuft...',
    'leave_warning'                     => 'Eine Synchronisation läuft noch. Möchtest du die Seite wirklich verlassen?',
    'all_steps_completed'               => 'Alle Schritte abgeschlossen',
    'workflow_finished'                 => 'Workflow abgeschlossen',
    'simulation_suffix'                 => ' (Simulation)',
    'file_label'                        => 'Datei',
    'step_completed'                    => 'Schritt abgeschlossen',
    'of'                                => 'von',
    'image_id'                          => 'Bild-ID',
    'error_during_step'                 => 'Fehler während des Schritts',
    'no_info_found'                     => 'Keine Informationen im Ergebnisblock gefunden.',
    'no_success_message'                => 'Keine Erfolgsnachricht gefunden.',
    'invalid_response'                  => '❌ Ungültige Antwort (kein gültiges JSON):',
    'network_error'                     => '❌ Netzwerkfehler:',
    'thumb_type_label'                  => 'Typ',

    // Step names
    'step_sync_files'                   => '1. Neue Dateien und deren Metadaten in Datenbank importieren',
    'step_update_metadata'              => '2. Metadaten aller Dateien in Datenbank importieren (langsam!)',
	'step_generate_video_posters'       => '3. Videoposter generieren',
    'step_generate_thumbnails'          => '4. Thumbnails generieren',
    'step_calculate_checksums'          => '5. Fehlende Checksummen berechnen',
    'step_reassign_smart_albums'        => '6. Smart-Alben neu zuweisen',
    'step_update_album_metadata'        => '7. Album-Metadaten aktualisieren',
    'step_update_photo_information'     => '8. Bildinformationen aktualisieren',
    'step_optimize_database'            => '9. Datenbank reparieren und optimieren',
    'step_run_integrity_check'          => '10. Optimierung und Integritätsprüfung ausführen',

    'videojs_not_active'                => 'VideoJS nicht aktiv',
    'smartalbums_not_active'            => 'SmartAlbums nicht aktiv',
    'skipped_simulation_mode'           => 'übersprungen – Simulationsmodus',

    // Progress type labels
    'step_video'                        => 'Videos',
    'step_thumbnail'                    => 'Thumbnails',
    'step_checksum'                     => 'Bilder',
    'step_metadata'                     => 'Metadaten',

    'reset_error'                       => 'Fortschrittsdaten konnten nicht zurückgesetzt werden.',
    'reset_error_details'               => 'Fehler beim Zurücksetzen der Fortschrittsdaten:',

    'label_select_thumb_types'          => 'Bildgrößen',
    'label_thumb_overwrite'             => 'Existierende Thumbnails überschreiben (falls vorhanden)',

    // --- VideoJS UI translations ---
    // 'label_videojs_poster_and_thumb_options' => 'Optionen',
    'VideoJS_RepAdd'                    => 'Hochgeladenes Poster übernehmen (falls verfügbar)',
    'VideoJS_AddPoster'                 => 'Poster aus Frame generieren nach',
    'VideoJS_PosterSec'                 => 'Sekunden',
    'VideoJS_PosterOverwrite'           => 'Existierendes Poster überschreiben (falls vorhanden)',
	'VideoJS_OutputFormat'              => 'Ausgabeformat für Poster',
    'VideoJS_jpg'                       => 'JPG',
    'VideoJS_png'                       => 'PNG',
    'VideoJS_OverlayAdd'                => 'Filmeffekt auf Poster anwenden',
    'VideoJS_AddThumb'                  => 'Vorschaubilder automatisch erzeugen alle',
    'VideoJS_ThumbSec'                  => 'Sekunden',
	'VideoJS_ThumbSize'                 => 'Größe der Thumbnails',

    'External_trigger_url'              => 'Externer Aufruf-Link',
    'External_trigger_description'      => 'Dieser Link kann z. B. in einem Script verwendet werden, um AlbumPilot extern auszuführen. Start unter Windows und Chrome mit start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" --new-window --autoplay-policy=no-user-gesture-required --disable-blink-features=AutomationControlled --disable-popup-blocking --disable-features=SameSiteByDefaultCookies,CookiesWithoutSameSiteMustBeSecure --disable-background-timer-throttling --disable-renderer-backgrounding --disable-infobars "https://..."',

    'end_frontend_section'              => '', // Separator - from here on backend only

    // --- Backend only ---
    'all_albums_label'                  => 'Alben',
    'AlbumPilot_title'                  => 'AlbumPilot – Automatisierte Synchronisation',
    'Albums_to_sync'                    => 'Zu synchronisierende Alben',
    'Album_search_placeholder'          => '🔍 Album suchen …',
    'Include_subalbums'                 => 'Suche in Unteralben',
    'Select_all_steps'                  => 'Alle auswählen/abwählen',
    'Options_heading'                   => 'Optionen',
    'Simulate_mode'                     => 'Nur Simulation durchführen',
    
    // Logging errors and admin diagnostics
    'log_write_error'                   => 'Schreibfehler: Keine Schreibrechte für Logdatei oder Verzeichnis.',
    'log_write_error_path'              => 'Pfad zur Logdatei: %s',

    // Sync logging (backend logs)
    'log_sync_started'                  => 'Synchronisation gestartet',
    'log_sync_ended'                    => 'Synchronisation beendet',
    'log_sync_options'                  => 'Optionen',
    'log_sync_mode_batch'               => '(Batch-Modus)',
    'simulate_mode'                     => 'Simulation',
    'only_new_files'                    => 'Nur neue Dateien',
    'include_subalbums'                 => 'Unteralben einbeziehen',
    'selected_album'                    => 'Album',
    'yes'                               => 'Ja',
    'no'                                => 'Nein',

	// Batch mode warnings 
    'batch_mode_warning'                => 'Batch-Modus aktiv',
    'batch_mode_limited_ui'             => 'Die Benutzeroberfläche ist deaktiviert, da der Vorgang extern gestartet wurde.',

    // Help link
    'documentation_link'                => 'AlbumPilot-Dokumentation (englisch)',
									  
    // Thumbnail generation logs
    'log_scan_missing_thumbs'           => 'Suche nach fehlenden Thumbnails...',
    'log_total_thumbs_to_generate'      => 'Anzahl der zu generierenden Thumbnails: %d',
    'log_invalid_dimensions'            => 'Ungültige Bildmaße in der Datenbank für ID %d (%s) – Breite/Höhe fehlt',
    'log_srcimage_error'                => 'SrcImage-Fehler für ID %d (%s): %s',
    'log_derivative_error'              => 'Fehler bei Derivat-Erstellung für ID %d (%s): %s',
    'log_file_missing'                  => 'Datei fehlt für ID %d (%s) – Datei nicht gefunden',
    'log_get_target_size_error'         => 'Fehler beim Ermitteln der Zielgröße (Typ: %s) – ID %d (%s): %s',
    'log_image_too_small'               => 'Zu klein für %s – ID %d (%s): Original %dx%d, erforderlich ≥ %dx%d',
    'log_thumb_progress_line'           => '🖼️ Thumbnail %d von %d (%d%%) – Bild-ID %d%s – Typ: %s | Pfad: %s',

    // Metadata sync logs
    'log_metadata_scan_start'           => 'Suche nach Bildern zur Metadaten-Aktualisierung...',
    'log_total_images_to_process'       => 'Anzahl der zu verarbeitenden Bilder: %d',
    'log_metadata_progress_line'        => 'Metadaten %d von %d – Bild-ID %d%s | Pfad: %s',
    'log_metadata_summary'              => 'Schritt abgeschlossen: Metadaten für %d Bilder aktualisiert.',

    // MD5 checksum logs
    'log_md5_no_album'                  => 'Kein gültiges Album ausgewählt.',
    'log_md5_scan_start'                => 'Suche nach fehlenden Checksummen...',
    'log_md5_total_to_calculate'        => 'Anzahl der zu berechnenden Checksummen: %d',
    'log_md5_file_missing'              => 'Datei nicht gefunden: %s',
    'log_md5_calc_error'                => 'Fehler beim Berechnen der MD5-Prüfsumme: %s',
    'log_md5_progress_line'             => 'Checksumme %d von %d (%d%%) – Bild-ID %d%s | Pfad: %s',
    'log_md5_summary'                   => 'Schritt abgeschlossen: Alle Checksummen berechnet.',

    // Video poster logs
    'log_video_nothing_to_do'           => 'Keine fehlenden Poster gefunden.',
    'log_video_scan_start'              => 'Suche nach fehlenden Video-Postern...',
    'log_video_total_to_generate'       => 'Anzahl der zu generierenden Poster: %d',
    'log_video_progress_line'           => 'Poster %d von %d (%d%%) – Bild-ID %d%s | Pfad: %s',
    'log_video_add_frame_failed'        => 'Poster konnte nicht als Videoframe hinzugefügt werden: %s',
    'log_video_error_details'           => 'Fehlerdetails: %s',
    'log_video_output'                  => 'Ausgabe: %s',
    'log_video_unreadable_poster'       => 'Poster konnte nicht verarbeitet werden – ungültiges oder beschädigtes JPG: %s',
    'log_video_unknown_gd_error'        => 'Unbekannter GD-Fehler',
    'log_video_summary'                 => 'Es wurden %d Video-Poster generiert.',
    'log_video_too_short'               => 'Hinweis: Das Video "%s" ist kürzer (%d Sek.) als der eingestellte Poster-Zeitpunkt (%d Sek.). Es wird auf %d Sek. zurückgesetzt.',
    'log_video_thumb_start'             => 'Starte Thumbnail-Erstellung für Video: %s',
    'log_video_thumb_done'              => 'Thumbnail-Erstellung abgeschlossen (%d Thumbnails) für: %s',
    'log_video_combined_counts'         => '%d zu behandelnde Dateien (%d fehlende Poster, %d Dateien mit fehlenden Thumbnails)',

    // Step summaries and simulation labels
    'log_step_completed_with_count'     => 'Schritt abgeschlossen: %s für %d %s.',
    'step_video'                        => 'Videos',
    'step_thumbnail'                    => 'Thumbnails',
    'step_checksum'                     => 'Bilder',
    'step_metadata'                     => 'Bilder',

    // Step 1 (sync files) logs
    'log_sync_step1_start'              => 'Starte Synchronisation (Dateien)',
    'log_sync_step1_options'            => 'Optionen: %s, %s, %s',
    'label_simulate'                    => 'Simulation',
    'label_live'                        => 'Live-Modus',
    'label_only_new'                    => 'nur neue Dateien',
    'label_all_files'                   => 'alle Dateien',
    'label_subalbums_yes'               => 'inkl. Unteralben',
    'label_subalbums_no'                => 'nur dieses Album',
    'log_sync_step1_summary'            => 'Synchronisation abgeschlossen. Hinzugefügt: %d, Gelöscht: %d, Differenz: %d (vorher: %d, nachher: %d)',
    'log_sync_step1_simulation_done'    => 'Simulation abgeschlossen. Keine Änderungen vorgenommen.',
);

