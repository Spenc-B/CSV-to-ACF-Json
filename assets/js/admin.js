(function ($) {
    'use strict';

    const $wrap = $('.ctaj-wrap');
    let parsedFields = [];
    let sampleData = [];
    let generatedJson = null;
    let repeaterGroups = [];

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
                    repeaterGroups = res.data.repeaterGroups || [];
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

        // Repeater detection notice.
        const $repeaterNotice = $('#ctaj-repeater-notice');
        if (repeaterGroups.length) {
            $repeaterNotice.removeClass('hidden');
            const $list = $('#ctaj-repeater-groups').empty();
            repeaterGroups.forEach(function (rg, rgIdx) {
                const subFieldNames = rg.subFields.map(function (sf) { return sf.label; }).join(', ');
                const $item = $(
                    '<div class="ctaj-repeater-group" data-rg-index="' + rgIdx + '">' +
                    '<div class="ctaj-repeater-header">' +
                    '<span class="ctaj-repeater-icon">🔁</span> ' +
                    '<strong>' + escHtml(rg.label) + '</strong>' +
                    ' <span class="ctaj-repeater-meta">(' + rg.maxRows + ' rows, sub-fields: ' + escHtml(subFieldNames) + ')</span>' +
                    ' <button type="button" class="button button-small ctaj-repeater-break" data-rg-index="' + rgIdx + '">Break Apart</button>' +
                    '</div>' +
                    '</div>'
                );
                $list.append($item);

                // Mark the constituent flat fields as part of a repeater (hide them).
                rg.fieldIndices.forEach(function (fi) {
                    $body.find('tr[data-index="' + fi + '"]').addClass('ctaj-in-repeater').attr('data-repeater', rgIdx);
                    $body.find('.ctaj-choices-row[data-parent="' + fi + '"]').addClass('ctaj-in-repeater');
                });
            });
        } else {
            $repeaterNotice.addClass('hidden');
        }

        // Break apart a repeater group.
        $('#ctaj-repeater-groups').off('click', '.ctaj-repeater-break').on('click', '.ctaj-repeater-break', function () {
            const rgIdx = parseInt($(this).data('rg-index'), 10);
            const rg = repeaterGroups[rgIdx];
            if (!rg) return;

            // Show the flat fields again.
            rg.fieldIndices.forEach(function (fi) {
                $body.find('tr[data-index="' + fi + '"]').removeClass('ctaj-in-repeater').removeAttr('data-repeater');
                $body.find('.ctaj-choices-row[data-parent="' + fi + '"]').removeClass('ctaj-in-repeater');
            });

            // Remove this group from the list.
            repeaterGroups[rgIdx] = null;
            $(this).closest('.ctaj-repeater-group').remove();

            // Hide notice if no groups left.
            const remaining = repeaterGroups.filter(function (g) { return g !== null; });
            if (!remaining.length) {
                $repeaterNotice.addClass('hidden');
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

        // Collect active repeater groups (non-null, non-broken-apart).
        const activeRepeaters = repeaterGroups.filter(function (g) { return g !== null; }).map(function (rg) {
            return {
                name:         rg.name,
                label:        rg.label,
                category:     rg.category,
                subFields:    rg.subFields,
                maxRows:      rg.maxRows,
                fieldIndices: rg.fieldIndices,
            };
        });

        $.ajax({
            url: ctajData.ajaxUrl + '?action=ctaj_generate_json&nonce=' + encodeURIComponent(ctajData.nonce),
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                fields:        fields,
                repeaters:     activeRepeaters,
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

    // ----- Step 3: Import directly to ACF -----

    // Show the import button only when ACF is active.
    if (ctajData.acfActive) {
        // Show it whenever Step 3 becomes visible.
        const origGoToStep = goToStep;
        goToStep = function (step) {
            origGoToStep(step);
            if (step === 3) {
                $('#ctaj-import-acf').show();
            }
        };
    }

    $('#ctaj-import-acf').on('click', function () {
        if (!generatedJson) return;

        const $btn = $(this).prop('disabled', true).text('Importing…');
        const $status = $('#ctaj-import-status').removeClass('hidden ctaj-error ctaj-success').addClass('ctaj-loading').text('Importing field group into ACF…');

        // The generated JSON is an array with one field group.
        const fieldGroup = Array.isArray(generatedJson) ? generatedJson[0] : generatedJson;

        $.ajax({
            url: ctajData.ajaxUrl + '?action=ctaj_import_to_acf&nonce=' + encodeURIComponent(ctajData.nonce),
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ fieldGroup: fieldGroup }),
            success: function (res) {
                $btn.prop('disabled', false).text('Import Directly to ACF');
                if (res.success) {
                    $status.removeClass('ctaj-loading').addClass('ctaj-success').html(
                        res.data.message + ' <a href="' + escHtml(res.data.editUrl) + '" target="_blank">Edit field group &rarr;</a>'
                    );
                } else {
                    $status.removeClass('ctaj-loading').addClass('ctaj-error').text(res.data.message || 'Import failed.');
                }
            },
            error: function () {
                $btn.prop('disabled', false).text('Import Directly to ACF');
                $status.removeClass('ctaj-loading').addClass('ctaj-error').text('Network error. Please try again.');
            }
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

    // ----- Mode toggle (Import / Export) -----

    let exportCsvContent = '';

    $('.ctaj-mode-btn').on('click', function () {
        const mode = $(this).data('mode');
        $('.ctaj-mode-btn').removeClass('button-primary active').addClass('button');
        $(this).addClass('button-primary active').removeClass('button');

        if (mode === 'import') {
            $('.ctaj-import-mode').removeClass('hidden');
            $('.ctaj-export-mode').addClass('hidden');
        } else {
            $('.ctaj-import-mode').addClass('hidden');
            $('.ctaj-export-mode').removeClass('hidden');
        }
    });

    // ----- Export: sub-tab toggle -----

    $('.ctaj-export-tab').on('click', function () {
        const tab = $(this).data('tab');
        $('.ctaj-export-tab').removeClass('button-primary active').addClass('button');
        $(this).addClass('button-primary active').removeClass('button');
        $('.ctaj-export-panel').addClass('hidden');
        $('#ctaj-export-' + tab).removeClass('hidden');

        // Load existing field groups on first click.
        if (tab === 'existing' && !$('#ctaj-field-groups-list').data('loaded')) {
            loadFieldGroups();
        }
    });

    // ----- Export: Upload JSON file -----

    const $jsonDropZone = $('#ctaj-json-drop-zone');
    const $jsonFileInput = $('#ctaj-json-file-input');

    $('#ctaj-json-browse-btn').on('click', function () {
        $jsonFileInput.trigger('click');
    });

    $jsonDropZone.on('dragover', function (e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    }).on('dragleave drop', function (e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
    }).on('drop', function (e) {
        const files = e.originalEvent.dataTransfer.files;
        if (files.length) {
            $jsonFileInput[0].files = files;
            handleJsonUpload(files[0]);
        }
    });

    $jsonFileInput.on('change', function () {
        if (this.files.length) {
            handleJsonUpload(this.files[0]);
        }
    });

    function handleJsonUpload(file) {
        const $status = $('#ctaj-export-status');
        $status.removeClass('hidden ctaj-error ctaj-success').addClass('ctaj-loading').text('Reading JSON file…');

        const reader = new FileReader();
        reader.onload = function (e) {
            try {
                let json = JSON.parse(e.target.result);
                // ACF exports an array of field groups.
                const fieldGroup = Array.isArray(json) ? json[0] : json;
                if (!fieldGroup || !fieldGroup.fields) {
                    $status.removeClass('ctaj-loading').addClass('ctaj-error').text('Invalid ACF JSON — no fields found.');
                    return;
                }
                convertJsonToCsv(fieldGroup);
            } catch (err) {
                $status.removeClass('ctaj-loading').addClass('ctaj-error').text('Failed to parse JSON: ' + err.message);
            }
        };
        reader.readAsText(file);
    }

    // ----- Export: Select existing field group -----

    function loadFieldGroups() {
        const $list = $('#ctaj-field-groups-list');
        $list.html('<p class="ctaj-loading-text">Loading field groups…</p>');

        $.ajax({
            url: ctajData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ctaj_list_field_groups',
                nonce: ctajData.nonce,
            },
            success: function (res) {
                if (res.success && res.data.groups.length) {
                    let html = '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Field Group</th><th style="width:120px">Action</th></tr></thead><tbody>';
                    res.data.groups.forEach(function (g) {
                        html += '<tr><td>' + escHtml(g.title) + '</td><td><button type="button" class="button button-small ctaj-export-group-btn" data-key="' + escAttr(g.key) + '">' + 'Export' + '</button></td></tr>';
                    });
                    html += '</tbody></table>';
                    $list.html(html).data('loaded', true);
                } else if (res.success) {
                    $list.html('<p>No field groups found.</p>');
                } else {
                    $list.html('<p class="ctaj-error">' + escHtml(res.data.message) + '</p>');
                }
            },
            error: function () {
                $list.html('<p class="ctaj-error">Network error loading field groups.</p>');
            }
        });
    }

    // Click handler for exporting an existing field group.
    $(document).on('click', '.ctaj-export-group-btn', function () {
        const $btn = $(this).prop('disabled', true).text('Loading…');
        const groupKey = $(this).data('key');

        const $status = $('#ctaj-export-status');
        $status.removeClass('hidden ctaj-error ctaj-success').addClass('ctaj-loading').text('Converting field group…');

        $.ajax({
            url: ctajData.ajaxUrl + '?action=ctaj_json_to_csv&nonce=' + encodeURIComponent(ctajData.nonce),
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                groupKey: groupKey,
                includeCategories: $('#ctaj-export-include-categories').is(':checked'),
                delimiter: $('#ctaj-export-delimiter').val(),
            }),
            success: function (res) {
                $btn.prop('disabled', false).text('Export');
                if (res.success) {
                    $status.removeClass('ctaj-loading').addClass('ctaj-success').text('CSV generated for "' + res.data.groupTitle + '"');
                    showExportPreview(res.data);
                } else {
                    $status.removeClass('ctaj-loading').addClass('ctaj-error').text(res.data.message || 'Export failed.');
                }
            },
            error: function () {
                $btn.prop('disabled', false).text('Export');
                $status.removeClass('ctaj-loading').addClass('ctaj-error').text('Network error.');
            }
        });
    });

    // ----- Export: Convert uploaded JSON via AJAX -----

    function convertJsonToCsv(fieldGroup) {
        const $status = $('#ctaj-export-status');
        $status.removeClass('hidden ctaj-error ctaj-success').addClass('ctaj-loading').text('Converting to CSV…');

        $.ajax({
            url: ctajData.ajaxUrl + '?action=ctaj_json_to_csv&nonce=' + encodeURIComponent(ctajData.nonce),
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                fieldGroup: fieldGroup,
                includeCategories: $('#ctaj-export-include-categories').is(':checked'),
                delimiter: $('#ctaj-export-delimiter').val(),
            }),
            success: function (res) {
                if (res.success) {
                    $status.removeClass('ctaj-loading').addClass('ctaj-success').text('CSV generated for "' + res.data.groupTitle + '"');
                    showExportPreview(res.data);
                } else {
                    $status.removeClass('ctaj-loading').addClass('ctaj-error').text(res.data.message || 'Conversion failed.');
                }
            },
            error: function () {
                $status.removeClass('ctaj-loading').addClass('ctaj-error').text('Network error.');
            }
        });
    }

    // ----- Export: Show preview & enable download -----

    function showExportPreview(data) {
        exportCsvContent = data.csv;

        const $preview = $('#ctaj-export-preview').removeClass('hidden');
        const $table = $('#ctaj-export-preview-table').empty();

        const rowLabels = [];
        const hasCategories = data.rows.length >= 6;
        if (hasCategories) {
            rowLabels.push('Category', 'Field Name', 'Label', 'Type', 'Required', 'Instructions', 'Choices');
        } else {
            rowLabels.push('Field Name', 'Label', 'Type', 'Required', 'Instructions', 'Choices');
        }

        data.rows.forEach(function (row, idx) {
            const $tr = $('<tr>');
            const tag = idx === 0 ? 'th' : 'td';
            const label = rowLabels[idx] || '';
            $tr.append('<td class="ctaj-row-label"><strong>' + escHtml(label) + '</strong></td>');
            row.forEach(function (cell) {
                $tr.append('<' + tag + '>' + escHtml(cell || '') + '</' + tag + '>');
            });
            $table.append($tr);
        });
    }

    // ----- Export: Download CSV -----

    $('#ctaj-export-download').on('click', function () {
        if (!exportCsvContent) return;
        const blob = new Blob([exportCsvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'acf-field-group-export.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

    // ----- Export: Copy CSV -----

    $('#ctaj-export-copy').on('click', function () {
        if (!exportCsvContent) return;
        navigator.clipboard.writeText(exportCsvContent).then(function () {
            const $btn = $('#ctaj-export-copy');
            $btn.text('Copied!');
            setTimeout(function () { $btn.text('Copy to Clipboard'); }, 2000);
        });
    });

})(jQuery);
