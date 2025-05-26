<?php
/*
File: language/de_DE/plugin.lang.php – AlbumPilot Plugin for Piwigo
Author: Hendrik Schöttle
License: MIT License
SPDX-License-Identifier: MIT
*/

$lang['AlbumPilot_description'] = 'Automatisiert die Synchronisation und Wartung nach dem Medienimport (inkl. Videos, Smart-Alben usw.).';

$lang['AlbumPilot_title'] = 'AlbumPilot – Automatisierte Synchronisation';
$lang['Albums_to_sync'] = 'Zu synchronisierende Alben';
$lang['Include_subalbums'] = 'Suche in Unteralben';
$lang['Select_all_steps'] = 'Alle auswählen/abwählen';
$lang['Options_heading'] = 'Optionen';
$lang['Simulate_mode'] = 'Nur Simulation durchführen';
$lang['Start_sync'] = 'Starte Synchronisation';
$lang['Reset_settings'] = 'Einstellungen zurücksetzen';
$lang['progress_heading'] = 'Fortschritt';

$lang['log_write_error'] = '⚠️ Schreibfehler: Keine Schreibrechte für Logdatei oder Verzeichnis.';
$lang['log_write_error_path'] = 'Pfad zur Logdatei: %s';

$lang['reset_error'] = 'Fortschrittsdaten konnten nicht zurückgesetzt werden.';
$lang['reset_error_details'] = 'Fehler beim Zurücksetzen der Fortschrittsdaten:';
$lang['invalid_response'] = '❌ Ungültige Antwort (kein gültiges JSON):';
$lang['network_error'] = '❌ Netzwerkfehler:';

$lang['step_sync_files'] = '1. Neue Dateien und Metadaten synchronisieren';
$lang['step_generate_thumbnails'] = '2. Thumbnails generieren';
$lang['step_generate_video_posters'] = '3. Videoposter generieren';
$lang['step_update_metadata'] = '4. (Optional) Metadaten vorhandener Dateien aktualisieren (langsam!)';
$lang['step_calculate_checksums'] = '5. Fehlende Checksummen berechnen';

$lang['step_reassign_smart_albums'] = '6. Smart-Alben neu zuweisen';
$lang['step_update_album_metadata'] = '7. Album-Metadaten aktualisieren';
$lang['step_update_photo_information'] = '8. Bildinformationen aktualisieren';
$lang['step_optimize_database'] = '9. Datenbank reparieren und optimieren';
$lang['step_run_integrity_check'] = '10. Optimierung und Integritätsprüfung ausführen';

$lang['videojs_not_active'] = 'VideoJS nicht aktiv';
$lang['smartalbums_not_active'] = 'SmartAlbums nicht aktiv';

$lang['select_album_alert'] = 'Bitte wähle ein Album aus.';
$lang['select_step_alert'] = 'Bitte wähle mindestens einen Schritt aus.';
$lang['sync_in_progress'] = 'Synchronisation läuft...';
$lang['leave_warning'] = 'Eine Synchronisation läuft noch. Möchtest du die Seite wirklich verlassen?';

$lang['all_steps_completed'] = 'Alle Schritte abgeschlossen';
$lang['workflow_finished'] = 'Workflow abgeschlossen';
$lang['skipped_simulation_mode'] = 'übersprungen – Simulationsmodus';
$lang['no_info_found'] = 'Keine Informationen im Ergebnisblock gefunden.';
$lang['no_success_message'] = 'Keine Erfolgsnachricht  gefunden.';

$lang['step_video'] = 'Video';
$lang['step_thumbnail'] = 'Thumbnail';
$lang['step_checksum'] = 'Prüfsumme';
$lang['step_metadata'] = 'Metadaten';

$lang['file_label'] = 'Datei';
$lang['step_completed'] = 'Schritt abgeschlossen';

$lang['of'] = 'von';
$lang['image_id'] = 'Bild-ID';
$lang['simulation_suffix'] = ' (Simulation)';

$lang['error_during_step'] = 'Fehler während des Schritts';

$lang['log_sync_started'] = 'Synchronisation gestartet';
$lang['log_sync_ended'] = 'Synchronisation beendet';
$lang['log_sync_options'] = 'Optionen';
$lang['simulate_mode'] = 'Simulation';
$lang['only_new_files'] = 'Nur neue Dateien';
$lang['include_subalbums'] = 'Unteralben einbeziehen';
$lang['selected_album'] = 'Album';
$lang['yes'] = 'Ja';
$lang['no'] = 'Nein';

$lang['log_scan_missing_thumbs'] = 'Suche nach fehlenden Thumbnails...';
$lang['log_total_thumbs_to_generate'] = 'Anzahl der zu generierenden Thumbnails: %d';
$lang['log_invalid_dimensions'] = 'Ungültige Bildmaße in der Datenbank für ID %d (%s) – Breite/Höhe fehlt';
$lang['log_srcimage_error'] = 'SrcImage-Fehler für ID %d (%s): %s';
$lang['log_derivative_error'] = 'Fehler bei Derivat-Erstellung für ID %d (%s): %s';
$lang['log_file_missing'] = 'Datei fehlt für ID %d (%s) – Datei nicht gefunden';
$lang['log_getimagesize_error'] = 'Fehler bei getimagesize für ID %d (%s)';
$lang['log_get_target_size_error'] = 'Fehler beim Ermitteln der Zielgröße (Typ: %s) – ID %d (%s): %s';
$lang['log_image_too_small'] = 'Zu klein für %s – ID %d (%s): Original %dx%d, erforderlich ≥ %dx%d';

$lang['log_thumb_progress_line'] = '🖼️ Thumbnail %d von %d (%d%%) – Bild-ID %d%s – Typ: %s | Pfad: %s';
$lang['thumb_type_label'] = 'Typ';

$lang['log_metadata_scan_start'] = 'Suche nach Bildern zur Metadaten-Aktualisierung...';
$lang['log_total_images_to_process'] = 'Anzahl der zu verarbeitenden Bilder: %d';
$lang['log_metadata_progress_line'] = 'Metadaten %d von %d – Bild-ID %d%s | Pfad: %s';
$lang['log_metadata_summary'] = 'Schritt abgeschlossen: Metadaten für %d Bilder aktualisiert.';

$lang['log_md5_no_album'] = 'Kein gültiges Album ausgewählt.';
$lang['log_md5_scan_start'] = 'Suche nach fehlenden Checksummen...';
$lang['log_md5_total_to_calculate'] = 'Anzahl der zu berechnenden Checksummen: %d';

$lang['log_md5_file_missing'] = 'Datei nicht gefunden: %s';
$lang['log_md5_calc_error'] = 'Fehler beim Berechnen der MD5-Prüfsumme: %s';
$lang['log_md5_progress_line'] = 'Checksumme %d von %d (%d%%) – Bild-ID %d%s | Pfad: %s';
$lang['log_md5_summary'] = 'Schritt abgeschlossen: Alle Checksummen berechnet.';

$lang['log_video_nothing_to_do'] = 'Keine fehlenden Poster gefunden.';
$lang['log_video_scan_start'] = 'Suche nach fehlenden Video-Postern...';
$lang['log_video_total_to_generate'] = 'Anzahl der zu generierenden Poster: %d';
$lang['log_video_progress_line'] = 'Poster %d von %d (%d%%) – Bild-ID %d%s | Pfad: %s';
$lang['log_video_add_frame_failed'] = 'Poster konnte nicht als Videoframe hinzugefügt werden: %s';
$lang['log_video_error_details'] = 'Fehlerdetails: %s';
$lang['log_video_output'] = 'Ausgabe: %s';

$lang['log_video_unreadable_poster'] = 'Poster konnte nicht verarbeitet werden – ungültiges oder beschädigtes JPEG: %s';
$lang['log_video_unknown_gd_error'] = 'Unbekannter GD-Fehler';
$lang['log_video_summary'] = 'Es wurden %d Video-Poster generiert.';

$lang['log_sync_step1_start'] = 'Starte Synchronisation (Dateien)';
$lang['log_sync_step1_options'] = 'Optionen: %s, %s, %s';
$lang['label_simulate'] = 'Simulation';
$lang['label_live'] = 'Live-Modus';
$lang['label_only_new'] = 'nur neue Dateien';
$lang['label_all_files'] = 'alle Dateien';
$lang['label_subalbums_yes'] = 'inkl. Unteralben';
$lang['label_subalbums_no'] = 'nur dieses Album';
$lang['log_sync_step1_before_count'] = 'Vorher: %d Bilder in der Datenbank';
$lang['log_sync_step1_after_count'] = 'Nachher: %d Bilder. Unterschied: %d neue Dateien';
$lang['log_sync_step1_summary'] = 'Synchronisation abgeschlossen. Neue Dateien: %d (vorher: %d, nachher: %d)';
$lang['log_sync_step1_simulation_done'] = 'Simulation abgeschlossen. Keine Änderungen vorgenommen.';

?>