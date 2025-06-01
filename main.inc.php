<?php
/*
Plugin Name: AlbumPilot
Version: 0.2.0
Description: Batch processing: Media sync, thumbs, video posters, maintenance
Author: Hendrik Schöttle
Has Settings: true
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
*/

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

// Plugin-Pfade definieren
define('ALBUMPILOT_DIR', basename(dirname(__FILE__)));
define('ALBUMPILOT_PATH', PHPWG_PLUGINS_PATH . ALBUMPILOT_DIR . '/');
define('ALBUMPILOT_ADMIN', get_root_url() . 'admin.php?page=plugin-' . ALBUMPILOT_DIR);

// Sprachdateien laden
load_language('plugin.lang', ALBUMPILOT_PATH);

// Lokalisierte Beschreibung im Plugin-Manager anzeigen

add_event_handler('plugin_admin_description', function ($desc, $plugin_id) {
  if ($plugin_id === 'AlbumPilot') {
    return l10n('AlbumPilot_description');
  }
  return $desc;
}, EVENT_HANDLER_PRIORITY_NEUTRAL, 2);

