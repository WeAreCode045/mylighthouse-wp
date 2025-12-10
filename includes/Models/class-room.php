<?php

/**
 * Room Model
 * Handles CRUD operations for rooms
 *
 * @package Mylighthouse_Booker
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Class Mylighthouse_Booker_Room
 */
class Mylighthouse_Booker_Room
{
	/**
	 * Get rooms by hotel ID
	 *
	 * @param int   $hotel_id Hotel ID.
	 * @param array $args     Query arguments.
	 * @return array
	 */
	public static function get_by_hotel($hotel_id, $args = array())
	{
		global $wpdb;
		
		$defaults = array(
			'status' => 'active',
			'orderby' => 'name',
			'order' => 'ASC',
		);
		
		$args = wp_parse_args($args, $defaults);
		
		$table = $wpdb->prefix . 'mlb_rooms';
		
		$where = $wpdb->prepare("WHERE hotel_id = %d", $hotel_id);
		
		if (!empty($args['status'])) {
			$where .= $wpdb->prepare(" AND status = %s", $args['status']);
		}
		
		$orderby = sanitize_sql_orderby("{$args['orderby']} {$args['order']}");
		
		$sql = "SELECT * FROM $table $where ORDER BY $orderby";
		
		return $wpdb->get_results($sql, ARRAY_A);
	}

	/**
	 * Get room by ID
	 *
	 * @param int $id Room ID.
	 * @return array|null
	 */
	public static function get_by_id($id)
	{
		global $wpdb;
		
		$table = $wpdb->prefix . 'mlb_rooms';
		
		return $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id),
			ARRAY_A
		);
	}

	/**
	 * Get room by room_id
	 *
	 * @param string $room_id Room ID from external system.
	 * @return array|null
	 */
	public static function get_by_room_id($room_id)
	{
		global $wpdb;
		
		$table = $wpdb->prefix . 'mlb_rooms';
		
		return $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM $table WHERE room_id = %s", $room_id),
			ARRAY_A
		);
	}

	/**
	 * Create room
	 *
	 * @param array $data Room data.
	 * @return int|false Room ID or false on failure.
	 */
	public static function create($data)
	{
		global $wpdb;
		
		$table = $wpdb->prefix . 'mlb_rooms';
		
		$result = $wpdb->insert(
			$table,
			array(
				'hotel_id' => absint($data['hotel_id']),
				'name' => sanitize_text_field($data['name']),
				'room_id' => sanitize_text_field($data['room_id']),
				'status' => isset($data['status']) ? $data['status'] : 'active',
			),
			array('%d', '%s', '%s', '%s')
		);
		
		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update room
	 *
	 * @param int   $id   Room ID.
	 * @param array $data Room data.
	 * @return bool
	 */
	public static function update($id, $data)
	{
		global $wpdb;
		
		$table = $wpdb->prefix . 'mlb_rooms';
		
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
		
		if (isset($data['room_id'])) {
			$update_data['room_id'] = sanitize_text_field($data['room_id']);
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

	/**
	 * Delete room
	 *
	 * @param int $id Room ID.
	 * @return bool
	 */
	public static function delete($id)
	{
		global $wpdb;
		
		$table = $wpdb->prefix . 'mlb_rooms';
		
		$result = $wpdb->delete(
			$table,
			array('id' => $id),
			array('%d')
		);
		
		return $result !== false;
	}

	/**
	 * Delete all rooms for a hotel
	 *
	 * @param int $hotel_id Hotel ID.
	 * @return bool
	 */
	public static function delete_by_hotel($hotel_id)
	{
		global $wpdb;
		
		$table = $wpdb->prefix . 'mlb_rooms';
		
		$result = $wpdb->delete(
			$table,
			array('hotel_id' => $hotel_id),
			array('%d')
		);
		
		return $result !== false;
	}

	/**
	 * Get all rooms
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public static function get_all($args = array())
	{
		global $wpdb;
		
		$defaults = array(
			'status' => 'active',
			'orderby' => 'name',
			'order' => 'ASC',
		);
		
		$args = wp_parse_args($args, $defaults);
		
		$table = $wpdb->prefix . 'mlb_rooms';
		
		$where = '';
		if (!empty($args['status'])) {
			$where = $wpdb->prepare("WHERE status = %s", $args['status']);
		}
		
		$orderby = sanitize_sql_orderby("{$args['orderby']} {$args['order']}");
		
		$sql = "SELECT * FROM $table $where ORDER BY $orderby";
		
		return $wpdb->get_results($sql, ARRAY_A);
	}
}
