<?php
/*
File: include/checksum.php – AlbumPilot Plugin for Piwigo - Checksum handler
Author: Hendrik Schöttle
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
*/

// Checksum calculation entry point (AJAX, JSON response)
if (isset($_GET['calculate_md5'], $_GET['pwg_token']) && $_GET['pwg_token'] === get_pwg_token()) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    header('Content-Type: application/json');

    $chunkSize        = 10;
    $simulate         = isset($_GET['simulate']) && $_GET['simulate'] === '1';
    $offset           = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $albumId          = isset($_GET['album_id']) ? (int)$_GET['album_id'] : 0;
    $includeSubalbums = isset($_GET['subalbums']) && $_GET['subalbums'] === '1';

    if ($albumId === 0) {
        $msg = l10n('log_md5_no_album');
        echo json_encode([
            'done'    => true,
            'log'     => "❌ {$msg}",
            'summary' => "❌ {$msg}",
        ]);
        exit;
    }

    // Initial call: build list of images needing MD5 checksums
    if (!isset($_SESSION['md5_progress'])) {
        include_once(PHPWG_ROOT_PATH . 'admin/site_reader_local.php');
        $siteReader = new LocalSiteReader('./');

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

        $albumsList = implode(',', $albums);
        $files = array_from_query(
            "SELECT i.id, i.path
             FROM " . IMAGES_TABLE . " i
             JOIN " . IMAGE_CATEGORY_TABLE . " ic ON i.id = ic.image_id
             WHERE ic.category_id IN ($albumsList)"
        );

        $queue = [];
        foreach ($files as $info) {
            $id       = (int) $info['id'];
            $existing = pwg_db_fetch_assoc(
                pwg_query("SELECT md5sum FROM " . IMAGES_TABLE . " WHERE id = $id")
            );
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
            'simulate'  => $simulate,
        ];

        $log           = [];
        $scanStartMsg  = "🔍 " . l10n('log_md5_scan_start');
        $log[]         = $scanStartMsg;
        log_message($scanStartMsg);

        $countMsg      = "🧮 " . sprintf(l10n('log_md5_total_to_calculate'), $total);
        $log[]         = $countMsg;
        log_message($countMsg);

        echo json_encode([
            'processed' => 0,
            'generated' => 0,
            'offset'    => 0,
            'done'      => false,
            'total'     => $total,
            'log'       => $log,
            'summary'   => '',
        ]);
        exit;
    }

    // Follow-up call: process next chunk of files
    $prog      = &$_SESSION['md5_progress'];
    $queue     = &$prog['queue'];
    $index     = &$prog['index'];
    $generated = &$prog['generated'];
    $total     = $prog['total'];
    $simulate  = $prog['simulate'];

    if ($offset > $index) {
        $index = $offset;
    }

    $log       = [];
    $processed = 0;

    while ($index < $total && $processed < $chunkSize) {
        $item     = $queue[$index];
        $filePath = PHPWG_ROOT_PATH . $item['path'];
        $index++;
        $processed++;

        if (!file_exists($filePath)) {
            $log[] = sprintf(
                "❌ [%d/%d] %s",
                $index,
                $total,
                sprintf(l10n('log_md5_file_missing'), $item['path'])
            );
            continue;
        }

        $md5 = @md5_file($filePath);
        if (!$md5) {
            $log[] = sprintf(
                "⚠️ [%d/%d] %s",
                $index,
                $total,
                sprintf(l10n('log_md5_calc_error'), $item['path'])
            );
            continue;
        }

        if (!$simulate) {
            pwg_query(
                "UPDATE " . IMAGES_TABLE .
                " SET md5sum = '" . pwg_db_real_escape_string($md5) .
                "' WHERE id = " . (int) $item['id']
            );
        }

        $generated++;
        $prog['index'] = $index;

        $percent = $total > 0 ? floor($index / $total * 100) : 100;
        $log[]   = [
            'type'     => 'progress',
            'step'     => 'checksum',
            'index'    => $index,
            'total'    => $total,
            'percent'  => $percent,
            'image_id' => $item['id'],
            'simulate' => $simulate,
            'path'     => $item['path'],
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

    $done    = $index >= $total;
    $summary = '';
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
        'summary'   => $summary,
    ]);
    exit;
}
