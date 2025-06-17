/*
File: template/js/videos.js – AlbumPilot Plugin for Piwigo - videos handler (frontend)
Author: Hendrik Schöttle
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
 */

window.renderVideoOptions = function ({
    stepCheckbox,
    listItem,
    savedSettings,
    t
}) {

    const optionsWrapper = document.createElement('div');
    optionsWrapper.className = 'videojs-options-wrapper';
    optionsWrapper.style.marginTop = '6px';
    optionsWrapper.style.paddingLeft = '24px';

    const cb1 = document.createElement('label');
    cb1.innerHTML = `<input type="checkbox" class="videojs-option" data-key="videojs_import_uploaded" checked> ${t('VideoJS_RepAdd')}`;
    optionsWrapper.appendChild(cb1);

    const cb2 = document.createElement('label');
    cb2.innerHTML = `<br><input type="checkbox" class="videojs-option" data-key="videojs_create_poster" checked> ${t('VideoJS_AddPoster')}`;
    const inputSec = document.createElement('input');
    inputSec.type = 'number';
    inputSec.min = '0';
    inputSec.max = '999';
    inputSec.value = savedSettings['videojs_poster_second'] || '4';
    inputSec.className = 'videojs-time-input videojs-poster-second';
    inputSec.style.width = '60px';
    inputSec.style.marginLeft = '4px';
    cb2.appendChild(inputSec);
    cb2.append(' ' + t('VideoJS_PosterSec'));
    optionsWrapper.appendChild(cb2);

    const cb3 = document.createElement('label');
    cb3.innerHTML = `<br><input type="checkbox" class="videojs-option" data-key="videojs_poster_overwrite"> <span>${t('VideoJS_PosterOverwrite')}</span>`;

    optionsWrapper.appendChild(cb3);

    const formatWrapper = document.createElement('div');
    formatWrapper.className = 'videojs-output-format-line option-line';

    formatWrapper.innerHTML =
        t('VideoJS_OutputFormat') + ': ' +
        '<label><input type="radio" name="videojs_output_format" value="jpg" checked class="readonly-radio"><span> ' + t('VideoJS_jpg') + ' </span></label>' +
        '<label><input type="radio" name="videojs_output_format" value="png" class="readonly-radio"><span> ' + t('VideoJS_png') + ' </span></label>';

    optionsWrapper.appendChild(formatWrapper);

    const cb4 = document.createElement('label');

    cb4.innerHTML = `<input type="checkbox" class="videojs-option" data-key="videojs_add_overlay" checked> <span>${t('VideoJS_OverlayAdd')}</span>`;

    optionsWrapper.appendChild(cb4);

    const cb5 = document.createElement('label');
    cb5.innerHTML = `<br><input type="checkbox" class="videojs-option" data-key="videojs_add_thumbs"> ${t('VideoJS_AddThumb')}`;

    const thumbSecInput = document.createElement('input');
    thumbSecInput.type = 'number';
    thumbSecInput.min = '1';
    thumbSecInput.max = '60';
    thumbSecInput.value = savedSettings['videojs_thumbs_interval'] || '5';
    thumbSecInput.className = 'videojs-time-input videojs-thumb-interval';
    thumbSecInput.style.margin = '0 8px';
    cb5.appendChild(thumbSecInput);
    cb5.append(' ' + t('VideoJS_ThumbSec'));
    optionsWrapper.appendChild(cb5);

    const cb6 = document.createElement('label');
    cb6.innerHTML = `<br>${t('VideoJS_ThumbSize')}: `;
    const thumbSizeInput = document.createElement('input');
    thumbSizeInput.type = 'text';
    thumbSizeInput.value = savedSettings['videojs_thumbs_size'] || '120x68';
    thumbSizeInput.className = 'videojs-size-input';
    thumbSizeInput.style.marginLeft = '6px';
    thumbSizeInput.style.width = '80px';
    cb6.appendChild(thumbSizeInput);
    optionsWrapper.appendChild(cb6);

    listItem.appendChild(optionsWrapper);

    // Enable/disable video option fields based on checkbox states
    function updateVideoOptionStates() {
        const isMainChecked = stepCheckbox.checked;
        const isCreatePosterChecked = optionsWrapper.querySelector('[data-key="videojs_create_poster"]')?.checked;

        // grey-out poster-related controls when “create poster” is off
        const posterDisabled = !isMainChecked || !isCreatePosterChecked;
        cb2.classList.toggle('disabled-block', !isMainChecked);
        cb3.classList.toggle('disabled-block', posterDisabled);
        formatWrapper.classList.toggle('disabled-block', posterDisabled);
        cb4.classList.toggle('disabled-block', posterDisabled);

        const isCreateThumbsChecked = optionsWrapper.querySelector('[data-key="videojs_add_thumbs"]')?.checked;

        optionsWrapper.querySelectorAll('input').forEach(el => {
            el.disabled = !isMainChecked;
        });

        // Poster generation fields
        inputSec.disabled = !isMainChecked || !isCreatePosterChecked;
        optionsWrapper.querySelector('[data-key="videojs_poster_overwrite"]').disabled = !isMainChecked || !isCreatePosterChecked;
        cb3.querySelector('input').disabled = posterDisabled;
        cb4.querySelector('input').disabled = posterDisabled;

        formatWrapper.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.disabled = !isMainChecked || !isCreatePosterChecked;
        });

        optionsWrapper.querySelector('[data-key="videojs_add_overlay"]').disabled = !isMainChecked || !isCreatePosterChecked;

        // Thumbnail generation fields

        thumbSecInput.disabled = !isMainChecked || !isCreateThumbsChecked;
        thumbSizeInput.disabled = !isMainChecked || !isCreateThumbsChecked;

        // toggle grey-out class on entire block
        optionsWrapper.classList.toggle('disabled-block', !stepCheckbox.checked);

    }

    // Update on any change
    stepCheckbox.addEventListener('change', updateVideoOptionStates);
    optionsWrapper.querySelectorAll('input').forEach(el => {
        el.addEventListener('change', updateVideoOptionStates);
    });

    // Run once on initial render
    updateVideoOptionStates();

}
