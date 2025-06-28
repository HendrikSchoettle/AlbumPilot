<?php
/*
File: include/videos.php – AlbumPilot Plugin for Piwigo - Video handler
Author: Hendrik Schöttle
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
*/

// Optional: extract metadata from video using ffprobe
function extract_video_metadata($video_path)
{
    $cmd = "ffprobe -v quiet -print_format json -show_streams " . escapeshellarg($video_path);
    exec($cmd, $output, $retval);
    if ($retval !== 0) {
        return [];
    }

    $json = json_decode(implode("\n", $output), true);
    foreach ($json['streams'] as $stream) {
        if ($stream['codec_type'] === 'video') {
            return [
                'width'    => $stream['width']  ?? null,
                'height'   => $stream['height'] ?? null,
                'duration' => isset($stream['duration']) ? round($stream['duration']) : 0,
            ];
        }
    }

    return [];
}

// Insert or update VideoJS metadata into custom table
function insert_videojs_metadata($image_id, $video_path)
{
    $meta = extract_video_metadata($video_path);
    if (empty($meta)) {
        return;
    }

    $metadata = [
        'DurationSeconds' => $meta['duration'],
        'width'           => $meta['width'],
        'height'          => $meta['height'],
        'filesize'        => @filesize($video_path),
        'date_creation'   => date('Y-m-d'),
    ];

    $serialized = pwg_db_real_escape_string(serialize($metadata));
    $query = "
        INSERT INTO " . $GLOBALS['prefixeTable'] . "image_videojs
            (id, metadata, date_metadata_update)
        VALUES
            ($image_id, '$serialized', NOW())
        ON DUPLICATE KEY UPDATE
            metadata = '$serialized',
            date_metadata_update = NOW()
    ";
    pwg_query($query);
}

// Get video duration in seconds via ffprobe
function get_video_duration(string $filename): int
{
    $cmd    = 'ffprobe -v error -show_entries format=duration '
            . '-of default=noprint_wrappers=1:nokey=1 '
            . escapeshellarg($filename);
    $output = shell_exec($cmd);
    $duration = (int) floor((float) trim((string) $output));
    
    return $duration;
}

// Main video poster & thumbnail generation block
if (
    isset($_GET['video_thumb_block'], $_GET['cat_id'], $_GET['pwg_token'])
    && $_GET['pwg_token'] === get_pwg_token()
) {
    // Clear output buffers and set JSON header
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    global $conf;

    $cat_id           = (int) $_GET['cat_id'];
    $simulate         = isset($_GET['simulate']) && $_GET['simulate'] === '1';
    $includeSubalbums = isset($_GET['subalbums']) && $_GET['subalbums'] === '1';
    $log              = [];

    // If root album selected but “search in subalbums” is OFF, abort without scanning
    abortOnRootNoSubs($cat_id, $includeSubalbums, $log);

    // Extract parameters
    $posterSecond  = isset($_GET['poster_second'])  ? (int) $_GET['poster_second'] : 4;
    $thumbInterval = isset($_GET['thumb_interval']) ? (int) $_GET['thumb_interval'] : 5;
    $thumbSize     = $_GET['thumb_size']            ?? '120x68';
    $outputFormat  = $_GET['output_format']         ?? 'jpg';

    // Flags from checkboxes
    $createPoster    = ($_GET['videojs_create_poster']  ?? '1') === '1';
    $posterOverwrite = ($_GET['videojs_poster_overwrite'] ?? '1') === '1';
    $addOverlay      = ($_GET['videojs_add_overlay']    ?? '1') === '1';
    $addThumbs       = ($_GET['videojs_add_thumbs']     ?? '0') === '1';

    // Initial scan: prepare queue if not set
    if (!isset($_SESSION['video_progress'])) {
        // Build album list (including subalbums if requested)
        
	    if ($cat_id === 0 && $includeSubalbums) {
            // root + Subalbums: tatsächlich alle realen Alben scannen
            $newAlbumList = [];
            $res = pwg_query(
                'SELECT id FROM ' . CATEGORIES_TABLE . ' WHERE dir IS NOT NULL'
            );
            while ($row = pwg_db_fetch_assoc($res)) {
                $newAlbumList[] = (int) $row['id'];
            }
            sort($newAlbumList);
        }
        else {
            // gewohntes Verhalten für alle anderen Fälle
            $newAlbumList = [$cat_id];
            if ($includeSubalbums) {
                $res = pwg_query(
                    'SELECT id FROM ' . CATEGORIES_TABLE .
                    ' WHERE uppercats REGEXP "(^|,)' . $cat_id . '(,|$)" AND dir IS NOT NULL'
                );
                while ($row = pwg_db_fetch_assoc($res)) {
                    $id = (int) $row['id'];
                    if (!in_array($id, $newAlbumList, true)) {
                        $newAlbumList[] = $id;
                    }
                }
            }
            sort($newAlbumList);
        }

        $upload_dir       = $conf['upload_dir'] ?? 'upload';
        $video_extensions = $conf['video_ext']   ?? ['mp4', 'mov', 'avi', 'mkv'];

        // Fetch all media entries in these albums
        $results = array_from_query(
            'SELECT i.id, i.path
             FROM ' . IMAGES_TABLE . ' i
             JOIN ' . IMAGE_CATEGORY_TABLE . ' ic ON i.id = ic.image_id
             WHERE ic.category_id IN (' . implode(',', $newAlbumList) . ')
			 ORDER BY i.path ASC'
        );

        $log[] = '🔍 ' . l10n('log_video_scan_start');
        log_message('🔍 ' . l10n('log_video_scan_start'));

        $missingPosters     = [];
        $posterMissingCount = 0;
        $thumbsMissingCount = 0;

        foreach ($results as $img) {
            $ext = strtolower(pathinfo($img['path'], PATHINFO_EXTENSION));

            if (!in_array($ext, $video_extensions, true)) {
                continue;
            }

            $filename = (strpos($img['path'], 'galleries/') === 0 || strpos($img['path'], '/galleries/') !== false)
                ? PHPWG_ROOT_PATH . $img['path']
                : PHPWG_ROOT_PATH . $upload_dir . '/' . $img['path'];
            if (!file_exists($filename)) {
                continue;
            }

            $posterDir = dirname($filename) . '/pwg_representative/';
			$baseName = pathinfo($filename, PATHINFO_FILENAME); 
            $poster    = $posterDir . $baseName . '.' . $outputFormat;

            $needsPoster = $createPoster && ((!$posterOverwrite && !file_exists($poster)) || $posterOverwrite);
			
			$needsThumbs = false;
            if ($addThumbs) {
                $existing = glob($posterDir . $baseName . '-th_*.' . $outputFormat);
                $needsThumbs = $posterOverwrite || empty($existing);
            }

            if ($needsPoster || $needsThumbs) {
                if ($needsPoster) {
                    $posterMissingCount++;
                }
                if ($needsThumbs) {
                    $thumbsMissingCount++;
                }
                $img['needsPoster'] = $needsPoster;
                $img['needsThumbs'] = $needsThumbs;
                $missingPosters[]   = $img;
            }
        }

        $totalToProcess = count($missingPosters);
        $summaryLine    = sprintf(
            l10n('log_video_combined_counts'),
            $totalToProcess,
            $posterMissingCount,
            $thumbsMissingCount
        );
        $log[] = '🧮 ' . $summaryLine;
        log_message('🧮 ' . $summaryLine);

        if ($totalToProcess === 0) {
            unset($_SESSION['video_progress']);
            $summary = '✅ ' . sprintf(
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
                'summary'   => $summary,
            ]);
            exit;
        }

        $_SESSION['video_progress'] = [
            'albumId'   => $cat_id,
            'albums'    => $newAlbumList,
            'images'    => $missingPosters,
            'index'     => 0,
            'generated' => 0,
            'total'     => $totalToProcess,
            'simulate'  => $simulate,
        ];
    }

    // Process queue step-by-step
    $prog      = &$_SESSION['video_progress'];
    $images    = &$prog['images'];
    $index     = &$prog['index'];
    $total     = $prog['total'];
    $simulate  = $prog['simulate'];
    $generated = &$prog['generated'];
    $processed = 0;

    if ($index < $total) {
        $img         = $images[$index];
        $needsPoster = $img['needsPoster'] ?? false;
        $needsThumbs = $img['needsThumbs'] ?? false;
        $upload_dir  = $conf['upload_dir'] ?? 'upload';
        $video_exts  = $conf['video_ext']   ?? ['mp4', 'mov', 'avi', 'mkv'];

        $filename = (strpos($img['path'], 'galleries/') === 0 || strpos($img['path'], '/galleries/') !== false)
            ? PHPWG_ROOT_PATH . $img['path']
            : PHPWG_ROOT_PATH . $upload_dir . '/' . $img['path'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Determine skip conditions
        $skip = (! $needsPoster && ! $needsThumbs)
              || ! file_exists($filename)
              || ! in_array($ext, $video_exts, true);
        if ($skip) {
            $index++;
        } else {
            // Ensure poster directory exists
            $posterDir = dirname($filename) . '/pwg_representative/';
            if (!is_dir($posterDir)) {
                mkdir($posterDir, 0755, true);
            }

            $baseName     = pathinfo($filename, PATHINFO_FILENAME);
            $poster       = $posterDir . $baseName . '.' . $outputFormat;
            $posterExists = file_exists($poster);

            if (! $simulate) {

                // Invalidate any existing derivatives
                if (class_exists('DerivativeImage')) {
                    try {
                        $posterPath  = str_replace(PHPWG_ROOT_PATH, '', $poster);
                        $posterImg   = [
                            'id'                => $img['id'],
                            'path'              => $posterPath,
                            'file'              => basename($posterPath),
                            'tn_ext'            => $outputFormat,
                            'is_video'          => false,
                            'representative_ext'=> null,
                        ];
                        $srcPoster   = new SrcImage($posterImg);
                        $derivatives = DerivativeImage::get_all($srcPoster);
                        foreach ($derivatives as $deriv) {
                            $p = $deriv->get_path();
                            if ($deriv->is_cached() && file_exists($p) && strpos($p, PWG_DERIVATIVE_DIR) !== false) {
                                @unlink($p);
                            }
                        }
                    } catch (Throwable $e) {
                        // ignore errors
                    }
                }

                // Capture poster frame
                $duration     = get_video_duration($filename);
                $posterTime   = min($posterSecond, $duration);
                $shortWarning = false;
                if ($duration > 0 && $posterSecond > $duration) {
                    $msg = sprintf(
                        l10n('log_video_too_short'),
                        $img['path'],
                        $duration,
                        $posterSecond,
                        $posterTime
                    );
                    $log[] = '⚠️ ' . $msg;
                    log_message($msg);
                    $shortWarning = true;
                }

                $cmd = 'ffmpeg -ss ' . escapeshellarg($posterTime)
                     . ' -i ' . escapeshellarg($filename)
                     . ' -vcodec ' . ($outputFormat === 'png' ? 'png' : 'mjpeg')
                     . ' -vframes 1 -an -f rawvideo -y ' . escapeshellarg($poster) . ' 2>&1';

                // Backup existing poster
                if ($posterExists && $needsPoster) {
                    $backupPoster = $poster . '.bak';
                    @rename($poster, $backupPoster);
                }
				
                exec($cmd, $_, $_);

				// Restore or delete backup 
				if (isset($backupPoster)) {


    				if (file_exists($poster)) {
        				// if new poster OK, delete backup
        				@unlink($backupPoster);
    				} else {
        				// it poster failed, then restore backup
        				@rename($backupPoster, $poster);
    				}
    				unset($backupPoster);
				}
				
				// always delete the poster with the other extension when overwriting
    				if (file_exists($poster)) {
        				$otherExt = ($outputFormat === 'jpg') ? 'png' : 'jpg';
        				$otherPoster = $posterDir . $baseName . '.' . $otherExt;
        				if (file_exists($otherPoster)) {
            				@unlink($otherPoster);
        				}
    				}
				
				insert_videojs_metadata((int) $img['id'], $filename);

                if ($addOverlay && function_exists('add_movie_frame')) {

					$testImage = $outputFormat === 'png'
						? @imagecreatefrompng($poster)
						: @imagecreatefromjpeg($poster);

					if ($testImage !== false) {
                        imagedestroy($testImage);
                        ob_start();
                        @add_movie_frame($poster);
                        ob_end_clean();
                    }
                }

                // Ensure DB flag for representative_ext
                $check = pwg_db_fetch_assoc(
                    pwg_query("SELECT representative_ext FROM " . IMAGES_TABLE . " WHERE id = " . (int) $img['id'])
                );

				// update representative_ext to current format
				pwg_query(
					"UPDATE " . IMAGES_TABLE .
					" SET representative_ext = '" . pwg_db_real_escape_string($outputFormat) . "'" .
					" WHERE id = " . (int) $img['id']
				);

                // Log start of thumbnails only in logfile, not GUI
                log_message("📽️ " . sprintf(l10n('log_video_thumb_start'), $img['path']));

                // Generate extra thumbnails if requested
                if ($needsThumbs) {
                    $sizeArg = preg_match('/^\d+x\d+$/', $thumbSize)
                        ? '-s ' . escapeshellarg($thumbSize)
                        : '';
                    if ($duration > 0 && $posterSecond > $duration && ! $shortWarning) {
                        $posterTime = min($posterSecond, $duration);
                        $warning    = sprintf(
                            l10n('log_video_too_short'),
                            $img['path'],
                            $duration,
                            $posterSecond,
                            $posterTime
                        );
                        $log[] = '⚠️ ' . $warning;
                        log_message($warning);
                        $shortWarning = true;
                    }
                    if ($duration > 0 && $thumbInterval > 0) {

                        for ($second = 0; $second < $duration; $second += $thumbInterval) {
                            $thumbPath = $posterDir . $baseName . '-th_' . $second . '.' . $outputFormat;

                            // Backup existing thumbnail if exists
                            $thumbBackup = null;
                            if (file_exists($thumbPath)) {
                                $thumbBackup = $thumbPath . '.bak';
                                @rename($thumbPath, $thumbBackup);
                            }
                            
							$thumb_width = preg_split("/x/", $thumbSize);
							if (!isset($thumb_width[0]) || empty($thumb_width[0])) {
								$thumb_width[0] = "120";
							}

							$scale = "scale='".$thumb_width[0].":trunc(ow/a)'";

							$cmdThumb = 'ffmpeg -ss ' . escapeshellarg($second)
									  . ' -i ' . escapeshellarg($filename)
									  . ' -vcodec ' . ($outputFormat === 'png' ? 'png' : 'mjpeg')
									  . ' -vframes 1 -an -f rawvideo -vf ' . escapeshellarg($scale)
									  . ' -y ' . escapeshellarg($thumbPath) . ' 2>&1';

							exec($cmdThumb);
					
							// Restore or delete backup
							if (isset($thumbBackup)) {
								if (file_exists($thumbPath)) {
									@unlink($thumbBackup); // success
								} else {
									@rename($thumbBackup, $thumbPath); // restore
								}
								unset($thumbBackup);
							}

							// delete other-format thumbnails after successful thumbnail creation
							if (file_exists($thumbPath)) {
								$otherExt = ($outputFormat === 'jpg') ? 'png' : 'jpg';
								foreach (glob($posterDir . $baseName . '-th_*.' . $otherExt) as $oldThumb) {
									@unlink($oldThumb);
								}
							}
						}

                        pwg_query(
                            "UPDATE " . IMAGES_TABLE .
                            " SET representative_ext = '" . pwg_db_real_escape_string($outputFormat) . "'" .
                            " WHERE id = " . (int) $img['id']
                        );
                    }
                    log_message("✅ " . sprintf(
                        l10n('log_video_thumb_done'),
                        floor($duration / $thumbInterval),
                        $img['path']
                    ));
                }
            }

            // Increment counters and log progress
            $generated++;
            $processed++;
            $index++;
            $prog['generated'] = $generated;

            $percent = $total > 0 ? floor($generated / $total * 100) : 100;
            $log[] = [
                'type'     => 'progress',
                'step'     => 'video',
                'index'    => $generated,
                'total'    => $total,
                'percent'  => $percent,
                'image_id' => $img['id'],
                'simulate' => $simulate,
                'path'     => $img['path'],
            ];
            log_message(sprintf(
                l10n('log_video_progress_line'),
                $generated,
                $total,
                $percent,
                $img['id'],
                ($simulate ? l10n('simulation_suffix') : ''),
                $img['path']
            ));
        }
    }

    // Finalize
    $done = ($index >= $total);
    if ($done) {
        unset($_SESSION['video_progress']);
        $summary = '✅ ' . sprintf(
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
        'log'       => $log,
        'summary'   => $summary ?? '',
    ]);
    exit;
}
