<?php

/**
 * Hotel Model
 * Handles CRUD operations for hotels
 *
 * @package Mylighthouse_Booker
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Class Mylighthouse_Booker_Hotel
 */
class Mylighthouse_Booker_Hotel
{
	/**
	 * Get all hotels
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public static function get_all($args = array())
	{
		global $wpdb;
		
		$defaults = array(
			'status' => 'active',
			'orderby' => 'display_order',
			'order' => 'ASC',
		);
		
		$args = wp_parse_args($args, $defaults);
		
		$table = $wpdb->prefix . 'mlb_hotels';
		
		$where = '';
		if (!empty($args['status'])) {
			$where = $wpdb->prepare("WHERE status = %s", $args['status']);
		}
		
		$orderby = sanitize_sql_orderby("{$args['orderby']} {$args['order']}");
		if (empty($orderby)) {
			// fallback to display_order then name
			$orderby = 'display_order ASC, name ASC';
		}
		
		$sql = "SELECT * FROM $table $where ORDER BY $orderby";
		
		return $wpdb->get_results($sql, ARRAY_A);
	}

	/**
	 * Get hotel by ID
	 *
	 * @param int $id Hotel ID.
	 * @return array|null
	 */
	public static function get_by_id($id)
	{
		global $wpdb;
		
		$table = $wpdb->prefix . 'mlb_hotels';
		
		return $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id),
			ARRAY_A
		);
	}

	/**
	 * Get hotel by hotel_id
	 *
	 * @param string $hotel_id Hotel ID from external system.
	 * @return array|null
	 */
	public static function get_by_hotel_id($hotel_id)
	{
		global $wpdb;
		
		$table = $wpdb->prefix . 'mlb_hotels';
		
		return $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM $table WHERE hotel_id = %s", $hotel_id),
			ARRAY_A
		);
	}

	/**
	 * Create hotel
	 *
	 * @param array $data Hotel data.
	 * @return int|false Hotel ID or false on failure.
	 */
	public static function create($data)
	{
		global $wpdb;
		
		$table = $wpdb->prefix . 'mlb_hotels';
		
		$result = $wpdb->insert(
			$table,
			array(
				'name' => sanitize_text_field($data['name']),
				'hotel_id' => sanitize_key($data['hotel_id']),
				'status' => isset($data['status']) ? $data['status'] : 'active',
				'display_order' => isset($data['display_order']) ? intval($data['display_order']) : 0,
			),
			array('%s', '%s', '%s', '%d')
		);
		
		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update hotel
	 *
	 * @param int   $id   Hotel ID.
	 * @param array $data Hotel data.
	 * @return bool
	 */
	public static function update($id, $data)
	{
		global $wpdb;
		
		$table = $wpdb->prefix . 'mlb_hotels';
		
		$update_data = array();
		
		if (isset($data['name'])) {
			$update_data['name'] = sanitize_text_field($data['name']);
		}
		
		if (isset($data['hotel_id'])) {
			$update_data['hotel_id'] = sanitize_key($data['hotel_id']);
		}
		
		if (isset($data['status'])) {
			$update_data['status'] = $data['status'];
		}

		if (isset($data['display_order'])) {
			$update_data['display_order'] = intval($data['display_order']);
		}
		
		if (empty($update_data)) {
			return false;
		}
		
		$result = $wpdb->update(
			$table,
			$update_data,
			array('id' => $id),
			array_fill(0, count($update_data), '%s'),
			array('%d')
		);
		
		return $result !== false;
	}

	/**
	 * Delete hotel
	 *
	 * @param int $id Hotel ID.
	 * @return bool
	 */
	public static function delete($id)
	{
		global $wpdb;
		
		$table = $wpdb->prefix . 'mlb_hotels';
		
		// Delete associated rooms first
		Mylighthouse_Booker_Room::delete_by_hotel($id);
		
		$result = $wpdb->delete(
			$table,
			array('id' => $id),
			array('%d')
		);
		
		return $result !== false;
	}

	/**
	 * Get hotel with rooms
	 *
	 * @param int $id Hotel ID.
	 * @return array|null
	 */
	public static function get_with_rooms($id)
	{
		$hotel = self::get_by_id($id);
		
		if (!$hotel) {
			return null;
		}
		
		$hotel['rooms'] = Mylighthouse_Booker_Room::get_by_hotel($id);
		// Include specials if model exists
		if (class_exists('Mylighthouse_Booker_Special')) {
			$hotel['specials'] = Mylighthouse_Booker_Special::get_by_hotel($id);
		} else {
			$hotel['specials'] = array();
		}
		
		return $hotel;
	}

	/**
	 * Get all hotels with rooms
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public static function get_all_with_rooms($args = array())
	{
		$hotels = self::get_all($args);

		// If DB-backed hotels are empty, fall back to legacy options storage
		if (empty($hotels) || !is_array($hotels)) {
			$legacy = get_option('mlb_hotels', array());
			if (empty($legacy)) {
				$legacy = get_option('mlb_hotels_backup', array());
			}
			if (!empty($legacy) && is_array($legacy)) {
				// Normalize legacy entries to the same shape expected by callers
				$mapped = array();
				foreach ($legacy as $item) {
					// legacy items may be associative arrays with keys 'id' and 'name'
					$h = array();
					if (is_array($item)) {
						$h['id'] = isset($item['id']) ? $item['id'] : (isset($item['hotel_id']) ? $item['hotel_id'] : 0);
						$h['name'] = isset($item['name']) ? $item['name'] : (isset($item['title']) ? $item['title'] : '');
						$h['hotel_id'] = isset($item['id']) ? $item['id'] : (isset($item['hotel_id']) ? $item['hotel_id'] : '');
					} elseif (is_object($item)) {
						$h['id'] = isset($item->id) ? $item->id : 0;
						$h['name'] = isset($item->name) ? $item->name : '';
						$h['hotel_id'] = isset($item->id) ? $item->id : '';
					} else {
						continue;
					}
					$h['rooms'] = array();
					$h['specials'] = array();
					$mapped[] = $h;
				}
				return $mapped;
			}
		}

		foreach ($hotels as & $hotel) {
			$hotel['rooms'] = Mylighthouse_Booker_Room::get_by_hotel($hotel['id']);
			if (class_exists('Mylighthouse_Booker_Special')) {
				$hotel['specials'] = Mylighthouse_Booker_Special::get_by_hotel($hotel['id']);
			} else {
				$hotel['specials'] = array();
			}
		}

		return $hotels;
	}
}
