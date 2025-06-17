/*
File: template/script.js – AlbumPilot Plugin for Piwigo
Author: Hendrik Schöttle
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
 */

document.addEventListener('DOMContentLoaded', function () {

    // Toggle enable/disable of dependent checkboxes (e.g. for thumb/video options)
    function updateDependentCheckboxStates() {
        const step3 = document.getElementById('step3');
        const step2 = document.getElementById('step2');

        const thumbOptions = document.querySelectorAll('.thumb-type-checkbox');
        const videoWrapper = document.querySelector('.videojs-options-wrapper');

        // Thumbnails (Step 3)
        thumbOptions.forEach(cb => {
            cb.disabled = !step3?.checked;
        });

        // VideoJS (Step 2)
        if (!step2?.checked || !videoWrapper) {
            // Deactivate everything
            videoWrapper?.querySelectorAll('input, select').forEach(el => el.disabled = true);
            return;
        }

        // Ensure all video option inputs are enabled before applying specific state
        // (this re-enables the main checkboxes like "import poster" and "create poster")
        videoWrapper.querySelectorAll('input, select').forEach(el => el.disabled = false);

        // Activate only certain fields
        const createPoster = videoWrapper.querySelector('[data-key="videojs_create_poster"]')?.checked;
        const addThumbs = videoWrapper.querySelector('[data-key="videojs_add_thumbs"]')?.checked;

        const posterSecond = videoWrapper.querySelector('.videojs-poster-second');
        const posterOverwrite = videoWrapper.querySelector('[data-key="videojs_poster_overwrite"]');
        const overlay = videoWrapper.querySelector('[data-key="videojs_add_overlay"]');
        const outputRadios = videoWrapper.querySelectorAll('input[name="videojs_output_format"]');

        if (posterSecond)
            posterSecond.disabled = !createPoster;
        if (posterOverwrite)
            posterOverwrite.disabled = !createPoster;
        if (overlay)
            overlay.disabled = !createPoster;
        outputRadios.forEach(r => r.disabled = !createPoster);

        const thumbInterval = videoWrapper.querySelector('.videojs-thumb-interval');
        const thumbSize = videoWrapper.querySelector('.videojs-size-input');

        if (thumbInterval)
            thumbInterval.disabled = !addThumbs;
        if (thumbSize)
            thumbSize.disabled = !addThumbs;
    }

    function disableAllInputsForBatchMode() {
        if (window.location.search.includes('external_run=1') || window.syncInProgress) {

            const elements = document.querySelectorAll(
                    'input, select, textarea, button');

            elements.forEach(el => {
                el.disabled = true;
                el.style.opacity = 0.6;
                el.style.cursor = 'not-allowed';
                el.title = 'Disabled in batch mode';
            });

            const logBox = document.getElementById('sync-log');
            if (logBox)
                logBox.style.opacity = 1;

            document.querySelector('.album-sync-wrapper')?.classList.add('batch-mode');

        }
    }

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
        const params = new URLSearchParams(window.location.search);
        const isExternalRun = params.get('external_run') === '1';

        if (isExternalRun) {
            return; // Skip if running in batch mode
        }

        fetch(pluginPageUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                save_sync_settings: true,
                settings: settingsObj
            })
        });
    }

    // Robust against invalid JSON responses (e.g. PHP warnings as HTML)
    window.fetchSafeJSON = async function (url, options = {}) {
        try {
            const res = await fetch(url, {
                credentials: 'same-origin',
                ...options
            });
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
    };
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
    const pluginBasePath = pluginPageUrl.replace(/admin\.php.*/, '');

    // Normalize plugin URL to ensure leading slash
    const safePluginUrl = pluginPageUrl.startsWith('/') ? pluginPageUrl : '/' + pluginPageUrl;
    const root = config.rootUrl;
    const token = config.token;

    // Function for generating external URL
    window.generateExternalUrlFromSelection = function generateExternalUrlFromSelection() {

        const albumId = document.querySelector('#album-list')?.value;
        const simulate = document.querySelector('#simulate')?.checked;
        const onlyNew = document.querySelector('#onlyNew')?.checked;
        const includeSubalbums = document.querySelector('#includeSubalbums')?.checked;

        const textArea = document.getElementById('external-url-chain');
        if (!textArea)
            return;

        // Collect selected steps (IDs only, e.g., "1", "2", "5")
        const selectedStepIds = [...new Set(
                Array.from(document.querySelectorAll('.step-list input[type=checkbox]:checked'))
                .map(cb => {
                    const match = cb.id.match(/^step(\d+)$/);
                    return match ? match[1] : null;
                })
                .filter(Boolean))];

        // No album selected
        if (!albumId) {
            const msg = window.AlbumPilotLang?.select_album_alert || 'Please select an album.';
            textArea.value = '⚠️ ' + msg;
            return;
        }

        // No steps selected
        if (selectedStepIds.length === 0) {
            const msg = window.AlbumPilotLang?.select_step_alert || 'Please select at least one step.';
            textArea.value = '⚠️ ' + msg;
            return;
        }

        // All inputs valid – generate external URL
        const base = new URL(window.location.href);
        const fullPath = new URL(config.pluginPageUrl, base);

        fullPath.searchParams.set('external_run', '1');
        fullPath.searchParams.set('album', albumId);
        fullPath.searchParams.set('simulate', simulate ? '1' : '0');
        fullPath.searchParams.set('onlynew', onlyNew ? '1' : '0');
        fullPath.searchParams.set('subalbums', includeSubalbums ? '1' : '0');
        fullPath.searchParams.set('steps', selectedStepIds.join(','));

        // Add selected thumb types if step3 is selected
        if (selectedStepIds.includes('3')) {
            const selectedThumbTypes = [];
            document.querySelectorAll('.thumb-type-checkbox').forEach(cb => {
                if (cb.checked)
                    selectedThumbTypes.push(cb.value);
            });

            // Immer thumb_types setzen (auch wenn leer)
            fullPath.searchParams.set('thumb_types', selectedThumbTypes.join(','));

            // thumb_overwrite unabhängig von Auswahl setzen
            const overwriteThumbs = document.querySelector('.thumb-overwrite-checkbox')?.checked;
            fullPath.searchParams.set('thumb_overwrite', overwriteThumbs ? '1' : '0');
        }

        // Add VideoJS parameters if step2 is selected
        if (selectedStepIds.includes('2')) {
            const posterSec = document.querySelector('.videojs-poster-second')?.value || '4';
            const thumbInterval = document.querySelector('.videojs-thumb-interval')?.value || '5';
            const thumbSize = document.querySelector('.videojs-size-input')?.value || '120x68';
            const outputFormat = document.querySelector('input[name="videojs_output_format"]:checked')?.value || 'jpg';

            const videoOptions = {};
            document.querySelectorAll('.videojs-option').forEach(cb => {
                const key = cb.dataset.key;
                if (key)
                    videoOptions[key] = cb.checked ? '1' : '0';
            });

            fullPath.searchParams.set('poster_second', posterSec);
            fullPath.searchParams.set('thumb_interval', thumbInterval);
            fullPath.searchParams.set('thumb_size', thumbSize);
            fullPath.searchParams.set('output_format', outputFormat);

            for (const [k, v] of Object.entries(videoOptions)) {
                fullPath.searchParams.set(k, v);
            }
        }

        const url = fullPath.toString();
        textArea.value = url;
        textArea.setAttribute('value', url); // Optional: supports older browsers
    }

    let settings = config.savedSettings;
    let syncSettings = {};

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
            ['step2', t('step_generate_video_posters') + (isVideoJSActive ? '' : ' (' + t('videojs_not_active') + ')'), root + pluginPageUrl + '&video_thumb_block=1&cat_id=ALBUM_ID&pwg_token=' + token],
            ['step3', t('step_generate_thumbnails'), root + pluginPageUrl + '&generate_image_thumbs=1&album_id=ALBUM_ID&pwg_token=' + token],
            ['step4', t('step_calculate_checksums'), root + pluginPageUrl + '&calculate_md5=1&album_id=ALBUM_ID&pwg_token=' + token],
            ['step5', t('step_update_metadata'), root + pluginPageUrl + '&update_metadata_for_album=ALBUM_ID&pwg_token=' + token],
        ],
        [
            ['step6', t('step_reassign_smart_albums') + (isSmartAlbumsActive ? '' : ' (' + t('smartalbums_not_active') + ')'), root + 'admin.php?page=plugin-SmartAlbums-cat_list&smart_generate=all'],
            ['step7', t('step_update_album_metadata'), root + 'admin.php?page=maintenance&action=categories&pwg_token=' + token],
            ['step8', t('step_update_photo_information'), root + 'admin.php?page=maintenance&action=images&pwg_token=' + token],
            ['step9', t('step_optimize_database'), root + 'admin.php?page=maintenance&action=database&pwg_token=' + token],
            ['step10', t('step_run_integrity_check'), root + 'admin.php?page=maintenance&action=c13y&pwg_token=' + token]
        ]);

    // Render step checkboxes
    urls.forEach((step, index) => {
        const li = document.createElement('li');
        const label = document.createElement('label');
        const checkbox = document.createElement('input');

        checkbox.type = 'checkbox';
        checkbox.className = 'step-checkbox';
        checkbox.id = step[0];

        // By default, step 5 (metadata) is unchecked; others remain checked initially
        checkbox.checked = step[0] !== 'step5';

        // For VideoJS step (step2), only enable if plugin is active
        if (step[0] === 'step2') {
            // plugin inactive → leave unchecked and disabled
            checkbox.checked = isVideoJSActive;
            checkbox.disabled = !isVideoJSActive;
        }

        // For SmartAlbums step (step6), only enable if plugin is active
        if (step[0] === 'step6') {
            checkbox.checked = isSmartAlbumsActive;
            checkbox.disabled = !isSmartAlbumsActive;
        }

        label.appendChild(checkbox);
        const span = document.createElement('span');
        span.innerHTML = ' ' + step[1];
        label.appendChild(span);

        li.appendChild(label);
        // create Options for thumb generation

        if (step[0] === 'step3') {
            if (typeof window.renderImageOptions === 'function') {
                window.renderImageOptions({
                    stepCheckbox: checkbox,
                    listItem: li,
                    savedSettings,
                    t
                });
            } else {
                console.warn('⚠️ renderImageOptions not defined');
            }
        }

        // ✅ Einfacher globaler Zugriff – funktioniert mit {combine_script}
        if (step[0] === 'step2') {
            if (typeof window.renderVideoOptions === 'function') {
                window.renderVideoOptions({
                    stepCheckbox: checkbox,
                    listItem: li,
                    savedSettings,
                    t
                });
            } else {
                console.warn('⚠️ renderVideoOptions not defined');
            }
        }

        const targetList = index <= 4 ? stepsPre : stepsPost;
        targetList.appendChild(li);

    });

    requestAnimationFrame(() => {
        updateDependentCheckboxStates();
        requestAnimationFrame(() => generateExternalUrlFromSelection());
    });

    // Automatically regenerate external URL when relevant inputs change
    document.addEventListener('change', function (e) {
        if (
            e.target.classList.contains('thumb-type-checkbox') ||
            e.target.classList.contains('thumb-overwrite-checkbox') ||
            e.target.classList.contains('videojs-option') ||
            e.target.classList.contains('videojs-time-input') ||
            e.target.classList.contains('videojs-size-input') ||
            (e.target.name === 'videojs_output_format')) {

            generateExternalUrlFromSelection();
        }
    });

    document.addEventListener('input', function (e) {
        if (
            e.target.classList.contains('videojs-time-input') ||
            e.target.classList.contains('videojs-size-input')) {

            generateExternalUrlFromSelection();
        }
    });

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
                syncSettings[cb.id] = '0';
            }
        });

        // Apply dependency logic for UI (enable/disable sub-options)
        updateDependentCheckboxStates();

        saveSyncSettings(syncSettings);
    });

    // Apply saved checkbox settings after load
    const thumbOverwriteCb = document.querySelector('.thumb-overwrite-checkbox');
    if (thumbOverwriteCb && 'thumb_overwrite' in savedSettings) {
        thumbOverwriteCb.checked = savedSettings['thumb_overwrite'] === '1';
    }

    const urlParams = new URLSearchParams(window.location.search);
    const isExternalRun = urlParams.get('external_run') === '1';

    if (savedSettings && !isExternalRun) {

        if ('simulate' in savedSettings) {
            document.getElementById('simulate').checked = savedSettings['simulate'] === '1';
        }
        if ('onlyNew' in savedSettings) {
            document.getElementById('onlyNew').checked = savedSettings['onlyNew'] === '1';
        }
        if ('includeSubalbums' in savedSettings) {
            document.getElementById('includeSubalbums').checked = savedSettings['includeSubalbums'] === '1';
        }

        for (const key in savedSettings) {
            const cb = document.getElementById(key);
            if (cb && cb.type === 'checkbox') {
                cb.checked = savedSettings[key] === '1';
            }
        }

        document.querySelectorAll('.videojs-option').forEach(cb => {
            const key = cb.dataset.key;
            if (key in savedSettings) {
                cb.checked = savedSettings[key] === '1';
                syncSettings[key] = savedSettings[key];
            }
        });

        const posterSec = savedSettings['videojs_poster_second'];
        const thumbInterval = savedSettings['videojs_thumbs_interval'];
        const thumbSize = savedSettings['videojs_thumbs_size'];
        const outputFormat = savedSettings['videojs_output_format'];

        if (posterSec)
            document.querySelector('.videojs-poster-second').value = posterSec;
        if (thumbInterval)
            document.querySelector('.videojs-thumb-interval').value = thumbInterval;
        if (thumbSize)
            document.querySelector('.videojs-size-input').value = thumbSize;
        if (outputFormat) {
            const formatRadio = document.querySelector(`input[name="videojs_output_format"][value="${outputFormat}"]`);
            if (formatRadio)
                formatRadio.checked = true;
        }

        syncSettings['videojs_poster_second'] = posterSec;
        syncSettings['videojs_thumbs_interval'] = thumbInterval;
        syncSettings['videojs_thumbs_size'] = thumbSize;
        syncSettings['videojs_output_format'] = outputFormat;

        document.querySelectorAll('.thumb-type-checkbox').forEach(cb => {
            const key = 'thumb_type_' + cb.value;
            if (key in savedSettings) {
                cb.checked = savedSettings[key] === '1';
            }
        });

        // Save VideoJS-related settings
        document.querySelectorAll('.videojs-option').forEach(cb => {
            const key = cb.dataset.key;
            if (key) {
                syncSettings[key] = cb.checked ? '1' : '0';
            }
        });

        syncSettings['videojs_poster_second'] = document.querySelector('.videojs-poster-second')?.value || '4';
        syncSettings['videojs_thumbs_interval'] = document.querySelector('.videojs-thumb-interval')?.value || '5';
        syncSettings['videojs_thumbs_size'] = document.querySelector('.videojs-size-input')?.value || '120x68';
        syncSettings['videojs_output_format'] = document.querySelector('input[name="videojs_output_format"]:checked')?.value || 'jpg';

        saveSyncSettings(syncSettings);

        // Deaktiviere Thumb-Optionen, wenn step3 beim Laden deaktiviert ist
        const step3Checkbox = document.getElementById('step3');
        const thumbOverwrite = document.querySelector('.thumb-overwrite-checkbox');
        const thumbTypeCheckboxes = document.querySelectorAll('.thumb-type-checkbox');

        if (step3Checkbox && !step3Checkbox.checked) {
            if (thumbOverwrite)
                thumbOverwrite.disabled = true;
            thumbTypeCheckboxes.forEach(cb => cb.disabled = true);
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
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    save_sync_settings: true,
                    settings: {
                        selectedAlbum: this.value
                    }
                })
            });

            // Update the external URL after album selection changes
            generateExternalUrlFromSelection();
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

            // also mirror into the typeahead field on initial load
            const ta = document.getElementById('album-typeahead');
            if (ta && Array.isArray(window.AlbumPilotAlbums)) {
                const found = window.AlbumPilotAlbums.find(a => a.id === savedSettings.selectedAlbum);
                if (found) {
                    ta.value = found.fullPath;
                }
            }

        }
    }

    // Event-Listener for updating existing URL
    ['simulate', 'onlyNew', 'includeSubalbums', 'album-select', 'select-all-steps'].forEach(id => {
        const el = document.getElementById(id);
        if (el)
            el.addEventListener('change', generateExternalUrlFromSelection);
    });

    document.querySelectorAll('.step-checkbox').forEach(cb => {
        cb.addEventListener('change', generateExternalUrlFromSelection);
    });

    document.getElementById('start-sync').addEventListener('click', async function () {
        generateExternalUrlFromSelection(); // Ensure current URL is regenerated

        // mirror full album path into the typeahead input on Sync start
        const ta = document.getElementById('album-typeahead');
        if (ta && Array.isArray(window.AlbumPilotAlbums)) {
            const album = window.AlbumPilotAlbums.find(a => a.id === hiddenInput.value);
            if (album) {
                ta.value = album.fullPath;
            }
        }

        // Security mechanism: prevent double clicks + warn before leaving
        if (window.syncInProgress)
            return; // Already running? → Do nothing

        const simulate = document.getElementById('simulate')?.checked || false;
        const onlyNew = document.getElementById('onlyNew')?.checked || false;
        const includeSubalbums = document.getElementById('includeSubalbums')?.checked || false;
        const albumId = hiddenInput.value;

        const syncBeginParams = new URLSearchParams();
        syncBeginParams.set('sync_begin', '1');
        syncBeginParams.set('album', albumId);
        syncBeginParams.set('simulate', simulate ? '1' : '0');
        syncBeginParams.set('onlynew', onlyNew ? '1' : '0');
        syncBeginParams.set('subalbums', includeSubalbums ? '1' : '0');
        syncBeginParams.set('pwg_token', token);

        // VideoJS-related options (only if step 2 is selected)
        if (document.getElementById('step2')?.checked) {
            syncBeginParams.set('poster_second', document.querySelector('.videojs-poster-second')?.value || '4');
            syncBeginParams.set('thumb_interval', document.querySelector('.videojs-thumb-interval')?.value || '5');
            syncBeginParams.set('thumb_size', document.querySelector('.videojs-size-input')?.value || '120x68');
            syncBeginParams.set('output_format', document.querySelector('input[name="videojs_output_format"]:checked')?.value || 'jpg');

            // Additional flags from checkboxes (e.g., import uploaded poster, overlay effect, etc.)
            document.querySelectorAll('.videojs-option').forEach(cb => {
                const key = cb.dataset.key;
                if (key)
                    syncBeginParams.set(key, cb.checked ? '1' : '0');
            });
        }

        // Thumbnail-related options (only if step 3 is selected)
        if (document.getElementById('step3')?.checked) {
            const selectedThumbTypes = [];
            document.querySelectorAll('.thumb-type-checkbox').forEach(cb => {
                if (cb.checked)
                    selectedThumbTypes.push(cb.value);
            });

            syncBeginParams.set('thumb_types', selectedThumbTypes.join(','));

            const overwrite = document.querySelector('.thumb-overwrite-checkbox')?.checked;
            syncBeginParams.set('thumb_overwrite', overwrite ? '1' : '0');
        }

        // Send sync_begin request with full context for logging
        fetch(config.pluginPageUrl + '&' + syncBeginParams.toString(), {
            credentials: 'same-origin'
        });

        // Save checkbox settings on start
        syncSettings.simulate = simulate ? '1' : '0';
        syncSettings.onlyNew = onlyNew ? '1' : '0';
        syncSettings.includeSubalbums = includeSubalbums ? '1' : '0';

        document.querySelectorAll('.step-checkbox').forEach(cb => {
            syncSettings[cb.id] = cb.checked ? '1' : '0';
        });

        // Save selected thumbnail types (step 2)

        const overwrite = document.querySelector('.thumb-overwrite-checkbox');
        if (overwrite) {
            syncSettings['thumb_overwrite'] = overwrite.checked ? '1' : '0';
        }
        document.querySelectorAll('.thumb-type-checkbox').forEach(cb => {
            syncSettings['thumb_type_' + cb.value] = cb.checked ? '1' : '0';
        });

        // Save VideoJS-related settings (step 3)
        document.querySelectorAll('.videojs-option').forEach(cb => {
            const key = cb.dataset.key;
            if (key) {
                syncSettings[key] = cb.checked ? '1' : '0';
            }
        });

        syncSettings['videojs_poster_second'] = document.querySelector('.videojs-poster-second')?.value || '4';
        syncSettings['videojs_thumbs_interval'] = document.querySelector('.videojs-thumb-interval')?.value || '5';
        syncSettings['videojs_thumbs_size'] = document.querySelector('.videojs-size-input')?.value || '120x68';
        syncSettings['videojs_output_format'] = document.querySelector('input[name="videojs_output_format"]:checked')?.value || 'jpg';

        saveSyncSettings(syncSettings);

        if (!albumId) {
            alert(t('select_album_alert'));
            return;
        }

        let selectedThumbTypes = [];

        document.querySelectorAll('.thumb-type-checkbox').forEach(cb => {
            if (cb.checked)
                selectedThumbTypes.push(cb.value);
        });

        const selectedSteps = urls
            .filter(step => {
                const cb = document.getElementById(step[0]);
                return cb && cb.checked;
            })
            .map(step => {
                const id = step[0];
                const name = step[1];
                let url = step[2].replace(/ALBUM_ID/g, albumId);
                // Only pass selected thumbnail types for step3
                if (id === 'step3') {
                    const selectedThumbTypes = [];
                    document.querySelectorAll('.thumb-type-checkbox').forEach(cb => {
                        if (cb.checked)
                            selectedThumbTypes.push(cb.value);
                    });

                    if (selectedThumbTypes.length > 0) {
                        url += '&thumb_types=' + encodeURIComponent(selectedThumbTypes.join(','));
                    }
                    const overwrite = document.querySelector('.thumb-overwrite-checkbox')?.checked;
                    if (overwrite) {
                        url += '&thumb_overwrite=1';
                    }

                }

                if (id === 'step2') {
                    const posterSec = document.querySelector('.videojs-poster-second')?.value || '4';
                    const thumbInterval = document.querySelector('.videojs-thumb-interval')?.value || '5';
                    const thumbSize = document.querySelector('.videojs-size-input')?.value || '120x68';
                    const outputFormat = document.querySelector('input[name="videojs_output_format"]:checked')?.value || 'jpg';

                    const videoOptions = {};
                    document.querySelectorAll('.videojs-option').forEach(cb => {
                        const key = cb.dataset.key;
                        if (key)
                            videoOptions[key] = cb.checked ? '1' : '0';
                    });

                    url += '&poster_second=' + encodeURIComponent(posterSec);
                    url += '&thumb_interval=' + encodeURIComponent(thumbInterval);
                    url += '&thumb_size=' + encodeURIComponent(thumbSize);
                    url += '&output_format=' + encodeURIComponent(outputFormat);

                    for (const [k, v] of Object.entries(videoOptions)) {
                        url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(v);
                    }
                }

                url += '&simulate=' + (simulate ? '1' : '0');
                url += '&onlynew=' + (onlyNew ? '1' : '0');

                if (id === 'step1') {
                    url += '&album=' + albumId;
                }

                if (['step1', 'step2', 'step3', 'step4', 'step5'].includes(id)) {
                    url += '&subalbums=' + (includeSubalbums ? '1' : '0');
                }

                return {
                    id,
                    name,
                    url
                };
            });

        if (selectedSteps.length === 0) {
            alert(t('select_step_alert'));
            return;
        }

        await resetProgress();
        offset = 0;

        window.syncInProgress = true;
        disableAllInputsForBatchMode();

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
  <div id="progress-log" style="margin-top:10px;"></div>
`;

        let index = 0;

        if (typeof window.next === 'function') {
            window._runnerIndex = 0;
            window._runnerSelectedSteps = selectedSteps;
            window.next();

        }

    });

    // Reset all settings to default values
    document.getElementById('reset-settings').addEventListener('click', function () {
        const syncSettings = {
            simulate: '1',
            onlyNew: '1',
            includeSubalbums: '1',
            selectedAlbum: ''
        };

        const allSteps = [].concat(
            ['step1', 'step3', 'step2', 'step4'],
            ['step5', 'step6', 'step7', 'step8', 'step9', 'step10']);

        allSteps.forEach(id => {
            const cb = document.getElementById(id);
            if (cb) {
                if (!cb.disabled) {
                    cb.checked = id !== 'step5';
                }
                syncSettings[id] = (!cb.disabled && id !== 'step5') ? '1' : '0';
            }
        });

        document.getElementById('simulate').checked = true;
        document.getElementById('onlyNew').checked = true;
        document.getElementById('includeSubalbums').checked = true;
        document.getElementById('select-all-steps').checked = false;

        // Reset thumbnail type checkboxes
        const thumbOverwriteCb = document.querySelector('.thumb-overwrite-checkbox');
        if (thumbOverwriteCb) {
            thumbOverwriteCb.checked = false;
            syncSettings['thumb_overwrite'] = '0';
        }

        document.querySelectorAll('.thumb-type-checkbox').forEach(cb => {
            cb.checked = true;
            syncSettings['thumb_type_' + cb.value] = '1';
        });

        // Reset all VideoJS-related checkboxes
        document.querySelectorAll('.videojs-option').forEach(cb => {
            const key = cb.dataset.key;
            if (!key)
                return;

            if (key === 'videojs_add_thumbs') {
                cb.checked = false;
                syncSettings[key] = '0';
            } else if (key === 'videojs_poster_overwrite') {
                cb.checked = false;
                syncSettings[key] = '0';
            } else {
                cb.checked = true;
                syncSettings[key] = '1';
            }

        });

        // Reset VideoJS input fields to default values
        const posterSecInput = document.querySelector('.videojs-poster-second');
        const thumbIntervalInput = document.querySelector('.videojs-thumb-interval');
        const thumbSizeInput = document.querySelector('.videojs-size-input');
        const outputJpg = document.querySelector('input[name="videojs_output_format"][value="jpg"]');

        if (posterSecInput)
            posterSecInput.value = '4';
        if (thumbIntervalInput)
            thumbIntervalInput.value = '5';
        if (thumbSizeInput)
            thumbSizeInput.value = '120x68';
        if (outputJpg)
            outputJpg.checked = true;

        syncSettings['videojs_poster_second'] = '4';
        syncSettings['videojs_thumbs_interval'] = '5';
        syncSettings['videojs_thumbs_size'] = '120x68';
        syncSettings['videojs_output_format'] = 'jpg';

        // Reset album selection
        const albumSelect = document.getElementById('album-list');
        const albumHidden = document.getElementById('album-select');
        if (albumSelect)
            albumSelect.value = '';
        if (albumHidden)
            albumHidden.value = '';

        const typeahead = document.getElementById('album-typeahead');
        const resultsBox = document.getElementById('album-typeahead-results');
        if (typeahead) {
            typeahead.value = '';
        }
        if (resultsBox) {
            resultsBox.style.display = 'none';
            resultsBox.innerHTML = '';
        }

        // Re-enable dependent fields (e.g. input fields under unchecked steps)
        requestAnimationFrame(() => updateDependentCheckboxStates());

        saveSyncSettings(syncSettings);

        generateExternalUrlFromSelection();
    });

    // --- Auto-start sync when triggered via external_run URL parameter ---
    function autoStartFromExternalParams() {
        const params = new URLSearchParams(window.location.search);

        if (params.get('external_run') !== '1')
            return;

        const albumId = params.get('album') || '';
        const simulate = params.get('simulate') === '1';
        const onlyNew = params.get('onlynew') === '1';
        const subalbums = params.get('subalbums') === '1';
        const stepIds = (params.get('steps') || '')
        .split(',')
        .map(s => s.trim())
        .filter(Boolean);

        // Set selected album
        const albumSelect = document.getElementById('album-list');
        const albumHidden = document.getElementById('album-select');
        if (albumSelect && albumId) {
            albumSelect.value = albumId;
            albumHidden.value = albumId;
        }

        // Mirror full album path into the typeahead input
        const typeahead = document.getElementById('album-typeahead');
        if (typeahead) {
            const found = Array.isArray(window.AlbumPilotAlbums)
                 ? window.AlbumPilotAlbums.find(a => a.id === albumId)
                 : null;

            if (found) {
                typeahead.value = found.fullPath;
            }
        }

        // Apply general options
        const simulateCheckbox = document.getElementById('simulate');
        const onlyNewCheckbox = document.getElementById('onlyNew');
        const includeSubalbumsCheckbox = document.getElementById('includeSubalbums');

        if (simulateCheckbox)
            simulateCheckbox.checked = simulate;
        if (onlyNewCheckbox)
            onlyNewCheckbox.checked = onlyNew;
        if (includeSubalbumsCheckbox)
            includeSubalbumsCheckbox.checked = subalbums;

        // First, uncheck all step checkboxes to ensure clean state
        document.querySelectorAll('.step-checkbox').forEach(cb => {
            if (!cb.disabled)
                cb.checked = false;
        });

        // Enable specified step checkboxes
        stepIds.forEach(id => {
            const cb = document.getElementById('step' + id);
            if (cb && !cb.disabled)
                cb.checked = true;
        });

        // VideoJS step configuration (step 2)
        if (stepIds.includes('2')) {
            const posterSec = params.get('poster_second') || '4';
            const thumbInterval = params.get('thumb_interval') || '5';
            const thumbSize = params.get('thumb_size') || '120x68';
            const outputFormat = params.get('output_format') || 'jpg';

            const posterInput = document.querySelector('.videojs-poster-second');
            const intervalInput = document.querySelector('.videojs-thumb-interval');
            const sizeInput = document.querySelector('.videojs-size-input');
            const outputRadio = document.querySelector(`input[name="videojs_output_format"][value="${outputFormat}"]`);

            if (posterInput)
                posterInput.value = posterSec;
            if (intervalInput)
                intervalInput.value = thumbInterval;
            if (sizeInput)
                sizeInput.value = thumbSize;
            if (outputRadio)
                outputRadio.checked = true;

            document.querySelectorAll('.videojs-option').forEach(cb => {
                const key = cb.dataset.key;
                if (key && params.has(key)) {
                    cb.checked = params.get(key) === '1';
                }
            });
        }

        // Thumbnail generation step configuration (step 3)
        if (stepIds.includes('3')) {
            const types = (params.get('thumb_types') || '').split(',');
            const overwrite = params.get('thumb_overwrite') === '1';

            document.querySelectorAll('.thumb-type-checkbox').forEach(cb => {
                cb.checked = types.includes(cb.value);
            });

            const overwriteCb = document.querySelector('.thumb-overwrite-checkbox');
            if (overwriteCb)
                overwriteCb.checked = overwrite;
        }

        // Wait until DOM is fully ready and UI has focus before triggering the sync
        function waitAndStartSyncWithFocusCheck() {
            const startBtn = document.getElementById('start-sync');

            if (!startBtn || !document.getElementById('album-list')?.value) {
                return setTimeout(waitAndStartSyncWithFocusCheck, 200);
            }

            if (document.visibilityState !== 'visible' || !document.hasFocus()) {
                return setTimeout(waitAndStartSyncWithFocusCheck, 200);
            }

            requestAnimationFrame(() => {
                setTimeout(() => {
                    try {
                        const evt = new MouseEvent('click', {
                            bubbles: true,
                            cancelable: true
                        });
                        startBtn.dispatchEvent(evt);
                    } catch {
                        startBtn.click();
                    }
                }, 100);
            });
        }

        waitAndStartSyncWithFocusCheck();
        disableAllInputsForBatchMode();
    }

});

(function initAlbumTypeahead() {
    const input = document.getElementById('album-typeahead');
    const resultsBox = document.getElementById('album-typeahead-results');
    const select = document.getElementById('album-list');
    const hidden = document.getElementById('album-select');

    if (!input || !resultsBox || !select || !hidden)
        return;

    // Build complete folder path from indentation

    const albums = [];
    const stack = [];

    Array.from(select.options).forEach(opt => {
        const id = opt.value;
        const rawText = opt.textContent;
        const name = rawText.trim();

        // Determine depth of whitespace or &nbsp; (here two spaces = one level)
        const indent = (rawText.match(/^[\s\u00a0]+/) || [''])[0];
        const depth = Math.floor(indent.replace(/\u00a0/g, ' ').length / 3);

        // Update stack to current depth
        stack[depth] = name;
        stack.length = depth + 1;

        const fullPath = stack.join('/');

        albums.push({
            id,
            name,
            fullPath,
            displayPath: truncatePath(fullPath),
            lower: name.toLowerCase()
        });
    });

    // expose albums for external use (batch-mode, start-click)
    window.AlbumPilotAlbums = albums;

    // Helper: shorten paths that are too long with ‘...’
    function truncatePath(path, maxLen = 60) {
        return path.length <= maxLen ? path : '…' + path.slice(-maxLen);
    }

    input.addEventListener('input', function () {
        const query = input.value.trim().toLowerCase();
        resultsBox.innerHTML = '';
        if (!query) {
            resultsBox.style.display = 'none';
            return;
        }

        // Reset the current selection in the dropdown
        activeIndex = -1;

        const matches = albums
            .filter(album => {
                // if I enter a slash, search in the complete path
                if (query.includes('/')) {
                    return album.fullPath.toLowerCase().includes(query);
                }
                // otherwise as usual only in the folder name
                return album.name.toLowerCase().includes(query);
            })
            .slice(0, albums.length);

        if (matches.length === 0) {
            resultsBox.style.display = 'none';
            return;
        }

        matches.forEach(album => {
            const li = document.createElement('li');
            li.textContent = album.displayPath;
            li.title = album.fullPath; // Tooltip for full path


            li.dataset.id = album.id;
            li.style.cursor = 'pointer';
            li.style.padding = '4px 8px';

            const selectAlbum = () => {
                select.value = album.id;
                hidden.value = album.id;
                input.value = album.fullPath;
                resultsBox.style.display = 'none';

                generateExternalUrlFromSelection();

                const selectedOption = select.querySelector(`option[value="${album.id}"]`);
                if (selectedOption) {
                    selectedOption.selected = true;
                    select.scrollTop = selectedOption.offsetTop - select.clientHeight / 2;
                }
                select.dispatchEvent(new Event('change'));
            };

            // Mouse click
            li.addEventListener('mousedown', selectAlbum);

            // Keyboard: Enter (via click())
            li.addEventListener('click', selectAlbum);

            resultsBox.appendChild(li);
        });

        resultsBox.style.display = 'block';
    });

    // Keyboard navigation for dropdown
    let activeIndex = -1;

    input.addEventListener('keydown', function (e) {
        const items = Array.from(resultsBox.querySelectorAll('li'));
        if (!items.length)
            return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = activeIndex < items.length - 1 ? activeIndex + 1 : 0;
            updateActive(items, activeIndex);
        } else if (e.key === 'ArrowUp') {

            e.preventDefault();
            activeIndex = activeIndex > 0 ? activeIndex - 1 : items.length - 1;
            updateActive(items, activeIndex);
        } else if (e.key === 'Enter') {

            e.preventDefault();
            if (activeIndex >= 0) {
                items[activeIndex].click();
                activeIndex = -1;
            }
        }
    });

    function updateActive(items, index) {
        items.forEach((li, i) => {
            if (i === index) {
                li.style.backgroundColor = '#bde4ff';
                li.scrollIntoView({
                    block: 'nearest'
                });
            } else {
                li.style.backgroundColor = '';
            }
        });
    }

    // Select everything directly with focus in the text field
    input.addEventListener('focus', function () {
        input.select();
        resultsBox.style.display = 'none';
        resultsBox.innerHTML = '';
        activeIndex = -1;
    });

    input.addEventListener('blur', () => {
        setTimeout(() => resultsBox.style.display = 'none', 150);
    });

    // Selection in the <select> is reflected downwards in the text box
    select.addEventListener('change', () => {
        const opt = select.selectedOptions[0];
        if (!opt)
            return;
        // find the object for the selected ID in the albums array
        const found = albums.find(a => a.id === opt.value);
        if (found) {
            input.value = found.fullPath;
            // reset dropdown and index
            resultsBox.style.display = 'none';
            activeIndex = -1;
        }
    });

})();
