<?php
/*
File: include/images.php – AlbumPilot Plugin for Piwigo - Images handler
Author: Hendrik Schöttle
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
*/

// Utility: Detect base URL automatically
function get_base_url() {
    $protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host       = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script_dir = dirname($_SERVER['SCRIPT_NAME']); // e.g., /piwigo/plugins/AlbumPilot
    $base       = preg_replace('#/plugins/.*#', '', $script_dir); // remove /plugins/...
    return rtrim($protocol . '://' . $host . $base, '/');
}

/**
 * Handle thumbnail generation for images and video posters.
 *
 * Expects GET parameters:
 *  - generate_image_thumbs: flag to trigger
 *  - album_id: ID of the album
 *  - pwg_token: CSRF token
 *  - simulate (optional): '1' to skip actual generation
 *  - thumb_overwrite (optional): '1' to force overwrite
 *  - thumb_types (optional): comma-separated list of types
 *  - subalbums (optional): '1' to include subalbums
 *
 * Returns JSON with progress and log entries.
 */
if (
    isset($_GET['generate_image_thumbs'], $_GET['album_id'], $_GET['pwg_token'])
    && $_GET['pwg_token'] === get_pwg_token()
) {
    if (session_status() === PHP_SESSION_NONE) {
    }

    header('Content-Type: application/json');

    $simulate         = isset($_GET['simulate']) && $_GET['simulate'] === '1';
    $overwriteThumbs  = (!empty($_GET['thumb_overwrite']) && $_GET['thumb_overwrite'] === '1');

    // Parse selected thumbnail types from request
    $allowedTypes = [];
    if (isset($_GET['thumb_types']) && is_string($_GET['thumb_types'])) {
        $allowedTypes = explode(',', $_GET['thumb_types']);
    }

    $includeSubalbums = isset($_GET['subalbums']) && $_GET['subalbums'] === '1';
    $albumId         = (int) $_GET['album_id'];
    $log = [];
	
    // If root album selected but “search in subalbums” is OFF, abort without scanning
	abortOnRootNoSubs($albumId, $includeSubalbums, $log);


    include_once(PHPWG_ROOT_PATH . 'include/derivative.inc.php');
    include_once(PHPWG_ROOT_PATH . 'include/derivative_params.inc.php');

    // If root album selected but “search in subalbums” is OFF, abort without scanning
	abortOnRootNoSubs($albumId, $includeSubalbums, $log);
		
    // Step 1: Initial request – build processing queue and store in session
    if (!isset($_SESSION['thumb_progress'])) {
        // Log scan start
        $msg = l10n('log_scan_missing_thumbs');
        $log[]      = '🔍 ' . $msg;
        log_message('🔍 ' . $msg);

        
        // Gather albums (special case: root album + subalbums = all albums)
        if ($albumId === 0 && $includeSubalbums) {
            $albums = [];
            $res = pwg_query(
                'SELECT id FROM ' . CATEGORIES_TABLE . ' WHERE dir IS NOT NULL'
            );
            while ($row = pwg_db_fetch_assoc($res)) {
                $albums[] = (int) $row['id'];
            }
        }
        else {
            $albums = [$albumId];
            if ($includeSubalbums) {
                $res = pwg_query(
                    'SELECT id FROM ' . CATEGORIES_TABLE .
                    ' WHERE uppercats REGEXP "(^|,)' . $albumId . '(,|$)" AND dir IS NOT NULL'
                );
                while ($row = pwg_db_fetch_assoc($res)) {
                    $albums[] = (int) $row['id'];
                }
            }
        }


        $albumsList = implode(',', $albums);

        // Fetch image records
        $images = array_from_query(
            "SELECT DISTINCT i.id, i.path, i.file, i.width, i.height, i.rotation
             FROM " . IMAGES_TABLE . " i
             JOIN " . IMAGE_CATEGORY_TABLE . " ic ON i.id = ic.image_id
             WHERE ic.category_id IN ($albumsList)"
        );

        $queue = [];

        foreach ($images as $img) {
            $ext = strtolower(pathinfo($img['path'], PATHINFO_EXTENSION));

            // Skip extra video thumbnails to avoid recursive thumbnail generation
            if (strpos($img['path'], '-th_') == true) {
                continue;				
            }

            global $conf;
  
            $video_extensions = $conf['video_ext'] ?? ['mp4', 'mov', 'avi', 'mkv'];

            // Handle video posters
            if (in_array($ext, $video_extensions, true)) {
                $videoPath = PHPWG_ROOT_PATH . $img['path'];
                if (!file_exists($videoPath)) {
                    continue;
                }

                $posterPath = dirname($videoPath)
                    . '/pwg_representative/'
                    . basename($videoPath, '.' . $ext)
                    . '.jpg';
                if (!file_exists($posterPath)) {
                    continue;
                }

                $posterRelPath = str_replace(PHPWG_ROOT_PATH, '', $posterPath);
                $posterImg     = [
                    'id'       => $img['id'],
                    'path'     => $posterRelPath,
                    'file'     => basename($posterRelPath),
                    'width'    => null,
                    'height'   => null,
                    'rotation' => 0,
                ];

                try {
                    $srcPoster = new SrcImage($posterImg);
                } catch (Throwable $e) {
                    $msg = sprintf(
                        l10n('log_srcimage_error'),
                        $img['id'],
                        $posterRelPath,
                        $e->getMessage()
                    );
                    log_message('❌ ' . $msg);
                    $log[] = '❌ ' . $msg;
                    continue;
                }

                try {
                    foreach (DerivativeImage::get_all($srcPoster) as $type => $deriv) {
                        if (!empty($allowedTypes) && !in_array($type, $allowedTypes, true)) {
                            continue;
                        }

																					  
                        if ($overwriteThumbs && $deriv->is_cached()) {
                            $thumbPath = $deriv->get_path();

                            if (file_exists($thumbPath)) {
                                @unlink($thumbPath);
																										  
                            }
                        }

											
                        if (!$deriv->is_cached()) {
                            $queue[] = [
                                'img'   => $posterImg,
                                'type'  => $type,
                                'deriv' => $deriv,
                            ];
                        }
                    }
                } catch (Throwable $e) {
                    $msg = sprintf(
                        l10n('log_derivative_error'),
                        $img['id'],
                        $posterRelPath,
                        $e->getMessage()
                    );
                    log_message('❌ ' . $msg);
                    $log[] = '❌ ' . $msg;
                }

                continue;
            }

            // Skip non-picture files
 
            if (!in_array($ext, $conf['picture_ext'], true)) {
                continue;
            }

            // Validate dimensions
            if (empty($img['width']) || empty($img['height']) || $img['width'] <= 0 || $img['height'] <= 0) {
                $msg     = sprintf(l10n('log_invalid_dimensions'), $img['id'], $img['path']);
                log_message('⛔ ' . $msg);
                $log[]   = '⛔ ' . $msg;
                continue;
            }

            if (!isset($img['rotation']) || !is_numeric($img['rotation'])) {
                $img['rotation'] = 0;
            }

            try {
                $src = new SrcImage($img);
            } catch (Throwable $e) {
                $msg   = sprintf(l10n('log_srcimage_error'), $img['id'], $img['path'], $e->getMessage());
                log_message('❌ ' . $msg);
                $log[] = '❌ ' . $msg;
                continue;
            }

            // Determine which derivatives to generate
            try {
                $derivsToGenerate = [];
	 
                foreach (DerivativeImage::get_all($src) as $type => $deriv) {
										   
                    if (!empty($allowedTypes) && !in_array($type, $allowedTypes, true)) {
                        continue;
                    }

                    if (!$deriv->is_cached()) {
                        $derivsToGenerate[$type] = $deriv;
                    }
                }
            } catch (Throwable $e) {
                $msg   = sprintf(l10n('log_derivative_error'), $img['id'], $img['path'], $e->getMessage());
                log_message('❌ ' . $msg);
                $log[] = '❌ ' . $msg;
                continue;
            }

            if (empty($derivsToGenerate)) {
                continue;
            }

            // Ensure dimensions are available
            if (empty($img['width']) || empty($img['height'])) {
                $fullPath = PHPWG_ROOT_PATH . $img['path'];
                if (!file_exists($fullPath)) {
                    $msg   = sprintf(l10n('log_file_missing'), $img['id'], $img['path']);
                    log_message('❌ ' . $msg);
                    $log[] = '❌ ' . $msg;
                    continue;
                }

                $sizeData = @getimagesize($fullPath);
                if ($sizeData === false) {
                    $msg   = sprintf(l10n('log_getimagesize_error'), $img['id'], $img['path']);
                    log_message('❌ ' . $msg);
                    $log[] = '❌ ' . $msg;
                    continue;
                }

                $img['width']  = $sizeData[0];
                $img['height'] = $sizeData[1];
            }

            $origWidth  = $img['width'];
            $origHeight = $img['height'];

	
            foreach ($derivsToGenerate as $type => $deriv) {
                try {
                    list($targetWidth, $targetHeight) = $deriv->get_size();
                } catch (Throwable $e) {
                    $msg   = sprintf(
                        l10n('log_get_target_size_error'),
                        $type,
                        $img['id'],
                        $img['path'],
                        $e->getMessage()
                    );
                    log_message('❌ ' . $msg);
                    $log[] = '❌ ' . $msg;
                    continue;
                }

                // Only proceed if the source is large enough
                if ($origWidth >= $targetWidth && $origHeight >= $targetHeight) {

					// If overwrite is enabled and thumbnail is cached: delete it first												   
                    if ($overwriteThumbs && $deriv->is_cached()) {
                        $path = $deriv->get_path();
                        if (file_exists($path)) {
                            @unlink($path);
																										
                        }
                    }

																  
                    if (!$deriv->is_cached()) {
                        $queue[] = [
                            'img'   => $img,
                            'type'  => $type,
                            'deriv' => $deriv,
                        ];
                    }

                } else {
                    $msg   = sprintf(
                        l10n('log_image_too_small'),
                        $type,
                        $img['id'],
                        $img['path'],
                        $origWidth,
                        $origHeight,
                        $targetWidth,
                        $targetHeight
                    );
                    log_message('⛔ ' . $msg);
                    $log[] = '⛔ ' . $msg;
                }
            }

        }

        $_SESSION['thumb_progress'] = [
            'albumId'         => $albumId,
            'albums'          => $albums,
            'queue'           => $queue,
            'index'           => 0,
            'generated'       => 0,
            'totalThumbnails' => count($queue),
            'simulate'        => $simulate,
        ];

        $countMsg = sprintf(l10n('log_total_thumbs_to_generate'), count($queue));
        $log[]    = '🧮 ' . $countMsg;
        log_message('🧮 ' . $countMsg);
    }

    // Step 2: Process the queue in steps
    $prog           = &$_SESSION['thumb_progress'];
    $queue          = &$prog['queue'];
    $index          = &$prog['index'];
    $blockGenerated = 0;
    $simulate       = (bool) $prog['simulate'];

    $steps          = 1;

    while ($index < count($queue) && $blockGenerated < $steps) {
        $item  = $queue[$index];
        $img   = $item['img'];
        $type  = $item['type'];
        $deriv = $item['deriv'];

        if (!$deriv->is_cached()) {
            if (!$simulate) {
                $url = get_base_url() . '/' . ltrim($deriv->get_url(), '/');
                $ch  = curl_init($url);
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
                'thumb_type' => l10n($type),
            ];

	  
            $logLine = sprintf(
                l10n('log_thumb_progress_line'),
                $prog['generated'],
                $prog['totalThumbnails'],
                $percent,
                $img['id'],
                ($simulate ? l10n('simulation_suffix') : ''),
                l10n($type),
                $img['path']
            );
   
	 
            log_message($logLine);
        }

        $index++;
    }

    $done    = $index >= count($queue);
    $summary = '';

    if ($done) {
        $summary = '✅ ' . sprintf(
            l10n('log_step_completed_with_count'),
            l10n('step_generate_thumbnails'),
            $prog['generated'],
            l10n('step_thumbnail')
        );
        log_message($summary);
        unset($_SESSION['thumb_progress']);
    }

										   
    echo json_encode([
        'processed' => $blockGenerated,
        'generated' => $prog['generated'],
        'offset'    => $index,
        'done'      => $done,
        'total'     => $prog['totalThumbnails'],
        'log'       => $log,
        'summary'   => $summary,
    ]);
    exit;
}

