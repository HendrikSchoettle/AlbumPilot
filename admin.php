<?php
/*
File: admin.php – AlbumPilot Plugin for Piwigo
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
  while (ob_get_level()) ob_end_clean();
}

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');
if (!defined('ALBUM_PILOT_PATH')) {
  define('ALBUM_PILOT_PATH', dirname(__FILE__) . '/');
}

check_status(ACCESS_ADMINISTRATOR);
include_once(PHPWG_PLUGINS_PATH . 'piwigo-videojs/include/function_frame.php');

// Execute chain of external URLs (sent via POST or GET)
if (isset($_GET['external_batch']) && $_GET['external_batch'] === '1') {
    if (!is_admin()) {
        echo "Access denied – admin login required.";
        exit;
    }

    $album = $_GET['album'] ?? '';
    $simulate = $_GET['simulate'] ?? '0';
    $onlyNew = $_GET['onlynew'] ?? '0';
    $subalbums = $_GET['subalbums'] ?? '0';
    $steps = explode(',', $_GET['steps'] ?? '');
    $token = get_pwg_token();

    // Determine album path for logging
    $albumPath = '(not set)';
    if (is_numeric($album)) {
        $albumId = (int)$album;
        $albumData = pwg_db_fetch_assoc(pwg_query("SELECT name, uppercats FROM " . CATEGORIES_TABLE . " WHERE id = $albumId"));
        if ($albumData) {
            $ids = explode(',', $albumData['uppercats']);
            $names = [];
            foreach ($ids as $id) {
                $r = pwg_db_fetch_assoc(pwg_query("SELECT name FROM " . CATEGORIES_TABLE . " WHERE id = " . (int)$id));
                if ($r) $names[] = $r['name'];
            }
            $albumPath = implode(' / ', $names);
        }
    }

    // Log sync start (batch)
    log_message("🟢 " . l10n('log_sync_started') . " (User ID: " . (int)$user['id'] . ")" . ' ' . l10n('log_sync_mode_batch'));
    log_message("📋 " . l10n('log_sync_options') . ": " .
        l10n('simulate_mode') . " = " . ($simulate === '1' ? l10n('yes') : l10n('no')) . ", " .
        l10n('only_new_files') . " = " . ($onlyNew === '1' ? l10n('yes') : l10n('no')) . ", " .
        l10n('include_subalbums') . " = " . ($subalbums === '1' ? l10n('yes') : l10n('no')) . ", " .
        l10n('selected_album') . " = \"$albumPath\"");

    // Instead of direct execution: forward to GUI with parameters

    $queryParams = [
        'external_run' => '1',
        'album' => $album,
        'simulate' => $simulate,
        'onlynew' => $onlyNew,
        'subalbums' => $subalbums,
        'steps' => implode(',', $steps)
    ];

    $redirectUrl = get_root_url() . 'admin.php?page=plugin-' . basename(__DIR__) . '&' . http_build_query($queryParams);
    header('Location: ' . $redirectUrl);
    exit;
}

// Handle session progress reset request
if (isset($_GET['reset_progress']) && $_GET['reset_progress'] === '1' && $_GET['pwg_token'] === get_pwg_token()) {
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }

  // Clear all session progress keys
  unset($_SESSION['thumb_progress']);
  unset($_SESSION['meta_progress']);
  unset($_SESSION['md5_progress']);
  unset($_SESSION['video_progress']);

  header('Content-Type: application/json');
  echo json_encode(['success' => true]);
  exit;
}

// Handle sync start request
if (isset($_GET['sync_begin']) && $_GET['pwg_token'] === get_pwg_token()) {
  $userId = (int)$user['id'];
  $simulate = isset($_GET['simulate']) && $_GET['simulate'] === '1';
  $onlyNew = isset($_GET['onlynew']) && $_GET['onlynew'] === '1';
  $includeSubalbums = isset($_GET['subalbums']) && $_GET['subalbums'] === '1';

  // Try to determine the album ID from multiple possible keys
  $albumId = null;
  foreach (['album', 'album_id', 'cat_id'] as $key) {
    if (isset($_GET[$key]) && is_numeric($_GET[$key])) {
      $albumId = (int)$_GET[$key];
      break;
    }
  }

  $albumPath = '❓ (not set)';
  if ($albumId) {
    $album = pwg_db_fetch_assoc(pwg_query("SELECT name, uppercats FROM " . CATEGORIES_TABLE . " WHERE id = $albumId"));
    if ($album) {
      $ids = explode(',', $album['uppercats']);
      $names = [];
      foreach ($ids as $id) {
        $r = pwg_db_fetch_assoc(pwg_query("SELECT name FROM " . CATEGORIES_TABLE . " WHERE id = " . (int)$id));
        if ($r) $names[] = $r['name'];
      }
      $albumPath = implode(' / ', $names);
    }
  }
  log_message("🟢 " . l10n('log_sync_started') . " (User ID: $userId)");
  log_message("📋 " . l10n('log_sync_options') . ": " .
              l10n('simulate_mode') . " = " . ($simulate ? l10n('yes') : l10n('no')) . ", " .
              l10n('only_new_files') . " = " . ($onlyNew ? l10n('yes') : l10n('no')) . ", " .
              l10n('include_subalbums') . " = " . ($includeSubalbums ? l10n('yes') : l10n('no')) . ", " .
              l10n('selected_album') . " = \"$albumPath\"");

  echo json_encode(['ok' => true]);
  exit;
}

// Handle sync end request
if (isset($_GET['sync_end']) && $_GET['pwg_token'] === get_pwg_token()) {
  log_message("🔴 " . l10n('log_sync_ended'));
  echo json_encode(['ok' => true]);
  exit;
}

// Create table for user-specific sync settings if not exists
pwg_query("CREATE TABLE IF NOT EXISTS piwigo_album_pilot_settings (
  user_id SMALLINT UNSIGNED NOT NULL,
  setting_key VARCHAR(50) NOT NULL,
  setting_value VARCHAR(255) NOT NULL,
  PRIMARY KEY (user_id, setting_key)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Handle incoming POST request to save settings
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (
  isset($data['save_sync_settings'], $data['settings']) &&
  is_array($data['settings']) &&
  is_numeric($user['id'])
) {
  $userId = (int)$user['id'];
  foreach ($data['settings'] as $key => $value) {
    $key = pwg_db_real_escape_string($key);
    $val = pwg_db_real_escape_string($value);
    pwg_query("REPLACE INTO piwigo_album_pilot_settings (user_id, setting_key, setting_value) VALUES ($userId, '$key', '$val')");
  }

  header('Content-Type: application/json');
  echo json_encode(['success' => true]);
  exit;
}


// Handler for thumbnail generation per image (generate_image_thumbs)
if (
  isset($_GET['generate_image_thumbs'], $_GET['album_id'], $_GET['pwg_token']) &&
  $_GET['pwg_token'] === get_pwg_token()
) {
  if (session_status() === PHP_SESSION_NONE) session_start();

  $simulate = isset($_GET['simulate']) && $_GET['simulate'] === '1';
  $includeSubalbums = isset($_GET['subalbums']) && $_GET['subalbums'] === '1';
  $albumId = (int)$_GET['album_id'];

  include_once(PHPWG_ROOT_PATH . 'include/derivative.inc.php');
  include_once(PHPWG_ROOT_PATH . 'include/derivative_params.inc.php');

  $log = [];

// Step 1: First request - initialize processing queue and store it in session
if (!isset($_SESSION['thumb_progress'])) {
  $msg = l10n('log_scan_missing_thumbs');
  $log[] = "🔍 " . $msg;
  log_message("🔍 " . $msg);

  $albums = [$albumId];
  if ($includeSubalbums) {
    $res = pwg_query('SELECT id FROM ' . CATEGORIES_TABLE . ' WHERE uppercats REGEXP "(^|,)' . $albumId . '(,|$)" AND dir IS NOT NULL');
    while ($row = pwg_db_fetch_assoc($res)) {
      $albums[] = (int)$row['id'];
    }
  }

  $albumsList = implode(',', $albums);
  $images = array_from_query("
    SELECT DISTINCT i.id, i.path, i.file, i.width, i.height, i.rotation
    FROM " . IMAGES_TABLE . " i
    JOIN " . IMAGE_CATEGORY_TABLE . " ic ON i.id = ic.image_id
    WHERE ic.category_id IN ($albumsList)
  ");

  $queue = [];

  foreach ($images as $img) {
    $ext = strtolower(pathinfo($img['path'], PATHINFO_EXTENSION));
    if (!in_array($ext, $conf['picture_ext'])) {
      continue;
    }

    if (empty($img['width']) || empty($img['height']) || $img['width'] <= 0 || $img['height'] <= 0) {
      $msg = sprintf(l10n('log_invalid_dimensions'), $img['id'], $img['path']);
      log_message("⛔ " . $msg);
      $log[] = "⛔ " . $msg;
      continue;
    }

    if (!isset($img['rotation']) || !is_numeric($img['rotation'])) {
      $img['rotation'] = 0;
    }

    try {
      $src = new SrcImage($img);
    } catch (Throwable $e) {
      $msg = sprintf(l10n('log_srcimage_error'), $img['id'], $img['path'], $e->getMessage());
      log_message("❌ " . $msg);
      $log[] = "❌ " . $msg;
      continue;
    }

    $derivsToGenerate = [];
    try {
      foreach (DerivativeImage::get_all($src) as $type => $deriv) {
        if (!$deriv->is_cached()) {
          $derivsToGenerate[$type] = $deriv;
        }
      }
    } catch (Throwable $e) {
      $msg = sprintf(l10n('log_derivative_error'), $img['id'], $img['path'], $e->getMessage());
      log_message("❌ " . $msg);
      $log[] = "❌ " . $msg;
      continue;
    }

    if (empty($derivsToGenerate)) {
      continue;
    }

    if (empty($img['width']) || empty($img['height'])) {
      $fullPath = PHPWG_ROOT_PATH . $img['path'];
      if (!file_exists($fullPath)) {
        $msg = sprintf(l10n('log_file_missing'), $img['id'], $img['path']);
        log_message("❌ " . $msg);
        $log[] = "❌ " . $msg;
        continue;
      }

      $sizeData = @getimagesize($fullPath);
      if ($sizeData === false) {
        $msg = sprintf(l10n('log_getimagesize_error'), $img['id'], $img['path']);
        log_message("❌ " . $msg);
        $log[] = "❌ " . $msg;
        continue;
      }

      $img['width'] = $sizeData[0];
      $img['height'] = $sizeData[1];
    }

    $origWidth = $img['width'];
    $origHeight = $img['height'];

    foreach ($derivsToGenerate as $type => $deriv) {
      try {
        list($targetWidth, $targetHeight) = $deriv->get_size();
      } catch (Throwable $e) {
        $msg = sprintf(l10n('log_get_target_size_error'), $type, $img['id'], $img['path'], $e->getMessage());
        log_message("❌ " . $msg);
        $log[] = "❌ " . $msg;
        continue;
      }

      if ($origWidth >= $targetWidth && $origHeight >= $targetHeight) {
        $queue[] = [
          'img'   => $img,
          'type'  => $type,
          'deriv' => $deriv
        ];
      } else {
        $msg = sprintf(
          l10n('log_image_too_small'),
          $type,
          $img['id'],
          $img['path'],
          $origWidth,
          $origHeight,
          $targetWidth,
          $targetHeight
        );
        log_message("⛔ " . $msg);
        $log[] = "⛔ " . $msg;
      }
    }
  }

  $_SESSION['thumb_progress'] = [
    'albumId' => $albumId,
    'albums' => $albums,
    'queue' => $queue,
    'index' => 0,
    'generated' => 0,
    'totalThumbnails' => count($queue),
    'simulate' => $simulate
  ];

  $countMsg = sprintf(l10n('log_total_thumbs_to_generate'), count($queue));
  $log[] = "🧮 " . $countMsg;
  log_message("🧮 " . $countMsg);
}


  $prog = &$_SESSION['thumb_progress'];
  $queue = &$prog['queue'];
  $index = &$prog['index'];
  $blockGenerated = 0;
  $simulate = !empty($prog['simulate']);

  $steps = 1;

  while ($index < count($queue) && $blockGenerated < $steps) {
    $item = $queue[$index];
    $img = $item['img'];
    $type = $item['type'];
    $deriv = $item['deriv'];

    if (!$deriv->is_cached()) {
      if (!$simulate) {
        $url = get_base_url() . '/' . ltrim($deriv->get_url(), '/');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_COOKIE, 'pwg_id=' . $_COOKIE['pwg_id']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        curl_close($ch);
      }

      $prog['generated']++;
      $blockGenerated++;
      $percent = $prog['totalThumbnails'] > 0
        ? floor($prog['generated'] / $prog['totalThumbnails'] * 100)
        : 100;

      $log[] = [
        'type'       => 'progress',
        'step'       => 'thumbnail',
        'index'      => $prog['generated'],
        'total'      => $prog['totalThumbnails'],
        'percent'    => $percent,
        'image_id'   => $img['id'],
        'simulate'   => $simulate,
        'path'       => $img['path'],
        'thumb_type' => $type
      ];

      
      $logLine = sprintf(
        l10n('log_thumb_progress_line'),
        $prog['generated'],
        $prog['totalThumbnails'],
        $percent,
        $img['id'],
        ($simulate ? l10n('simulation_suffix') : ''),
        $type,
        $img['path']
      );
		 
				 
      log_message($logLine);
    }

    $index++;
  }

  $done = $index >= count($queue);
  $summary = '';

  if ($done) {
    $summary = "✅ " . sprintf(
      l10n('log_step_completed_with_count'),
      l10n('step_generate_thumbnails'),
      $prog['generated'],
      l10n('step_thumbnail')
    );
    log_message($summary);
    unset($_SESSION['thumb_progress']);
  }

  header('Content-Type: application/json');
  echo json_encode([
    'processed' => $blockGenerated,
    'generated' => $prog['generated'],
    'offset'    => $index,
    'done'      => $done,
    'total'     => $prog['totalThumbnails'],
    'log'       => $log,
    'summary'   => $summary
  ]);
  exit;
}

// === Options ===
$simulate = !isset($_GET['simulate']) || $_GET['simulate'] === '1';
$onlyNew = !isset($_GET['onlynew']) || $_GET['onlynew'] === '1';
$includeSubalbums = !isset($_GET['subalbums']) || $_GET['subalbums'] === '1';



// --- Helper functions ---
function log_message($message) {
  $logfile = ALBUM_PILOT_PATH . 'album_pilot.log';
  $oldfile = ALBUM_PILOT_PATH . 'album_pilot_old.log';

  // Rotate log file if it exceeds 100 MB
  if (file_exists($logfile) && filesize($logfile) > 100 * 1024 * 1024) {
    @rename($logfile, $oldfile); // overwrite if old file exists
  }

  // Try to open the log file for appending
  $loghandle = @fopen($logfile, 'a');

  if ($loghandle) {
    fwrite($loghandle, '[' . date('Y-m-d H:i:s') . "] $message\n");
    fclose($loghandle);
  } else {
    // Cannot write to log file – store message to show once in session
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (empty($_SESSION['log_write_error_displayed'])) {
      $_SESSION['log_write_error_text'] =
        l10n('log_write_error') . ' ' . sprintf(l10n('log_write_error_path'), $logfile);
      $_SESSION['log_write_error_displayed'] = true;
    }
  }
}

function count_total_images() {
  $result = pwg_db_fetch_assoc(pwg_query("SELECT COUNT(*) AS cnt FROM " . IMAGES_TABLE));
  return (int)$result['cnt'];
}

// Optional: insert/update VideoJS metadata after poster creation
function extract_video_metadata($video_path) {
  $cmd = "ffprobe -v quiet -print_format json -show_streams " . escapeshellarg($video_path);
  exec($cmd, $output, $retval);
  if ($retval !== 0) return [];

  $json = json_decode(implode("\n", $output), true);
  foreach ($json['streams'] as $stream) {
    if ($stream['codec_type'] === 'video') {
      return [
        'width' => $stream['width'] ?? null,
        'height' => $stream['height'] ?? null,
        'duration' => isset($stream['duration']) ? round($stream['duration']) : 0
      ];
    }
  }
  return [];
}

function insert_videojs_metadata($image_id, $video_path) {
  $meta = extract_video_metadata($video_path);
  if (empty($meta)) return;

  $metadata = [
    'DurationSeconds' => $meta['duration'],
    'width' => $meta['width'],
    'height' => $meta['height'],
    'filesize' => @filesize($video_path),
    'date_creation' => date('Y-m-d'),
  ];

  $serialized = pwg_db_real_escape_string(serialize($metadata));
  $query = "INSERT INTO " . $GLOBALS['prefixeTable'] . "image_videojs (id, metadata, date_metadata_update)
            VALUES ($image_id, '$serialized', NOW())
            ON DUPLICATE KEY UPDATE metadata = '$serialized', date_metadata_update = NOW()";
  pwg_query($query);
}

// --- Special: Update metadata  ---
if (
  isset($_GET['update_metadata_for_album'], $_GET['pwg_token']) &&
  $_GET['pwg_token'] === get_pwg_token()
) {
  while (ob_get_level()) ob_end_clean();
  header('Content-Type: application/json');

  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }

  $simulate = isset($_GET['simulate']) && $_GET['simulate'] === '1';
  $albumId = (int)$_GET['update_metadata_for_album'];
  $includeSubalbums = isset($_GET['subalbums']) && $_GET['subalbums'] === '1';

  // 🔧 Number of images per chunk
  $chunkSize = 10;

  $log = [];

  // Step 1: First request - collect items to process and store them in the session
  if (!isset($_SESSION['meta_progress'])) {
    $albums = [$albumId];
    if ($includeSubalbums) {
      $res = pwg_query('SELECT id FROM ' . CATEGORIES_TABLE . ' WHERE uppercats REGEXP "(^|,)' . $albumId . '(,|$)" AND dir IS NOT NULL');
      while ($row = pwg_db_fetch_assoc($res)) {
        $albums[] = (int)$row['id'];
      }
    }

    $albumsList = implode(',', $albums);
    $images = array_from_query("
      SELECT DISTINCT i.id, i.path
      FROM " . IMAGES_TABLE . " i
      JOIN " . IMAGE_CATEGORY_TABLE . " ic ON i.id = ic.image_id
      WHERE ic.category_id IN ($albumsList)
    ");

    $_SESSION['meta_progress'] = [
      'queue'     => $images,
      'index'     => 0,
      'updated'   => 0,
      'total'     => count($images),
      'simulate'  => $simulate
    ];

    $msg1 = "🔍 " . l10n('log_metadata_scan_start');
    $msg2 = "🧮 " . sprintf(l10n('log_total_images_to_process'), count($images));
    log_message($msg1);
    log_message($msg2);
    $log[] = $msg1;
    $log[] = $msg2;

    echo json_encode([
      'processed' => 0,
      'updated'   => 0,
      'offset'    => 0,
      'done'      => false,
      'total'     => count($images),
      'log'       => $log
    ]);
    exit;
  }

  // Step 2: Follow-up requests - process the next batch of items from the queue
  $prog = &$_SESSION['meta_progress'];
  $queue = &$prog['queue'];
  $index = &$prog['index'];
  $updated = &$prog['updated'];
  $total = $prog['total'];
  $simulate = $prog['simulate'];

  $processed = 0;
  $batchIds = [];

  while ($index < $total && $processed < $chunkSize) {
    $img = $queue[$index];
    $imageId = (int)$img['id'];
    $path = $img['path'];

    $batchIds[] = $imageId;

    $log[] = [
      'type'     => 'progress',
      'step'     => 'metadata',
      'index'    => $index + 1,
      'total'    => $total,
      'percent'  => floor(($index + 1) / $total * 100),
      'image_id' => $imageId,
      'simulate' => $simulate,
      'path'     => $path
    ];

    $logLine = sprintf(
      l10n('log_metadata_progress_line'),
      $index + 1,
      $total,
      $imageId,
      ($simulate ? l10n('simulation_suffix') : ''),
      $path
    );
    log_message("📸 " . $logLine);


    $index++;
    $processed++;
  }

  if (!$simulate && !empty($batchIds)) {
    include_once(PHPWG_ROOT_PATH . 'admin/include/functions_metadata.php');
    sync_metadata($batchIds);
  }

  $updated += count($batchIds);
  $prog['index'] = $index;
  $prog['updated'] = $updated;

  $done = $index >= $total;
  $summary = '';

  if ($done) {    
    $summary = "✅ " . sprintf(
      l10n('log_step_completed_with_count'),
      l10n('step_update_metadata'),
      $updated,
      l10n('step_metadata')
    );

	log_message($summary);
    unset($_SESSION['meta_progress']);
  }
  
  echo json_encode([
    'processed' => $processed,
    'updated'   => $updated,
    'offset'    => $index,
    'done'      => $done,
    'total'     => $total,
    'log'       => $log,
    'summary'   => $summary
  ]);
  exit;
}

// --- Special: Checksum calculation ---
if (isset($_GET['calculate_md5'], $_GET['pwg_token']) && $_GET['pwg_token'] === get_pwg_token()) {
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }

  header('Content-Type: application/json');

  $chunkSize = 10;
  $simulate = isset($_GET['simulate']) && $_GET['simulate'] === '1';
  $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
  $albumId = isset($_GET['album_id']) ? (int)$_GET['album_id'] : 0;
  $includeSubalbums = isset($_GET['subalbums']) && $_GET['subalbums'] === '1';

  if (!$albumId) {
    $msg = l10n('log_md5_no_album');
    echo json_encode([
      'done' => true,
      'log' => "❌ " . $msg,
      'summary' => "❌ " . $msg
    ]);
    exit;
  }

  // === 1. Initial call: Build list of missing MD5s ===
  if (!isset($_SESSION['md5_progress'])) {
    include_once(PHPWG_ROOT_PATH . 'admin/site_reader_local.php');
    $site_reader = new LocalSiteReader('./');

    $albums = [$albumId];
    if ($includeSubalbums) {
      $res = pwg_query('SELECT id FROM ' . CATEGORIES_TABLE . ' WHERE uppercats REGEXP "(^|,)' . $albumId . '(,|$)" AND dir IS NOT NULL');
      while ($row = pwg_db_fetch_assoc($res)) {
        $albums[] = (int)$row['id'];
      }
    }

    $albumsList = implode(',', $albums);
    $files = array_from_query("
      SELECT i.id, i.path
      FROM " . IMAGES_TABLE . " i
      JOIN " . IMAGE_CATEGORY_TABLE . " ic ON i.id = ic.image_id
      WHERE ic.category_id IN ($albumsList)
    ");

    $queue = [];
    foreach ($files as $info) {
      $id = (int)$info['id'];
      $existing = pwg_db_fetch_assoc(pwg_query("SELECT md5sum FROM " . IMAGES_TABLE . " WHERE id = $id"));
      if (empty($existing['md5sum'])) {
        $queue[] = ['id' => $id, 'path' => $info['path']];
      }
    }

    $total = count($queue);
    $_SESSION['md5_progress'] = [
      'queue'     => $queue,
      'index'     => 0,
      'generated' => 0,
      'total'     => $total,
      'simulate'  => $simulate
    ];

    $scanStartMsg = "🔍 " . l10n('log_md5_scan_start');
    $log[] = $scanStartMsg;
    log_message($scanStartMsg);

    $countMsg = "🧮 " . sprintf(l10n('log_md5_total_to_calculate'), $total);
    $log[] = $countMsg;
    log_message($countMsg);
	
	echo json_encode([
      'processed' => 0,
      'generated' => 0,
      'offset'    => 0,
      'done'      => false,
      'total'     => $total,
      'log'       => $log,
      'summary'   => ''
    ]);

    exit;
  }

  // === 2. Follow-up call: Continue processing ===
$prog      = &$_SESSION['md5_progress'];
$queue     = &$prog['queue'];
$index     = &$prog['index'];
$generated = &$prog['generated'];
$total     = $prog['total'];
$simulate  = $prog['simulate'];

if ($offset > $index) {
  $index = $offset;
}

$log = [];
$processed = 0;
$summary = '';

while ($index < $total && $processed < $chunkSize) {
  $item = $queue[$index];
  $filePath = PHPWG_ROOT_PATH . $item['path'];
  $index++;
  $processed++;

  if (!file_exists($filePath)) {
    $log[] = sprintf("❌ [%d/%d] %s", $index, $total, sprintf(l10n('log_md5_file_missing'), $item['path']));
    continue;
  }

  $md5 = @md5_file($filePath);
  if (!$md5) {
    $log[] = sprintf("⚠️ [%d/%d] %s", $index, $total, sprintf(l10n('log_md5_calc_error'), $item['path']));
    continue;
  }

  if (!$simulate) {
    pwg_query("UPDATE " . IMAGES_TABLE . " SET md5sum = '" . pwg_db_real_escape_string($md5) . "' WHERE id = " . (int)$item['id']);
  }

  $generated++;
  $prog['index'] = $index;

  $percent = $total > 0 ? floor($index / $total * 100) : 100;
  $log[] = [
    'type'     => 'progress',
    'step'     => 'checksum',
    'index'    => $index,
    'total'    => $total,
    'percent'  => $percent,
    'image_id' => $item['id'],
    'simulate' => $simulate,
    'path'     => $item['path']
  ];

  $logLine = sprintf(
    l10n('log_md5_progress_line'),
    $index,
    $total,
    $percent,
    $item['id'],
    ($simulate ? l10n('simulation_suffix') : ''),
    $item['path']
  );
  log_message("🖼️ " . $logLine);
}

$done = $index >= $total;
if ($done) {
  $summary = "✅ " . sprintf(
    l10n('log_step_completed_with_count'),
    l10n('step_calculate_checksums'),
    $generated,
    l10n('step_checksum')
  );

  log_message($summary); 
  unset($_SESSION['md5_progress']);
}

echo json_encode([
  'processed' => $processed,
  'generated' => $generated,
  'offset'    => $index,
  'done'      => $done,
  'total'     => $total,
  'log'       => $log,
  'summary'   => $summary
]);
exit;
}
// Utility: Detect base URL automatically
function get_base_url() {
  $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $script_dir = dirname($_SERVER['SCRIPT_NAME']); // e.g., /piwigo/plugins/AlbumPilot
  $base = preg_replace('#/plugins/.*#', '', $script_dir); // remove /plugins/...
  return rtrim($protocol . '://' . $host . $base, '/');
}

// --- Special: Generate missing video thumbnails (posters) ---
if (
  isset($_GET['video_thumb_block'], $_GET['cat_id'], $_GET['pwg_token']) &&
  $_GET['pwg_token'] === get_pwg_token()
) {
  while (ob_get_level()) ob_end_clean();
  header('Content-Type: application/json');

  if (session_status() === PHP_SESSION_NONE) session_start();

  global $conf;

  $cat_id = (int)$_GET['cat_id'];
  $simulate = isset($_GET['simulate']) && $_GET['simulate'] === '1';
  $includeSubalbums = isset($_GET['subalbums']) && $_GET['subalbums'] === '1';
  $log = [];

  $newAlbumList = [$cat_id];
  if ($includeSubalbums) {
    $res = pwg_query('SELECT id FROM ' . CATEGORIES_TABLE . ' WHERE uppercats REGEXP "(^|,)' . $cat_id . '(,|$)" AND dir IS NOT NULL');
    while ($row = pwg_db_fetch_assoc($res)) {
      $id = (int)$row['id'];
      if (!in_array($id, $newAlbumList)) {
        $newAlbumList[] = $id;
      }
    }
  }
  sort($newAlbumList);

  if (
    !isset($_SESSION['video_progress']) ||
    $_SESSION['video_progress']['albumId'] !== $cat_id ||
    $_SESSION['video_progress']['simulate'] !== $simulate ||
    $_SESSION['video_progress']['albums'] !== $newAlbumList
  ) {
    $upload_dir = $conf['upload_dir'] ?? 'upload';
    $video_extensions = $conf['video_ext'] ?? ['mp4', 'mov', 'avi', 'mkv'];
    $missingPosters = [];

    $results = array_from_query('
      SELECT i.id, i.path
      FROM ' . IMAGES_TABLE . ' i
      JOIN ' . IMAGE_CATEGORY_TABLE . ' ic ON i.id = ic.image_id
      WHERE ic.category_id IN (' . implode(',', $newAlbumList) . ')
    ');

    foreach ($results as $img) {
      $ext = strtolower(pathinfo($img['path'], PATHINFO_EXTENSION));
      if (!in_array($ext, $video_extensions)) continue;

      $filename = (strpos($img['path'], 'galleries/') === 0 || strpos($img['path'], '/galleries/') !== false)
        ? PHPWG_ROOT_PATH . $img['path']
        : PHPWG_ROOT_PATH . $upload_dir . '/' . $img['path'];

      if (!file_exists($filename)) continue;

      $poster = dirname($filename) . '/pwg_representative/' . basename($filename, '.' . $ext) . '.jpg';
      if (!file_exists($poster)) {
        $missingPosters[] = $img;
      }
    }
    
    $log[] = "🔍 " . l10n('log_video_scan_start');
    log_message("🔍 " . l10n('log_video_scan_start'));

    $posterCount = count($missingPosters);
    $countMsg = sprintf(l10n('log_video_total_to_generate'), $posterCount);
    $countLine = "🧮 " . $countMsg;
    log_message($countLine);
    $log[] = $countLine;
  
    if ($posterCount === 0) {
      unset($_SESSION['video_progress']);

      $summary = "✅ " . sprintf(
        l10n('log_step_completed_with_count'),
        l10n('step_generate_video_posters'),
        0,
        l10n('step_video')
      );
      log_message($summary);

      echo json_encode([
        'processed' => 0,
        'generated' => 0,
        'offset'    => 0,
        'done'      => true,
        'total'     => 0,
        'log'       => $log,
        'summary'   => $summary
      ]);
      exit;
    }

    $_SESSION['video_progress'] = [
      'albumId'   => $cat_id,
      'albums'    => $newAlbumList,
      'images'    => $missingPosters,
      'index'     => 0,
      'generated' => 0,
      'total'     => $posterCount,
      'simulate'  => $simulate
    ];
  }


  $prog = &$_SESSION['video_progress'];
  $images = &$prog['images'];
  $index = &$prog['index'];
  $total = $prog['total'];
  $simulate = $prog['simulate'];
  $generated = &$prog['generated'];
  $processed = 0;

  if ($index < $total) {
    $img = $images[$index];
    $upload_dir = $conf['upload_dir'] ?? 'upload';

    $filename = (strpos($img['path'], 'galleries/') === 0 || strpos($img['path'], '/galleries/') !== false)
      ? PHPWG_ROOT_PATH . $img['path']
      : PHPWG_ROOT_PATH . $upload_dir . '/' . $img['path'];

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $video_extensions = $conf['video_ext'] ?? ['mp4', 'mov', 'avi', 'mkv'];

    if (file_exists($filename) && in_array($ext, $video_extensions)) {
      $posterDir = dirname($filename) . '/pwg_representative/';
      if (!is_dir($posterDir)) mkdir($posterDir, 0755, true);
      $poster = $posterDir . basename($filename, '.' . $ext) . '.jpg';

      if (!file_exists($poster)) {
        $processed++;
        $prog['generated']++;

        $percent = $prog['total'] > 0 ? floor($prog['generated'] / $prog['total'] * 100) : 100;

        $logLine = sprintf(
          l10n('log_video_progress_line'),
          $prog['generated'],
          $prog['total'],
          $percent,
          $img['id'],
          ($simulate ? l10n('simulation_suffix') : ''),
          $img['path']
        );
        log_message("🖼️ " . $logLine);

        $log[] = [
          'type'     => 'progress',
          'step'     => 'video',
          'index'    => $prog['generated'],
          'total'    => $prog['total'],
          'percent'  => $percent,
          'image_id' => $img['id'],
          'simulate' => $simulate,
          'path'     => $img['path']
        ];

        if (!$simulate) {
          $cmd = 'ffmpeg -ss 4 -i "' . $filename . '" -vcodec mjpeg -vframes 1 -an -f rawvideo -y "' . $poster . '" 2>&1';
          exec($cmd);

          // After successful poster generation, insert/update videojs metadata
          insert_videojs_metadata((int)$img['id'], $filename);

          if (function_exists('add_movie_frame')) {
            $testImage = @imagecreatefromjpeg($poster);
            if ($testImage === false) {
              $msg = "❌ " . sprintf(l10n('log_video_unreadable_poster'), $poster);
              log_message($msg);
              $log[] = $msg;
              $index++;
              echo json_encode([
                'processed' => $processed,
                'generated' => $generated,
                'offset'    => $index,
                'done'      => false,
                'total'     => $total,
                'log'       => $log
              ]);
              exit;
            }
            imagedestroy($testImage);

            ob_start();
            $success = @add_movie_frame($poster);
            $output = trim(ob_get_clean());

            if (!$success) {
              $errorDetails = error_get_last();
              $errorText = $errorDetails['message'] ?? l10n('log_video_unknown_gd_error');
              log_message("❌ " . sprintf(l10n('log_video_add_frame_failed'), $poster));
              log_message("🛠️ " . sprintf(l10n('log_video_error_details'), $errorText));
              if (!empty($output)) {
                log_message("🧾 " . sprintf(l10n('log_video_output'), $output));
              }
            }
          }

          $check = pwg_db_fetch_assoc(pwg_query("SELECT representative_ext FROM " . IMAGES_TABLE . " WHERE id = " . (int)$img['id']));
          if (empty($check['representative_ext'])) {
            pwg_query("UPDATE " . IMAGES_TABLE . " SET representative_ext = 'jpg' WHERE id = " . (int)$img['id']);
          }
        }
      }
    }

    $index++;
  }

  $done = $index >= $total;
  if ($done) {
    unset($_SESSION['video_progress']);    
	$summary = "✅ " . sprintf(
      l10n('log_step_completed_with_count'),
      l10n('step_generate_video_posters'),
      $generated,
      l10n('step_video')
    );

    log_message($summary); 
  }

  echo json_encode([
    'processed' => $processed,
    'generated' => $generated,
    'offset'    => $index,
    'done'      => $done,
    'total'     => $total,
    'log'       => $log
  ]);
  exit;
}

// --- Step 1 – Sync files ---
if (isset($_GET['wrapped_sync'], $_GET['pwg_token']) && $_GET['pwg_token'] === get_pwg_token()) {
  while (ob_get_level()) ob_end_clean();
  header('Content-Type: application/json; charset=utf-8');

  $simulate = isset($_GET['simulate']) && $_GET['simulate'] === '1';
  $onlyNew = isset($_GET['onlynew']) && $_GET['onlynew'] === '1';
  $includeSubalbums = isset($_GET['subalbums']) && $_GET['subalbums'] === '1';

  log_message("📂 " . l10n('log_sync_step1_start'));
  log_message("⚙️ " . sprintf(
    l10n('log_sync_step1_options'),
    $simulate ? l10n('label_simulate') : l10n('label_live'),
    $onlyNew ? l10n('label_only_new') : l10n('label_all_files'),
    $includeSubalbums ? l10n('label_subalbums_yes') : l10n('label_subalbums_no')
  ));

  $before = count_total_images();
  log_message(sprintf(l10n('log_sync_step1_before_count'), $before));

  // Simulate POST for site_update.php
  $_POST['submit'] = 'Quick Local Synchronization';
  $_POST['sync'] = 'files';
  $_POST['display_info'] = '1';
  $_POST['add_to_caddie'] = '1';
  $_POST['privacy_level'] = '0';
  $_POST['sync_meta'] = '1';
  $_POST['simulate'] = $simulate ? '1' : '0';
  $_POST['subcats-included'] = $includeSubalbums ? '1' : '0';
  $_POST['only_new'] = $onlyNew ? '1' : '0';

  if (isset($_GET['album'])) {
    $_POST['cat'] = (int)$_GET['album'];
  }

  $_GET['site'] = 1;

  ob_start();
  include(PHPWG_ROOT_PATH . 'admin/site_update.php');
  $output = ob_get_clean();

  if ($simulate) {
    $message = l10n('log_sync_step1_simulation_done');
  } else {
    $after = count_total_images();
    $diff = $after - $before;
    log_message(sprintf(l10n('log_sync_step1_after_count'), $after, $diff));
    $message = sprintf(l10n('log_sync_step1_summary'), $diff, $before, $after);
  }

  echo json_encode([
    'success' => true,
    'message' => "📊 $message",
    'raw_output' => nl2br(htmlspecialchars(trim($output)))
  ]);
  exit;
}

load_language('plugin.lang', ALBUM_PILOT_PATH);

// Full language pack for Smarty
$template->assign('LANG', $lang);

// Reduced language pack for frontend JS (until 'end_frontend_section')
$lang_frontend = array();
foreach ($lang as $key => $value) {
  if ($key === 'end_frontend_section') {
    break;
  }
  $lang_frontend[$key] = $value;
}

$template->assign('L10N_JS', $lang_frontend);


include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

global $template;

$query = 'SELECT id, name, uppercats FROM ' . CATEGORIES_TABLE . ' WHERE dir IS NOT NULL ORDER BY global_rank ASC';
$result = pwg_query($query);
$categories = [];
while ($row = pwg_db_fetch_assoc($result)) {
  $categories[] = $row;
}

// Tree structure
$tree = [];
foreach ($categories as $cat) {
  $parents = explode(',', $cat['uppercats']);
  $parent_id = (count($parents) >= 2) ? $parents[count($parents) - 2] : 0;
  $tree[$parent_id][] = $cat;
}

function build_album_select($parent, $tree, $depth=0) {
  $html = '';
  if (isset($tree[$parent])) {
    usort($tree[$parent], fn($a, $b) => strcmp($a['name'], $b['name']));
    foreach ($tree[$parent] as $node) {
      $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $depth);
      $html .= '<option value="' . (int)$node['id'] . '">' . $indent . htmlspecialchars($node['name']) . '</option>';
      $html .= build_album_select($node['id'], $tree, $depth+1);
    }
  }
  return $html;
}

$album_select = '<select id="album-list" size="20" style="width:100%; max-width:400px;">' . build_album_select(0, $tree) . '</select>';

$result = pwg_query("SELECT id FROM " . PLUGINS_TABLE . " WHERE state = 'active'");
$active_plugins = [];
while ($row = pwg_db_fetch_assoc($result)) {
  $active_plugins[] = $row['id'];
}

$videojs_active = in_array('piwigo-videojs', $active_plugins, true);
$smartalbums_active = in_array('SmartAlbums', $active_plugins, true);

$plugin_id = basename(dirname(__FILE__)); // Dynamisch & sicher

$template->assign([
  'PLUGIN_ROOT_URL'    => get_root_url() . 'plugins/' . $plugin_id . '/',
  'PLUGIN_ADMIN_URL'   => get_root_url() . 'admin.php?page=plugin-' . $plugin_id,
  'U_SITE_URL'         => get_root_url(),
  'ADMIN_TOKEN'        => get_pwg_token(),
  'SIMULATE'           => $simulate ? 'true' : 'false',
  'ALBUM_SELECT'       => $album_select,
  'VIDEOJS_ACTIVE'     => $videojs_active,
  'SMARTALBUMS_ACTIVE' => $smartalbums_active
]);

// Load saved checkbox values for the logged-in user
$checkbox_settings = [];
if (isset($user['id']) && is_numeric($user['id'])) {
  $res = pwg_query("SELECT setting_key, setting_value FROM piwigo_album_pilot_settings WHERE user_id = " . (int)$user['id']);
  while ($row = pwg_db_fetch_assoc($res)) {
    $checkbox_settings[$row['setting_key']] = $row['setting_value'];
  }
}

// Pass to the template
$template->assign('SAVED_SYNC_SETTINGS', json_encode($checkbox_settings));

$template->set_filename('album_pilot', realpath(ALBUM_PILOT_PATH . 'template/admin.tpl'));
$template->assign_var_from_handle('ADMIN_CONTENT', 'album_pilot');
?>
