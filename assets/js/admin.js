(function ($) {
    'use strict';

    const $wrap = $('.ctaj-wrap');
    let parsedFields = [];
    let sampleData = [];
    let generatedJson = null;

    // ----- Step navigation -----

    function goToStep(step) {
        $wrap.find('.ctaj-step').removeClass('active done');
        $wrap.find('.ctaj-step').each(function () {
            const s = parseInt($(this).data('step'), 10);
            if (s < step) $(this).addClass('done');
            if (s === step) $(this).addClass('active');
        });
        $wrap.find('.ctaj-panel').addClass('hidden');
        $('#ctaj-step-' + step).removeClass('hidden');
    }

    // ----- Step 1: Upload -----

    const $dropZone = $('#ctaj-drop-zone');
    const $fileInput = $('#ctaj-file-input');

    $('#ctaj-browse-btn').on('click', function () {
        $fileInput.trigger('click');
    });

    $dropZone.on('dragover', function (e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    }).on('dragleave drop', function (e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
    }).on('drop', function (e) {
        const files = e.originalEvent.dataTransfer.files;
        if (files.length) {
            $fileInput[0].files = files;
            uploadFile(files[0]);
        }
    });

    $fileInput.on('change', function () {
        if (this.files.length) {
            uploadFile(this.files[0]);
        }
    });

    function uploadFile(file) {
        const $status = $('#ctaj-upload-status');
        $status.removeClass('hidden ctaj-error ctaj-success').addClass('ctaj-loading').text('Uploading and parsing…');

        const formData = new FormData();
        formData.append('action', 'ctaj_upload_csv');
        formData.append('nonce', ctajData.nonce);
        formData.append('csv_file', file);
        formData.append('has_category_row', $('#ctaj-has-category-row').is(':checked') ? '1' : '');
        formData.append('delimiter', $('#ctaj-delimiter').val());

        $.ajax({
            url: ctajData.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    $status.removeClass('ctaj-loading').addClass('ctaj-success').text(
                        'Parsed ' + res.data.fields.length + ' fields. Moving to configuration…'
                    );
                    parsedFields = res.data.fields;
                    sampleData = res.data.sampleData;
                    populateStep2();
                    setTimeout(function () { goToStep(2); }, 500);
                } else {
                    $status.removeClass('ctaj-loading').addClass('ctaj-error').text(res.data.message || 'Upload failed.');
                }
            },
            error: function () {
                $status.removeClass('ctaj-loading').addClass('ctaj-error').text('Network error. Please try again.');
            }
        });
    }

    // ----- Step 2: Configure fields -----

    function buildTypeSelect(selectedType) {
        const groups = ctajData.fieldTypes;
        let html = '<select class="ctaj-field-type">';
        for (const groupName in groups) {
            html += '<optgroup label="' + escHtml(groupName) + '">';
            for (const val in groups[groupName]) {
                const sel = val === selectedType ? ' selected' : '';
                html += '<option value="' + escHtml(val) + '"' + sel + '>' + escHtml(groups[groupName][val]) + '</option>';
            }
            html += '</optgroup>';
        }
        html += '</select>';
        return html;
    }

    function populateStep2() {
        // Set group title from filename or first category.
        const categories = [...new Set(parsedFields.map(f => f.category).filter(Boolean))];
        const suggestedTitle = categories.length ? categories[0] : 'Imported Field Group';
        $('#ctaj-group-title').val(suggestedTitle);

        const hasCats = categories.length > 0;
        $('#ctaj-use-tabs').prop('checked', hasCats).closest('.ctaj-setting-row').toggle(hasCats);

        // Populate bulk type select.
        const $bulkType = $('#ctaj-bulk-type').empty();
        const groups = ctajData.fieldTypes;
        for (const groupName in groups) {
            const $optgroup = $('<optgroup>').attr('label', groupName);
            for (const val in groups[groupName]) {
                $optgroup.append($('<option>').val(val).text(groups[groupName][val]));
            }
            $bulkType.append($optgroup);
        }

        // Populate fields table.
        const $body = $('#ctaj-fields-body').empty();
        parsedFields.forEach(function (field, i) {
            const $row = $('<tr>').attr('data-index', i);
            $row.append('<td class="ctaj-col-num">' + (i + 1) + '</td>');
            $row.append('<td class="ctaj-col-tab">' + escHtml(field.category || '—') + '</td>');
            $row.append('<td class="ctaj-col-name"><code>' + escHtml(field.name) + '</code></td>');
            $row.append(
                '<td class="ctaj-col-label"><input type="text" class="ctaj-field-label" value="' +
                escAttr(field.label) + '" /></td>'
            );
            $row.append('<td class="ctaj-col-type">' + buildTypeSelect(field.type) + '</td>');
            $row.append(
                '<td class="ctaj-col-required"><input type="checkbox" class="ctaj-field-required" /></td>'
            );
            $row.append(
                '<td class="ctaj-col-instructions"><input type="text" class="ctaj-field-instructions" placeholder="Optional…" /></td>'
            );
            $row.append(
                '<td class="ctaj-col-actions"><input type="checkbox" class="ctaj-field-include" checked /></td>'
            );
            $body.append($row);
        });

        // Sample data table.
        if (sampleData.length) {
            const $sampleWrap = $('#ctaj-sample-data').removeClass('hidden');
            const $sampleTable = $('#ctaj-sample-table').empty();
            const $thead = $('<thead><tr></tr></thead>');
            parsedFields.forEach(function (f) {
                $thead.find('tr').append('<th>' + escHtml(f.label) + '</th>');
            });
            $sampleTable.append($thead);
            const $tbody = $('<tbody>');
            sampleData.forEach(function (row) {
                const $tr = $('<tr>');
                row.forEach(function (cell) {
                    $tr.append('<td>' + escHtml(cell || '') + '</td>');
                });
                $tbody.append($tr);
            });
            $sampleTable.append($tbody);
        }
    }

    // Bulk apply.
    $('#ctaj-bulk-apply').on('click', function () {
        const type = $('#ctaj-bulk-type').val();
        $('#ctaj-fields-body .ctaj-field-type').val(type);
    });

    // Auto-detect (reset to server-guessed types).
    $('#ctaj-auto-detect').on('click', function () {
        parsedFields.forEach(function (field, i) {
            $('#ctaj-fields-body tr[data-index="' + i + '"] .ctaj-field-type').val(field.type);
        });
    });

    // Navigation.
    $('#ctaj-back-1').on('click', function () { goToStep(1); });
    $('#ctaj-back-2').on('click', function () { goToStep(2); });

    // ----- Step 2 → 3: Generate -----

    $('#ctaj-generate').on('click', function () {
        const $btn = $(this).prop('disabled', true).text('Generating…');

        const fields = [];
        $('#ctaj-fields-body tr').each(function () {
            const i = parseInt($(this).data('index'), 10);
            fields.push({
                name:         parsedFields[i].name,
                label:        $(this).find('.ctaj-field-label').val(),
                type:         $(this).find('.ctaj-field-type').val(),
                required:     $(this).find('.ctaj-field-required').is(':checked'),
                instructions: $(this).find('.ctaj-field-instructions').val(),
                category:     parsedFields[i].category,
                include:      $(this).find('.ctaj-field-include').is(':checked'),
            });
        });

        $.ajax({
            url: ctajData.ajaxUrl + '?action=ctaj_generate_json&nonce=' + encodeURIComponent(ctajData.nonce),
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                fields:        fields,
                groupTitle:    $('#ctaj-group-title').val(),
                useTabs:       $('#ctaj-use-tabs').is(':checked'),
                locationParam: $('#ctaj-location-param').val(),
                locationOp:    $('#ctaj-location-operator').val(),
                locationValue: $('#ctaj-location-value').val(),
            }),
            success: function (res) {
                $btn.prop('disabled', false).text('Generate JSON →');
                if (res.success) {
                    generatedJson = res.data.json;
                    $('#ctaj-json-output').text(JSON.stringify(generatedJson, null, 2));
                    goToStep(3);
                } else {
                    alert(res.data.message || 'Generation failed.');
                }
            },
            error: function () {
                $btn.prop('disabled', false).text('Generate JSON →');
                alert('Network error.');
            }
        });
    });

    // ----- Step 3: Download / Copy -----

    $('#ctaj-download').on('click', function () {
        if (!generatedJson) return;
        const blob = new Blob([JSON.stringify(generatedJson, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'acf-field-group.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

    $('#ctaj-copy-json').on('click', function () {
        if (!generatedJson) return;
        const text = JSON.stringify(generatedJson, null, 2);
        navigator.clipboard.writeText(text).then(function () {
            const $btn = $('#ctaj-copy-json');
            $btn.text('Copied!');
            setTimeout(function () { $btn.text('Copy to Clipboard'); }, 2000);
        });
    });

    // ----- Helpers -----

    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function escAttr(str) {
        return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

})(jQuery);
