<?php
/*
Plugin Name: AlbumPilot
Version: 1.3.0
Description: Batch processing: Media sync, thumbs, video posters, maintenance
Author: Hendrik Schöttle
Has Settings: true
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=1038
*/

if (!defined('PHPWG_ROOT_PATH')) {
    die('Hacking attempt!');
}

// Define plugin folder and path dynamically
define('ALBUMPILOT_DIR', basename(__DIR__));


define('ALBUMPILOT_PATH', PHPWG_PLUGINS_PATH . ALBUMPILOT_DIR . '/');
define('ALBUM_PILOT_PATH', ALBUMPILOT_PATH);

define('ALBUMPILOT_ADMIN', get_root_url() . 'admin.php?page=plugin-' . ALBUMPILOT_DIR);

// Load the plugin's language files
load_language('plugin.lang', ALBUMPILOT_PATH);

/**
 * Show localized description in the plugin manager.
 *
 * @param string $desc      Existing description.
 * @param string $plugin_id Identifier of the current plugin.
 * @return string           Localized description if matches, otherwise original.
 */
add_event_handler(
    'plugin_admin_description',
    function ($desc, $plugin_id) {
        if ($plugin_id === ALBUMPILOT_DIR) {
            return l10n('AlbumPilot_description');
        }
        return $desc;
    },
    EVENT_HANDLER_PRIORITY_NEUTRAL,
    2
);

