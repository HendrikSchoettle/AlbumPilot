/*
File: template/js/runner.js – AlbumPilot Plugin for Piwigo - general loop handler (frontend)
Author: Hendrik Schöttle
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
 */

window.next = function () {
    if (typeof fetchSafeJSON !== 'function') {
        console.error('❌ fetchSafeJSON is not defined – check script load order.');
        return;
    }

    const index = window._runnerIndex || 0;
    const selectedSteps = window._runnerSelectedSteps || [];
    const steps = document.getElementById('sync-steps');
    const log = document.getElementById('progress-log');
    const t = window.t || (key => (window.AlbumPilotLang && window.AlbumPilotLang[key]) ? window.AlbumPilotLang[key] : key);
    const config = window.AlbumPilotConfig || {};
    const token = config.token;
    const simulate = document.getElementById('simulate')?.checked || false;
    const startBtn = document.getElementById('start-sync');

    if (index >= selectedSteps.length) {
        steps.innerHTML += '<li><strong>✅ ' + (window.AlbumPilotLang.all_steps_completed) + '</strong></li>';

        window.syncInProgress = false;

        // Remove batch-mode styling
        const wrapper = document.querySelector('.album-sync-wrapper.batch-mode');
        if (wrapper)
            wrapper.classList.remove('batch-mode');

        // Only re-enable controls that were not disabled in the original markup
        document.querySelectorAll('input, select, textarea, button').forEach(el => {
            if (el.defaultDisabled)
                return;
            el.disabled = false;
            el.style.opacity = '';
            el.style.cursor = '';
            el.title = '';
        });

        // Fire a change on each step-checkbox to reapply nested enable/disable logic
        document.querySelectorAll('.step-checkbox').forEach(cb => {
            cb.dispatchEvent(new Event('change'));
        });

        // Regenerate external-URL preview
        generateExternalUrlFromSelection();

        const isBatchMode = new URLSearchParams(window.location.search).get('external_run') === '1';

        if (!isBatchMode) {
            if (startBtn && window.originalStartBtnHTML) {
                startBtn.disabled = false;
                startBtn.innerHTML = window.originalStartBtnHTML;
            }
        }

        window.onbeforeunload = null;

        document.getElementById('progress-log').innerHTML += '<div style="margin-top:1.5em;"><strong>✅ ' + (window.AlbumPilotLang.workflow_finished) + '</strong></div>';

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
        window._runnerIndex = index + 1;
        setTimeout(window.next, 600);
        return;
    }

    li.textContent = '🔄 ' + step.name;

    if (['step1', 'step2', 'step3', 'step4', 'step5'].includes(step.id)) {
        const heading = document.createElement('h5');
        heading.textContent = step.name;
        heading.className = 'step-heading';
        log.appendChild(heading);

        if (['step2', 'step3', 'step4', 'step5'].includes(step.id)) {
            let offset = 0;
            let progressElement = null;
            let filenameElement = null;

            function fetchChunk() {
                // build base URL
                let fetchUrlBase = step.url;

                // for thumbnails (step4) always append current thumb_types & overwrite flag
                if (step.id === 'step4') {
                    const types = Array.from(document.querySelectorAll('.thumb-type-checkbox:checked'))
                        .map(cb => cb.value);
                    const overwrite = document.querySelector('.thumb-overwrite-checkbox')?.checked ? '1' : '0';
                    fetchUrlBase += '&thumb_types=' + encodeURIComponent(types.join(','))
                     + '&thumb_overwrite=' + overwrite;
                }

                // add offset only for chunked steps
                const fetchUrl = (['step2', 'step4', 'step5'].includes(step.id))
                 ? fetchUrlBase + '&offset=' + offset
                 : fetchUrlBase;

                window.handleJsonStep(fetchUrl, (data) => {
                    const logEntries = Array.isArray(data.log) ? data.log : [data.log];

                    logEntries.forEach(entry => {
                        if (typeof entry === 'object' && entry.type === 'progress') {
                            const percent = entry.percent || 0;
                            const filename = entry.path?.split('/').pop() || '(unknown)';
                            const typeSuffix = entry.thumb_type ? ` – ${t('thumb_type_label')}: ${entry.thumb_type}` : '';
                            const stepName = t('step_' + entry.step) || entry.step;
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
                        if (bar)
                            bar.remove();

                        const fname = document.getElementById(step.id + '-filename-line');
                        if (fname)
                            fname.remove();

                        li.innerHTML = '✅ ' + step.name;
                        window._runnerIndex = index + 1;
                        setTimeout(window.next, 800);
                    }
                });
            }

            fetchChunk();
            return;
        }

        window.handleJsonStep(step.url, (data) => {
            let resultHTML = '';
            if (data.message)
                resultHTML += '<div>' + data.message + '</div>';
            if (data.raw_output)
                resultHTML += '<div>' + data.raw_output + '</div>';

            const resultBlock = document.createElement('div');
            resultBlock.innerHTML = resultHTML;
            log.appendChild(resultBlock);

            li.innerHTML = '✅ ' + step.name;
            window._runnerIndex = index + 1;
            setTimeout(window.next, 1000);
        });

        return;
    }

    if (step.id === 'step6') {
        fetch(step.url, {
            credentials: 'same-origin'
        })
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const infoListItems = doc.querySelectorAll('.eiw .infos li');

            // If a fatal PHP error occurred, display its HTML and stop this step
            if (html.includes('<b>Fatal error')) {
                const errorBlock = document.createElement('div');
                errorBlock.innerHTML = html;
                log.appendChild(errorBlock);
                li.innerHTML = '❌ ' + step.name;
                window._runnerIndex = index + 1;
                setTimeout(window.next, 1000);
                return;
            }

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
            window._runnerIndex = index + 1;
            setTimeout(window.next, 1000);
        })
        .catch(err => {
            log.innerHTML += '<div style="color:red;">❌ ' + t('error_during_step') + ' <strong>' + step.name + '</strong>: ' + err.message + '</div>';
            li.innerHTML = '❌ ' + step.name;
            window._runnerIndex = index + 1;
            setTimeout(window.next, 1000);
        });
        return;
    }

    if (['step7', 'step8', 'step9', 'step10'].includes(step.id)) {
        fetch(step.url, {
            credentials: 'same-origin'
        })
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const heading = document.createElement('h5');
            heading.textContent = step.name;
            heading.className = 'step-heading';
            log.appendChild(heading);

            const infoList = doc.querySelectorAll('.eiw .infos li');

            // If a fatal PHP error occurred, display its HTML and stop this step
            if (html.includes('<b>Fatal error')) {
                const errorBlock = document.createElement('div');
                errorBlock.innerHTML = html;
                log.appendChild(errorBlock);
                li.innerHTML = '❌ ' + step.name;
                window._runnerIndex = index + 1;
                setTimeout(window.next, 1000);
                return;
            }

            if (infoList.length > 0) {
                infoList.forEach(el => {
                    const div = document.createElement('div');
                    div.textContent = el.textContent.trim();
                    log.appendChild(div);
                });
            } else {
                const div = document.createElement('div');
                div.textContent = '⚠️ ' + t('no_success_message');
                log.appendChild(div);
            }

            li.innerHTML = '✅ ' + step.name;
            window._runnerIndex = index + 1;
            setTimeout(window.next, 1000);
        })
        .catch(err => {
            log.innerHTML += '<div style="color:red;">❌ ' + t('error_during_step') + ' <strong>' + step.name + '</strong>: ' + err.message + '</div>';
            li.innerHTML = '❌ ' + step.name;
            window._runnerIndex = index + 1;
            setTimeout(window.next, 1000);
        });
        return;
    }
};

window.handleJsonStep = window.handleJsonStep || function (url, onSuccess) {
    const log = document.getElementById('progress-log');

    if (typeof fetchSafeJSON !== 'function') {
        console.error('❌ fetchSafeJSON is missing – possibly not loaded yet.');
        return;
    }

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

    // Continue to next step even after error
    if (typeof window._runnerIndex !== 'undefined' && typeof window._runnerSelectedSteps !== 'undefined') {
        const liList = document.querySelectorAll('#sync-steps li');
        const li = liList[window._runnerIndex];
        if (li) li.innerHTML = '❌ ' + window._runnerSelectedSteps[window._runnerIndex].name;

        window._runnerIndex++;
        setTimeout(window.next, 1000); // delay helps UI stay smooth
    }
}






else {
            onSuccess(data);
        }
    });
};

window.resetProgress = function () {
    const config = window.AlbumPilotConfig || {};
    const token = config.token;

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
};
