<?php
/*
File: include/metadata.php – AlbumPilot Plugin for Piwigo - Metadata handler
Author: Hendrik Schöttle
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
*/

// Metadata update entry point (AJAX, JSON response)
/**
 * Handle metadata update for images in an album, processing in batches.
 *
 * Expects GET parameters:
 *  - update_metadata_for_album: album ID to process
 *  - pwg_token: CSRF token
 *  - simulate (optional): '1' to skip database writes
 *  - subalbums (optional): '1' to include subalbums
 *
 * Returns JSON with progress and log entries.
 */
if (
isset($_GET['update_metadata_for_album'], $_GET['pwg_token'])
&& $_GET['pwg_token'] === get_pwg_token()
) {
    // Clean output buffers for JSON response
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $simulate          = isset($_GET['simulate']) && $_GET['simulate'] === '1';
    $albumId           = (int) $_GET['update_metadata_for_album'];
    $includeSubalbums  = isset($_GET['subalbums']) && $_GET['subalbums'] === '1';
    
    // Number of images to process per batch
    $chunkSize         = 10;
    
    $log = [];
    
    // If root album selected but “search in subalbums” is OFF, abort without scanning
    abortOnRootNoSubs($albumId, $includeSubalbums, $log);
    
    // Initial request: collect items and store in session
    if (!isset($_SESSION['meta_progress'])) {
        // Gather albums (special-case: root album + subalbums = all albums)
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
        $images     = array_from_query(
        "SELECT DISTINCT i.id, i.path
        FROM " . IMAGES_TABLE . " i
        JOIN " . IMAGE_CATEGORY_TABLE . " ic ON i.id = ic.image_id
        WHERE ic.category_id IN ($albumsList)
        ORDER BY i.path ASC"
        );
        
        $_SESSION['meta_progress'] = [
        'queue'     => $images,
        'index'     => 0,
        'updated'   => 0,
        'total'     => count($images),
        'simulate'  => $simulate,
        ];
        
        $msg1 = '🔍 ' . l10n('log_metadata_scan_start');
        $msg2 = '🧮 ' . sprintf(l10n('log_total_images_to_process'), count($images));
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
        'log'       => $log,
        ]);
        exit;
    }
    
    // Follow-up: process next batch from session queue
    $prog             = &$_SESSION['meta_progress'];
    $queue            = &$prog['queue'];
    $index            = &$prog['index'];
    $updated          = &$prog['updated'];
    $total            = $prog['total'];
    $simulate         = $prog['simulate'];
    
    $processed = 0;
    $batchIds  = [];
    
    while ($index < $total && $processed < $chunkSize) {
        $item     = $queue[$index];
        $imageId  = (int) $item['id'];
        $path     = $item['path'];
        
        $batchIds[] = $imageId;
        $processed++;
        
        $log[] = [
        'type'     => 'progress',
        'step'     => 'metadata',
        'index'    => $index + 1,
        'total'    => $total,
        'percent'  => floor(($index + 1) / $total * 100),
        'image_id' => $imageId,
        'simulate' => $simulate,
        'path'     => $path,
        ];
        
        $logLine = sprintf(
        l10n('log_metadata_progress_line'),
        $index + 1,
        $total,
        $imageId,
        ($simulate ? l10n('simulation_suffix') : ''),
        $path
        );
        log_message('📸 ' . $logLine);
        
        $index++;
        
    }
    
    if (!$simulate && !empty($batchIds)) {
        include_once(PHPWG_ROOT_PATH . 'admin/include/functions_metadata.php');
        // temporarily convert warnings to exceptions for sync_metadata
        set_error_handler(function($errno, $errstr) {
            throw new \ErrorException($errstr, 0, $errno);
        });
        try {
            sync_metadata($batchIds);
            } catch (\Throwable $e) {
            // log missing file or metadata sync error
            $errMsg = sprintf(
            l10n('log_file_missing'),
            $imageId,
            $path
            );
            log_message('❌ ' . $errMsg);
            $log[] = '❌ ' . $errMsg;
        }
        restore_error_handler();
        
    }
    
    $updated       += count($batchIds);
    $prog['index']  = $index;
    $prog['updated']= $updated;
    
    $done    = ($index >= $total);
    $summary = '';
    
    if ($done) {
        $summary = '✅ ' . sprintf(
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
    'summary'   => $summary,
    ]);
    exit;
}

