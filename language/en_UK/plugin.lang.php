<?php
/*
File: language/en_UK/plugin.lang.php – AlbumPilot Plugin for Piwigo
Author: Hendrik Schöttle
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
*/

$lang = array(
  // --- Frontend translations for JavaScript ---
  'Start_sync' => 'Start synchronisation',
  'Reset_settings' => 'Reset settings',
  'progress_heading' => 'Progress',
  'select_album_alert' => 'Please select an album.',
  'select_step_alert' => 'Please select at least one step.',
  'sync_in_progress' => 'Sync in progress...',
  'leave_warning' => 'A sync is still in progress. Do you really want to leave the page?',
  'all_steps_completed' => 'All steps completed',
  'workflow_finished' => 'Workflow finished',
  'simulation_suffix' => ' (Simulation)',
  'file_label' => 'File',
  'step_completed' => 'Step completed',
  'of' => 'of',
  'image_id' => 'Image ID',
  'error_during_step' => 'Error during step',
  'no_info_found' => 'No information found in result block.',
  'no_success_message' => 'No success message found.',
  'invalid_response' => '❌ Invalid response (not valid JSON):',
  'network_error' => '❌ Network error:',
  'thumb_type_label' => 'Type',

  // Step names
  'step_sync_files' => '1. Sync new files and metadata',
  'step_generate_thumbnails' => '2. Generate thumbnails',
  'step_generate_video_posters' => '3. Generate video posters',
  'step_calculate_checksums' => '4. Calculate missing checksums',
  'step_update_metadata' => '5. (Optional) Update metadata of existing files (slow!)',
  'step_reassign_smart_albums' => '6. Reassign smart albums',
  'step_update_album_metadata' => '7. Update album metadata',
  'step_update_photo_information' => '8. Update photo information',
  'step_optimize_database' => '9. Repair and optimise database',
  'step_run_integrity_check' => '10. Run optimisation and integrity check',

  'videojs_not_active' => 'VideoJS not active',
  'smartalbums_not_active' => 'SmartAlbums not active',
  'skipped_simulation_mode' => 'skipped – simulation mode',

  // Progress type labels
  'step_video' => 'Videos',
  'step_thumbnail' => 'Thumbnails',
  'step_checksum' => 'Images',
  'step_metadata' => 'Metadata',

  'reset_error' => 'Progress data could not be reset.',
  'reset_error_details' => 'Error while resetting progress data:',

  'end_frontend_section' => '', // Separator – backend only from here

  // --- Backend only (for admin panel, logs, etc.) ---
  'AlbumPilot_description' => 'Automates synchronisation and maintenance after media import (including videos, smart albums, etc).',
  'AlbumPilot_title' => 'AlbumPilot – Automated Synchronisation',
  'Albums_to_sync' => 'Albums to be synchronised',
  'Include_subalbums' => 'Search in sub-albums',
  'Select_all_steps' => 'Select/Deselect all',
  'Options_heading' => 'Options',
  'Simulate_mode' => 'Run as simulation only',
  'External_trigger_url' => 'External Trigger URL',
  'External_trigger_description' => 'This link can be used in a script to run AlbumPilot externally. Start on Windows using: start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" --new-window --autoplay-policy=no-user-gesture-required --disable-blink-features=AutomationControlled --disable-popup-blocking --disable-features=SameSiteByDefaultCookies,CookiesWithoutSameSiteMustBeSecure --disable-background-timer-throttling --disable-renderer-backgrounding --disable-infobars "https://..."',

  'log_write_error' => '⚠️ Write error: no write permissions for log file or directory.',
  'log_write_error_path' => 'Log file path: %s',

  'log_sync_started' => 'Synchronisation started',
  'log_sync_ended' => 'Synchronisation ended',
  'log_sync_options' => 'Options',
  'log_sync_mode_batch' => '(batch mode)',

  'simulate_mode' => 'Simulate',
  'only_new_files' => 'Only new files',
  'include_subalbums' => 'Include subalbums',
  'selected_album' => 'Album',
  'yes' => 'Yes',
  'no' => 'No',

  'log_scan_missing_thumbs' => 'Scanning for missing thumbnails...',
  'log_total_thumbs_to_generate' => 'Total thumbnails to generate: %d',
  'log_invalid_dimensions' => 'Invalid image dimensions in DB for ID %d (%s) – width/height missing',
  'log_srcimage_error' => 'SrcImage error for ID %d (%s): %s',
  'log_derivative_error' => 'Derivative error for ID %d (%s): %s',
  'log_file_missing' => 'File missing for ID %d (%s) – file not found',
  'log_getimagesize_error' => 'getimagesize error for ID %d (%s)',
  'log_get_target_size_error' => 'Failed to get target size (type: %s) – ID %d (%s): %s',
  'log_image_too_small' => 'Too small for %s – ID %d (%s): Original %dx%d, required ≥ %dx%d',
  'log_thumb_progress_line' => '🖼️ Thumbnail %d of %d (%d%%) – Image ID %d%s – Type: %s | Path: %s',

  'log_metadata_scan_start' => 'Searching for images to update metadata...',
  'log_total_images_to_process' => 'Total images to process: %d',
  'log_metadata_progress_line' => 'Metadata %d of %d – Image ID %d%s | Path: %s',
  'log_metadata_summary' => 'Step completed: Metadata updated for %d images.',

  'log_md5_no_album' => 'No valid album selected.',
  'log_md5_scan_start' => 'Searching for missing checksums...',
  'log_md5_total_to_calculate' => 'Total checksums to calculate: %d',
  'log_md5_file_missing' => 'File not found: %s',
  'log_md5_calc_error' => 'Error calculating MD5 checksum: %s',
  'log_md5_progress_line' => 'Checksum %d of %d (%d%%) – Image ID %d%s | Path: %s',
  'log_md5_summary' => 'Step completed: All checksums calculated.',

  'log_video_nothing_to_do' => 'No missing posters found.',
  'log_video_scan_start' => 'Scanning for missing video posters...',
  'log_video_total_to_generate' => 'Total posters to generate: %d',
  'log_video_progress_line' => 'Poster %d of %d (%d%%) – Image ID %d%s | Path: %s',
  'log_video_add_frame_failed' => 'Could not add video frame: %s',
  'log_video_error_details' => 'Error details: %s',
  'log_video_output' => 'Output: %s',
  'log_video_unreadable_poster' => 'Poster could not be processed – invalid or corrupt JPEG: %s',
  'log_video_unknown_gd_error' => 'Unknown GD error',
  'log_video_summary' => '%d video poster(s) have been generated.',

  'log_step_completed_with_count' => 'Step completed: %s for %d %s.',
  'step_video' => 'videos',
  'step_thumbnail' => 'thumbnails',
  'step_checksum' => 'images',
  'step_metadata' => 'images',

  'log_sync_step1_start' => 'Starting synchronisation (files)',
  'log_sync_step1_options' => 'Options: %s, %s, %s',
  'label_simulate' => 'Simulate',
  'label_live' => 'Live',
  'label_only_new' => 'only new files',
  'label_all_files' => 'all files',
  'label_subalbums_yes' => 'including subalbums',
  'label_subalbums_no' => 'album only',
  'log_sync_step1_before_count' => 'Before: %d images in database',
  'log_sync_step1_after_count' => 'After: %d images. Difference: %d new files',
  'log_sync_step1_summary' => 'Synchronisation completed. New files: %d (before: %d, after: %d)',
  'log_sync_step1_simulation_done' => 'Simulation completed. No changes made.',
);

?>
