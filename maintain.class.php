<?php
/*
File: maintain.class.php – AlbumPilot Plugin for Piwigo - Maintenance on plugin uninstall
Author: Hendrik Schöttle
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
*/
if (!defined('PHPWG_ROOT_PATH')) {
    die('Hacking attempt!');
}

// Determine plugin folder name and convert to a valid class name


$pluginId   = basename(__DIR__);                          
$pluginClass = preg_replace('/[^A-Za-z0-9_]/', '_', $pluginId) . '_maintain';

// Dynamically generated maintenance class for the plugin.
if (!class_exists($pluginClass, false)) {
    eval('class ' . $pluginClass . ' extends PluginMaintain {
        function install($plugin_version, &$errors = array()) {}
        function activate($plugin_version, &$errors = array()) {}
        function update($old_version, $new_version, &$errors = array()) {}
        function deactivate() {}
        function uninstall() {
            //  Called on plugin uninstall; drop the settings table.
            global $prefixeTable;
            pwg_query("DROP TABLE IF EXISTS `{$prefixeTable}album_pilot_settings`;");
        }
    }');
}
