<?php

/**
 * Special Model
 * Handles CRUD operations for specials
 *
 * @package Mylighthouse_Booker
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Mylighthouse_Booker_Special
{
    public static function get_by_hotel($hotel_id, $args = array())
    {
        global $wpdb;

        $defaults = array(
            'status' => 'active',
            'orderby' => 'name',
            'order' => 'ASC',
        );

        $args = wp_parse_args($args, $defaults);

        $table = $wpdb->prefix . 'mlb_specials';

        $where = $wpdb->prepare("WHERE hotel_id = %d", $hotel_id);

        if (!empty($args['status'])) {
            $where .= $wpdb->prepare(" AND status = %s", $args['status']);
        }

        $orderby = sanitize_sql_orderby("{$args['orderby']} {$args['order']}");

        $sql = "SELECT * FROM $table $where ORDER BY $orderby";

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public static function create($data)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'mlb_specials';

        $result = $wpdb->insert(
            $table,
            array(
                'hotel_id' => absint($data['hotel_id']),
                'name' => sanitize_text_field($data['name']),
                'special_id' => sanitize_text_field($data['special_id']),
                'status' => isset($data['status']) ? $data['status'] : 'active',
            ),
            array('%d', '%s', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    public static function update($id, $data)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'mlb_specials';

        $update_data = array();
        $formats = array();

        if (isset($data['hotel_id'])) {
            $update_data['hotel_id'] = absint($data['hotel_id']);
            $formats[] = '%d';
        }

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
            $formats[] = '%s';
        }

        if (isset($data['special_id'])) {
            $update_data['special_id'] = sanitize_text_field($data['special_id']);
            $formats[] = '%s';
        }

        if (isset($data['status'])) {
            $update_data['status'] = $data['status'];
            $formats[] = '%s';
        }

        if (empty($update_data)) {
            return false;
        }

        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $id),
            $formats,
            array('%d')
        );

        return $result !== false;
    }

    public static function get_by_special_id($special_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'mlb_specials';

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE special_id = %s", $special_id),
            ARRAY_A
        );
    }

    public static function delete($id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'mlb_specials';

        $result = $wpdb->delete(
            $table,
            array('id' => $id),
            array('%d')
        );

        return $result !== false;
    }

    public static function delete_by_hotel($hotel_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'mlb_specials';

        $result = $wpdb->delete(
            $table,
            array('hotel_id' => $hotel_id),
            array('%d')
        );

        return $result !== false;
    }
}
