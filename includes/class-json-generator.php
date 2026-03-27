<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CTAJ_JSON_Generator {

    /**
     * Generate ACF Field Group JSON from configuration.
     *
     * @param array $config {
     *     @type string $group_title    Field group title.
     *     @type bool   $use_tabs       Whether to generate tab fields from categories.
     *     @type string $location_param Location rule param (post_type, page_template, etc).
     *     @type string $location_op    Location rule operator (==, !=).
     *     @type string $location_value Location rule value.
     *     @type array  $fields         Array of field definitions.
     * }
     * @return array ACF-compatible field group array.
     */
    public function generate( array $config ) {
        $group_key = 'group_' . substr( md5( $config['group_title'] . wp_generate_password( 8, false ) ), 0, 13 );

        $acf_fields  = [];
        $current_tab = '';
        $field_order = 0;

        foreach ( $config['fields'] as $field ) {
            // Insert tab field if category changed and tabs are enabled.
            if ( $config['use_tabs'] && ! empty( $field['category'] ) && $field['category'] !== $current_tab ) {
                $current_tab  = $field['category'];
                $acf_fields[] = $this->make_tab_field( $current_tab, $group_key, $field_order );
                $field_order++;
            }

            $acf_fields[] = $this->make_field( $field, $group_key, $field_order );
            $field_order++;
        }

        $group = [
            'key'                   => $group_key,
            'title'                 => $config['group_title'],
            'fields'                => $acf_fields,
            'location'              => [
                [
                    [
                        'param'    => $config['location_param'],
                        'operator' => $config['location_op'],
                        'value'    => $config['location_value'],
                    ],
                ],
            ],
            'menu_order'            => 0,
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen'        => '',
            'active'                => true,
            'description'           => '',
            'show_in_rest'          => 0,
        ];

        return [ $group ];
    }

    private function make_field( array $field, $group_key, $order ) {
        $field_key = 'field_' . substr( md5( $group_key . $field['name'] . $order ), 0, 13 );

        $acf_field = [
            'key'               => $field_key,
            'label'             => $field['label'],
            'name'              => $field['name'],
            'aria-label'        => '',
            'type'              => $field['type'],
            'instructions'      => $field['instructions'] ?? '',
            'required'          => $field['required'] ?? 0,
            'conditional_logic' => 0,
            'wrapper'           => [
                'width' => '',
                'class' => '',
                'id'    => '',
            ],
        ];

        // Add type-specific defaults.
        $acf_field = array_merge( $acf_field, $this->type_defaults( $field['type'] ) );

        return $acf_field;
    }

    private function make_tab_field( $label, $group_key, $order ) {
        $tab_key = 'field_' . substr( md5( $group_key . 'tab_' . $label . $order ), 0, 13 );

        return [
            'key'               => $tab_key,
            'label'             => $label,
            'name'              => '',
            'aria-label'        => '',
            'type'              => 'tab',
            'instructions'      => '',
            'required'          => 0,
            'conditional_logic' => 0,
            'wrapper'           => [
                'width' => '',
                'class' => '',
                'id'    => '',
            ],
            'placement'         => 'top',
            'endpoint'          => 0,
        ];
    }

    /**
     * Return sensible defaults for each ACF field type.
     */
    private function type_defaults( $type ) {
        switch ( $type ) {
            case 'text':
                return [
                    'default_value' => '',
                    'maxlength'     => '',
                    'placeholder'   => '',
                    'prepend'       => '',
                    'append'        => '',
                ];

            case 'textarea':
                return [
                    'default_value' => '',
                    'maxlength'     => '',
                    'rows'          => '',
                    'placeholder'   => '',
                    'new_lines'     => '',
                ];

            case 'number':
                return [
                    'default_value' => '',
                    'min'           => '',
                    'max'           => '',
                    'step'          => '',
                    'placeholder'   => '',
                    'prepend'       => '',
                    'append'        => '',
                ];

            case 'email':
                return [
                    'default_value' => '',
                    'placeholder'   => '',
                    'prepend'       => '',
                    'append'        => '',
                ];

            case 'url':
                return [
                    'default_value' => '',
                    'placeholder'   => '',
                ];

            case 'password':
                return [
                    'placeholder' => '',
                    'prepend'     => '',
                    'append'      => '',
                ];

            case 'image':
                return [
                    'return_format' => 'array',
                    'library'       => 'all',
                    'min_width'     => '',
                    'min_height'    => '',
                    'min_size'      => '',
                    'max_width'     => '',
                    'max_height'    => '',
                    'max_size'      => '',
                    'mime_types'    => '',
                    'preview_size'  => 'medium',
                ];

            case 'file':
                return [
                    'return_format' => 'array',
                    'library'       => 'all',
                    'min_size'      => '',
                    'max_size'      => '',
                    'mime_types'    => '',
                ];

            case 'wysiwyg':
                return [
                    'default_value' => '',
                    'tabs'          => 'all',
                    'toolbar'       => 'full',
                    'media_upload'  => 1,
                    'delay'         => 0,
                ];

            case 'select':
                return [
                    'choices'       => [],
                    'default_value' => false,
                    'return_format' => 'value',
                    'multiple'      => 0,
                    'allow_null'    => 0,
                    'ui'            => 0,
                    'ajax'          => 0,
                    'placeholder'   => '',
                ];

            case 'checkbox':
                return [
                    'choices'       => [],
                    'default_value' => [],
                    'return_format' => 'value',
                    'allow_custom'  => 0,
                    'layout'        => 'vertical',
                    'toggle'        => 0,
                    'save_custom'   => 0,
                ];

            case 'radio':
                return [
                    'choices'       => [],
                    'default_value' => '',
                    'return_format' => 'value',
                    'allow_null'    => 0,
                    'other_choice'  => 0,
                    'layout'        => 'vertical',
                    'save_other'    => 0,
                ];

            case 'button_group':
                return [
                    'choices'       => [],
                    'default_value' => '',
                    'return_format' => 'value',
                    'allow_null'    => 0,
                    'layout'        => 'horizontal',
                ];

            case 'true_false':
                return [
                    'default_value' => 0,
                    'ui'            => 0,
                    'ui_on_text'    => '',
                    'ui_off_text'   => '',
                    'message'       => '',
                ];

            case 'link':
                return [
                    'return_format' => 'array',
                ];

            case 'post_object':
                return [
                    'post_type'     => [],
                    'return_format' => 'object',
                    'multiple'      => 0,
                    'allow_null'    => 0,
                    'ui'            => 1,
                ];

            case 'relationship':
                return [
                    'post_type'     => [],
                    'filters'       => [ 'search', 'post_type', 'taxonomy' ],
                    'return_format' => 'object',
                    'min'           => '',
                    'max'           => '',
                    'elements'      => '',
                ];

            case 'taxonomy':
                return [
                    'taxonomy'      => 'category',
                    'add_term'      => 1,
                    'save_terms'    => 0,
                    'load_terms'    => 0,
                    'return_format' => 'id',
                    'field_type'    => 'checkbox',
                    'allow_null'    => 0,
                    'multiple'      => 0,
                ];

            case 'color_picker':
                return [
                    'default_value'  => '',
                    'enable_opacity' => 0,
                    'return_format'  => 'string',
                ];

            case 'date_picker':
                return [
                    'display_format' => 'd/m/Y',
                    'return_format'  => 'd/m/Y',
                    'first_day'      => 1,
                ];

            case 'time_picker':
                return [
                    'display_format' => 'g:i a',
                    'return_format'  => 'g:i a',
                ];

            case 'google_map':
                return [
                    'center_lat' => '',
                    'center_lng' => '',
                    'zoom'       => '',
                    'height'     => '',
                ];

            case 'range':
                return [
                    'default_value' => '',
                    'min'           => '',
                    'max'           => '',
                    'step'          => '',
                    'prepend'       => '',
                    'append'        => '',
                ];

            case 'oembed':
                return [
                    'width'  => '',
                    'height' => '',
                ];

            case 'gallery':
                return [
                    'return_format' => 'array',
                    'library'       => 'all',
                    'min'           => '',
                    'max'           => '',
                    'min_width'     => '',
                    'min_height'    => '',
                    'min_size'      => '',
                    'max_width'     => '',
                    'max_height'    => '',
                    'max_size'      => '',
                    'mime_types'    => '',
                    'insert'        => 'append',
                    'preview_size'  => 'medium',
                ];

            case 'user':
                return [
                    'role'          => [],
                    'return_format' => 'array',
                    'multiple'      => 0,
                    'allow_null'    => 0,
                ];

            case 'page_link':
                return [
                    'post_type'  => [],
                    'allow_null' => 0,
                    'multiple'   => 0,
                ];

            case 'group':
                return [
                    'layout'     => 'block',
                    'sub_fields' => [],
                ];

            case 'repeater':
                return [
                    'layout'     => 'table',
                    'min'        => 0,
                    'max'        => 0,
                    'collapsed'  => '',
                    'button_label' => 'Add Row',
                    'sub_fields' => [],
                ];

            case 'message':
                return [
                    'message'   => '',
                    'new_lines' => 'wpautop',
                    'esc_html'  => 0,
                ];

            case 'accordion':
                return [
                    'open'       => 0,
                    'multi_expand' => 0,
                    'endpoint'   => 0,
                ];

            case 'tab':
                return [
                    'placement' => 'top',
                    'endpoint'  => 0,
                ];

            default:
                return [
                    'default_value' => '',
                    'placeholder'   => '',
                ];
        }
    }
}
