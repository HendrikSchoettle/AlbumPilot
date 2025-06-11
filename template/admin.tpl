{*
File: template/admin.tpl – AlbumPilot Plugin for Piwigo
Author: Hendrik Schöttle
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
*}

{html_head}
<!--
    <script src="admin/themes/default/js/album_selector.js"></script>
    <script src="admin/themes/default/js/addAlbum.js"></script>
    <link rel="stylesheet" href="admin/themes/default/theme.css">
-->
{/html_head}

<div class="album-sync-wrapper">
    {if isset($LOG_WRITE_ERROR)}
    <div class="log-warning" style="margin:1em 0; padding:0.8em; background:#fff3cd; color:#856404; border:1px solid #ffeeba; border-radius:4px;">
        {$LOG_WRITE_ERROR}
    </div>
    {/if}

    {if $BATCH_MODE_ACTIVE}
    <div class="log-warning" style="margin:1em 0; padding:0.8em; background:#fff3cd; color:#856404; border:1px solid #ffeeba; border-radius:4px;">
        ⚠️ {'batch_mode_warning'|@translate} – {'batch_mode_limited_ui'|@translate}
    </div>
    {/if}

    <h2>{'AlbumPilot_title'|@translate}</h2>
    <div class="album-settings-box">
        <p><strong>{'Albums_to_sync'|@translate}</strong></p>
        <div class="album-select">
            {$ALBUM_SELECT}
        </div>

        <div class="album-typeahead-wrapper" style="margin-top:8px;">
            <input
                type="text"
                id="album-typeahead"
                placeholder="{'Album_search_placeholder'|@translate}"
                class="album-typeahead-input"
                autocomplete="off"
                style="width:99%; margin-bottom:0.75em;"
            >
            <ul
                id="album-typeahead-results"
                class="album-typeahead-results"
                style="display:none;"
            ></ul>
        </div>

        <input type="hidden" id="album-select" value="">
        <p class="option-line">
            <label><input type="checkbox" id="includeSubalbums" checked> {'Include_subalbums'|@translate}</label>
        </p>
        <ul id="sync-steps-list-pre" class="step-list"></ul>
    </div>

    <div class="step-list-wrapper">
        <ul id="sync-steps-list-post" class="step-list"></ul>
    </div>

    <div class="options-block">
        <label><input type="checkbox" id="select-all-steps" checked> {'Select_all_steps'|@translate}</label>
    </div>

    <div class="options-block">
        <strong class="options-heading">{'Options_heading'|@translate}</strong>
        <label><input type="checkbox" id="simulate" checked> {'Simulate_mode'|@translate}</label>
        <input type="checkbox" id="onlyNew" checked class="hidden-checkbox">
    </div>

    <div class="button-row">
        <button id="start-sync" class="btn btn-primary" style="min-width:250px;">
            <span class="btn-icon">🚀</span>
            <span class="btn-text">{'Start_sync'|@translate}</span>
        </button>
        <button id="reset-settings" class="btn btn-secondary">🔁 {'Reset_settings'|@translate}</button>
    </div>

    <div class="album-settings-box mt-block">
        <p><strong>{'External_trigger_url'|@translate}</strong></p>
        <textarea id="external-url-chain" readonly></textarea>
        <p style="font-size:0.85em; color:#777; margin-top:6px;">
            {'External_trigger_description'|@translate}
        </p>
    </div>

    <ul id="sync-steps" class="sync-steps"></ul>
    <div id="sync-log" class="sync-log" style="display:none;">
        <h4 class="progress-heading">{'progress_heading'|@translate}</h4>
        <div id="progress-log" class="progress-log"></div>
        <div id="scroll-anchor" style="height:1px;"></div>
    </div>
</div>

<!-- Include CSS -->
<link rel="stylesheet" href="{$PLUGIN_ROOT_URL}template/style.css">

{literal}
<script type="text/javascript">
    // localization and core config
    window.AlbumPilotLang    = {/literal}{$L10N_JS|@json_encode nofilter}{literal};
    window.AlbumPilotConfig  = {
        savedSettings: {/literal}{$SAVED_SYNC_SETTINGS|@json_encode nofilter}{literal},
        rootUrl:       '{/literal}{$U_SITE_URL}{literal}',
        token:         '{/literal}{$ADMIN_TOKEN}{literal}',
        videojsActive: {/literal}{$VIDEOJS_ACTIVE|@json_encode nofilter}{literal},
        smartalbumsActive: {/literal}{$SMARTALBUMS_ACTIVE|@json_encode nofilter}{literal},
        pluginPageUrl: '{/literal}{$PLUGIN_ADMIN_URL|escape:javascript}{literal}',
        pluginRootUrl: '{/literal}{$PLUGIN_ROOT_URL|escape:javascript}{literal}'
    };

    // available thumbnail types for Image step
    window.AlbumPilotThumbTypes = {/literal}{$ALBUM_PILOT_THUMB_TYPES|@json_encode nofilter}{literal};
</script>
{/literal}

<!-- Include main JS -->
{combine_script id="album_pilot_runner" load="footer" path=$PLUGIN_ROOT_URL|cat:"template/js/runner.js"}
{combine_script id="album_pilot_main"   require="album_pilot_runner" load="footer" path=$PLUGIN_ROOT_URL|cat:"template/script.js"}
{combine_script id="album_pilot_videos" require="album_pilot_main"   load="footer" path=$PLUGIN_ROOT_URL|cat:"template/js/videos.js"}
{combine_script id="album_pilot_images" require="album_pilot_main"   load="footer" path=$PLUGIN_ROOT_URL|cat:"template/js/images.js"}
