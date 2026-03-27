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

            // Choices sub-row (shown for choice-type fields with detected options).
            const choices = field.choices || [];
            const isChoiceType = ['select', 'checkbox', 'radio', 'button_group'].indexOf(field.type) !== -1;
            const $choicesRow = $('<tr>').addClass('ctaj-choices-row').attr('data-parent', i);
            if (!isChoiceType || choices.length === 0) {
                $choicesRow.addClass('hidden');
            }
            $choicesRow.append(
                '<td></td><td colspan="7">' +
                '<div class="ctaj-choices-wrap">' +
                '<label class="ctaj-choices-label">Choices:</label>' +
                '<div class="ctaj-choices-tags" data-index="' + i + '">' +
                choices.map(function (c) {
                    return '<span class="ctaj-choice-tag">' + escHtml(c) +
                        '<button type="button" class="ctaj-choice-remove" title="Remove">&times;</button></span>';
                }).join('') +
                '</div>' +
                '<input type="text" class="ctaj-choice-add-input" placeholder="Add choice…" />' +
                '<button type="button" class="button button-small ctaj-choice-add-btn">+</button>' +
                '</div></td>'
            );
            $body.append($choicesRow);
        });

        // Toggle choices row when field type changes.
        $body.on('change', '.ctaj-field-type', function () {
            const $tr = $(this).closest('tr');
            const idx = $tr.data('index');
            const type = $(this).val();
            const isChoice = ['select', 'checkbox', 'radio', 'button_group'].indexOf(type) !== -1;
            $body.find('.ctaj-choices-row[data-parent="' + idx + '"]').toggleClass('hidden', !isChoice);
        });

        // Remove a choice tag.
        $body.on('click', '.ctaj-choice-remove', function () {
            $(this).closest('.ctaj-choice-tag').remove();
        });

        // Add a choice tag.
        $body.on('click', '.ctaj-choice-add-btn', function () {
            const $input = $(this).siblings('.ctaj-choice-add-input');
            const val = $input.val().trim();
            if (!val) return;
            const $tags = $(this).siblings('.ctaj-choices-tags');
            $tags.append(
                '<span class="ctaj-choice-tag">' + escHtml(val) +
                '<button type="button" class="ctaj-choice-remove" title="Remove">&times;</button></span>'
            );
            $input.val('').focus();
        });

        // Allow Enter key to add choice.
        $body.on('keydown', '.ctaj-choice-add-input', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $(this).siblings('.ctaj-choice-add-btn').trigger('click');
            }
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
        $('#ctaj-fields-body tr[data-index]').each(function () {
            const $tr = $(this);
            const i = parseInt($tr.data('index'), 10);

            // Collect choices from the sub-row tags.
            const choices = [];
            $tr.next('.ctaj-choices-row').find('.ctaj-choice-tag').each(function () {
                // Get text content excluding the × button.
                const text = $(this).clone().children().remove().end().text().trim();
                if (text) choices.push(text);
            });

            fields.push({
                name:         parsedFields[i].name,
                label:        $tr.find('.ctaj-field-label').val(),
                type:         $tr.find('.ctaj-field-type').val(),
                required:     $tr.find('.ctaj-field-required').is(':checked'),
                instructions: $tr.find('.ctaj-field-instructions').val(),
                category:     parsedFields[i].category,
                include:      $tr.find('.ctaj-field-include').is(':checked'),
                choices:      choices,
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
