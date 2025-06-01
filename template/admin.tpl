{*
File: template/admin.tpl – AlbumPilot Plugin for Piwigo
Author: Hendrik Schöttle
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
*}

<div class="album-sync-wrapper">
  <h2>{'AlbumPilot_title'|@translate}</h2>
  <div class="album-settings-box">
    <p><strong>{'Albums_to_sync'|@translate}</strong></p>
    <div class="album-select">
      {$ALBUM_SELECT}
    </div>
    <input type="hidden" id="album-select" value="">
    <p class="option-line">
      <label><input type="checkbox" id="include-subalbums" checked> {'Include_subalbums'|@translate}</label>
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
    <label><input type="checkbox" id="simulate-mode" checked> {'Simulate_mode'|@translate}</label>
    <input type="checkbox" id="only-new-files" checked class="hidden-checkbox">
  </div>

  <div class="button-row">
    <button id="start-sync" class="btn btn-primary" style="min-width: 250px;">
      <span class="btn-icon">🚀</span> <span class="btn-text">{'Start_sync'|@translate}</span>
    </button>
    <button id="reset-settings" class="btn btn-secondary">🔁 {'Reset_settings'|@translate}</button>
  </div>

<div class="album-settings-box mt-block">
  <p><strong>{'External_trigger_url'|@translate}</strong></p>
  <textarea id="external-url-chain" readonly></textarea>
  <p style="font-size: 0.85em; color: #777; margin-top: 6px;">
    {'External_trigger_description'|@translate}
  </p>
</div>

  <ul id="sync-steps" class="sync-steps"></ul>
  <div id="sync-log" class="sync-log" style="display:none;">
    <h4 class="progress-heading">{'progress_heading'|@translate}</h4>
    <div id="progress-log" class="progress-log"></div>
  </div>
</div>

<!-- Include CSS -->
<link rel="stylesheet" href="{$PLUGIN_ROOT_URL}template/style.css">

{literal}
<script>
  window.AlbumPilotLang = {/literal}{$L10N_JS|@json_encode nofilter}{literal};
  window.AlbumPilotConfig = {
    savedSettings: {/literal}{$SAVED_SYNC_SETTINGS|@json_encode nofilter}{literal},
    rootUrl: '{/literal}{$U_SITE_URL}{literal}',
    token: '{/literal}{$ADMIN_TOKEN}{literal}',
    videojsActive: {/literal}{$VIDEOJS_ACTIVE|@json_encode nofilter}{literal},
    smartalbumsActive: {/literal}{$SMARTALBUMS_ACTIVE|@json_encode nofilter}{literal},
    pluginPageUrl: '{/literal}{$PLUGIN_ADMIN_URL|escape:javascript}{literal}'
  };
</script>
{/literal}

<!-- Include main JS -->
<script src="{$PLUGIN_ROOT_URL}template/script.js"></script>
