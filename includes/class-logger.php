<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Logger {

    /**
     * Adds a new log entry.
     *
     * @param string $title The main message/title of the log.
     * @param array $args Optional arguments.
     * - 'content' (string) Detailed description.
     * - 'type' (string) 'log' for general activity, 'notification' for user-facing alerts.
     * - 'user_id' (int) The user ID this log is associated with (e.g., the customer).
     * - 'object_id' (int) The ID of the related post (e.g., contract ID, task ID).
     */
    public static function add( $title, $args = [] ) {
        $defaults = [
            'content'   => '',
            'type'      => 'log', // 'log' or 'notification'
            'user_id'   => get_current_user_id(),
            'object_id' => 0,
        ];
        $args = wp_parse_args( $args, $defaults );

        $log_id = wp_insert_post([
            'post_title'    => sanitize_text_field( $title ),
            'post_content'  => wp_kses_post( $args['content'] ),
            'post_type'     => 'puzzling_log',
            'post_status'   => 'publish',
            'post_author'   => $args['user_id'], // Associate log with the relevant user
        ]);

        if ( ! is_wp_error( $log_id ) ) {
            update_post_meta( $log_id, '_log_type', sanitize_key( $args['type'] ) );
            update_post_meta( $log_id, '_related_object_id', intval( $args['object_id'] ) );
            if ( $args['type'] === 'notification' ) {
                update_post_meta( $log_id, '_is_read', '0' ); // 0 for unread, 1 for read
            }
        }
    }
}