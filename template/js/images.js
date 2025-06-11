/*
File: template/js/images.js – AlbumPilot Plugin for Piwigo - images handler (frontend)
Author: Hendrik Schöttle
SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
 */

window.renderImageOptions = function ({
    stepCheckbox,
    listItem,
    savedSettings,
    t
}) {
    const optionsWrapper = document.createElement('div');
    optionsWrapper.className = 'thumb-options-wrapper';
    optionsWrapper.style.marginTop = '6px';
    optionsWrapper.style.paddingLeft = '24px';

    // Checkbox: Overwrite existing thumbnails
    const overwriteWrap = document.createElement('label');
    const overwriteCb = document.createElement('input');
    overwriteCb.type = 'checkbox';
    overwriteCb.className = 'thumb-overwrite-checkbox';
    overwriteCb.checked = savedSettings?.thumb_overwrite === '1';

    overwriteWrap.appendChild(overwriteCb);
    overwriteWrap.appendChild(document.createTextNode(' ' + t('label_thumb_overwrite')));
    overwriteWrap.style.marginBottom = '10px';
    optionsWrapper.appendChild(overwriteWrap);

    // Label: Thumbnail size selection
    const labelEl = document.createElement('div');
    labelEl.textContent = t('label_select_thumb_types');
    labelEl.style.fontWeight = 'bold';
    optionsWrapper.appendChild(labelEl);

    // Create grid for available thumbnail types
    const grid = document.createElement('div');
    grid.style.display = 'grid';
    grid.style.gridTemplateColumns = '1fr 1fr';
    grid.style.gap = '6px 20px';

    (window.AlbumPilotThumbTypes || []).forEach(type => {
        const wrap = document.createElement('label');
        const cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.className = 'thumb-type-checkbox';
        cb.value = type.id;
        cb.checked = true;

        wrap.appendChild(cb);
        wrap.appendChild(document.createTextNode(' ' + type.label));
        grid.appendChild(wrap);
    });

    optionsWrapper.appendChild(grid);
    listItem.appendChild(optionsWrapper);

    // Disable controls if checkbox is unchecked
    stepCheckbox.addEventListener('change', () => {
        const disabled = !stepCheckbox.checked;
        grid.querySelectorAll('input').forEach(cb => cb.disabled = disabled);
        overwriteCb.disabled = disabled;
    });

    // Apply initial state
    if (!stepCheckbox.checked) {
        grid.querySelectorAll('input').forEach(cb => cb.disabled = true);
        overwriteCb.disabled = true;
    }
};
