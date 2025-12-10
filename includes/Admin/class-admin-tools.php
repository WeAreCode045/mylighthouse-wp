<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Mylighthouse_Booker_Admin_Tools {
    /**
     * Render a lightweight tools fragment for the dashboard loader.
     * The wrapper class `mlb-tools-wrap` is used by the fragment extractor.
     */
    public function render_page() {
        $export_nonce = wp_create_nonce('mlb_tools_export');
        $import_nonce = wp_create_nonce('mlb_tools_import');
        $preview_nonce = wp_create_nonce('mlb_tools_preview_import');

        ?>
        <div class="mlb-tools-wrap">
            <div class="mlb-section-card">
                <h2 class="mlb-section-title"><?php esc_html_e('Tools', 'mylighthouse-booker'); ?></h2>
                <p><?php esc_html_e('Utility actions for this plugin.', 'mylighthouse-booker'); ?></p>
                <div class="mlb-tools-actions">
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php')); ?>" data-mlb-tools-ajax="1" data-result-target="#mlb-tools-ajax-result">
                        <?php wp_nonce_field('mlb_tools_action', 'mlb_tools_nonce'); ?>
                        <input type="hidden" name="action" value="mlb_tools_check_tables" />
                        <button class="mlb-btn mlb-btn-primary" type="submit"><?php esc_html_e('Check/Create Tables', 'mylighthouse-booker'); ?></button>
                    </form>
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php')); ?>" data-mlb-tools-ajax="1" data-result-target="#mlb-tools-ajax-result">
                        <?php wp_nonce_field('mlb_tools_action', 'mlb_tools_nonce'); ?>
                        <input type="hidden" name="action" value="mlb_tools_migrate_hotels" />
                        <button class="mlb-btn mlb-btn-secondary" type="submit"><?php esc_html_e('Migrate Legacy Hotels to Database', 'mylighthouse-booker'); ?></button>
                    </form>
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="mlb-tools-schema-form" data-mlb-tools-ajax="1" data-result-target="#mlb-tools-ajax-result">
                        <?php wp_nonce_field('mlb_tools_action', 'mlb_tools_nonce'); ?>
                        <input type="hidden" name="action" value="mlb_tools_update_schema" />
                        <button class="mlb-btn" type="submit"><?php esc_html_e('Update Database Tables (dbDelta)', 'mylighthouse-booker'); ?></button>
                    </form>
                </div>
                <div id="mlb-tools-ajax-result" class="mlb-tools-result" aria-live="polite"></div>
            </div>

            <div class="mlb-section-card">
                <h3 class="mlb-section-title"><?php esc_html_e('Import & Export', 'mylighthouse-booker'); ?></h3>
                <p><?php esc_html_e('Move hotels (with rooms and specials) between environments using JSON exports.', 'mylighthouse-booker'); ?></p>

                <div class="mlb-tools-split">
                    <div class="mlb-tools-panel">
                        <h4><?php esc_html_e('Export Data', 'mylighthouse-booker'); ?></h4>
                        <p><?php esc_html_e('Download a JSON snapshot of all hotels, rooms, and specials.', 'mylighthouse-booker'); ?></p>
                        <form id="mlb-export-form" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" target="mlb-export-frame" class="mlb-export-form">
                            <input type="hidden" name="action" value="mlb_tools_export" />
                            <input type="hidden" name="nonce" value="<?php echo esc_attr( $export_nonce ); ?>" />
                            <input type="hidden" name="download" value="1" />
                            <label for="mlb-export-type" class="screen-reader-text"><?php esc_html_e('Export scope', 'mylighthouse-booker'); ?></label>
                            <select id="mlb-export-type" name="export_type" class="mlb-select">
                                <option value="full"><?php esc_html_e('All hotels (includes rooms and specials)', 'mylighthouse-booker'); ?></option>
                            </select>
                            <label class="mlb-checkbox">
                                <input type="checkbox" name="include_settings" value="1" checked />
                                <span><?php esc_html_e( 'Include General Settings', 'mylighthouse-booker' ); ?></span>
                            </label>
                            <p class="description"><?php esc_html_e( 'Adds booking page URL, display mode, and spinner image to the export.', 'mylighthouse-booker' ); ?></p>
                            <button type="submit" id="mlb-export-btn" class="mlb-btn mlb-btn-secondary">
                                <?php esc_html_e('Download Export', 'mylighthouse-booker'); ?>
                            </button>
                        </form>
                        <iframe id="mlb-export-frame" name="mlb-export-frame" class="mlb-hidden-frame" title="<?php esc_attr_e( 'Export download frame', 'mylighthouse-booker' ); ?>" aria-hidden="true" tabindex="-1" style="display:none;width:0;height:0;border:0;"></iframe>
                    </div>

                    <div class="mlb-tools-panel">
                        <h4><?php esc_html_e('Import Data', 'mylighthouse-booker'); ?></h4>
                        <p><?php esc_html_e('Upload a JSON export to preview changes and import hotels, rooms, and specials.', 'mylighthouse-booker'); ?></p>
                        <label for="mlb-import-file"><?php esc_html_e('JSON file', 'mylighthouse-booker'); ?></label>
                        <input type="file" id="mlb-import-file" accept="application/json,.json" />
                        <label class="mlb-checkbox">
                            <input type="checkbox" id="mlb-import-apply-settings" checked />
                            <span><?php esc_html_e( 'Apply General Settings from file', 'mylighthouse-booker' ); ?></span>
                        </label>
                        <p class="description"><?php esc_html_e( 'Updates booking-related options when the export contains them.', 'mylighthouse-booker' ); ?></p>
                        <label class="mlb-checkbox">
                            <input type="checkbox" id="mlb-import-skip-existing" checked />
                            <span><?php esc_html_e( 'Skip items that already exist', 'mylighthouse-booker' ); ?></span>
                        </label>
                        <p class="description"><?php esc_html_e( 'When enabled, matching hotel, room, or special IDs already in the database will be ignored.', 'mylighthouse-booker' ); ?></p>
                        <div class="mlb-import-actions">
                            <button type="button" id="mlb-preview-btn" class="mlb-btn mlb-btn-secondary" data-preview-nonce="<?php echo esc_attr($preview_nonce); ?>"><?php esc_html_e('Preview Import', 'mylighthouse-booker'); ?></button>
                            <button type="button" id="mlb-import-btn" class="mlb-btn mlb-btn-secondary" data-import-nonce="<?php echo esc_attr($import_nonce); ?>"><?php esc_html_e('Import JSON', 'mylighthouse-booker'); ?></button>
                        </div>
                        <div id="mlb-import-result" class="mlb-tools-result" aria-live="polite"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

if ( ! class_exists( 'Mylighthouse_Booker_Admin_Tools_Page' ) ) {
    class Mylighthouse_Booker_Admin_Tools_Page extends Mylighthouse_Booker_Admin_Tools {

        /**
         * AJAX callback to check/create required tables.
         */
        public function ajax_check_tables() {
            $this->validate_tools_request('mlb_tools_action');

            if ( ! class_exists( 'Mylighthouse_Booker_Database_Manager' ) ) {
                require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Database/class-database-manager.php';
            }

            global $wpdb;

            $tables = array(
                'hotels' => array(
                    'name'    => $wpdb->prefix . 'mlb_hotels',
                    'label'   => __( 'Hotels Table', 'mylighthouse-booker' ),
                    'columns' => array( 'id', 'name', 'hotel_id', 'status', 'display_order', 'created_at', 'updated_at' ),
                ),
                'rooms' => array(
                    'name'    => $wpdb->prefix . 'mlb_rooms',
                    'label'   => __( 'Rooms Table', 'mylighthouse-booker' ),
                    'columns' => array( 'id', 'hotel_id', 'name', 'room_id', 'status', 'created_at', 'updated_at' ),
                ),
                'specials' => array(
                    'name'    => $wpdb->prefix . 'mlb_specials',
                    'label'   => __( 'Specials Table', 'mylighthouse-booker' ),
                    'columns' => array( 'id', 'hotel_id', 'name', 'special_id', 'status', 'created_at', 'updated_at' ),
                ),
            );

            $before = array();
            foreach ( $tables as $key => $table ) {
                $before[ $key ] = $this->table_exists( $table['name'] );
            }

            $db_manager = new Mylighthouse_Booker_Database_Manager();
            $db_manager->create_tables();

            $statuses = array();
            foreach ( $tables as $key => $table ) {
                $exists = $this->table_exists( $table['name'] );
                if ( ! $exists ) {
                    $statuses[ $table['label'] ] = 'failed';
                    continue;
                }

                $missing_columns = $this->get_missing_columns( $table['name'], $table['columns'] );

                $statuses[ $table['label'] ] = array(
                    'status'  => empty( $before[ $key ] ) ? 'created' : 'exists',
                    'missing' => $missing_columns,
                );
            }

            wp_send_json_success( array(
                'message' => __( 'Table check completed.', 'mylighthouse-booker' ),
                'tables'  => $statuses,
            ) );
        }

        /**
         * Generate a JSON export for hotels, rooms, and specials.
         */
        public function ajax_export_hotels() {
            $this->validate_tools_request( array( 'mlb_tools_export', 'mlb_tools_action' ) );
            $this->ensure_models_loaded();

            $export_type = isset( $_POST['export_type'] ) ? sanitize_key( wp_unslash( $_POST['export_type'] ) ) : 'full';
            if ( empty( $export_type ) ) {
                $export_type = 'full';
            }

            $include_settings = $this->request_flag( 'include_settings', true );
            $settings_payload = $include_settings ? $this->get_general_settings() : array();

            $hotels = Mylighthouse_Booker_Hotel::get_all_with_rooms( array( 'status' => '' ) );
            $payload_hotels = array();
            $room_count = 0;
            $special_count = 0;

            if ( ! empty( $hotels ) && is_array( $hotels ) ) {
                foreach ( $hotels as $hotel ) {
                    $rooms = $this->format_rooms_for_export( isset( $hotel['rooms'] ) ? $hotel['rooms'] : array() );
                    $specials = $this->format_specials_for_export( isset( $hotel['specials'] ) ? $hotel['specials'] : array() );
                    $room_count    += count( $rooms );
                    $special_count += count( $specials );

                    $payload_hotels[] = array(
                        'id'            => isset( $hotel['id'] ) ? intval( $hotel['id'] ) : 0,
                        'hotel_id'      => isset( $hotel['hotel_id'] ) ? sanitize_text_field( $hotel['hotel_id'] ) : '',
                        'name'          => isset( $hotel['name'] ) ? $hotel['name'] : '',
                        'status'        => isset( $hotel['status'] ) ? $hotel['status'] : '',
                        'display_order' => isset( $hotel['display_order'] ) ? intval( $hotel['display_order'] ) : 0,
                        'rooms'         => $rooms,
                        'specials'      => $specials,
                    );
                }
            }

            $payload = array(
                'type'         => $export_type,
                'generated_at' => gmdate( 'c' ),
                'site'         => array(
                    'name' => get_bloginfo( 'name' ),
                    'url'  => home_url(),
                ),
                'version'      => defined( 'MYLIGHTHOUSE_BOOKER_VERSION' ) ? MYLIGHTHOUSE_BOOKER_VERSION : '1.0.0',
                'counts'       => array(
                    'hotels'   => count( $payload_hotels ),
                    'rooms'    => $room_count,
                    'specials' => $special_count,
                ),
                'hotels'       => $payload_hotels,
                'settings'     => $settings_payload,
            );

            $should_download = isset( $_REQUEST['download'] ) && $_REQUEST['download'] !== '';
            if ( $should_download ) {
                $filename = sprintf(
                    'mlb-export-%1$s-%2$s.json',
                    sanitize_key( $export_type ),
                    gmdate( 'Ymd-His' )
                );
                $json = wp_json_encode( $payload, JSON_PRETTY_PRINT );
                if ( false === $json ) {
                    $json = wp_json_encode( $payload );
                }

                nocache_headers();
                header( 'Content-Type: application/json; charset=utf-8' );
                header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
                header( 'Content-Length: ' . strlen( $json ) );
                echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                wp_die();
            }

            wp_send_json_success( array(
                'message' => __( 'Export generated successfully.', 'mylighthouse-booker' ),
                'payload' => $payload,
            ) );
        }

        /**
         * Preview an import payload without changing the database.
         */
        public function ajax_preview_import() {
            $this->validate_tools_request( array( 'mlb_tools_preview_import', 'mlb_tools_action' ) );

            $payload = $this->decode_import_payload();
            if ( null === $payload ) {
                wp_send_json_error( array( 'message' => __( 'Invalid JSON payload.', 'mylighthouse-booker' ) ) );
            }

            $hotels_data = $this->extract_hotels_from_payload( $payload );
            if ( empty( $hotels_data ) ) {
                wp_send_json_error( array( 'message' => __( 'No hotels found in the provided payload.', 'mylighthouse-booker' ) ) );
            }

            $this->ensure_models_loaded();

            $apply_settings     = $this->request_flag( 'apply_settings', true );
            $skip_existing      = $this->request_flag( 'skip_existing', true );
            $settings_payload   = $this->extract_settings_from_payload( $payload );
            $sanitized_settings = $this->sanitize_settings_payload( $settings_payload );

            $preview       = array();
            $create_count  = 0;
            $update_count  = 0;
            $skipped_count = 0;

            foreach ( $hotels_data as $hotel_entry ) {
                if ( ! is_array( $hotel_entry ) ) {
                    $skipped_count++;
                    continue;
                }

                $external_id = isset( $hotel_entry['hotel_id'] ) ? sanitize_text_field( $hotel_entry['hotel_id'] ) : '';
                $name        = isset( $hotel_entry['name'] ) ? sanitize_text_field( $hotel_entry['name'] ) : '';

                if ( empty( $external_id ) || empty( $name ) ) {
                    $preview[] = array(
                        'hotel_id' => $external_id,
                        'action'   => 'skip',
                        'rooms'    => array(),
                        'specials' => array(),
                        'reason'   => __( 'Missing hotel ID or name.', 'mylighthouse-booker' ),
                    );
                    $skipped_count++;
                    continue;
                }

                $existing     = Mylighthouse_Booker_Hotel::get_by_hotel_id( $external_id );
                $hotel_exists = ( $existing && isset( $existing['id'] ) );
                $hotel_action = $hotel_exists ? 'update' : 'create';
                if ( 'create' === $hotel_action ) {
                    $create_count++;
                } else {
                    $update_count++;
                }

                $room_preview = array();
                if ( ! empty( $hotel_entry['rooms'] ) && is_array( $hotel_entry['rooms'] ) ) {
                    foreach ( $hotel_entry['rooms'] as $room_entry ) {
                        if ( ! is_array( $room_entry ) ) {
                            continue;
                        }
                        $room_id = isset( $room_entry['room_id'] ) ? sanitize_text_field( $room_entry['room_id'] ) : '';
                        if ( empty( $room_id ) ) {
                            continue;
                        }
                        $existing_room = Mylighthouse_Booker_Room::get_by_room_id( $room_id );
                        $room_preview[] = array(
                            'room_id' => $room_id,
                            'action'  => ( $existing_room && isset( $existing_room['id'] ) ) ? 'update' : 'create',
                            'exists'  => (bool) $existing_room,
                            'will_skip' => $skip_existing && (bool) $existing_room,
                        );
                    }
                }

                $special_preview = array();
                if ( ! empty( $hotel_entry['specials'] ) && is_array( $hotel_entry['specials'] ) ) {
                    foreach ( $hotel_entry['specials'] as $special_entry ) {
                        if ( ! is_array( $special_entry ) ) {
                            continue;
                        }
                        $special_id = isset( $special_entry['special_id'] ) ? sanitize_text_field( $special_entry['special_id'] ) : '';
                        if ( empty( $special_id ) ) {
                            continue;
                        }
                        $existing_special = $this->get_special_by_external_id( $special_id );
                        $special_preview[] = array(
                            'special_id' => $special_id,
                            'action'     => ( $existing_special && isset( $existing_special['id'] ) ) ? 'update' : 'create',
                            'exists'     => (bool) $existing_special,
                            'will_skip'  => $skip_existing && (bool) $existing_special,
                        );
                    }
                }

                $preview[] = array(
                    'hotel_id' => $external_id,
                    'name'     => $name,
                    'action'   => $hotel_action,
                    'exists'   => $hotel_exists,
                    'will_skip'=> $skip_existing && $hotel_exists,
                    'rooms'    => $room_preview,
                    'specials' => $special_preview,
                );
            }

            $message = sprintf(
                __( 'Preview ready: %1$d hotel(s) to create, %2$d hotel(s) to update.', 'mylighthouse-booker' ),
                $create_count,
                $update_count
            );

            if ( $apply_settings && ! empty( $sanitized_settings ) ) {
                $message .= ' ' . __( 'General settings will also be updated.', 'mylighthouse-booker' );
            }

            wp_send_json_success( array(
                'message' => $message,
                'hotels'  => $preview,
                'skipped' => $skipped_count,
                'skip_existing' => $skip_existing,
                'settings' => array(
                    'available' => ! empty( $sanitized_settings ),
                    'apply'     => $apply_settings && ! empty( $sanitized_settings ),
                    'values'    => $sanitized_settings,
                ),
            ) );
        }

        /**
         * Import hotels, rooms, and specials from a JSON payload.
         */
        public function ajax_import_hotels() {
            $this->validate_tools_request( array( 'mlb_tools_import', 'mlb_tools_action' ) );

            $payload = $this->decode_import_payload();
            if ( null === $payload ) {
                wp_send_json_error( array( 'message' => __( 'Invalid JSON payload.', 'mylighthouse-booker' ) ) );
            }

            $hotels_data = $this->extract_hotels_from_payload( $payload );
            if ( empty( $hotels_data ) ) {
                wp_send_json_error( array( 'message' => __( 'No hotels found in the provided payload.', 'mylighthouse-booker' ) ) );
            }

            $this->ensure_models_loaded();

            $apply_settings   = $this->request_flag( 'apply_settings', true );
            $skip_existing    = $this->request_flag( 'skip_existing', true );
            $selection        = $this->parse_selection_map();
            $settings_payload = $this->extract_settings_from_payload( $payload );

            $entries = array();
            $summary = array(
                'hotels_created'   => 0,
                'hotels_updated'   => 0,
                'rooms_created'    => 0,
                'rooms_updated'    => 0,
                'specials_created' => 0,
                'specials_updated' => 0,
                'skipped'          => 0,
                'settings_applied' => 0,
                'skipped_existing' => 0,
                'rooms_skipped_existing' => 0,
                'specials_skipped_existing' => 0,
            );

            foreach ( $hotels_data as $hotel_entry ) {
                if ( ! is_array( $hotel_entry ) ) {
                    $summary['skipped']++;
                    continue;
                }

                $external_id = isset( $hotel_entry['hotel_id'] ) ? sanitize_text_field( $hotel_entry['hotel_id'] ) : '';
                $name        = isset( $hotel_entry['name'] ) ? sanitize_text_field( $hotel_entry['name'] ) : '';

                if ( empty( $external_id ) || empty( $name ) ) {
                    $summary['skipped']++;
                    $entries[] = array(
                        'hotel_id' => $external_id,
                        'action'   => 'skipped',
                        'reason'   => __( 'Missing hotel ID or name.', 'mylighthouse-booker' ),
                    );
                    continue;
                }

                $status        = isset( $hotel_entry['status'] ) ? sanitize_text_field( $hotel_entry['status'] ) : 'active';
                $display_order = isset( $hotel_entry['display_order'] ) ? intval( $hotel_entry['display_order'] ) : 0;

                if ( ! $this->is_hotel_selected( $selection, $external_id ) ) {
                    $summary['skipped']++;
                    $entries[] = array(
                        'hotel_id' => $external_id,
                        'action'   => 'deselected',
                    );
                    continue;
                }

                $existing = Mylighthouse_Booker_Hotel::get_by_hotel_id( $external_id );
                if ( $skip_existing && $existing && isset( $existing['id'] ) ) {
                    $summary['skipped_existing']++;
                    $entries[] = array(
                        'hotel_id' => $external_id,
                        'action'   => 'skipped_existing',
                    );
                    continue;
                }

                if ( $existing && isset( $existing['id'] ) ) {
                    Mylighthouse_Booker_Hotel::update( $existing['id'], array(
                        'name'          => $name,
                        'hotel_id'      => $external_id,
                        'status'        => $status,
                        'display_order' => $display_order,
                    ) );
                    $hotel_db_id  = intval( $existing['id'] );
                    $hotel_action = 'updated';
                    $summary['hotels_updated']++;
                } else {
                    $hotel_db_id = Mylighthouse_Booker_Hotel::create( array(
                        'name'          => $name,
                        'hotel_id'      => $external_id,
                        'status'        => $status,
                        'display_order' => $display_order,
                    ) );
                    if ( ! $hotel_db_id ) {
                        wp_send_json_error( array( 'message' => sprintf( __( 'Failed to create hotel %s.', 'mylighthouse-booker' ), $external_id ) ) );
                    }
                    $hotel_action = 'created';
                    $summary['hotels_created']++;
                }

                $room_log = array();
                if ( ! empty( $hotel_entry['rooms'] ) && is_array( $hotel_entry['rooms'] ) ) {
                    foreach ( $hotel_entry['rooms'] as $room_entry ) {
                        if ( ! is_array( $room_entry ) ) {
                            continue;
                        }
                        $room_id   = isset( $room_entry['room_id'] ) ? sanitize_text_field( $room_entry['room_id'] ) : '';
                        $room_name = isset( $room_entry['name'] ) ? sanitize_text_field( $room_entry['name'] ) : '';
                        if ( empty( $room_id ) || empty( $room_name ) ) {
                            continue;
                        }
                        $room_status   = isset( $room_entry['status'] ) ? sanitize_text_field( $room_entry['status'] ) : 'active';
                        if ( ! $this->is_room_selected( $selection, $external_id, $room_id ) ) {
                            continue;
                        }
                        $existing_room = Mylighthouse_Booker_Room::get_by_room_id( $room_id );
                        if ( $skip_existing && $existing_room && isset( $existing_room['id'] ) ) {
                            $summary['rooms_skipped_existing']++;
                            $room_log[] = array( 'room_id' => $room_id, 'action' => 'skipped_existing' );
                            continue;
                        }
                        if ( $existing_room && isset( $existing_room['id'] ) ) {
                            Mylighthouse_Booker_Room::update( $existing_room['id'], array(
                                'hotel_id' => $hotel_db_id,
                                'name'     => $room_name,
                                'room_id'  => $room_id,
                                'status'   => $room_status,
                            ) );
                            $summary['rooms_updated']++;
                            $room_log[] = array( 'room_id' => $room_id, 'action' => 'updated' );
                        } else {
                            $new_room_id = Mylighthouse_Booker_Room::create( array(
                                'hotel_id' => $hotel_db_id,
                                'name'     => $room_name,
                                'room_id'  => $room_id,
                                'status'   => $room_status,
                            ) );
                            if ( $new_room_id ) {
                                $summary['rooms_created']++;
                                $room_log[] = array( 'room_id' => $room_id, 'action' => 'created' );
                            }
                        }
                    }
                }

                $special_log = array();
                if ( ! empty( $hotel_entry['specials'] ) && is_array( $hotel_entry['specials'] ) ) {
                    foreach ( $hotel_entry['specials'] as $special_entry ) {
                        if ( ! is_array( $special_entry ) ) {
                            continue;
                        }
                        $special_id   = isset( $special_entry['special_id'] ) ? sanitize_text_field( $special_entry['special_id'] ) : '';
                        $special_name = isset( $special_entry['name'] ) ? sanitize_text_field( $special_entry['name'] ) : '';
                        if ( empty( $special_id ) || empty( $special_name ) ) {
                            continue;
                        }
                        $special_status   = isset( $special_entry['status'] ) ? sanitize_text_field( $special_entry['status'] ) : 'active';
                        if ( ! $this->is_special_selected( $selection, $external_id, $special_id ) ) {
                            continue;
                        }
                        $existing_special = $this->get_special_by_external_id( $special_id );
                        if ( $skip_existing && $existing_special && isset( $existing_special['id'] ) ) {
                            $summary['specials_skipped_existing']++;
                            $special_log[] = array( 'special_id' => $special_id, 'action' => 'skipped_existing' );
                            continue;
                        }
                        if ( $existing_special && isset( $existing_special['id'] ) ) {
                            Mylighthouse_Booker_Special::update( $existing_special['id'], array(
                                'hotel_id'   => $hotel_db_id,
                                'name'       => $special_name,
                                'special_id' => $special_id,
                                'status'     => $special_status,
                            ) );
                            $summary['specials_updated']++;
                            $special_log[] = array( 'special_id' => $special_id, 'action' => 'updated' );
                        } else {
                            $new_special_id = Mylighthouse_Booker_Special::create( array(
                                'hotel_id'   => $hotel_db_id,
                                'name'       => $special_name,
                                'special_id' => $special_id,
                                'status'     => $special_status,
                            ) );
                            if ( $new_special_id ) {
                                $summary['specials_created']++;
                                $special_log[] = array( 'special_id' => $special_id, 'action' => 'created' );
                            }
                        }
                    }
                }

                $entries[] = array(
                    'hotel_id' => $external_id,
                    'action'   => $hotel_action,
                    'rooms'    => $room_log,
                    'specials' => $special_log,
                );
            }

            $applied_settings = array();
            if ( $apply_settings && ! empty( $settings_payload ) ) {
                $applied_settings = $this->apply_general_settings( $settings_payload );
                if ( ! empty( $applied_settings ) ) {
                    $summary['settings_applied'] = count( $applied_settings );
                }
            }

            $message = sprintf(
                __( 'Import completed. %1$d hotel(s) created, %2$d hotel(s) updated.', 'mylighthouse-booker' ),
                $summary['hotels_created'],
                $summary['hotels_updated']
            );

            if ( ! empty( $applied_settings ) ) {
                $message .= ' ' . __( 'General settings updated.', 'mylighthouse-booker' );
            }

            wp_send_json_success( array(
                'message' => $message,
                'log'     => array(
                    'summary' => $summary,
                    'entries' => $entries,
                    'settings' => $applied_settings,
                ),
                'settings' => $applied_settings,
            ) );
        }

        /**
         * Helper to detect whether a table exists.
         */
        private function table_exists( $table_name ) {
            global $wpdb;
            $like = $wpdb->esc_like( $table_name );
            $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );
            return ( $found === $table_name );
        }

        /**
         * Determine any required columns missing from the table.
         *
         * @param string $table_name
         * @param array  $required_columns
         * @return array
         */
        private function get_missing_columns( $table_name, $required_columns ) {
            global $wpdb;

            if ( empty( $required_columns ) ) {
                return array();
            }

            $table = esc_sql( $table_name );
            $columns = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`", 0 );
            if ( empty( $columns ) ) {
                return $required_columns;
            }

            $columns = array_map( 'strtolower', $columns );
            $missing = array();
            foreach ( $required_columns as $col ) {
                if ( ! in_array( strtolower( $col ), $columns, true ) ) {
                    $missing[] = $col;
                }
            }

            return $missing;
        }

        /**
         * Shared capability and nonce validation for tools actions.
         *
         * @param string|array $nonce_actions Accepted nonce action names.
         */
        private function validate_tools_request( $nonce_actions = array( 'mlb_tools_action' ) ) {
            $nonce = '';
            if ( isset( $_REQUEST['nonce'] ) ) {
                $nonce = sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) );
            }
            if ( empty( $nonce ) && isset( $_REQUEST['mlb_tools_nonce'] ) ) {
                $nonce = sanitize_text_field( wp_unslash( $_REQUEST['mlb_tools_nonce'] ) );
            }

            $actions   = array_filter( (array) $nonce_actions );
            $actions[] = 'mlb_tools_action';
            $actions   = array_unique( $actions );

            $valid = false;
            foreach ( $actions as $action ) {
                if ( $nonce && wp_verify_nonce( $nonce, $action ) ) {
                    $valid = true;
                    break;
                }
            }

            if ( ! $valid ) {
                wp_send_json_error( array( 'message' => __( 'Security check failed.', 'mylighthouse-booker' ) ) );
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'mylighthouse-booker' ) ) );
            }
        }

        /**
         * Normalize truthy request flags coming from checkboxes or query args.
         */
        private function request_flag( $key, $default = false ) {
            if ( ! isset( $_REQUEST[ $key ] ) ) {
                return (bool) $default;
            }

            $value = wp_unslash( $_REQUEST[ $key ] );
            if ( is_bool( $value ) ) {
                return $value;
            }

            if ( is_numeric( $value ) ) {
                return (bool) intval( $value );
            }

            $value = strtolower( trim( (string) $value ) );
            if ( in_array( $value, array( '1', 'true', 'yes', 'on' ), true ) ) {
                return true;
            }
            if ( in_array( $value, array( '0', 'false', 'no', 'off' ), true ) ) {
                return false;
            }

            return (bool) $default;
        }

        /**
         * Ensure model classes are loaded before database operations.
         */
        private function ensure_models_loaded() {
            if ( ! class_exists( 'Mylighthouse_Booker_Hotel' ) ) {
                require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Models/class-hotel.php';
            }
            if ( ! class_exists( 'Mylighthouse_Booker_Room' ) ) {
                require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Models/class-room.php';
            }
            if ( ! class_exists( 'Mylighthouse_Booker_Special' ) ) {
                require_once MYLIGHTHOUSE_BOOKER_ABSPATH . 'includes/Models/class-special.php';
            }
        }

        private function get_general_settings_keys() {
            return array(
                'mlb_booking_page_url' => 'text',
                'mlb_display_mode'     => 'display_mode',
                'mlb_spinner_image_url'=> 'url',
            );
        }

        private function get_general_settings() {
            $keys = $this->get_general_settings_keys();
            $settings = array();
            foreach ( $keys as $option => $type ) {
                $settings[ $option ] = get_option( $option, '' );
            }
            return $settings;
        }

        private function sanitize_settings_payload( $settings ) {
            if ( empty( $settings ) || ! is_array( $settings ) ) {
                return array();
            }

            $keys      = $this->get_general_settings_keys();
            $sanitized = array();
            foreach ( $keys as $option => $type ) {
                if ( ! isset( $settings[ $option ] ) ) {
                    continue;
                }
                $value = $settings[ $option ];
                switch ( $type ) {
                    case 'url':
                        $value = esc_url_raw( $value );
                        break;
                    case 'display_mode':
                        $value = in_array( $value, array( 'modal', 'booking_page' ), true ) ? $value : 'modal';
                        break;
                    default:
                        $value = sanitize_text_field( $value );
                        break;
                }
                $sanitized[ $option ] = $value;
            }
            return $sanitized;
        }

        private function apply_general_settings( $settings ) {
            $sanitized = $this->sanitize_settings_payload( $settings );
            if ( empty( $sanitized ) ) {
                return array();
            }

            foreach ( $sanitized as $option => $value ) {
                update_option( $option, $value );
            }

            return $sanitized;
        }

        private function parse_selection_map() {
            if ( empty( $_POST['selection'] ) ) {
                return array();
            }

            $raw = json_decode( wp_unslash( $_POST['selection'] ), true );
            return is_array( $raw ) ? $raw : array();
        }

        private function is_hotel_selected( $selection, $hotel_id ) {
            if ( empty( $selection ) || empty( $selection['hotels'] ) ) {
                return true;
            }

            if ( ! isset( $selection['hotels'][ $hotel_id ] ) ) {
                return false;
            }

            $entry = $selection['hotels'][ $hotel_id ];
            if ( isset( $entry['include'] ) ) {
                return (bool) $entry['include'];
            }

            return true;
        }

        private function is_room_selected( $selection, $hotel_id, $room_id ) {
            if ( empty( $selection ) || empty( $selection['hotels'] ) ) {
                return true;
            }

            if ( ! isset( $selection['hotels'][ $hotel_id ] ) ) {
                return false;
            }

            $entry = $selection['hotels'][ $hotel_id ];
            if ( empty( $entry['rooms'] ) ) {
                return isset( $entry['include'] ) ? (bool) $entry['include'] : true;
            }

            if ( ! array_key_exists( $room_id, $entry['rooms'] ) ) {
                return isset( $entry['include'] ) ? (bool) $entry['include'] : true;
            }

            return (bool) $entry['rooms'][ $room_id ];
        }

        private function is_special_selected( $selection, $hotel_id, $special_id ) {
            if ( empty( $selection ) || empty( $selection['hotels'] ) ) {
                return true;
            }

            if ( ! isset( $selection['hotels'][ $hotel_id ] ) ) {
                return false;
            }

            $entry = $selection['hotels'][ $hotel_id ];
            if ( empty( $entry['specials'] ) ) {
                return isset( $entry['include'] ) ? (bool) $entry['include'] : true;
            }

            if ( ! array_key_exists( $special_id, $entry['specials'] ) ) {
                return isset( $entry['include'] ) ? (bool) $entry['include'] : true;
            }

          return (bool) $entry['specials'][ $special_id ];
        }

        /**
         * Decode the JSON payload sent by import/preview requests.
         *
         * @return array|null
         */
        private function decode_import_payload() {
            $raw = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
            if ( empty( $raw ) ) {
                return null;
            }
            $decoded = json_decode( $raw, true );
            if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded ) ) {
                return null;
            }
            return $decoded;
        }

        /**
         * Extract the hotels array from a payload that may contain nested structures.
         *
         * @param array $payload
         * @return array
         */
        private function extract_hotels_from_payload( $payload ) {
            if ( ! is_array( $payload ) ) {
                return array();
            }

            if ( isset( $payload['hotels'] ) && is_array( $payload['hotels'] ) ) {
                return $payload['hotels'];
            }

            if ( isset( $payload['data'] ) && is_array( $payload['data'] ) ) {
                $from_data = $this->extract_hotels_from_payload( $payload['data'] );
                if ( ! empty( $from_data ) ) {
                    return $from_data;
                }
            }

            if ( isset( $payload['payload'] ) && is_array( $payload['payload'] ) ) {
                $from_payload = $this->extract_hotels_from_payload( $payload['payload'] );
                if ( ! empty( $from_payload ) ) {
                    return $from_payload;
                }
            }

            if ( function_exists( 'wp_is_numeric_array' ) && wp_is_numeric_array( $payload ) ) {
                return $payload;
            }

            return array();
        }

        private function extract_settings_from_payload( $payload ) {
            if ( ! is_array( $payload ) ) {
                return array();
            }

            if ( isset( $payload['settings'] ) && is_array( $payload['settings'] ) ) {
                return $payload['settings'];
            }

            foreach ( array( 'data', 'payload' ) as $key ) {
                if ( isset( $payload[ $key ] ) && is_array( $payload[ $key ] ) ) {
                    $nested = $this->extract_settings_from_payload( $payload[ $key ] );
                    if ( ! empty( $nested ) ) {
                        return $nested;
                    }
                }
            }

            return array();
        }

        /**
         * Format rooms for export payloads.
         *
         * @param array $rooms
         * @return array
         */
        private function format_rooms_for_export( $rooms ) {
            $formatted = array();
            if ( empty( $rooms ) || ! is_array( $rooms ) ) {
                return $formatted;
            }

            foreach ( $rooms as $room ) {
                if ( ! is_array( $room ) ) {
                    continue;
                }
                $formatted[] = array(
                    'id'       => isset( $room['id'] ) ? intval( $room['id'] ) : 0,
                    'hotel_id' => isset( $room['hotel_id'] ) ? intval( $room['hotel_id'] ) : 0,
                    'room_id'  => isset( $room['room_id'] ) ? sanitize_text_field( $room['room_id'] ) : '',
                    'name'     => isset( $room['name'] ) ? $room['name'] : '',
                    'status'   => isset( $room['status'] ) ? $room['status'] : '',
                );
            }

            return $formatted;
        }

        /**
         * Format specials for export payloads.
         *
         * @param array $specials
         * @return array
         */
        private function format_specials_for_export( $specials ) {
            $formatted = array();
            if ( empty( $specials ) || ! is_array( $specials ) ) {
                return $formatted;
            }

            foreach ( $specials as $special ) {
                if ( ! is_array( $special ) ) {
                    continue;
                }
                $formatted[] = array(
                    'id'         => isset( $special['id'] ) ? intval( $special['id'] ) : 0,
                    'hotel_id'   => isset( $special['hotel_id'] ) ? intval( $special['hotel_id'] ) : 0,
                    'special_id' => isset( $special['special_id'] ) ? sanitize_text_field( $special['special_id'] ) : '',
                    'name'       => isset( $special['name'] ) ? $special['name'] : '',
                    'status'     => isset( $special['status'] ) ? $special['status'] : '',
                );
            }

            return $formatted;
        }

        /**
         * Fetch a special by its external identifier with a DB fallback.
         *
         * @param string $special_id
         * @return array|null
         */
        private function get_special_by_external_id( $special_id ) {
            if ( empty( $special_id ) ) {
                return null;
            }

            if ( class_exists( 'Mylighthouse_Booker_Special' ) && method_exists( 'Mylighthouse_Booker_Special', 'get_by_special_id' ) ) {
                return Mylighthouse_Booker_Special::get_by_special_id( $special_id );
            }

            global $wpdb;
            $table = $wpdb->prefix . 'mlb_specials';
            return $wpdb->get_row(
                $wpdb->prepare( "SELECT * FROM {$table} WHERE special_id = %s", $special_id ),
                ARRAY_A
            );
        }
    }
}
?>