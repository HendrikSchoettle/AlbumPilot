<?php
/*
File: admin.php - AlbumPilot Plugin for Piwigo
Author: Hendrik Schöttle
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
*/

// Clear output buffers for clean JSON responses in AJAX contexts
if (
isset($_GET['generate_image_thumbs']) ||
isset($_GET['resize_for_album']) ||
isset($_GET['calculate_md5']) ||
isset($_GET['wrapped_sync']) ||
isset($_GET['update_metadata_for_album']) ||
isset($_GET['videojs_generate_thumbs'])
) {
    while (ob_get_level()) {
        ob_end_clean();
    }
}

// Prevent direct access
if (!defined('PHPWG_ROOT_PATH')) {
    die('Hacking attempt!');
}

// Define plugin path if not already defined
if (!defined('ALBUM_PILOT_PATH')) {
    define('ALBUM_PILOT_PATH', dirname(__FILE__) . '/');
}

check_status(ACCESS_ADMINISTRATOR);

// Load plugin language files for admin interface
load_language('plugin.lang', ALBUM_PILOT_PATH);

// Include VideoJS helper functions
include_once(PHPWG_PLUGINS_PATH . 'piwigo-videojs/include/function_frame.php');

// Load derivative types for admin UI template
include_once(PHPWG_ROOT_PATH . 'include/derivative.inc.php');
include_once(PHPWG_ROOT_PATH . 'include/derivative_params.inc.php');

global $prefixeTable;
// Build the proper table name once Piwigo has set up the prefix
$table = $prefixeTable . 'album_pilot_settings';

// SQL to create the table if it does not already exist
$sql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$table}` (
user_id       SMALLINT UNSIGNED NOT NULL,
setting_key   VARCHAR(50)      NOT NULL,
setting_value VARCHAR(255)     NOT NULL,
PRIMARY KEY (user_id, setting_key)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

// Execute and check for errors
$result = pwg_query($sql);
if (false === $result) {
    $dbError = pwg_db_error();
    // Log an error in Piwigo’s log and trigger a PHP warning
    pwg_log('ERROR', 'AlbumPilot', "admin.php: Could not create table {$table}: {$dbError}");
    trigger_error("AlbumPilot: Failed to create settings table ({$dbError})", E_USER_WARNING);
}

$translated_thumb_types = [];

// Hardcoded fallback for thumbnail labels when US locale fails to load core translations.
// This is a workaround until the root cause in en_US context is resolved in a final bugfix.
$thumb_label_fallback = [
    'square'   => 'Square',
    'thumb'    => 'Thumbnail',
    '2small'   => 'XXS – tiny',
    'xsmall'   => 'XS – extra small',
    'small'    => 'S – small',
    'medium'   => 'M – medium',
    'large'    => 'L – large',
    'xlarge'   => 'XL – extra large',
    'xxlarge'  => 'XXL – huge',
    '3xlarge'  => '3XL – extra huge',
    '4xlarge'  => '4XL – gigantic',
];

foreach (ImageStdParams::get_defined_type_map() as $type => $params) {
    $label = l10n($type);
     // if no translation found, see previous comment (l10n returned the key), and fallback exists, use it
    if ($label === $type && isset($thumb_label_fallback[$type])) {
        $label = $thumb_label_fallback[$type];
    }
    $translated_thumb_types[] = [
        'id'    => $type,
        'label' => $label,
    ];
}

$template->assign('ALBUM_PILOT_THUMB_TYPES', $translated_thumb_types);

// Handle external batch execution via GET
if (isset($_GET['external_batch']) && $_GET['external_batch'] === '1') {
    if (!is_admin()) {
        echo 'Access denied – admin login required.';
        exit;
    }
    
    $album     = $_GET['album']     ?? '';
    $simulate  = $_GET['simulate']  ?? '0';
    $onlyNew   = $_GET['onlynew']   ?? '0';
    $subalbums = $_GET['subalbums'] ?? '0';
    $steps     = explode(',', $_GET['steps'] ?? '');
    $token     = get_pwg_token();
    
    // Determine album path for logging
    $albumPath = '(not set)';
    if (is_numeric($album)) {
        $albumId   = (int)$album;
        $albumData = pwg_db_fetch_assoc(
        pwg_query("SELECT name, uppercats FROM " . CATEGORIES_TABLE . " WHERE id = $albumId")
        );
        
        if ($albumData) {
            $ids   = explode(',', $albumData['uppercats']);
            $names = [];
            
            foreach ($ids as $id) {
                $row = pwg_db_fetch_assoc(
                pwg_query("SELECT name FROM " . CATEGORIES_TABLE . " WHERE id = " . (int)$id)
                );
                
                if ($row) {
                    $names[] = $row['name'];
                }
            }
            
            $albumPath = implode(' / ', $names);
        }
    }
    
    // Log batch sync start
    log_message(
    "🟢 " . l10n('log_sync_started') .
    " (User ID: " . (int)$user['id'] . ") " .
    l10n('log_sync_mode_batch')
    );
    
    log_sync_options_full($_GET, $albumPath);
    
    // Forward to admin GUI with parameters
    $queryParams = [
    'external_run' => '1',
    'album'        => $album,
    'simulate'     => $simulate,
    'onlynew'      => $onlyNew,
    'subalbums'    => $subalbums,
    'steps'        => implode(',', $steps),
    ];
    
    $redirectUrl = get_root_url()
    . 'admin.php?page=plugin-' . basename(__DIR__)
    . '&' . http_build_query($queryParams);
    
    header('Location: ' . $redirectUrl);
    exit;
}

// Reset session progress via GET
if (
isset($_GET['reset_progress'], $_GET['pwg_token']) &&
$_GET['reset_progress'] === '1' &&
$_GET['pwg_token'] === get_pwg_token()
) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Immediately clear all in-memory session arrays
    unset(
    $_SESSION['thumb_progress'],
    $_SESSION['meta_progress'],
    $_SESSION['md5_progress'],
    $_SESSION['video_progress']
    );
    
    // Write a single reset-all flag into the DB
    global $prefixeTable, $user;
    $table  = $prefixeTable . 'album_pilot_settings';
    $userId = (int)$user['id'];
    pwg_query(
    "REPLACE INTO `$table` (user_id, setting_key, setting_value)
    VALUES ($userId, 'reset_all', '1')"
    );
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Start sync via GET
if (
isset($_GET['sync_begin'], $_GET['pwg_token']) &&
$_GET['pwg_token'] === get_pwg_token()
) {
    $userId           = (int)$user['id'];
    $simulate         = isset($_GET['simulate'])   && $_GET['simulate']   === '1';
    $onlyNew          = isset($_GET['onlynew'])    && $_GET['onlynew']    === '1';
    $includeSubalbums = isset($_GET['subalbums'])  && $_GET['subalbums']  === '1';
    
    // Determine album ID from possible keys
    $albumId = null;
    foreach (['album', 'album_id', 'cat_id'] as $key) {
        if (isset($_GET[$key]) && is_numeric($_GET[$key])) {
            $albumId = (int)$_GET[$key];
            break;
        }
    }
    
    // Resolve album path for logging
    $albumPath = '❓ (not set)';
    if ($albumId !== null) {
        $albumData = pwg_db_fetch_assoc(
        pwg_query("SELECT name, uppercats FROM " . CATEGORIES_TABLE . " WHERE id = $albumId")
        );
        
        if ($albumData) {
            $ids   = explode(',', $albumData['uppercats']);
            $names = [];
            
            foreach ($ids as $id) {
                $row = pwg_db_fetch_assoc(
                pwg_query("SELECT name FROM " . CATEGORIES_TABLE . " WHERE id = " . (int)$id)
                );
                
                if ($row) {
                    $names[] = $row['name'];
                }
            }
            
            $albumPath = implode(' / ', $names);
        }
    }
    
    // Normalize thumbnail types
    $thumbTypes = [];
    if (isset($_GET['thumb_types']) && is_string($_GET['thumb_types'])) {
        $thumbTypes = explode(',', $_GET['thumb_types']);
    }
    
    // Collect main sync parameters
    $params = array_merge([
    'simulate'               => $simulate ? '1' : '0',
    'onlynew'                => $onlyNew ? '1' : '0',
    'subalbums'              => $includeSubalbums ? '1' : '0',
    'videojs_create_poster'  => $_GET['videojs_create_poster']  ?? '0',
    'videojs_poster_overwrite'=> $_GET['videojs_poster_overwrite'] ?? '0',
    'videojs_add_overlay'    => $_GET['videojs_add_overlay']    ?? '0',
    'videojs_add_thumbs'     => $_GET['videojs_add_thumbs']     ?? '0',
    'poster_second'          => $_GET['poster_second']          ?? '',
    'thumb_interval'         => $_GET['thumb_interval']         ?? '',
    'thumb_size'             => $_GET['thumb_size']             ?? '',
    'output_format'          => $_GET['output_format']          ?? '',
    ], $_GET);
    
    // Add individual thumbnail flags
    foreach ($thumbTypes as $type) {
        $params['thumb_type_' . trim($type)] = '1';
    }
    
    log_message("🟢 " . l10n('log_sync_started') . " (User ID: $userId)");
    log_sync_options_full($params, $albumPath);
    
    echo json_encode(['ok' => true]);
    exit;
}

// End sync via GET
if (
isset($_GET['sync_end'], $_GET['pwg_token']) &&
$_GET['pwg_token'] === get_pwg_token()
) {
    log_message("🔴 " . l10n('log_sync_ended'));
    echo json_encode(['ok' => true]);
    exit;
}

// Handle settings save via POST
$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (
isset($data['save_sync_settings'], $data['settings']) &&
is_array($data['settings']) &&
is_numeric($user['id'])
) {
    $userId = (int)$user['id'];
    
    foreach ($data['settings'] as $key => $value) {
        $safeKey = pwg_db_real_escape_string($key);
        $safeVal = pwg_db_real_escape_string($value);
        
        $table = $prefixeTable . 'album_pilot_settings';
        pwg_query(
        "REPLACE INTO `$table` (user_id, setting_key, setting_value)
        VALUES ($userId, '$safeKey', '$safeVal')"
        );        
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Default options
$simulate          = !isset($_GET['simulate'])   || $_GET['simulate']   === '1';
$onlyNew           = !isset($_GET['onlynew'])    || $_GET['onlynew']    === '1';
$includeSubalbums  = !isset($_GET['subalbums'])  || $_GET['subalbums']  === '1';

// Helper functions

/**
    * If the global reset-all flag is set for this user, clear it and all progress sessions.
*/
function check_and_clear_reset(): void {
    global $prefixeTable, $user;
    $table  = $prefixeTable . 'album_pilot_settings';
    $userId = (int)$user['id'];
    
    // Fetch the flag
    $row = pwg_db_fetch_assoc(
    pwg_query(
    "SELECT setting_value
    FROM `$table`
    WHERE user_id = $userId
    AND setting_key = 'reset_all'"
    )
    );
    
    if ($row && $row['setting_value'] === '1') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Clear all step-progress
        unset(
        $_SESSION['thumb_progress'],
        $_SESSION['meta_progress'],
        $_SESSION['md5_progress'],
        $_SESSION['video_progress']
        );
        
        // Remove the flag
        pwg_query(
        "DELETE FROM `$table`
        WHERE user_id = $userId
        AND setting_key = 'reset_all'"
        );
    }
}

/**
    * Log a message to the plugin log file, rotating if large.
    *
    * @param string $message
*/
function log_message(string $message): void {
    // Use Piwigo's standard log directory
    $logDir  = PHPWG_ROOT_PATH . '_data/logs/';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }

    $logfile = $logDir . 'album_pilot.log';
    $oldfile = $logDir . 'album_pilot_old.log';
    
    // Rotate log if exceeds 100 MB
    if (file_exists($logfile) && filesize($logfile) > 100 * 1024 * 1024) {
        @rename($logfile, $oldfile);
    }
    
    // Attempt to open log for appending
    $handle = @fopen($logfile, 'a');
    
    if ($handle) {
        fwrite($handle, '[' . date('Y-m-d H:i:s') . "] $message\n");
        fclose($handle);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION['log_write_error_text'], $_SESSION['log_write_error_displayed']);
    } else {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['log_write_error_displayed'])) {
            $_SESSION['log_write_error_text'] =
            '⚠️ ' . l10n('log_write_error') . ' ' .
            sprintf(l10n('log_write_error_path'), $logfile);
            $_SESSION['log_write_error_displayed'] = true;
        }
    }
}

/**
    * Log full sync options.
    *
    * @param array  $params
    * @param string $albumPath
*/
function log_sync_options_full(array $params, string $albumPath): void {
    $boolFlags = [
    'simulate'                => 'simulate_mode',
    'onlynew'                 => 'only_new_files',
    'subalbums'               => 'include_subalbums',
    'thumb_overwrite'         => 'label_thumb_overwrite',
    'videojs_import_uploaded' => 'VideoJS_RepAdd',
    'videojs_create_poster'   => 'VideoJS_AddPoster',
    'videojs_poster_overwrite'=> 'VideoJS_PosterOverwrite',
    'videojs_add_overlay'     => 'VideoJS_OverlayAdd',
    'videojs_add_thumbs'      => 'VideoJS_AddThumb',
    ];
    
    $options = [];
    
    foreach ($boolFlags as $key => $langKey) {
        $value = (isset($params[$key]) && $params[$key] === '1')
        ? l10n('yes')
        : l10n('no');
        
        $options[] = l10n($langKey) . ' = ' . $value;
    }
    
    // Scalar VideoJS settings
    $options[] = l10n('VideoJS_AddPoster')
    . ' = ' . ($params['poster_second'] ?? '?') . ' ' . l10n('VideoJS_PosterSec');
    $options[] = l10n('VideoJS_AddThumb')
    . ' = ' . ($params['thumb_interval'] ?? '?') . ' ' . l10n('VideoJS_ThumbSec');
    $options[] = l10n('VideoJS_ThumbSize')
    . ' = ' . ($params['thumb_size'] ?? '?');
    $options[] = l10n('VideoJS_OutputFormat')
    . ' = ' . strtoupper($params['output_format'] ?? '?');
    
    // Thumbnail types
    $thumbList = [];
    foreach ($params as $key => $val) {
        if (strpos($key, 'thumb_type_') === 0 && $val === '1') {
            $thumbList[] = substr($key, strlen('thumb_type_'));
        }
    }
    
    $options[] = l10n('label_select_thumb_types')
    . ' = [' . implode(', ', $thumbList) . ']';
    
    $options[] = l10n('selected_album')
    . ' = "' . $albumPath . '"';
    
    // Write options log entry
    log_message('📋 ' . l10n('log_sync_options') . ': ' . implode(', ', $options));
}

/**
    * Ensure log file permissions are correct.
*/
function check_logfile_permissions(): void {
    // Use Piwigo's standard log directory
    $logDir  = PHPWG_ROOT_PATH . '_data/logs/';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }

    $logfile = $logDir . 'album_pilot.log';
    
    if (!file_exists($logfile)) {
        @touch($logfile); // Try to create file
    }
    
    if (!is_writable($logfile)) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['log_write_error_displayed'])) {
            $_SESSION['log_write_error_text'] =
            '⚠️ ' . l10n('log_write_error') . ' ' .
            sprintf(l10n('log_write_error_path'), $logfile);
            $_SESSION['log_write_error_displayed'] = true;
        }
    } else {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION['log_write_error_text'], $_SESSION['log_write_error_displayed']);
    }
}

/**
    * Count the total number of images.
    *
    * @return int
*/
function count_total_images(): int {
    $result = pwg_db_fetch_assoc(
    pwg_query("SELECT COUNT(*) AS cnt FROM " . IMAGES_TABLE)
    );
    
    return (int)$result['cnt'];
}



/**
    * Abort if root album without subalbums selected
    *
*/
function abortOnRootNoSubs(int $cat_id, bool $includeSubalbums, array &$log): void {
    if ($cat_id === 0 && !$includeSubalbums) {
        $warning = '⚠️ ' . l10n('select_album_alert');
        log_message($warning);
        echo json_encode([
        'processed' => 0,
        'generated' => 0,
        'offset'    => 0,
        'done'      => true,
        'total'     => 0,
        'log'       => $log,
        'summary'   => $warning,
        ]);
        exit;
    }
}

// Step 1: File synchronization
if (
isset($_GET['wrapped_sync'], $_GET['pwg_token']) &&
$_GET['pwg_token'] === get_pwg_token()
) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    $simulate         = isset($_GET['simulate'])   && $_GET['simulate']   === '1';
    $onlyNew          = isset($_GET['onlynew'])    && $_GET['onlynew']    === '1';
    $includeSubalbums = isset($_GET['subalbums'])  && $_GET['subalbums']  === '1';
    
    log_message("📂 " . l10n('log_sync_step1_start'));
    log_message(
    '⚙️ ' . sprintf(
    l10n('log_sync_step1_options'),
    $simulate
    ? l10n('label_simulate')
    : l10n('label_live'),
    $onlyNew
    ? l10n('label_only_new')
    : l10n('label_all_files'),
    $includeSubalbums
    ? l10n('label_subalbums_yes')
    : l10n('label_subalbums_no')
    )
    );
    
    $before = count_total_images();
    // Simulate POST for site update
    $_POST['submit']           = 'Quick Local Synchronization';
    $_POST['sync']             = 'files';
    $_POST['display_info']     = '1';
    $_POST['add_to_caddie']    = '1';
    $_POST['privacy_level']    = '0';
    $_POST['sync_meta']        = '1';
    $_POST['simulate']         = $simulate ? '1' : '0';
    $_POST['subcats-included'] = $includeSubalbums ? '1' : '0';
    $_POST['only_new']         = $onlyNew ? '1' : '0';
    
    $_GET['site'] = 1;
    
    // Snapshot IDs before sync
    $before_ids = array_column(
    array_from_query("SELECT id FROM " . IMAGES_TABLE),
    'id'
    );
    
    $albumId = isset($_GET['album']) && is_numeric($_GET['album'])
    ? (int)$_GET['album']
    : null;
    
    // If root album selected but “search in subalbums” is OFF, abort without scanning
    if ($albumId === 0 && !$includeSubalbums) {
        echo json_encode([
        'success'    => true,
        'message'    => '⚠️ ' . l10n('select_album_alert'),
        'raw_output' => ''
        ]);
        exit ;
    } 
    
    if ($albumId > 0) {
        $_POST['cat'] = $albumId; // Core uses 'cat_id' to restrict the scan
        $_POST['cat_id'] = $albumId; // Core uses 'cat_id' to restrict the scan
    }
    
        ob_start();
    include(PHPWG_ROOT_PATH . 'admin/site_update.php');
    $output = ob_get_clean();

    // Prefer structured details produced by Piwigo core (admin/site_update.php),
    // then fall back to parsing HTML output if nothing was collected.
    $siteUpdateLines = [];

    $collectLines = function ($val, string $prefix = '') use (&$siteUpdateLines): void {
        if ($val === null) {
            return;
        }
        if (is_string($val)) {
            $val = [$val];
        }
        if (!is_array($val)) {
            return;
        }

        foreach ($val as $item) {
            // Some core structures may be arrays of fragments/titles.
            if (is_array($item)) {
                $item = implode(' – ', array_map('strval', $item));
            }

            $line = trim(html_entity_decode(strip_tags((string)$item), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($line !== '') {
                $siteUpdateLines[] = $prefix . $line;
            }
        }
    };

    // admin/site_update.php collects detailed messages in $infos/$errors.
    if (isset($infos)) {
        $collectLines($infos);
    }
    if (isset($errors)) {
        $collectLines($errors, '❌ ');
    }

    // Some Piwigo contexts additionally use $page['infos'/'warnings'/'errors'].
    if (isset($page) && is_array($page)) {
        if (isset($page['infos'])) {
            $collectLines($page['infos']);
        }
        if (isset($page['warnings'])) {
            $collectLines($page['warnings'], '⚠️ ');
        }
        if (isset($page['errors'])) {
            $collectLines($page['errors'], '❌ ');
        }
    }

    // De-duplicate while keeping order.
    if (!empty($siteUpdateLines)) {
        $seen = [];
        $siteUpdateLines = array_values(array_filter($siteUpdateLines, function ($line) use (&$seen) {
            if (isset($seen[$line])) {
                return false;
            }
            $seen[$line] = true;
            return true;
        }));
    }

    // Fallback: parse HTML output (best-effort) if the structured arrays were empty.
    if (empty($siteUpdateLines)) {
        $siteUpdateHtml = trim((string)$output);

        if ($siteUpdateHtml !== '') {
            if (preg_match_all('~<li[^>]*>(.*?)</li>~is', $siteUpdateHtml, $matches)) {
                foreach ($matches[1] as $liHtml) {
                    $line = trim(html_entity_decode(strip_tags($liHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    if ($line !== '') {
                        $siteUpdateLines[] = $line;
                    }
                }
            }

            if (empty($siteUpdateLines)) {
                $normalized = preg_replace('~<\s*br\s*/?\s*>~i', "\n", $siteUpdateHtml);
                $normalized = preg_replace('~</\s*(p|li|tr|div|h[1-6])\s*>~i', "\n", $normalized);
                $normalized = strip_tags((string)$normalized);
                $decoded    = html_entity_decode($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                $siteUpdateLines = array_values(array_filter(array_map(
                    'trim',
                    preg_split("/\R/u", (string)$decoded)
                )));
            }
        }
    }

    // Prepare UI output: render each line as a sync-step-block to match the existing UI styling.
    $rawOutputHtml = '';
    if (!empty($siteUpdateLines)) {
        $blocks = [];

        foreach ($siteUpdateLines as $line) {
            $blocks[] =
                '<div class="sync-step-block">' .
                htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                '</div>';
        }

        $rawOutputHtml = implode('', $blocks);

        // Mirror details into the AlbumPilot logfile (one entry per line).
        foreach ($siteUpdateLines as $line) {
            log_message('📄 ' . $line);
        }
    }
    
    if ($simulate) {
        $message = l10n('log_sync_step1_simulation_done');
        } else {
        
        // Snapshot IDs after sync
        $after = count_total_images();
        
        $after_ids = array_column(
        array_from_query("SELECT id FROM " . IMAGES_TABLE),
        'id'
        );
        
        // Compare before/after for added and deleted
        $added   = array_diff($after_ids, $before_ids);
        $deleted = array_diff($before_ids, $after_ids);
        
        $added_count   = count($added);
        $deleted_count = count($deleted);
        $delta         = $after - $before;
        
        // AlbumPilot: Build sync summary
        $message = sprintf(
        l10n('log_sync_step1_summary'),
        $added_count,
        $deleted_count,
        $delta,
        $before,
        $after
        );
        log_message($message);
        
    }
    
    echo json_encode([
    'success'    => true,
    'message'    => "📊 $message",
    'raw_output' => $rawOutputHtml,
    ]);
    
    exit;

}

// If a global reset was requested, clear all progress at once
check_and_clear_reset();

// --- Step 2: Metadata update ---
include __DIR__ . '/include/metadata.php';

// --- Step 3: Video processing ---
include __DIR__ . '/include/videos.php';

// --- Step 4: Image thumbnails ---
include __DIR__ . '/include/images.php';

// --- Step 5: Checksum calculation ---
include __DIR__ . '/include/checksum.php';

// Assign full language pack to Smarty
$template->assign('LANG', $lang);

// Prepare frontend JS translations via whitelist.
// Earlier versions used a separator key to cut off the frontend section.
// This approach was abandoned because the automatic translation sorts keys alphabetically,
// which broke the separator logic. 
$lang_frontend = [];
$frontend_keys = [
    'Start_sync',
    'Reset_settings',
    'progress_heading',
    'select_album_alert',
    'select_step_alert',
    'sync_in_progress',
    'leave_warning',
    'all_steps_completed',
    'workflow_finished',
    'simulation_suffix',
    'file_label',
    'step_completed',
    'of',
    'image_id',
    'error_during_step',
    'no_info_found',
    'no_success_message',
    'invalid_response',
    'network_error',
    'thumb_type_label',
    'step_sync_files',
    'step_update_metadata',
    'step_generate_video_posters',
    'step_generate_thumbnails',
    'step_calculate_checksums',
    'step_reassign_smart_albums',
    'step_update_album_metadata',
    'step_update_photo_information',
    'step_optimize_database',
    'step_run_integrity_check',
    'videojs_not_active',
    'smartalbums_not_active',
    'skipped_simulation_mode',
    'step_video',
    'step_thumbnail',
    'step_checksum',
    'step_metadata',
    'reset_error',
    'reset_error_details',
    'label_select_thumb_types',
    'label_thumb_overwrite',
    'VideoJS_RepAdd',
    'VideoJS_AddPoster',
    'VideoJS_PosterSec',
    'VideoJS_PosterOverwrite',
    'VideoJS_OutputFormat',
    'VideoJS_jpg',
    'VideoJS_png',
    'VideoJS_OverlayAdd',
    'VideoJS_AddThumb',
    'VideoJS_ThumbSec',
    'VideoJS_ThumbSize',
    'External_trigger_url',
    'External_trigger_description',
    'Synchronize metadata',
];

foreach ($frontend_keys as $k) {
    if (isset($lang[$k])) {
        $lang_frontend[$k] = $lang[$k];
    }
}


$template->assign('L10N_JS', $lang_frontend);

// Include admin helper functions
include_once(PHPWG_ROOT_PATH . 'admin/include/functions.php');

// Check log file permission issues
check_logfile_permissions();

global $template;

// Fetch categories with directory set
$query  = 'SELECT id, name, uppercats, global_rank FROM ' . CATEGORIES_TABLE . ' WHERE dir IS NOT NULL';
$result = pwg_query($query);

$categories = [];
while ($row = pwg_db_fetch_assoc($result)) {
    $categories[] = $row;
}

// Build category tree
$tree = [];
foreach ($categories as $cat) {
    $parents   = explode(',', $cat['uppercats']);
    $parent_id = count($parents) >= 2 ? $parents[count($parents) - 2] : 0;
    $tree[$parent_id][] = $cat;
}

/**
    * Build HTML select options for album selection.
*/
function build_album_select(int $parent, array $tree, int $depth = 0): string {
    $html = '';
    
    if (isset($tree[$parent])) {
        usort($tree[$parent], 'global_rank_compare');
        foreach ($tree[$parent] as $node) {
            $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $depth);
            $html  .= '<option value="'
            . (int)$node['id'] . '">'
            . $indent . htmlspecialchars($node['name'])
            . '</option>';
            $html  .= build_album_select((int)$node['id'], $tree, $depth + 1);
        }
    }
    
    return $html;
}

// Get label for 'All albums' from language file
$allAlbumsLabel = l10n('all_albums_label');

// Construct album select HTML
$album_select = '<select id="album-list" size="20" style="width:100%; max-width:400px;">'
. '<option value="0">' . htmlspecialchars($allAlbumsLabel) . '</option>'
. build_album_select(0, $tree, 1)
. '</select>';

// Determine active plugins
$result         = pwg_query("SELECT id FROM " . PLUGINS_TABLE . " WHERE state = 'active'");
$active_plugins = [];

while ($row = pwg_db_fetch_assoc($result)) {
    $active_plugins[] = $row['id'];
}

$videojs_active     = in_array('piwigo-videojs', $active_plugins, true);
$smartalbums_active = in_array('SmartAlbums',      $active_plugins, true);

// Dynamic & secure plugin ID retrieval
$plugin_id = basename(dirname(__FILE__));

// Assign variables to template
$template->assign([
'PLUGIN_ROOT_URL'    => get_root_url() . 'plugins/' . $plugin_id . '/',
'PLUGIN_ADMIN_URL'   => get_root_url() . 'admin.php?page=plugin-' . $plugin_id,
'U_SITE_URL'         => get_root_url(),
'ADMIN_TOKEN'        => get_pwg_token(),
'SIMULATE'           => $simulate ? 'true' : 'false',
'ALBUM_SELECT'       => $album_select,
'VIDEOJS_ACTIVE'     => $videojs_active,
'SMARTALBUMS_ACTIVE' => $smartalbums_active,
]);

// Load saved checkbox settings for user
$checkbox_settings = [];

if (isset($user['id']) && is_numeric($user['id'])) {
    // [BEGIN REPLACEMENT: SELECT user settings]
    global $prefixeTable;
    $table = $prefixeTable . 'album_pilot_settings';
    $res   = pwg_query(
    "SELECT setting_key, setting_value FROM `$table` WHERE user_id = " . (int)$user['id']
    );
    // [END REPLACEMENT]
    
    while ($row = pwg_db_fetch_assoc($res)) {
        $checkbox_settings[$row['setting_key']] = $row['setting_value'];
    }
}

$template->assign('SAVED_SYNC_SETTINGS', json_encode($checkbox_settings));

// Inject log write error if exists
if (!empty($_SESSION['log_write_error_text'])) {
    $template->assign('LOG_WRITE_ERROR', $_SESSION['log_write_error_text']);
}

$template->assign('BATCH_MODE_ACTIVE', isset($_GET['external_run']) && $_GET['external_run'] === '1');
$template->set_filename('album_pilot', realpath(ALBUM_PILOT_PATH . 'template/admin.tpl'));
$template->assign_var_from_handle('ADMIN_CONTENT', 'album_pilot');

