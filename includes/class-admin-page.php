<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CTAJ_Admin_Page {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ctaj_upload_csv', [ $this, 'ajax_upload_csv' ] );
        add_action( 'wp_ajax_ctaj_generate_json', [ $this, 'ajax_generate_json' ] );
        add_action( 'wp_ajax_ctaj_import_to_acf', [ $this, 'ajax_import_to_acf' ] );
        add_action( 'wp_ajax_ctaj_json_to_csv', [ $this, 'ajax_json_to_csv' ] );
        add_action( 'wp_ajax_ctaj_list_field_groups', [ $this, 'ajax_list_field_groups' ] );
    }

    public function add_menu_page() {
        add_management_page(
            __( 'CSV to ACF JSON', 'csv-to-acf-json' ),
            __( 'CSV → ACF JSON', 'csv-to-acf-json' ),
            'manage_options',
            'csv-to-acf-json',
            [ $this, 'render_page' ]
        );
    }

    public function enqueue_assets( $hook ) {
        if ( 'tools_page_csv-to-acf-json' !== $hook ) {
            return;
        }
        wp_enqueue_style( 'ctaj-admin', CTAJ_PLUGIN_URL . 'assets/css/admin.css', [], CTAJ_VERSION );
        wp_enqueue_script( 'ctaj-admin', CTAJ_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery' ], CTAJ_VERSION, true );
        wp_localize_script( 'ctaj-admin', 'ctajData', [
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'ctaj_nonce' ),
            'fieldTypes' => self::get_acf_field_types(),
            'acfActive'  => class_exists( 'ACF' ) || function_exists( 'acf_get_field_groups' ),
            'acfEditUrl' => admin_url( 'post.php?action=edit&post=' ),
            'exportNonce' => wp_create_nonce( 'ctaj_nonce' ),
        ] );
    }

    public static function get_acf_field_types() {
        return [
            'Basic' => [
                'text'     => 'Text',
                'textarea' => 'Textarea',
                'number'   => 'Number',
                'range'    => 'Range',
                'email'    => 'Email',
                'url'      => 'URL',
                'password' => 'Password',
            ],
            'Content' => [
                'image'   => 'Image',
                'file'    => 'File',
                'wysiwyg' => 'WYSIWYG Editor',
                'oembed'  => 'oEmbed',
                'gallery' => 'Gallery',
            ],
            'Choice' => [
                'select'       => 'Select',
                'checkbox'     => 'Checkbox',
                'radio'        => 'Radio Button',
                'button_group' => 'Button Group',
                'true_false'   => 'True / False',
            ],
            'Relational' => [
                'link'         => 'Link',
                'post_object'  => 'Post Object',
                'page_link'    => 'Page Link',
                'relationship' => 'Relationship',
                'taxonomy'     => 'Taxonomy',
                'user'         => 'User',
            ],
            'Layout' => [
                'tab'       => 'Tab',
                'group'     => 'Group',
                'repeater'  => 'Repeater',
                'message'   => 'Message',
                'accordion' => 'Accordion',
            ],
            'Special' => [
                'color_picker' => 'Color Picker',
                'date_picker'  => 'Date Picker',
                'time_picker'  => 'Time Picker',
                'google_map'   => 'Google Map',
            ],
        ];
    }

    public function render_page() {
        ?>
        <div class="wrap ctaj-wrap">
            <h1><?php esc_html_e( 'CSV to ACF JSON', 'csv-to-acf-json' ); ?></h1>
            <p class="ctaj-description"><?php esc_html_e( 'Upload a CSV file to generate an ACF Field Group JSON file. The column headers become field definitions.', 'csv-to-acf-json' ); ?></p>

            <!-- Mode toggle -->
            <div class="ctaj-mode-toggle">
                <button type="button" class="button button-primary ctaj-mode-btn active" data-mode="import"><?php esc_html_e( 'CSV → ACF JSON', 'csv-to-acf-json' ); ?></button>
                <button type="button" class="button ctaj-mode-btn" data-mode="export"><?php esc_html_e( 'ACF JSON → CSV', 'csv-to-acf-json' ); ?></button>
            </div>

            <!-- Steps nav -->
            <div class="ctaj-import-mode">
            <div class="ctaj-steps">
                <div class="ctaj-step active" data-step="1"><span class="ctaj-step-num">1</span> Upload CSV</div>
                <div class="ctaj-step" data-step="2"><span class="ctaj-step-num">2</span> Configure Fields</div>
                <div class="ctaj-step" data-step="3"><span class="ctaj-step-num">3</span> Generate &amp; Download</div>
            </div>

            <!-- Step 1: Upload -->
            <div class="ctaj-panel" id="ctaj-step-1">
                <h2><?php esc_html_e( 'Upload CSV File', 'csv-to-acf-json' ); ?></h2>
                <div class="ctaj-upload-zone" id="ctaj-drop-zone">
                    <div class="ctaj-upload-icon">📄</div>
                    <p><?php esc_html_e( 'Drag & drop your CSV file here, or click to browse', 'csv-to-acf-json' ); ?></p>
                    <input type="file" id="ctaj-file-input" accept=".csv,.tsv,.txt" />
                    <button type="button" class="button button-primary" id="ctaj-browse-btn"><?php esc_html_e( 'Choose File', 'csv-to-acf-json' ); ?></button>
                </div>

                <div class="ctaj-upload-options">
                    <label>
                        <input type="checkbox" id="ctaj-has-category-row" checked />
                        <?php esc_html_e( 'First row contains category/tab groupings (second row has field names)', 'csv-to-acf-json' ); ?>
                    </label>
                    <label>
                        <?php esc_html_e( 'Delimiter:', 'csv-to-acf-json' ); ?>
                        <select id="ctaj-delimiter">
                            <option value=","><?php esc_html_e( 'Comma (,)', 'csv-to-acf-json' ); ?></option>
                            <option value="	"><?php esc_html_e( 'Tab', 'csv-to-acf-json' ); ?></option>
                            <option value=";"><?php esc_html_e( 'Semicolon (;)', 'csv-to-acf-json' ); ?></option>
                        </select>
                    </label>
                </div>

                <div id="ctaj-upload-status" class="ctaj-status hidden"></div>
            </div>

            <!-- Step 2: Configure -->
            <div class="ctaj-panel hidden" id="ctaj-step-2">
                <h2><?php esc_html_e( 'Configure Fields', 'csv-to-acf-json' ); ?></h2>

                <div class="ctaj-group-settings">
                    <div class="ctaj-setting-row">
                        <label for="ctaj-group-title"><?php esc_html_e( 'Field Group Title', 'csv-to-acf-json' ); ?></label>
                        <input type="text" id="ctaj-group-title" value="" placeholder="My Field Group" />
                    </div>
                    <div class="ctaj-setting-row">
                        <label><?php esc_html_e( 'Generate Tab fields from category row?', 'csv-to-acf-json' ); ?></label>
                        <label class="ctaj-toggle">
                            <input type="checkbox" id="ctaj-use-tabs" checked />
                            <span class="ctaj-toggle-label"><?php esc_html_e( 'Yes — group fields under tab headings', 'csv-to-acf-json' ); ?></span>
                        </label>
                    </div>
                    <div class="ctaj-setting-row">
                        <label><?php esc_html_e( 'Location Rule', 'csv-to-acf-json' ); ?></label>
                        <div class="ctaj-location-rule">
                            <select id="ctaj-location-param">
                                <option value="post_type"><?php esc_html_e( 'Post Type', 'csv-to-acf-json' ); ?></option>
                                <option value="page_template"><?php esc_html_e( 'Page Template', 'csv-to-acf-json' ); ?></option>
                                <option value="options_page"><?php esc_html_e( 'Options Page', 'csv-to-acf-json' ); ?></option>
                            </select>
                            <select id="ctaj-location-operator">
                                <option value="=="><?php esc_html_e( 'is equal to', 'csv-to-acf-json' ); ?></option>
                                <option value="!="><?php esc_html_e( 'is not equal to', 'csv-to-acf-json' ); ?></option>
                            </select>
                            <input type="text" id="ctaj-location-value" value="post" placeholder="post" />
                        </div>
                    </div>
                </div>

                <div class="ctaj-bulk-actions">
                    <label><?php esc_html_e( 'Bulk set type:', 'csv-to-acf-json' ); ?></label>
                    <select id="ctaj-bulk-type"></select>
                    <button type="button" class="button" id="ctaj-bulk-apply"><?php esc_html_e( 'Apply to All', 'csv-to-acf-json' ); ?></button>
                    <span class="ctaj-sep">|</span>
                    <button type="button" class="button" id="ctaj-auto-detect"><?php esc_html_e( 'Auto-Detect Types', 'csv-to-acf-json' ); ?></button>
                </div>

                <!-- Repeater detection notice -->
                <div id="ctaj-repeater-notice" class="ctaj-repeater-notice hidden">
                    <h3>🔁 <?php esc_html_e( 'Repeater Fields Detected', 'csv-to-acf-json' ); ?></h3>
                    <p class="ctaj-description"><?php esc_html_e( 'Numbered column patterns were detected and grouped into Repeater fields. Click "Break Apart" to convert back to flat fields.', 'csv-to-acf-json' ); ?></p>
                    <div id="ctaj-repeater-groups"></div>
                </div>

                <div id="ctaj-fields-table-wrap">
                    <table class="wp-list-table widefat fixed striped" id="ctaj-fields-table">
                        <thead>
                            <tr>
                                <th class="ctaj-col-num">#</th>
                                <th class="ctaj-col-tab"><?php esc_html_e( 'Tab / Group', 'csv-to-acf-json' ); ?></th>
                                <th class="ctaj-col-name"><?php esc_html_e( 'Field Name', 'csv-to-acf-json' ); ?></th>
                                <th class="ctaj-col-label"><?php esc_html_e( 'Label', 'csv-to-acf-json' ); ?></th>
                                <th class="ctaj-col-type"><?php esc_html_e( 'Field Type', 'csv-to-acf-json' ); ?></th>
                                <th class="ctaj-col-required"><?php esc_html_e( 'Required', 'csv-to-acf-json' ); ?></th>
                                <th class="ctaj-col-instructions"><?php esc_html_e( 'Instructions', 'csv-to-acf-json' ); ?></th>
                                <th class="ctaj-col-actions"><?php esc_html_e( 'Include', 'csv-to-acf-json' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="ctaj-fields-body"></tbody>
                    </table>
                </div>

                <div class="ctaj-sample-data hidden" id="ctaj-sample-data">
                    <h3><?php esc_html_e( 'Sample Data Preview', 'csv-to-acf-json' ); ?></h3>
                    <div class="ctaj-sample-scroll">
                        <table class="wp-list-table widefat fixed striped" id="ctaj-sample-table"></table>
                    </div>
                </div>

                <div class="ctaj-step-actions">
                    <button type="button" class="button" id="ctaj-back-1"><?php esc_html_e( '← Back', 'csv-to-acf-json' ); ?></button>
                    <button type="button" class="button button-primary" id="ctaj-generate"><?php esc_html_e( 'Generate JSON →', 'csv-to-acf-json' ); ?></button>
                </div>
            </div>

            <!-- Step 3: Download -->
            <div class="ctaj-panel hidden" id="ctaj-step-3">
                <h2><?php esc_html_e( 'Generated ACF JSON', 'csv-to-acf-json' ); ?></h2>
                <div class="ctaj-json-preview">
                    <pre id="ctaj-json-output"></pre>
                </div>
                <div id="ctaj-import-status" class="ctaj-status hidden"></div>

                <div class="ctaj-step-actions">
                    <button type="button" class="button" id="ctaj-back-2"><?php esc_html_e( '← Back to Configure', 'csv-to-acf-json' ); ?></button>
                    <button type="button" class="button button-primary" id="ctaj-download"><?php esc_html_e( 'Download JSON File', 'csv-to-acf-json' ); ?></button>
                    <button type="button" class="button" id="ctaj-copy-json"><?php esc_html_e( 'Copy to Clipboard', 'csv-to-acf-json' ); ?></button>
                    <button type="button" class="button button-primary" id="ctaj-import-acf" style="display:none;"><?php esc_html_e( 'Import Directly to ACF', 'csv-to-acf-json' ); ?></button>
                </div>
            </div>
            </div><!-- .ctaj-import-mode -->

            <!-- Export mode: ACF JSON → CSV -->
            <div class="ctaj-export-mode hidden">
                <h2><?php esc_html_e( 'ACF JSON → CSV Export', 'csv-to-acf-json' ); ?></h2>
                <p class="ctaj-description"><?php esc_html_e( 'Upload an ACF JSON export file or select an existing field group, then download a CSV that can be re-imported through Step 1.', 'csv-to-acf-json' ); ?></p>

                <div class="ctaj-export-source">
                    <div class="ctaj-export-tabs">
                        <button type="button" class="button button-primary ctaj-export-tab active" data-tab="upload"><?php esc_html_e( 'Upload JSON File', 'csv-to-acf-json' ); ?></button>
                        <?php if ( class_exists( 'ACF' ) || function_exists( 'acf_get_field_groups' ) ) : ?>
                            <button type="button" class="button ctaj-export-tab" data-tab="existing"><?php esc_html_e( 'Select Existing Field Group', 'csv-to-acf-json' ); ?></button>
                        <?php endif; ?>
                    </div>

                    <!-- Upload JSON sub-tab -->
                    <div class="ctaj-export-panel" id="ctaj-export-upload">
                        <div class="ctaj-upload-zone" id="ctaj-json-drop-zone">
                            <div class="ctaj-upload-icon">📋</div>
                            <p><?php esc_html_e( 'Drag & drop an ACF JSON export file here, or click to browse', 'csv-to-acf-json' ); ?></p>
                            <input type="file" id="ctaj-json-file-input" accept=".json" />
                            <button type="button" class="button button-primary" id="ctaj-json-browse-btn"><?php esc_html_e( 'Choose JSON File', 'csv-to-acf-json' ); ?></button>
                        </div>
                    </div>

                    <!-- Existing field groups sub-tab -->
                    <div class="ctaj-export-panel hidden" id="ctaj-export-existing">
                        <div id="ctaj-field-groups-list">
                            <p class="ctaj-loading-text"><?php esc_html_e( 'Loading field groups…', 'csv-to-acf-json' ); ?></p>
                        </div>
                    </div>
                </div>

                <div class="ctaj-export-options">
                    <label>
                        <input type="checkbox" id="ctaj-export-include-categories" checked />
                        <?php esc_html_e( 'Include category/tab row (row 1)', 'csv-to-acf-json' ); ?>
                    </label>
                    <label>
                        <?php esc_html_e( 'Delimiter:', 'csv-to-acf-json' ); ?>
                        <select id="ctaj-export-delimiter">
                            <option value=","><?php esc_html_e( 'Comma (,)', 'csv-to-acf-json' ); ?></option>
                            <option value="	"><?php esc_html_e( 'Tab', 'csv-to-acf-json' ); ?></option>
                            <option value=";"><?php esc_html_e( 'Semicolon (;)', 'csv-to-acf-json' ); ?></option>
                        </select>
                    </label>
                </div>

                <div id="ctaj-export-status" class="ctaj-status hidden"></div>

                <div class="ctaj-export-preview hidden" id="ctaj-export-preview">
                    <h3><?php esc_html_e( 'CSV Preview', 'csv-to-acf-json' ); ?></h3>
                    <div class="ctaj-sample-scroll">
                        <table class="wp-list-table widefat fixed striped" id="ctaj-export-preview-table"></table>
                    </div>
                    <div class="ctaj-step-actions">
                        <button type="button" class="button button-primary" id="ctaj-export-download"><?php esc_html_e( 'Download CSV', 'csv-to-acf-json' ); ?></button>
                        <button type="button" class="button" id="ctaj-export-copy"><?php esc_html_e( 'Copy to Clipboard', 'csv-to-acf-json' ); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function ajax_upload_csv() {
        check_ajax_referer( 'ctaj_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'csv-to-acf-json' ) ] );
        }

        if ( empty( $_FILES['csv_file'] ) ) {
            wp_send_json_error( [ 'message' => __( 'No file uploaded.', 'csv-to-acf-json' ) ] );
        }

        $file = $_FILES['csv_file'];

        // Validate file type.
        $allowed_mimes = [ 'text/csv', 'text/plain', 'text/tab-separated-values', 'application/csv', 'application/vnd.ms-excel' ];
        $finfo         = finfo_open( FILEINFO_MIME_TYPE );
        $mime_type      = finfo_file( $finfo, $file['tmp_name'] );
        finfo_close( $finfo );

        if ( ! in_array( $mime_type, $allowed_mimes, true ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid file type. Please upload a CSV file.', 'csv-to-acf-json' ) ] );
        }

        // Validate file extension.
        $ext = strtolower( pathinfo( sanitize_file_name( $file['name'] ), PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, [ 'csv', 'tsv', 'txt' ], true ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid file extension.', 'csv-to-acf-json' ) ] );
        }

        $has_category_row = ! empty( $_POST['has_category_row'] );
        $delimiter        = isset( $_POST['delimiter'] ) ? sanitize_text_field( wp_unslash( $_POST['delimiter'] ) ) : ',';

        if ( ! in_array( $delimiter, [ ',', "\t", ';' ], true ) ) {
            $delimiter = ',';
        }

        $content = file_get_contents( $file['tmp_name'] );

        // Strip BOM.
        if ( substr( $content, 0, 3 ) === "\xEF\xBB\xBF" ) {
            $content = substr( $content, 3 );
        }

        $lines = str_getcsv_rows( $content, $delimiter );

        if ( count( $lines ) < 1 ) {
            wp_send_json_error( [ 'message' => __( 'CSV file appears to be empty.', 'csv-to-acf-json' ) ] );
        }

        $categories  = [];
        $field_names = [];
        $sample_data = [];

        if ( $has_category_row && count( $lines ) >= 2 ) {
            $categories  = $lines[0];
            $field_names = $lines[1];
            $sample_data = array_slice( $lines, 2, 5 );
        } else {
            $field_names = $lines[0];
            $sample_data = array_slice( $lines, 1, 5 );
        }

        // Resolve spanning categories (fill empty cells with previous value).
        $resolved_categories = [];
        $last_cat = '';
        foreach ( $categories as $i => $cat ) {
            $cat = trim( $cat );
            if ( $cat !== '' ) {
                $last_cat = $cat;
            }
            $resolved_categories[ $i ] = $last_cat;
        }

        // Collect ALL data rows (not just sample) for unique value extraction.
        if ( $has_category_row && count( $lines ) >= 2 ) {
            $all_data_rows = array_slice( $lines, 2 );
        } else {
            $all_data_rows = array_slice( $lines, 1 );
        }

        // Build fields array.
        $fields = [];
        foreach ( $field_names as $i => $name ) {
            $name = trim( $name );
            if ( $name === '' ) {
                continue;
            }

            $unique_values = self::extract_unique_values( $i, $all_data_rows );
            $guessed_type  = self::guess_field_type( $name, $i, $sample_data );

            // If there are a small number of unique non-empty values, suggest select.
            $choices = [];
            if ( count( $unique_values ) >= 2 && count( $unique_values ) <= 15 && ! in_array( $guessed_type, [ 'url', 'email', 'image', 'file', 'wysiwyg', 'textarea', 'date_picker', 'color_picker', 'google_map' ], true ) ) {
                $guessed_type = 'select';
                $choices      = $unique_values;
            }

            $fields[] = [
                'index'    => $i,
                'name'     => sanitize_title( str_replace( ' ', '_', $name ) ),
                'label'    => self::name_to_label( $name ),
                'raw_name' => sanitize_text_field( $name ),
                'category' => isset( $resolved_categories[ $i ] ) ? sanitize_text_field( $resolved_categories[ $i ] ) : '',
                'type'     => $guessed_type,
                'choices'  => array_map( 'sanitize_text_field', $choices ),
            ];
        }

        // Detect repeater patterns (e.g. prefix_1_suffix, prefix_2_suffix).
        $repeater_groups = self::detect_repeater_patterns( $fields );

        // Filter sample data to only include columns that have fields.
        $field_indices = array_column( $fields, 'index' );
        $filtered_sample = [];
        foreach ( $sample_data as $row ) {
            $filtered_row = [];
            foreach ( $field_indices as $idx ) {
                $filtered_row[] = isset( $row[ $idx ] ) ? sanitize_text_field( $row[ $idx ] ) : '';
            }
            $filtered_sample[] = $filtered_row;
        }

        // Store in transient for generation step.
        $session_key = wp_generate_password( 12, false );
        set_transient( 'ctaj_session_' . $session_key, [
            'fields'      => $fields,
            'sample_data' => $filtered_sample,
        ], HOUR_IN_SECONDS );

        wp_send_json_success( [
            'sessionKey'      => $session_key,
            'fields'          => $fields,
            'sampleData'      => $filtered_sample,
            'repeaterGroups'  => $repeater_groups,
        ] );
    }

    public function ajax_generate_json() {
        // Nonce comes via query string since body is JSON.
        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'ctaj_nonce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed.', 'csv-to-acf-json' ) ] );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'csv-to-acf-json' ) ] );
        }

        $raw_input = file_get_contents( 'php://input' );
        $input     = json_decode( $raw_input, true );

        if ( ! $input || empty( $input['fields'] ) ) {
            wp_send_json_error( [ 'message' => __( 'No field configuration provided.', 'csv-to-acf-json' ) ] );
        }

        $config = [
            'group_title'    => isset( $input['groupTitle'] ) ? sanitize_text_field( $input['groupTitle'] ) : 'Imported Field Group',
            'use_tabs'       => ! empty( $input['useTabs'] ),
            'location_param' => isset( $input['locationParam'] ) ? sanitize_text_field( $input['locationParam'] ) : 'post_type',
            'location_op'    => isset( $input['locationOp'] ) ? sanitize_text_field( $input['locationOp'] ) : '==',
            'location_value' => isset( $input['locationValue'] ) ? sanitize_text_field( $input['locationValue'] ) : 'post',
            'fields'         => [],
            'repeaters'      => [],
        ];

        foreach ( $input['fields'] as $f ) {
            if ( empty( $f['include'] ) ) {
                continue;
            }
            $choices_raw = isset( $f['choices'] ) && is_array( $f['choices'] ) ? $f['choices'] : [];
            $choices = [];
            foreach ( $choices_raw as $c ) {
                $c = sanitize_text_field( $c );
                if ( $c !== '' ) {
                    $choices[ $c ] = $c;
                }
            }

            $config['fields'][] = [
                'name'         => sanitize_title( str_replace( ' ', '_', $f['name'] ) ),
                'label'        => sanitize_text_field( $f['label'] ),
                'type'         => sanitize_text_field( $f['type'] ),
                'required'     => ! empty( $f['required'] ) ? 1 : 0,
                'instructions' => sanitize_textarea_field( $f['instructions'] ?? '' ),
                'category'     => sanitize_text_field( $f['category'] ?? '' ),
                'choices'      => $choices,
            ];
        }

        // Parse repeater groups.
        if ( ! empty( $input['repeaters'] ) && is_array( $input['repeaters'] ) ) {
            foreach ( $input['repeaters'] as $rg ) {
                $sub_fields = [];
                if ( ! empty( $rg['subFields'] ) && is_array( $rg['subFields'] ) ) {
                    foreach ( $rg['subFields'] as $sf ) {
                        $sub_fields[] = [
                            'name'  => sanitize_title( $sf['name'] ?? 'value' ),
                            'label' => sanitize_text_field( $sf['label'] ?? 'Value' ),
                            'type'  => sanitize_text_field( $sf['type'] ?? 'text' ),
                        ];
                    }
                }
                $config['repeaters'][] = [
                    'name'         => sanitize_title( $rg['name'] ?? '' ),
                    'label'        => sanitize_text_field( $rg['label'] ?? '' ),
                    'category'     => sanitize_text_field( $rg['category'] ?? '' ),
                    'sub_fields'   => $sub_fields,
                    'max_rows'     => intval( $rg['maxRows'] ?? 0 ),
                    'field_indices' => array_map( 'intval', $rg['fieldIndices'] ?? [] ),
                ];
            }
        }

        $generator = new CTAJ_JSON_Generator();
        $json      = $generator->generate( $config );

        wp_send_json_success( [ 'json' => $json ] );
    }

    public function ajax_import_to_acf() {
        // Nonce comes via query string since body is JSON.
        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'ctaj_nonce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed.', 'csv-to-acf-json' ) ] );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'csv-to-acf-json' ) ] );
        }

        if ( ! function_exists( 'acf_import_field_group' ) ) {
            wp_send_json_error( [ 'message' => __( 'ACF is not active. Please activate Advanced Custom Fields first.', 'csv-to-acf-json' ) ] );
        }

        $raw_input = file_get_contents( 'php://input' );
        $input     = json_decode( $raw_input, true );

        if ( ! $input || empty( $input['fieldGroup'] ) || ! is_array( $input['fieldGroup'] ) ) {
            wp_send_json_error( [ 'message' => __( 'No field group data provided.', 'csv-to-acf-json' ) ] );
        }

        $field_group = $input['fieldGroup'];

        // Check for existing field group with same key.
        $existing = acf_get_field_group( $field_group['key'] );
        if ( $existing ) {
            wp_send_json_error( [
                'message' => sprintf(
                    __( 'A field group with key "%s" already exists (ID: %d). Delete it first or generate a new JSON to get a fresh key.', 'csv-to-acf-json' ),
                    esc_html( $field_group['key'] ),
                    $existing['ID']
                ),
            ] );
        }

        $imported = acf_import_field_group( $field_group );

        if ( ! $imported || empty( $imported['ID'] ) ) {
            wp_send_json_error( [ 'message' => __( 'ACF import failed. Please try downloading the JSON and importing manually.', 'csv-to-acf-json' ) ] );
        }

        wp_send_json_success( [
            'message' => sprintf( __( 'Field group "%s" imported successfully!', 'csv-to-acf-json' ), esc_html( $field_group['title'] ) ),
            'postId'  => $imported['ID'],
            'editUrl' => admin_url( 'post.php?action=edit&post=' . $imported['ID'] ),
        ] );
    }

    /**
     * AJAX: List existing ACF field groups.
     */
    public function ajax_list_field_groups() {
        check_ajax_referer( 'ctaj_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'csv-to-acf-json' ) ] );
        }

        if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'acf_get_fields' ) ) {
            wp_send_json_error( [ 'message' => __( 'ACF is not active.', 'csv-to-acf-json' ) ] );
        }

        $groups = acf_get_field_groups();
        $result = [];

        foreach ( $groups as $group ) {
            $result[] = [
                'key'   => $group['key'],
                'title' => $group['title'],
                'id'    => $group['ID'],
            ];
        }

        wp_send_json_success( [ 'groups' => $result ] );
    }

    /**
     * AJAX: Convert ACF JSON to CSV.
     */
    public function ajax_json_to_csv() {
        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'ctaj_nonce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed.', 'csv-to-acf-json' ) ] );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'csv-to-acf-json' ) ] );
        }

        $raw_input = file_get_contents( 'php://input' );
        $input     = json_decode( $raw_input, true );

        if ( ! $input ) {
            wp_send_json_error( [ 'message' => __( 'Invalid request data.', 'csv-to-acf-json' ) ] );
        }

        $field_group = null;

        // Source: uploaded JSON or existing field group key.
        if ( ! empty( $input['fieldGroup'] ) && is_array( $input['fieldGroup'] ) ) {
            $field_group = $input['fieldGroup'];
        } elseif ( ! empty( $input['groupKey'] ) && function_exists( 'acf_get_field_group' ) && function_exists( 'acf_get_fields' ) ) {
            $group_key = sanitize_text_field( $input['groupKey'] );
            $group     = acf_get_field_group( $group_key );
            if ( ! $group ) {
                wp_send_json_error( [ 'message' => __( 'Field group not found.', 'csv-to-acf-json' ) ] );
            }
            $fields = acf_get_fields( $group['key'] );
            $field_group = $group;
            $field_group['fields'] = $fields ?: [];
        }

        if ( ! $field_group || empty( $field_group['fields'] ) ) {
            wp_send_json_error( [ 'message' => __( 'No fields found in the field group.', 'csv-to-acf-json' ) ] );
        }

        $include_categories = ! empty( $input['includeCategories'] );
        $delimiter          = isset( $input['delimiter'] ) ? $input['delimiter'] : ',';
        if ( ! in_array( $delimiter, [ ',', "\t", ';' ], true ) ) {
            $delimiter = ',';
        }

        // Parse fields — separate tabs from real fields.
        $current_tab = '';
        $csv_fields  = [];

        foreach ( $field_group['fields'] as $field ) {
            if ( isset( $field['type'] ) && $field['type'] === 'tab' ) {
                $current_tab = $field['label'] ?? '';
                continue;
            }

            $choices_str = '';
            if ( ! empty( $field['choices'] ) && is_array( $field['choices'] ) ) {
                $choices_str = implode( ', ', array_values( $field['choices'] ) );
            }

            $csv_fields[] = [
                'category'     => $current_tab,
                'name'         => $field['name'] ?? '',
                'label'        => $field['label'] ?? '',
                'type'         => $field['type'] ?? 'text',
                'required'     => ! empty( $field['required'] ) ? '1' : '0',
                'instructions' => $field['instructions'] ?? '',
                'choices'      => $choices_str,
            ];
        }

        if ( empty( $csv_fields ) ) {
            wp_send_json_error( [ 'message' => __( 'No exportable fields found.', 'csv-to-acf-json' ) ] );
        }

        // Build CSV rows.
        $rows = [];
        $has_categories = false;
        foreach ( $csv_fields as $f ) {
            if ( ! empty( $f['category'] ) ) {
                $has_categories = true;
                break;
            }
        }

        // Category row (row 1) — only emit when there are tabs and user opted in.
        if ( $include_categories && $has_categories ) {
            $cat_row = [];
            $last_cat = '';
            foreach ( $csv_fields as $f ) {
                // Only emit category name when it changes (mimics spanning).
                if ( $f['category'] !== $last_cat ) {
                    $cat_row[] = $f['category'];
                    $last_cat  = $f['category'];
                } else {
                    $cat_row[] = '';
                }
            }
            $rows[] = $cat_row;
        }

        // Field name row.
        $name_row = [];
        foreach ( $csv_fields as $f ) {
            $name_row[] = $f['name'];
        }
        $rows[] = $name_row;

        // Metadata rows: label, type, required, instructions, choices.
        $label_row = [];
        $type_row  = [];
        $req_row   = [];
        $instr_row = [];
        $choice_row = [];

        foreach ( $csv_fields as $f ) {
            $label_row[]  = $f['label'];
            $type_row[]   = $f['type'];
            $req_row[]    = $f['required'];
            $instr_row[]  = $f['instructions'];
            $choice_row[] = $f['choices'];
        }

        $rows[] = $label_row;
        $rows[] = $type_row;
        $rows[] = $req_row;
        $rows[] = $instr_row;
        $rows[] = $choice_row;

        // Generate CSV string.
        $handle = fopen( 'php://temp', 'r+' );
        foreach ( $rows as $row ) {
            fputcsv( $handle, $row, $delimiter );
        }
        rewind( $handle );
        $csv_content = stream_get_contents( $handle );
        fclose( $handle );

        wp_send_json_success( [
            'csv'       => $csv_content,
            'rows'      => $rows,
            'fields'    => $csv_fields,
            'groupTitle' => $field_group['title'] ?? 'exported',
        ] );
    }

    /**
     * Heuristic to guess ACF field type from column name and sample data.
     */
    private static function guess_field_type( $name, $col_index, $sample_data ) {
        $lower = strtolower( $name );

        // URL-like names.
        if ( preg_match( '/\b(url|link|website|href|uri)\b/', $lower ) ) {
            return 'url';
        }
        // Email.
        if ( preg_match( '/\b(email|e-mail)\b/', $lower ) ) {
            return 'email';
        }
        // Image / file.
        if ( preg_match( '/\b(image|img|photo|thumbnail|avatar|logo|icon)\b/', $lower ) ) {
            return 'image';
        }
        if ( preg_match( '/\b(file|attachment|document|pdf)\b/', $lower ) ) {
            return 'file';
        }
        // Boolean-ish.
        if ( preg_match( '/\b(is_|has_|enable|active|visible|featured)\b/', $lower ) ) {
            return 'true_false';
        }
        // Date / time.
        if ( preg_match( '/\b(date|birthday|dob|created_at|updated_at)\b/', $lower ) ) {
            return 'date_picker';
        }
        // Number.
        if ( preg_match( '/\b(count|total|amount|price|cost|qty|quantity|number|num|score|rating|order)\b/', $lower ) ) {
            return 'number';
        }
        // Long text indicators.
        if ( preg_match( '/\b(description|content|body|summary|bio|notes|details|excerpt|message|honest_truth)\b/', $lower ) ) {
            return 'textarea';
        }
        // Rich text.
        if ( preg_match( '/\b(wysiwyg|editor|richtext|html)\b/', $lower ) ) {
            return 'wysiwyg';
        }
        // Color.
        if ( preg_match( '/\b(color|colour)\b/', $lower ) ) {
            return 'color_picker';
        }

        // Check sample data for patterns.
        $values = [];
        foreach ( $sample_data as $row ) {
            if ( isset( $row[ $col_index ] ) && trim( $row[ $col_index ] ) !== '' ) {
                $values[] = trim( $row[ $col_index ] );
            }
        }

        if ( ! empty( $values ) ) {
            $all_urls   = true;
            $all_nums   = true;
            $all_bool   = true;
            $all_emails = true;

            foreach ( $values as $v ) {
                if ( ! filter_var( $v, FILTER_VALIDATE_URL ) ) {
                    $all_urls = false;
                }
                if ( ! is_numeric( $v ) ) {
                    $all_nums = false;
                }
                if ( ! in_array( strtolower( $v ), [ '0', '1', 'yes', 'no', 'true', 'false' ], true ) ) {
                    $all_bool = false;
                }
                if ( ! filter_var( $v, FILTER_VALIDATE_EMAIL ) ) {
                    $all_emails = false;
                }
            }

            if ( $all_emails ) return 'email';
            if ( $all_urls )   return 'url';
            if ( $all_bool )   return 'true_false';
            if ( $all_nums )   return 'number';

            // If most values are long text, use textarea.
            $avg_len = array_sum( array_map( 'strlen', $values ) ) / count( $values );
            if ( $avg_len > 150 ) {
                return 'textarea';
            }
        }

        return 'text';
    }

    /**
     * Extract unique non-empty values for a column across all data rows.
     */
    private static function extract_unique_values( $col_index, $all_data_rows ) {
        $values = [];
        foreach ( $all_data_rows as $row ) {
            if ( isset( $row[ $col_index ] ) ) {
                $v = trim( $row[ $col_index ] );
                if ( $v !== '' ) {
                    $values[ $v ] = true;
                }
            }
        }
        return array_keys( $values );
    }

    /**
     * Detect repeater patterns from numbered column names.
     *
     * Patterns detected:
     *   prefix_1_suffix, prefix_2_suffix  → Repeater "prefix" with sub-field "suffix"
     *   prefix_1, prefix_2               → Repeater "prefix" with one sub-field "value"
     *   prefix_suffix_1, prefix_suffix_2  → Repeater "prefix_suffix" with one sub-field "value"
     *
     * Returns array of repeater groups, each with:
     *   name, label, fieldIndices (indices into $fields), subFields, maxRows
     */
    private static function detect_repeater_patterns( $fields ) {
        // Map each field to its numbered segments.
        $candidates = [];
        foreach ( $fields as $idx => $field ) {
            $name = $field['name'];
            // Match pattern: (prefix)_(number)_(suffix) or (prefix)_(number)
            if ( preg_match( '/^(.+?)_(\d+)(?:_(.+))?$/', $name, $m ) ) {
                $prefix = $m[1];
                $num    = (int) $m[2];
                $suffix = isset( $m[3] ) && $m[3] !== '' ? $m[3] : '_value';
                $candidates[] = [
                    'fieldIndex' => $idx,
                    'prefix'     => $prefix,
                    'number'     => $num,
                    'suffix'     => $suffix,
                ];
            }
        }

        // Group by prefix.
        $by_prefix = [];
        foreach ( $candidates as $c ) {
            $by_prefix[ $c['prefix'] ][] = $c;
        }

        $repeater_groups = [];

        foreach ( $by_prefix as $prefix => $items ) {
            // Need at least 2 items with different numbers to form a repeater.
            $numbers = array_unique( array_column( $items, 'number' ) );
            if ( count( $numbers ) < 2 ) {
                continue;
            }

            // Determine unique suffixes (sub-fields).
            $suffixes = array_unique( array_column( $items, 'suffix' ) );
            $max_rows = max( $numbers );

            // Build sub-fields list. Use the first occurrence to get type/category info.
            $sub_fields = [];
            foreach ( $suffixes as $suffix ) {
                // Find first field with this suffix.
                $first = null;
                foreach ( $items as $item ) {
                    if ( $item['suffix'] === $suffix ) {
                        $first = $fields[ $item['fieldIndex'] ];
                        break;
                    }
                }
                $sub_name = $suffix === '_value' ? 'value' : $suffix;
                $sub_fields[] = [
                    'name'  => sanitize_title( $sub_name ),
                    'label' => self::name_to_label( $sub_name ),
                    'type'  => $first ? $first['type'] : 'text',
                ];
            }

            $field_indices = array_column( $items, 'fieldIndex' );
            sort( $field_indices );

            // Use category from the first field in the group.
            $first_field = $fields[ $field_indices[0] ];

            $repeater_groups[] = [
                'prefix'       => $prefix,
                'name'         => sanitize_title( $prefix ),
                'label'        => self::name_to_label( $prefix ),
                'category'     => $first_field['category'] ?? '',
                'fieldIndices' => $field_indices,
                'subFields'    => $sub_fields,
                'maxRows'      => $max_rows,
            ];
        }

        return $repeater_groups;
    }

    private static function name_to_label( $name ) {
        // Convert snake_case/PascalCase to readable label.
        $label = preg_replace( '/[_\-]+/', ' ', $name );
        // Split camelCase.
        $label = preg_replace( '/([a-z])([A-Z])/', '$1 $2', $label );
        return ucwords( trim( $label ) );
    }
}

/**
 * Parse CSV content into rows.
 */
function str_getcsv_rows( $content, $delimiter = ',' ) {
    $rows   = [];
    $handle = fopen( 'php://temp', 'r+' );
    fwrite( $handle, $content );
    rewind( $handle );

    while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
        $rows[] = $row;
    }

    fclose( $handle );
    return $rows;
}
