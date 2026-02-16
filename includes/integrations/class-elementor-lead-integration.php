<?php
/**
 * PuzzlingCRM Elementor Pro Form Integration
 *
 * Integrates Elementor Pro forms with PuzzlingCRM leads. When "Send to CRM" is enabled
 * on an Elementor form, submissions create leads with full form data.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PuzzlingCRM_Elementor_Lead_Integration {

    /**
     * Field mapping: label/name patterns => CRM meta key.
     */
    private static $field_map = [
        '_first_name'    => [ 'نام', 'first_name', 'firstname', 'name' ],
        '_last_name'     => [ 'نام خانوادگی', 'last_name', 'lastname', 'نام_خانوادگی', 'family' ],
        '_mobile'        => [ 'موبایل', 'mobile', 'tel', 'شماره', 'تلفن', 'phone', 'cell' ],
        '_email'         => [ 'ایمیل', 'email' ],
        '_business_name' => [ 'نام کسب‌وکار', 'business', 'شرکت', 'company', 'business_name' ],
        '_gender'        => [ 'جنسیت', 'gender' ],
    ];

    private static $crm_section_injected = false;

    public function __construct() {
        add_action( 'elementor_pro/forms/new_record', [ $this, 'handle_form_submission' ], 10, 2 );
        add_action( 'elementor/element/form/section_form/after_section_end', [ $this, 'inject_crm_control' ], 10, 2 );
        add_action( 'elementor/element/form/form_fields/after_section_end', [ $this, 'inject_crm_control' ], 10, 2 );
    }

    /**
     * Injects "Send to CRM" switcher into Elementor form widget.
     */
    public function inject_crm_control( $element, $args ) {
        if ( self::$crm_section_injected ) {
            return;
        }
        self::$crm_section_injected = true;
        $element->start_controls_section(
            'section_pzl_crm',
            [
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
                'label' => __( 'PuzzlingCRM', 'puzzlingcrm' ),
            ]
        );
        $element->add_control(
            'pzl_send_to_crm',
            [
                'type'        => \Elementor\Controls_Manager::SWITCHER,
                'label'       => __( 'ارسال به CRM', 'puzzlingcrm' ),
                'description' => __( 'ارسال اطلاعات فرم به بخش سرنخ‌های PuzzlingCRM', 'puzzlingcrm' ),
                'default'     => '',
            ]
        );
        $element->end_controls_section();
    }

    /**
     * Handles Elementor form submission and creates lead when enabled.
     */
    public function handle_form_submission( $record, $handler ) {
        $settings = $record->get_form_settings();
        if ( empty( $settings['pzl_send_to_crm'] ) || $settings['pzl_send_to_crm'] !== 'yes' ) {
            return;
        }

        $raw_fields = $record->get( 'fields' );
        if ( ! is_array( $raw_fields ) || empty( $raw_fields ) ) {
            return;
        }

        $form_data   = [];
        $mapped      = [
            '_first_name'    => '',
            '_last_name'     => '',
            '_mobile'        => '',
            '_email'         => '',
            '_business_name' => '',
            '_gender'        => '',
        ];
        $notes_parts = [];

        foreach ( $raw_fields as $id => $field ) {
            $value = isset( $field['value'] ) ? $field['value'] : '';
            $type  = isset( $field['type'] ) ? $field['type'] : 'text';
            $title = isset( $field['title'] ) ? $field['title'] : ( isset( $field['label'] ) ? $field['label'] : $id );

            if ( is_array( $value ) ) {
                $value = implode( ', ', array_map( 'wp_strip_all_tags', $value ) );
            } else {
                $value = wp_strip_all_tags( (string) $value );
            }

            $form_data[] = [
                'label' => sanitize_text_field( $title ),
                'value' => sanitize_text_field( $value ),
            ];

            $normalized_title = $this->normalize_label( $title );
            $normalized_id    = $this->normalize_label( $id );

            foreach ( self::$field_map as $meta_key => $patterns ) {
                if ( $mapped[ $meta_key ] !== '' ) {
                    continue;
                }
                foreach ( $patterns as $pattern ) {
                    if ( $normalized_title === $this->normalize_label( $pattern ) ||
                         $normalized_id === $this->normalize_label( $pattern ) ) {
                        if ( $meta_key === '_email' ) {
                            $mapped[ $meta_key ] = sanitize_email( $value );
                        } else {
                            $mapped[ $meta_key ] = sanitize_text_field( $value );
                        }
                        break 2;
                    }
                }
            }

            if ( in_array( $normalized_title, [ 'پیام', 'message', 'توضیحات', 'notes', 'یادداشت' ], true ) ||
                 in_array( $normalized_id, [ 'message', 'notes' ], true ) ) {
                $notes_parts[] = sanitize_textarea_field( $value );
            }
        }

        $first_name  = $mapped['_first_name'];
        $last_name   = $mapped['_last_name'];
        $mobile      = $mapped['_mobile'];
        $email       = $mapped['_email'];
        $business    = $mapped['_business_name'];
        $gender      = $mapped['_gender'];
        $notes       = implode( "\n\n", array_filter( $notes_parts ) );

        if ( empty( $first_name ) && empty( $last_name ) ) {
            $first_name = __( 'نامشخص', 'puzzlingcrm' );
            $last_name  = '';
        }
        if ( empty( $mobile ) && empty( $email ) ) {
            $mobile = __( 'ثبت نشده', 'puzzlingcrm' );
        } elseif ( empty( $mobile ) ) {
            $mobile = '-';
        }

        $form_name = isset( $settings['form_name'] ) ? sanitize_text_field( $settings['form_name'] ) : __( 'فرم Elementor', 'puzzlingcrm' );
        $lead_id   = wp_insert_post( [
            'post_type'    => 'pzl_lead',
            'post_title'   => trim( $first_name . ' ' . $last_name ) ?: $form_name,
            'post_content' => $notes,
            'post_status'  => 'publish',
        ] );

        if ( is_wp_error( $lead_id ) ) {
            return;
        }

        update_post_meta( $lead_id, '_first_name', $first_name );
        update_post_meta( $lead_id, '_last_name', $last_name );
        update_post_meta( $lead_id, '_mobile', $mobile );
        update_post_meta( $lead_id, '_email', $email );
        update_post_meta( $lead_id, '_business_name', $business );
        update_post_meta( $lead_id, '_gender', $gender );
        update_post_meta( $lead_id, '_elementor_form_data', wp_json_encode( $form_data ) );
        update_post_meta( $lead_id, '_elementor_form_name', $form_name );

        $source_slug = sanitize_title( $form_name );
        if ( ! empty( $source_slug ) ) {
            if ( ! term_exists( $source_slug, 'lead_source' ) ) {
                wp_insert_term( $form_name, 'lead_source', [ 'slug' => $source_slug ] );
            }
            wp_set_object_terms( $lead_id, $source_slug, 'lead_source' );
        }

        if ( class_exists( 'PuzzlingCRM_Settings_Handler' ) ) {
            $settings_handler = PuzzlingCRM_Settings_Handler::get_all_settings();
            $default_status   = ! empty( $settings_handler['lead_default_status'] ) ? $settings_handler['lead_default_status'] : null;
            if ( $default_status ) {
                wp_set_object_terms( $lead_id, $default_status, 'lead_status' );
            } else {
                $first_status = get_terms( [
                    'taxonomy' => 'lead_status',
                    'hide_empty' => false,
                    'number'   => 1,
                    'orderby'  => 'term_id',
                    'order'    => 'ASC',
                ] );
                if ( ! empty( $first_status ) && ! is_wp_error( $first_status ) ) {
                    wp_set_object_terms( $lead_id, $first_status[0]->slug, 'lead_status' );
                }
            }
        }

        if ( class_exists( 'PuzzlingCRM_Logger' ) ) {
            PuzzlingCRM_Logger::add( 'سرنخ از فرم Elementor', [
                'content' => sprintf(
                    __( 'سرنخ جدید از فرم «%1$s»: %2$s %3$s', 'puzzlingcrm' ),
                    $form_name,
                    $first_name,
                    $last_name
                ),
                'type'    => 'log',
                'details' => [
                    'lead_id'    => $lead_id,
                    'form_name'  => $form_name,
                    'first_name' => $first_name,
                    'last_name'  => $last_name,
                    'mobile'     => $mobile,
                ],
            ] );
        }
    }

    /**
     * Normalizes a label for comparison (lowercase, no spaces/underscores).
     */
    private function normalize_label( $label ) {
        $label = str_replace( [ ' ', '_', '-', '\'', '"' ], '', (string) $label );
        return strtolower( $label );
    }
}
