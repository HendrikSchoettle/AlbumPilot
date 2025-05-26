<?php
/*
File: language/en_US/plugin.lang.php – AlbumPilot Plugin for Piwigo
Author: Hendrik Schöttle
License: MIT License
SPDX-License-Identifier: MIT
*/

$lang['AlbumPilot_description'] = 'Automates synchronization and maintenance after media import (including videos, smart albums, etc).';

$lang['AlbumPilot_title'] = 'AlbumPilot – Automated Synchronization';
$lang['Albums_to_sync'] = 'Albums to be synchronized';
$lang['Include_subalbums'] = 'Search in sub-albums';
$lang['Select_all_steps'] = 'Select/Deselect all';
$lang['Options_heading'] = 'Options';
$lang['Simulate_mode'] = 'Run as simulation only';
$lang['Start_sync'] = 'Start synchronization';
$lang['Reset_settings'] = 'Reset settings';
$lang['progress_heading'] = 'Progress';

$lang['log_write_error'] = '⚠️ Write error: no write permissions for log file or directory.';
$lang['log_write_error_path'] = 'Log file path: %s';

$lang['reset_error'] = 'Progress data could not be reset.';
$lang['reset_error_details'] = 'Error while resetting progress data:';
$lang['invalid_response'] = '❌ Invalid response (not valid JSON):';
$lang['network_error'] = '❌ Network error:';

$lang['step_sync_files'] = '1. Sync new files and metadata';
$lang['step_generate_thumbnails'] = '2. Generate thumbnails';
$lang['step_generate_video_posters'] = '3. Generate video posters';
$lang['step_update_metadata'] = '4. (Optional) Update metadata of existing files (slow!)';
$lang['step_calculate_checksums'] = '5. Calculate missing checksums';

$lang['step_reassign_smart_albums'] = '6. Reassign smart albums';
$lang['step_update_album_metadata'] = '7. Update album metadata';
$lang['step_update_photo_information'] = '8. Update photo information';
$lang['step_optimize_database'] = '9. Repair and optimize database';
$lang['step_run_integrity_check'] = '10. Run optimization and integrity check';

$lang['videojs_not_active'] = 'VideoJS not active';
$lang['smartalbums_not_active'] = 'SmartAlbums not active';

$lang['select_album_alert'] = 'Please select an album.';
$lang['select_step_alert'] = 'Please select at least one step.';
$lang['sync_in_progress'] = 'Sync in progress...';
$lang['leave_warning'] = 'A sync is still in progress. Do you really want to leave the page?';

$lang['all_steps_completed'] = 'All steps completed';
$lang['workflow_finished'] = 'Workflow finished';
$lang['skipped_simulation_mode'] = 'skipped – simulation mode';
$lang['no_info_found'] = 'No information found in result block.';
$lang['no_success_message'] = 'No success message found.';

$lang['step_video'] = 'Video';
$lang['step_thumbnail'] = 'Thumbnail';
$lang['step_checksum'] = 'Checksum';
$lang['step_metadata'] = 'Metadata';

$lang['file_label'] = 'File';
$lang['step_completed'] = 'Step completed';

$lang['of'] = 'of';
$lang['image_id'] = 'Image ID';
$lang['simulation_suffix'] = ' (Simulation)';

$lang['error_during_step'] = 'Error during step';

$lang['log_sync_started'] = 'Synchronization started';
$lang['log_sync_ended'] = 'Synchronization ended';
$lang['log_sync_options'] = 'Options';
$lang['simulate_mode'] = 'Simulate';
$lang['only_new_files'] = 'Only new files';
$lang['include_subalbums'] = 'Include subalbums';
$lang['selected_album'] = 'Album';
$lang['yes'] = 'Yes';
$lang['no'] = 'No';

$lang['log_scan_missing_thumbs'] = 'Scanning for missing thumbnails...';
$lang['log_total_thumbs_to_generate'] = 'Total thumbnails to generate: %d';
$lang['log_invalid_dimensions'] = 'Invalid image dimensions in DB for ID %d (%s) – width/height missing';
$lang['log_srcimage_error'] = 'SrcImage error for ID %d (%s): %s';
$lang['log_derivative_error'] = 'Derivative error for ID %d (%s): %s';
$lang['log_file_missing'] = 'File missing for ID %d (%s) – file not found';
$lang['log_getimagesize_error'] = 'getimagesize error for ID %d (%s)';
$lang['log_get_target_size_error'] = 'Failed to get target size (type: %s) – ID %d (%s): %s';
$lang['log_image_too_small'] = 'Too small for %s – ID %d (%s): Original %dx%d, required ≥ %dx%d';

$lang['log_thumb_progress_line'] = '🖼️ Thumbnail %d of %d (%d%%) – Image ID %d%s – Type: %s | Path: %s';
$lang['thumb_type_label'] = 'Type';

$lang['log_metadata_scan_start'] = 'Searching for images to update metadata...';
$lang['log_total_images_to_process'] = 'Total images to process: %d';
$lang['log_metadata_progress_line'] = 'Metadata %d of %d – Image ID %d%s | Path: %s';
$lang['log_metadata_summary'] = 'Step completed: Metadata updated for %d images.';

$lang['log_md5_no_album'] = 'No valid album selected.';
$lang['log_md5_scan_start'] = 'Searching for missing checksums...';
$lang['log_md5_total_to_calculate'] = 'Total checksums to calculate: %d';

$lang['log_md5_file_missing'] = 'File not found: %s';
$lang['log_md5_calc_error'] = 'Error calculating MD5 checksum: %s';
$lang['log_md5_progress_line'] = 'Checksum %d of %d (%d%%) – Image ID %d%s | Path: %s';
$lang['log_md5_summary'] = 'Step completed: All checksums calculated.';

$lang['log_video_nothing_to_do'] = 'No missing posters found.';
$lang['log_video_scan_start'] = 'Scanning for missing video posters...';
$lang['log_video_total_to_generate'] = 'Total posters to generate: %d';
$lang['log_video_progress_line'] = 'Poster %d of %d (%d%%) – Image ID %d%s | Path: %s';
$lang['log_video_add_frame_failed'] = 'Could not add video frame: %s';
$lang['log_video_error_details'] = 'Error details: %s';
$lang['log_video_output'] = 'Output: %s';

$lang['log_video_unreadable_poster'] = 'Poster could not be processed – invalid or corrupt JPEG: %s';
$lang['log_video_unknown_gd_error'] = 'Unknown GD error';
$lang['log_video_summary'] = '%d video poster(s) have been generated.';

$lang['log_sync_step1_start'] = 'Starting synchronization (files)';
$lang['log_sync_step1_options'] = 'Options: %s, %s, %s';
$lang['label_simulate'] = 'Simulate';
$lang['label_live'] = 'Live';
$lang['label_only_new'] = 'only new files';
$lang['label_all_files'] = 'all files';
$lang['label_subalbums_yes'] = 'including subalbums';
$lang['label_subalbums_no'] = 'album only';
$lang['log_sync_step1_before_count'] = 'Before: %d images in database';
$lang['log_sync_step1_after_count'] = 'After: %d images. Difference: %d new files';
$lang['log_sync_step1_summary'] = 'Synchronization completed. New files: %d (before: %d, after: %d)';
$lang['log_sync_step1_simulation_done'] = 'Simulation completed. No changes made.';

?>