<?php
/*
File: language/en_US/plugin.lang.php – AlbumPilot Plugin for Piwigo
Author: Hendrik Schöttle
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
*/

// --- Frontend translations for JavaScript (used in template via L10N_JS) ---
$lang = array(
    'Start_sync'                        => 'Start synchronization',
    'Reset_settings'                    => 'Reset settings',
    'progress_heading'                  => 'Progress',
    'select_album_alert'                => 'Please select an album.',
    'select_step_alert'                 => 'Please select at least one step.',
    'sync_in_progress'                  => 'Sync in progress...',
    'leave_warning'                     => 'A sync is still in progress. Do you really want to leave the page?',
    'all_steps_completed'               => 'All steps completed',
    'workflow_finished'                 => 'Workflow finished',
    'simulation_suffix'                 => ' (Simulation)',
    'file_label'                        => 'File',
    'step_completed'                    => 'Step completed',
    'of'                                => 'of',
    'image_id'                          => 'Image ID',
    'error_during_step'                 => 'Error during step',
    'no_info_found'                     => 'No information found in result block.',
    'no_success_message'                => 'No success message found.',
    'invalid_response'                  => '❌ Invalid response (not valid JSON):',
    'network_error'                     => '❌ Network error:',
    'thumb_type_label'                  => 'Type',

    // Step names
    'step_sync_files'                   => '1. Import new files and their metadata into database',
    'step_update_metadata'              => '2. Import metadata of all files into database (slow!)',
    'step_generate_video_posters'       => '3. Generate video posters',
    'step_generate_thumbnails'          => '4. Generate thumbnails',
    'step_calculate_checksums'          => '5. Calculate missing checksums',
    'step_reassign_smart_albums'        => '6. Reassign smart albums',
    'step_update_album_metadata'        => '7. Update album metadata',
    'step_update_photo_information'     => '8. Update photo information',
    'step_optimize_database'            => '9. Repair and optimize database',
    'step_run_integrity_check'          => '10. Run optimization and integrity check',

    'videojs_not_active'                => 'VideoJS not active',
    'smartalbums_not_active'            => 'SmartAlbums not active',
    'skipped_simulation_mode'           => 'skipped – simulation mode',

    // Progress type labels
    'step_video'                        => 'Videos',
    'step_thumbnail'                    => 'Thumbnails',
    'step_checksum'                     => 'Images',
    'step_metadata'                     => 'Metadata',

    'reset_error'                       => 'Progress data could not be reset.',
    'reset_error_details'               => 'Error while resetting progress data:',

    'label_select_thumb_types'          => 'Thumbnail sizes',
    'label_thumb_overwrite'             => 'Overwrite existing thumbnails (if present)',

    // --- VideoJS UI translations ---
    'label_videojs_poster_and_thumb_options' => 'Options',
    'VideoJS_RepAdd'                    => 'Use uploaded poster (if available)',
    'VideoJS_AddPoster'                 => 'Generate poster from frame at',
    'VideoJS_PosterSec'                 => 'seconds',
    'VideoJS_PosterOverwrite'           => 'Overwrite existing poster (if present)',
    'VideoJS_OutputFormat'              => 'Output format for poster',
    'VideoJS_jpg'                       => 'JPG',
    'VideoJS_png'                       => 'PNG',
    'VideoJS_OverlayAdd'                => 'Apply film effect to poster',
    'VideoJS_AddThumb'                  => 'Generate thumbnails automatically every',
    'VideoJS_ThumbSec'                  => 'seconds',
    'VideoJS_ThumbSize'                 => 'Thumbnail size',

    'External_trigger_url'              => 'External Trigger URL',
    'External_trigger_description'      => 'This link can be used in a script to run AlbumPilot externally. Start on Windows using Chrome with: start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" --new-window --autoplay-policy=no-user-gesture-required --disable-blink-features=AutomationControlled --disable-popup-blocking --disable-features=SameSiteByDefaultCookies,CookiesWithoutSameSiteMustBeSecure --disable-background-timer-throttling --disable-renderer-backgrounding --disable-infobars "https://..."',

    'end_frontend_section'              => '', // Separator - from here on backend only

    // --- Backend only ---
    'all_albums_label'                  => 'Albums',
    'AlbumPilot_description'            => 'Automates synchronization and maintenance after media import (including videos, smart albums, etc).',
    'AlbumPilot_title'                  => 'AlbumPilot – Automated Synchronization',
    'Albums_to_sync'                    => 'Albums to be synchronized',
    'Album_search_placeholder'          => '🔍 Search albums …',
    'Include_subalbums'                 => 'Search in subalbums',
    'Select_all_steps'                  => 'Select/Deselect all',
    'Options_heading'                   => 'Options',
    'Simulate_mode'                     => 'Run as simulation only',

    // Logging errors and admin diagnostics
    'log_write_error'                   => 'Write error: no write permissions for log file or directory.',
    'log_write_error_path'              => 'Log file path: %s',

    // Sync logging (backend logs)
    'log_sync_started'                  => 'Synchronization started',
    'log_sync_ended'                    => 'Synchronization ended',
    'log_sync_options'                  => 'Options',
    'log_sync_mode_batch'               => '(batch mode)',

    'simulate_mode'                     => 'Simulate',
    'only_new_files'                    => 'Only new files',
    'include_subalbums'                 => 'Include subalbums',
    'selected_album'                    => 'Album',
    'yes'                               => 'Yes',
    'no'                                => 'No',

    // Batch mode warnings
    'batch_mode_warning'                => 'Batch mode active',
    'batch_mode_limited_ui'             => 'The user interface is disabled because the process was triggered externally.',

    // Thumbnail generation logs
    'log_scan_missing_thumbs'           => 'Scanning for missing thumbnails...',
    'log_total_thumbs_to_generate'      => 'Total thumbnails to generate: %d',
    'log_invalid_dimensions'            => 'Invalid image dimensions in DB for ID %d (%s) – width/height missing',
    'log_srcimage_error'                => 'SrcImage error for ID %d (%s): %s',
    'log_derivative_error'              => 'Derivative error for ID %d (%s): %s',
    'log_file_missing'                  => 'File missing for ID %d (%s) – file not found',
    'log_getimagesize_error'            => 'getimagesize error for ID %d (%s)',
    'log_get_target_size_error'         => 'Failed to get target size (type: %s) – ID %d (%s): %s',
    'log_image_too_small'               => 'Too small for %s – ID %d (%s): Original %dx%d, required ≥ %dx%d',
    'log_thumb_progress_line'           => '🖼️ Thumbnail %d of %d (%d%%) – Image ID %d%s – Type: %s | Path: %s',

    // Metadata sync logs
    'log_metadata_scan_start'           => 'Searching for images to update metadata...',
    'log_total_images_to_process'       => 'Total images to process: %d',
    'log_metadata_progress_line'        => 'Metadata %d of %d – Image ID %d%s | Path: %s',
    'log_metadata_summary'              => 'Step completed: Metadata updated for %d images.',

    // MD5 checksum logs
    'log_md5_no_album'                  => 'No valid album selected.',
    'log_md5_scan_start'                => 'Searching for missing checksums...',
    'log_md5_total_to_calculate'        => 'Total checksums to calculate: %d',
    'log_md5_file_missing'              => 'File not found: %s',
    'log_md5_calc_error'                => 'Error calculating MD5 checksum: %s',
    'log_md5_progress_line'             => 'Checksum %d of %d (%d%%) – Image ID %d%s | Path: %s',
    'log_md5_summary'                   => 'Step completed: All checksums calculated.',

    // Video poster logs
    'log_video_nothing_to_do'           => 'No missing posters found.',
    'log_video_scan_start'              => 'Scanning for missing video posters...',
    'log_video_total_to_generate'       => 'Total posters to generate: %d',
    'log_video_progress_line'           => 'Poster %d of %d (%d%%) – Image ID %d%s | Path: %s',
    'log_video_add_frame_failed'        => 'Could not add video frame: %s',
    'log_video_error_details'           => 'Error details: %s',
    'log_video_output'                  => 'Output: %s',
    'log_video_unreadable_poster'       => 'Poster could not be processed – invalid or corrupt JPG: %s',
    'log_video_unknown_gd_error'        => 'Unknown GD error',
    'log_video_summary'                 => '%d video poster(s) have been generated.',
    'log_video_too_short'               => 'Notice: The video "%s" is shorter (%d sec) than the configured poster time (%d sec). Adjusted to %d sec.',
    'log_video_thumb_start'             => 'Starting thumbnail generation for video: %s',
    'log_video_thumb_done'              => 'Thumbnail generation completed (%d thumbnails) for: %s',
    'log_video_combined_counts'         => '%d file(s) to process (%d missing posters, %d files with missing thumbnails)',

    // Step summaries and simulation labels
    'log_step_completed_with_count'     => 'Step completed: %s for %d %s.',
    'step_video'                        => 'videos',
    'step_thumbnail'                    => 'thumbnails',
    'step_checksum'                     => 'images',
    'step_metadata'                     => 'images',

    // Step 1 (sync files) logs
    'log_sync_step1_start'              => 'Starting synchronization (files)',
    'log_sync_step1_options'            => 'Options: %s, %s, %s',
    'label_simulate'                    => 'Simulate',
    'label_live'                        => 'Live',
    'label_only_new'                    => 'only new files',
    'label_all_files'                   => 'all files',
    'label_subalbums_yes'               => 'including subalbums',
    'label_subalbums_no'                => 'album only',
    'log_sync_step1_summary'            => 'Synchronization completed. Added: %d, Deleted: %d, Delta: %d (before: %d, after: %d)',
    'log_sync_step1_simulation_done'    => 'Simulation completed. No changes made.',
);
