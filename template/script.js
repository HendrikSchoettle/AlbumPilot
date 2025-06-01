/*
File: template/script.js – AlbumPilot Plugin for Piwigo
Author: Hendrik Schöttle
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
*/

document.addEventListener('DOMContentLoaded', function () {



function t(key) {
  return window.AlbumPilotLang?.[key] || key;
}
	
function resetProgress() {
  return fetch(config.pluginPageUrl + '&reset_progress=1&pwg_token=' + token, {
    credentials: 'same-origin'
  })
  .then(res => res.json())
  .then(data => {
    if (!data?.success) {
      console.warn(window.AlbumPilotLang.reset_error);
    }
  })
  .catch(err => {
    console.error(window.AlbumPilotLang.reset_error_details, err);
  });
}

// Helper function for saving synchronization settings
function saveSyncSettings(settingsObj) {
  fetch(pluginPageUrl, {

    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      save_sync_settings: true,
      settings: settingsObj
    })
  });
}

// Robust against invalid JSON responses (e.g. PHP warnings as HTML)
async function fetchSafeJSON(url, options = {}) {
  try {
    const res = await fetch(url, { credentials: 'same-origin', ...options });
    const text = await res.text();

    try {
      return JSON.parse(text);
    } catch (jsonError) {
      return {
        error: true,
        htmlFallback: true,
        rawText: text,
        status: res.status,
        statusText: res.statusText,
      };
    }
  } catch (fetchError) {
    return {
      error: true,
      fetchFailed: true,
      message: fetchError.message
    };
  }
}

// Simple and reusable for all AJAX step executions
function handleJsonStep(url, onSuccess) {
  const log = document.getElementById('progress-log');

  fetchSafeJSON(url).then(data => {
    if (data.error) {
      const div = document.createElement('div');
      div.className = 'sync-step-block error';

      if (data.htmlFallback) {
        div.innerHTML = window.AlbumPilotLang.invalid_response + '<br><pre>' +
                        data.rawText.substring(0, 2000) + '</pre>';
      } else {
        div.textContent = window.AlbumPilotLang.network_error + ' ' + data.message;
      }

      log.appendChild(div);
    } else {
      onSuccess(data);
    }
  });
}

// Extract configuration from global JS object safely
const config = window.AlbumPilotConfig || {};
const pluginPageUrl = config.pluginPageUrl || '';

// Normalize plugin URL to ensure leading slash
const safePluginUrl = pluginPageUrl.startsWith('/') ? pluginPageUrl : '/' + pluginPageUrl;
const root = config.rootUrl;
const token = config.token;


// Function for generating external URL
function generateExternalUrlFromSelection() {
  const albumId = document.querySelector('#album-list')?.value;
  const simulate = document.querySelector('#simulate-mode')?.checked;
  const onlyNew = document.querySelector('#only-new-files')?.checked;
  const includeSubalbums = document.querySelector('#include-subalbums')?.checked;

  const textArea = document.getElementById('external-url-chain');
  if (!textArea) return;

  // Collect selected steps (IDs only, e.g., "1", "2", "5")
  const selectedStepIds = [...new Set(
    Array.from(document.querySelectorAll('.step-list input[type=checkbox]:checked'))
      .map(cb => {
        const match = cb.id.match(/^step(\d+)$/);
        return match ? match[1] : null;
      })
      .filter(Boolean)
  )];

  // ⚠️ No album selected
  if (!albumId) {
    const msg = window.AlbumPilotLang?.select_album_alert || 'Please select an album.';
    textArea.value = '⚠️ ' + msg;
    return;
  }

  // ⚠️ No steps selected
  if (selectedStepIds.length === 0) {
    const msg = window.AlbumPilotLang?.select_step_alert || 'Please select at least one step.';
    textArea.value = '⚠️ ' + msg;
    return;
  }

  // ✅ All inputs valid – generate external URL
  const base = new URL(window.location.href);
  const fullPath = new URL(config.pluginPageUrl, base);

  fullPath.searchParams.set('external_batch', '1');
  fullPath.searchParams.set('album', albumId);
  fullPath.searchParams.set('simulate', simulate ? '1' : '0');
  fullPath.searchParams.set('onlynew', onlyNew ? '1' : '0');
  fullPath.searchParams.set('subalbums', includeSubalbums ? '1' : '0');
  fullPath.searchParams.set('steps', selectedStepIds.join(','));

  const url = fullPath.toString();
  textArea.value = url;
  textArea.setAttribute('value', url); // Optional: supports older browsers
}

let settings = config.savedSettings;



if (typeof settings === 'string') {
  try {
    settings = JSON.parse(settings);
  } catch (e) {
    console.error('Error parsing savedSettings:', e);
    settings = {};
  }
}

const savedSettings = settings;

const stepsPre = document.getElementById('sync-steps-list-pre');
const stepsPost = document.getElementById('sync-steps-list-post');

const isVideoJSActive = window.AlbumPilotConfig.videojsActive === true;
const isSmartAlbumsActive = window.AlbumPilotConfig.smartalbumsActive === true;

// Define step list with labels and URLs

const urls = [].concat(
  [
    ['step1', t('step_sync_files'), root + pluginPageUrl + '&wrapped_sync=1&pwg_token=' + token],
    ['step2', t('step_generate_thumbnails'), root + pluginPageUrl + '&generate_image_thumbs=1&album_id=ALBUM_ID&pwg_token=' + token],
    ['step3', t('step_generate_video_posters') + (isVideoJSActive ? '' : ' (' + t('videojs_not_active') + ')'), root + pluginPageUrl + '&video_thumb_block=1&cat_id=ALBUM_ID&pwg_token=' + token],
    ['step4', t('step_calculate_checksums'), root + pluginPageUrl + '&calculate_md5=1&album_id=ALBUM_ID&pwg_token=' + token],
    ['step5', t('step_update_metadata'), root + pluginPageUrl + '&update_metadata_for_album=ALBUM_ID&pwg_token=' + token],
  ],
  [
    ['step6', t('step_reassign_smart_albums') + (isSmartAlbumsActive ? '' : ' (' + t('smartalbums_not_active') + ')'), root + 'admin.php?page=plugin-SmartAlbums-cat_list&smart_generate=all'],
    ['step7', t('step_update_album_metadata'), root + 'admin.php?page=maintenance&action=categories&pwg_token=' + token],
    ['step8', t('step_update_photo_information'), root + 'admin.php?page=maintenance&action=images&pwg_token=' + token],
    ['step9', t('step_optimize_database'), root + 'admin.php?page=maintenance&action=database&pwg_token=' + token],
    ['step10', t('step_run_integrity_check'), root + 'admin.php?page=maintenance&action=c13y&pwg_token=' + token]
  ]
);

// Render step checkboxes
urls.forEach((step, index) => {
  const li = document.createElement('li');
  const label = document.createElement('label');
  const checkbox = document.createElement('input');

  checkbox.type = 'checkbox';
  checkbox.className = 'step-checkbox';
  checkbox.id = step[0];

  // By default, step 4 is disabled, all others enabled
  checkbox.checked = step[0] !== 'step5';

  // Disable step 3 if VideoJS is not active
  if (step[0] === 'step3' && !isVideoJSActive) {
    checkbox.disabled = true;
  }

  // Disable step 6 if SmartAlbums is not active
  if (step[0] === 'step6' && !isSmartAlbumsActive) {
    checkbox.disabled = true;
  }

  label.appendChild(checkbox);
  label.append(' ' + step[1]);
  li.appendChild(label);

  const targetList = index <= 4 ? stepsPre : stepsPost;
  targetList.appendChild(li);
});


generateExternalUrlFromSelection();


// Wait until checkboxes are rendered
setTimeout(() => {
  autoStartFromExternalParams();
}, 300);


// Handle "Select all steps" toggle + save state
document.getElementById('select-all-steps').addEventListener('change', function () {
  const newState = this.checked;

  const syncSettings = {};

  document.querySelectorAll('.step-checkbox').forEach(cb => {
    if (!cb.disabled) {
      cb.checked = newState;
      syncSettings[cb.id] = newState ? '1' : '0';
    } else {
      syncSettings[cb.id] = '0'; // Disabled steps stay off
    }
  });

  saveSyncSettings(syncSettings);
});

// Apply saved checkbox settings after load
if (savedSettings) {
  if ('simulate' in savedSettings) {
    document.getElementById('simulate-mode').checked = savedSettings['simulate'] === '1';
  }
  if ('onlyNew' in savedSettings) {
    document.getElementById('only-new-files').checked = savedSettings['onlyNew'] === '1';
  }
  if ('includeSubalbums' in savedSettings) {
    document.getElementById('include-subalbums').checked = savedSettings['includeSubalbums'] === '1';
  }

  for (const key in savedSettings) {
    const cb = document.getElementById(key);
    if (cb && cb.type === 'checkbox') {
      cb.checked = savedSettings[key] === '1';
    }
  }
}



// Album selector handling
const select = document.getElementById('album-list');
const hiddenInput = document.getElementById('album-select');

if (select) {
  select.addEventListener('change', function () {
    hiddenInput.value = this.value;

    fetch(pluginPageUrl, {

      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        save_sync_settings: true,
        settings: {
          selectedAlbum: this.value
        }
      })
    });
  });

  // Restore selected album on load
  if (savedSettings?.selectedAlbum) {
    select.value = savedSettings.selectedAlbum;
    hiddenInput.value = savedSettings.selectedAlbum;

    const selectedOption = select.querySelector('option[value="' + savedSettings.selectedAlbum + '"]');

    if (selectedOption) {
      selectedOption.selected = true;
      select.scrollTop = selectedOption.offsetTop - select.clientHeight / 2;
    }
	generateExternalUrlFromSelection(); 
  }
}

// Event-Listener for updating existing URL
['simulate-mode', 'only-new-files', 'include-subalbums', 'album-select', 'select-all-steps'].forEach(id => {
  const el = document.getElementById(id);
  if (el) el.addEventListener('change', generateExternalUrlFromSelection);
});

document.querySelectorAll('.step-checkbox').forEach(cb => {
  cb.addEventListener('change', generateExternalUrlFromSelection);
});

  document.getElementById('start-sync').addEventListener('click', async function () {
  generateExternalUrlFromSelection(); // Ensure current URL is regenerated

// Security mechanism: prevent double clicks + warn before leaving
if (window.syncInProgress) return; // Already running? → Do nothing

const simulate = document.getElementById('simulate-mode')?.checked || false;
const onlyNew = document.getElementById('only-new-files')?.checked || false;
const includeSubalbums = document.getElementById('include-subalbums')?.checked || false;
const albumId = hiddenInput.value;

fetch(config.pluginPageUrl + '&sync_begin=1'
  + '&album=' + albumId
  + '&simulate=' + (simulate ? '1' : '0')
  + '&onlynew=' + (onlyNew ? '1' : '0')
  + '&subalbums=' + (includeSubalbums ? '1' : '0')
  + '&pwg_token=' + token, {
  credentials: 'same-origin'
});

// 🧠 Save checkbox settings on start
const syncSettings = {
  simulate: simulate ? '1' : '0',
  onlyNew: onlyNew ? '1' : '0',
  includeSubalbums: includeSubalbums ? '1' : '0'
};

document.querySelectorAll('.step-checkbox').forEach(cb => {
  syncSettings[cb.id] = cb.checked ? '1' : '0';
});

saveSyncSettings(syncSettings);

if (!albumId) {
  alert(t('select_album_alert'));
  return;
}

const selectedSteps = urls
  .filter(step => {
    const cb = document.getElementById(step[0]);
    return cb && cb.checked;
  })
  .map(step => {
    const id = step[0];
    const name = step[1];
    let url = step[2].replace(/ALBUM_ID/g, albumId);
    url += '&simulate=' + (simulate ? '1' : '0');
    url += '&onlynew=' + (onlyNew ? '1' : '0');

    if (id === 'step1') {
      url += '&album=' + albumId;
    }

    if (includeSubalbums && ['step1', 'step2', 'step3', 'step4', 'step5'].includes(id)) {
      url += '&subalbums=1';
    }

    return { id, name, url };
  });

if (selectedSteps.length === 0) {
  alert(t('select_step_alert'));
  return;
}

await resetProgress();
offset = 0;

window.syncInProgress = true;

const startBtn = document.getElementById('start-sync');

// Store original button HTML only once
if (!window.originalStartBtnHTML) {
  window.originalStartBtnHTML = startBtn.innerHTML;
}

startBtn.disabled = true;
startBtn.innerHTML = '<i class="icon-spin animate-spin"></i> ' + t('sync_in_progress');

// Show warning if user tries to leave during sync
window.addEventListener('beforeunload', function (e) {
  if (window.syncInProgress) {
    e.preventDefault();
    e.returnValue = t('leave_warning');
    return e.returnValue;
  }
});

const steps = document.getElementById('sync-steps');
const logBox = document.getElementById('sync-log');
logBox.style.display = 'block';

steps.innerHTML = '';
logBox.innerHTML = `
  <h4 class="progress-heading">` + t('progress_heading') + `</h4>
  <div id="progress-log" style="margin-top:10px; font-family:monospace;"></div>
`;

let index = 0;

function next() {
  if (index >= selectedSteps.length) {
    steps.innerHTML += '<li><strong>✅ ' + t('all_steps_completed') + '</strong></li>';

    
    // Sync completed: Re-enable buttons and remove page leave warning
    window.syncInProgress = false;
    startBtn.disabled = false;
    startBtn.innerHTML = window.originalStartBtnHTML;

    window.onbeforeunload = null;

    document.getElementById('progress-log').innerHTML += '<div><strong>✅ ' + t('workflow_finished') + '</strong></div>';

    fetch(config.pluginPageUrl + '&sync_end=1&pwg_token=' + token, {
      credentials: 'same-origin'
    });

    return;
  }

  const step = selectedSteps[index];
  const li = document.createElement('li');
  steps.appendChild(li);

  const skipInSimulation = simulate && ['step6', 'step7', 'step8', 'step9', 'step10'].includes(step.id);

  if (skipInSimulation) {
    li.innerHTML = '⏩ <em>' + step.name + '</em> <small>(' + t('skipped_simulation_mode') + ')</small>';

    index++;
    setTimeout(next, 600);
    return;
  }

  li.textContent = '🔄 ' + step.name;

  if (['step1', 'step2', 'step3', 'step4', 'step5'].includes(step.id)) {
    const log = document.getElementById('progress-log');
    const heading = document.createElement('h5');
    heading.textContent = step.name;
    heading.className = 'step-heading';
    log.appendChild(heading);

    // Steps with progressive feedback
    if (['step2', 'step3', 'step4', 'step5'].includes(step.id)) { // Step 4 added here
      let offset = 0;
      let progressElement = null;
      let filenameElement = null;

      function fetchChunk() {
        // Handle step 4 like step 5 regarding the offset parameter
        const fetchUrl = (['step2', 'step4', 'step5'].includes(step.id))
  ? step.url + '&offset=' + offset
  : step.url;


        handleJsonStep(fetchUrl, (data) => {
          const logEntries = Array.isArray(data.log) ? data.log : [data.log];

          logEntries.forEach(entry => {
            if (typeof entry === 'object' && entry.type === 'progress' && ['thumbnail', 'video', 'checksum', 'metadata'].includes(entry.step)) {

              const percent = entry.percent || 0;
              const filename = entry.path?.split('/').pop() || '(unknown)';

              const typeSuffix = entry.thumb_type ? ` – ${t('thumb_type_label')}: ${entry.thumb_type}` : '';


              const stepName = entry.step === 'video' ? t('step_video')
                             : entry.step === 'thumbnail' ? t('step_thumbnail')
                             : entry.step === 'checksum' ? t('step_checksum')
                             : entry.step === 'metadata' ? t('step_metadata')
                             : entry.step;


              const label = `🖼️ ${stepName} ${entry.index} ${t('of')} ${entry.total} (${percent}%) – ${t('image_id')} ${entry.image_id}${entry.simulate ? t('simulation_suffix') : ''}${typeSuffix}`;


              if (!progressElement) {
                progressElement = document.createElement('div');
                progressElement.id = step.id + '-progress-line';
                progressElement.className = 'sync-step-block progress';
                progressElement.style.marginBottom = '2px';
                log.appendChild(progressElement);
              }

              if (!filenameElement) {
                filenameElement = document.createElement('div');
                filenameElement.id = step.id + '-filename-line';
                filenameElement.className = 'sync-step-block';
                filenameElement.style.marginBottom = '10px';
                log.appendChild(filenameElement);
              }

              progressElement.innerHTML =
                '<div style="display: flex; align-items: center; gap: 12px; padding: 2px 0;">' +
                  '<div style="width: 600px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' +
                    label +
                  '</div>' +
                  '<div style="width: 200px; height: 12px; background: #eee; border: 1px solid #ccc; border-radius: 3px;">' +
                    '<div style="height: 100%; width: ' + percent + '%; background: #007bff; transition: width 0.3s ease;"></div>' +
                  '</div>' +
                '</div>';

              filenameElement.textContent = '📄 ' + t('file_label') + ': ' + filename;

            }

            // Log string entries (warnings, errors, status)
            if (typeof entry === 'string') {
              const div = document.createElement('div');
              div.textContent = entry;
              div.className = (entry.startsWith('❌') || entry.startsWith('⚠️')) ? 'sync-step-block error' : 'sync-step-block';
              log.appendChild(div);
            }
          });

          if (typeof data.offset !== 'undefined') {
            offset = data.offset;
          }

          if (!data.done) {
            setTimeout(fetchChunk, 400);
          } else {
            const doneLine = document.createElement('div');
            doneLine.className = 'sync-success-block';
            doneLine.textContent = data.summary || '✅ ' + t('step_completed') + ': ' + step.name;
            log.appendChild(doneLine);

            const bar = document.getElementById(step.id + '-progress-line');
            if (bar) bar.remove();

            const fname = document.getElementById(step.id + '-filename-line');
            if (fname) fname.remove();

            li.innerHTML = '✅ ' + step.name;
            index++;
            setTimeout(next, 800);
          }
        });
      }

      fetchChunk();
      return;
    }
 
  // Steps without progress feedback (only step 1)
handleJsonStep(step.url, (data) => {

  let resultHTML = '';

  if (data.message) {
    resultHTML += '<div>' + data.message + '</div>';
  }

  if (data.raw_output) {
    resultHTML += '<div>' + data.raw_output + '</div>';
  }

  const resultBlock = document.createElement('div');
  resultBlock.innerHTML = resultHTML;
  log.appendChild(resultBlock);

  li.innerHTML = '✅ ' + step.name;
  index++;
  setTimeout(next, 1000);
});

return;
}


// 💡 Step 6: Extract only text lines from .infos
if (step.id === 'step6') {
  fetch(step.url, { credentials: 'same-origin' })
    .then(res => res.text())
    .then(html => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');

      const infoListItems = doc.querySelectorAll('.eiw .infos li');
      const log = document.getElementById('progress-log');

      // Show heading
      const heading = document.createElement('h5');
      heading.textContent = step.name;
      heading.className = 'step-heading';
      log.appendChild(heading);

      if (infoListItems.length === 0) {
        const msg = document.createElement('div');
        msg.textContent = '⚠️ ' + t('no_info_found');
        log.appendChild(msg);
      } else {
        infoListItems.forEach(li => {
          const line = document.createElement('div');
          line.textContent = li.textContent.trim();
          log.appendChild(line);
        });
      }

      li.innerHTML = '✅ ' + step.name;
      index++;
      setTimeout(next, 1000);
    })
    .catch(err => {
      const log = document.getElementById('progress-log');
      log.innerHTML += '<div style="color:red;">❌ ' + t('error_during_step') + ' <strong>' + step.name + '</strong>: ' + err.message + '</div>';
      li.innerHTML = '❌ ' + step.name;
      index++;
      setTimeout(next, 1000);
    });
  return;
}


if (['step7', 'step8', 'step9', 'step10'].includes(step.id)) {
  fetch(step.url, { credentials: 'same-origin' })
    .then(function (res) { return res.text(); })
    .then(function (html) {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const log = document.getElementById('progress-log');

      // Insert heading
      const heading = document.createElement('h5');
      heading.textContent = step.name;
      heading.className = 'step-heading';
      log.appendChild(heading);

      const infoList = doc.querySelectorAll('.eiw .infos li');
      if (infoList.length > 0) {
        infoList.forEach(function (liElement) {
          const div = document.createElement('div');
          div.textContent = liElement.textContent.trim();
          log.appendChild(div);
        });
      } else {
        const div = document.createElement('div');
        div.textContent = '⚠️ ' + t('no_success_message');
        log.appendChild(div);
      }

      li.innerHTML = '✅ ' + step.name;
      index++;
      setTimeout(next, 1000);
    })
    .catch(function (err) {
      const log = document.getElementById('progress-log');
      log.innerHTML += '<div style="color:red;">❌ ' + t('error_during_step') + ' <strong>' + step.name + '</strong>: ' + err.message + '</div>';
      li.innerHTML = '❌ ' + step.name;
      index++;
      setTimeout(next, 1000);
    });
  return;
}

}

next();
});


// Reset all settings to default values
document.getElementById('reset-settings').addEventListener('click', function () {
  const allSteps = [].concat(
    ['step1', 'step2', 'step3', 'step4'],
    ['step5', 'step6', 'step7', 'step8', 'step9', 'step10']
  );

  const syncSettings = {
    simulate: '1',
    onlyNew: '1',
    includeSubalbums: '1',
    selectedAlbum: ''
  };

  allSteps.forEach(id => {
    const cb = document.getElementById(id);
    if (cb) {
      // Only activate if not disabled
      if (!cb.disabled) {
        cb.checked = id !== 'step5';
      }
      // Always store value — disabled steps always "0"
      syncSettings[id] = (!cb.disabled && id !== 'step5') ? '1' : '0';
    }
  });

  document.getElementById('simulate-mode').checked = true;
  document.getElementById('only-new-files').checked = true;
  document.getElementById('include-subalbums').checked = true;
  document.getElementById('select-all-steps').checked = false;

  const albumSelect = document.getElementById('album-list');
  const albumHidden = document.getElementById('album-select');
  if (albumSelect) albumSelect.value = '';
  if (albumHidden) albumHidden.value = '';

  saveSyncSettings(syncSettings);
});


// --- Auto-start sync when triggered via external_run URL parameter ---
function autoStartFromExternalParams() {
  const params = new URLSearchParams(window.location.search);

  if (params.get('external_run') !== '1') {
    return;
  }

  const albumId = params.get('album') || '';
  const simulate = params.get('simulate') === '1';
  const onlyNew = params.get('onlynew') === '1';
  const subalbums = params.get('subalbums') === '1';
  const stepIds = (params.get('steps') || '')
    .split(',')
    .map(s => s.trim())
    .filter(Boolean);

  // Select album
  const albumSelect = document.getElementById('album-list');
  const albumHidden = document.getElementById('album-select');
  if (albumSelect && albumId) {
    albumSelect.value = albumId;
    albumHidden.value = albumId;
  }

  // Set options
  const simulateCheckbox = document.getElementById('simulate-mode');
  const onlyNewCheckbox = document.getElementById('only-new-files');
  const includeSubalbumsCheckbox = document.getElementById('include-subalbums');

  if (simulateCheckbox) {
    simulateCheckbox.checked = simulate;
    simulateCheckbox.dispatchEvent(new Event('change'));
  }
  if (onlyNewCheckbox) {
    onlyNewCheckbox.checked = onlyNew;
    onlyNewCheckbox.dispatchEvent(new Event('change'));
  }
  if (includeSubalbumsCheckbox) {
    includeSubalbumsCheckbox.checked = subalbums;
    includeSubalbumsCheckbox.dispatchEvent(new Event('change'));
  }

  // Enable steps
  stepIds.forEach(id => {
    const stepCheckbox = document.getElementById('step' + id);
    if (stepCheckbox && !stepCheckbox.disabled) {
      stepCheckbox.checked = true;
      stepCheckbox.dispatchEvent(new Event('change'));
    }
  });

// Start sync (robust bei langsamer Initialisierung)
function waitAndStartSyncWithFocusCheck() {
  const startBtn = document.getElementById('start-sync');

  if (!startBtn || !document.getElementById('album-list')?.value) {
    return setTimeout(waitAndStartSyncWithFocusCheck, 200);
  }

  // Warten auf Sichtbarkeit & Fokus
  if (document.visibilityState !== 'visible' || !document.hasFocus()) {
    return setTimeout(waitAndStartSyncWithFocusCheck, 200);
  }

  // Sicherstellen, dass DOM komplett stabil ist
  requestAnimationFrame(() => {
    setTimeout(() => {
      try {
        const event = new MouseEvent('click', {
          bubbles: true,
          cancelable: true,
          view: window
        });

        startBtn.dispatchEvent(event);
        console.log('[AlbumPilot] Triggered start-sync via simulated click');
      } catch (e) {
        console.warn('[AlbumPilot] Fallback to .click():', e);
        startBtn.click();
      }
    }, 100);
  });
}

waitAndStartSyncWithFocusCheck();



}


});
