<?php

/**
 * Database Manager
 * Handles database table creation and migrations
 *
 * @package Mylighthouse_Booker
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Class Mylighthouse_Booker_Database_Manager
 */
class Mylighthouse_Booker_Database_Manager
{
	/**
	 * Database version
	 */
	const DB_VERSION = '1.0.2';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		add_action('plugins_loaded', array($this, 'check_version'));
	}

	/**
	 * Check database version and run updates if needed
	 */
	public function check_version()
	{
		$current_version = get_option('mlb_db_version', '0');
		
		if (version_compare($current_version, self::DB_VERSION, '<')) {
			$this->create_tables();
			$this->migrate_data();
			update_option('mlb_db_version', self::DB_VERSION);
		}
	}

	/**
	 * Create database tables
	 */
	public function create_tables()
	{
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		// Hotels table
		$hotels_table = $wpdb->prefix . 'mlb_hotels';
		$hotels_sql = "CREATE TABLE $hotels_table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			hotel_id varchar(100) NOT NULL,
			status varchar(20) DEFAULT 'active',
			display_order int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY hotel_id (hotel_id),
			KEY status (status),
			KEY display_order (display_order)
		) $charset_collate;";

		// Rooms table
		$rooms_table = $wpdb->prefix . 'mlb_rooms';
		$rooms_sql = "CREATE TABLE $rooms_table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			hotel_id bigint(20) UNSIGNED NOT NULL,
			name varchar(255) NOT NULL,
			room_id varchar(100) NOT NULL,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY hotel_id (hotel_id),
			KEY room_id (room_id),
			KEY status (status)
		) $charset_collate;";

		dbDelta($hotels_sql);
		dbDelta($rooms_sql);

		// Specials table
		$specials_table = $wpdb->prefix . 'mlb_specials';
		$specials_sql = "CREATE TABLE $specials_table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			hotel_id bigint(20) UNSIGNED NOT NULL,
			name varchar(255) NOT NULL,
			special_id varchar(100) NOT NULL,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY hotel_id (hotel_id),
			KEY special_id (special_id),
			KEY status (status)
		) $charset_collate;";

		dbDelta($specials_sql);
	}

	/**
	 * Migrate data from old options to new tables
	 */
	public function migrate_data()
	{
		global $wpdb;

		// Check if migration already done
		if (get_option('mlb_data_migrated', false)) {
			return;
		}

		$hotels_table = $wpdb->prefix . 'mlb_hotels';
		$rooms_table = $wpdb->prefix . 'mlb_rooms';

		// Get old hotels data
		$old_hotels = get_option('mlb_hotels', array());

		if (empty($old_hotels)) {
			update_option('mlb_data_migrated', true);
			return;
		}

		foreach ($old_hotels as $hotel) {
			if (empty($hotel['id']) || empty($hotel['name'])) {
				continue;
			}

			// Insert hotel
			$wpdb->insert(
				$hotels_table,
				array(
					'name' => sanitize_text_field($hotel['name']),
					'hotel_id' => sanitize_key($hotel['id']),
					'status' => 'active',
				),
				array('%s', '%s', '%s')
			);

			$hotel_db_id = $wpdb->insert_id;

			// Insert rooms if they exist
			if (isset($hotel['rooms']) && is_array($hotel['rooms'])) {
				foreach ($hotel['rooms'] as $room) {
					if (empty($room['id']) || empty($room['name'])) {
						continue;
					}

					$wpdb->insert(
						$rooms_table,
						array(
							'hotel_id' => $hotel_db_id,
							'name' => sanitize_text_field($room['name']),
							'room_id' => sanitize_text_field($room['id']),
							'status' => 'active',
						),
						array('%d', '%s', '%s', '%s')
					);
				}
			}
		}

		// Mark migration as complete
		update_option('mlb_data_migrated', true);
		
		// Keep old data as backup for now
		update_option('mlb_hotels_backup', $old_hotels);
	}

	/**
	 * Drop tables (for uninstall)
	 */
	public static function drop_tables()
	{
		global $wpdb;
		
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}mlb_rooms");
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}mlb_specials");
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}mlb_hotels");
		
		delete_option('mlb_db_version');
		delete_option('mlb_data_migrated');
	}
}
