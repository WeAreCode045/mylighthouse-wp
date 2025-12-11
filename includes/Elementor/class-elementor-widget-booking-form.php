<?php

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Icons_Manager;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * Elementor Widget: Lighthouse Booking Form
 */
class Mylighthouse_Booker_Elementor_Widget_Booking_Form extends Widget_Base
{
	public function get_name()
	{
		return 'mylighthouse-booking-form';
	}

	public function get_title()
	{
		return __('Lighthouse Booking Form', 'mylighthouse-booker');
	}

	public function get_icon()
	{
		return 'eicon-form-horizontal';
	}

	public function get_categories()
	{
		return array('mylighthouse-booker', 'general');
	}

	/**
	 * Register controls
	 */
	protected function _register_controls()
	{
		// CONTENT tab - General Settings
		$this->start_controls_section(
			'general_settings',
			[
				'label' => __('General Settings', 'mylighthouse-booker'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		// Get hotels from database
		$hotels = Mylighthouse_Booker_Hotel::get_all_with_rooms('active');
		$hotel_options = array();
		foreach ($hotels as $h) {
			$hotel_options[$h['hotel_id']] = $h['name'];
		}

		// Form Type
		$this->add_control(
			'form_type',
			[
				'label' => __('Form Type', 'mylighthouse-booker'),
				'type' => Controls_Manager::SELECT,
				'options' => [ 'hotel' => __('Hotel', 'mylighthouse-booker'), 'room' => __('Room', 'mylighthouse-booker'), 'special' => __('Special', 'mylighthouse-booker') ],
				'default' => 'hotel',
			]
		);

		$this->end_controls_section();

		// CONTENT tab - Icons
		$this->start_controls_section(
			'icons_content',
			[
				'label' => __('Icons', 'mylighthouse-booker'),
				'tab'   => Controls_Manager::TAB_CONTENT,
				'condition' => [ 'form_type' => 'hotel' ],
			]
		);

		$this->add_control(
			'hotel_icon',
			[
				'label' => __('Hotel Select Icon', 'mylighthouse-booker'),
				'type' => Controls_Manager::ICONS,
				'default' => [ 'value' => 'fas fa-map-marker-alt', 'library' => 'fa-solid' ],
			]
		);

		$this->add_control(
			'date_icon',
			[
				'label' => __('Date Field Icon', 'mylighthouse-booker'),
				'type' => Controls_Manager::ICONS,
				'default' => [ 'value' => 'fas fa-calendar-alt', 'library' => 'fa-solid' ],
			]
		);

		// Removed separator icon option per request

		$this->end_controls_section();

		// CONTENT tab - Source
		$this->start_controls_section(
			'source_settings',
			[
				'label' => __('Source', 'mylighthouse-booker'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		// HOTEL FORM TYPE: Optional preselect hotel
		$this->add_control(
			'preselect_hotel',
			[
				'label' => __('Preselect Hotel (Optional)', 'mylighthouse-booker'),
				'type' => Controls_Manager::SELECT,
				'options' => array_merge([ '' => __('None - User selects on frontend', 'mylighthouse-booker') ], $hotel_options),
				'default' => '',
				'condition' => [ 'form_type' => 'hotel' ],
				'description' => __('Optionally preselect a hotel. User can still change it on the frontend.', 'mylighthouse-booker'),
			]
		);

		// ROOM FORM TYPE: Step 1 - Select Hotel
		$this->add_control(
			'room_hotel',
			[
				'label' => __('1. Select Hotel', 'mylighthouse-booker'),
				'type' => Controls_Manager::SELECT,
				'options' => array_merge([ '' => __('— Kies een Hotel —', 'mylighthouse-booker') ], $hotel_options),
				'condition' => [ 'form_type' => 'room' ],
				'description' => __('First, select the hotel.', 'mylighthouse-booker'),
			]
		);

		// SPECIAL FORM TYPE: Step 1 - Select Hotel
		$this->add_control(
			'special_hotel',
			[
				'label' => __('1. Select Hotel', 'mylighthouse-booker'),
				'type' => Controls_Manager::SELECT,
				'options' => array_merge([ '' => __('— Select a Hotel —', 'mylighthouse-booker') ], $hotel_options),
				'condition' => [ 'form_type' => 'special' ],
				'description' => __('First, select the hotel.', 'mylighthouse-booker'),
			]
		);

		// ROOM FORM TYPE: Step 2 - Build room options for each hotel (shown conditionally)
		foreach ($hotels as $hotel) {
			$hotel_id = $hotel['hotel_id'] ?? '';
			$hotel_name = $hotel['name'] ?? '';
			
			if (!$hotel_id) continue;
			
			// Build room options for this specific hotel
			$room_options_for_hotel = [ '' => __('— Select a Room —', 'mylighthouse-booker') ];
			
			if (isset($hotel['rooms']) && is_array($hotel['rooms']) && count($hotel['rooms']) > 0) {
				foreach ($hotel['rooms'] as $room) {
					$room_id = $room['room_id'] ?? '';
					$room_name = $room['name'] ?? '';
					if ($room_id && $room_name) {
						$room_options_for_hotel[$room_id] = $room_name . ' (ID: ' . $room_id . ')';
					}
				}
			} else {
				$room_options_for_hotel['_no_rooms'] = __('No rooms available for this hotel', 'mylighthouse-booker');
			}

			$this->add_control(
				'room_id_' . $hotel_id,
				[
					/* translators: %s is the hotel name (e.g. "Seaside Hotel"). */
					'label' => sprintf(__('2. Select Room from %s', 'mylighthouse-booker'), $hotel_name),
					'type' => Controls_Manager::SELECT,
					'options' => $room_options_for_hotel,
					'conditions' => [
						'relation' => 'and',
						'terms' => [
							[
								'name' => 'form_type',
								'operator' => '===',
								'value' => 'room',
							],
							[
								'name' => 'room_hotel',
								'operator' => '===',
								'value' => (string)$hotel_id,
							],
						],
					],
					/* translators: %s is the hotel name (e.g. "Seaside Hotel"). */
					'description' => sprintf(__('Select a room from %s.', 'mylighthouse-booker'), $hotel_name),
				]
			);

			// Build special options for this specific hotel
			$special_options_for_hotel = [ '' => __('— Select a Special —', 'mylighthouse-booker') ];
			if (isset($hotel['specials']) && is_array($hotel['specials']) && count($hotel['specials']) > 0) {
				foreach ($hotel['specials'] as $spec) {
					$spec_id = $spec['special_id'] ?? '';
					$spec_name = $spec['name'] ?? '';
					if ($spec_id && $spec_name) {
						$special_options_for_hotel[$spec_id] = $spec_name . ' (ID: ' . $spec_id . ')';
					}
				}
			} else {
				$special_options_for_hotel['_no_specials'] = __('No specials available for this hotel', 'mylighthouse-booker');
			}

			$this->add_control(
				'special_id_' . $hotel_id,
				[
					/* translators: %s is the hotel name (e.g. "Seaside Hotel"). */
					'label' => sprintf(__('2. Select Special from %s', 'mylighthouse-booker'), $hotel_name),
					'type' => Controls_Manager::SELECT,
					'options' => $special_options_for_hotel,
					'conditions' => [
						'relation' => 'and',
						'terms' => [
							[
								'name' => 'form_type',
								'operator' => '===',
								'value' => 'special',
							],
							[
								'name' => 'special_hotel',
								'operator' => '===',
								'value' => (string)$hotel_id,
							],
						],
					],
					/* translators: %s is the hotel name (e.g. "Seaside Hotel"). */
					'description' => sprintf(__('Select a special from %s.', 'mylighthouse-booker'), $hotel_name),
				]
			);
		}

		$this->end_controls_section();

		// CONTENT tab - Layout & Visibility
		$this->start_controls_section(
			'layout_visibility',
			[
				'label' => __('Layout & Visibility', 'mylighthouse-booker'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		// Layout
		$this->add_responsive_control(
			'form_width',
			[
				'label' => __('Form Width', 'mylighthouse-booker'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'vw'],
				'range' => [ 'px' => [ 'min' => 0, 'max' => 2000 ], '%' => [ 'min' => 0, 'max' => 100 ], 'vw' => [ 'min' => 0, 'max' => 100 ] ],
				'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => 'width: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->add_control(
			'layout',
			[
				'label' => __('Form Element Placement', 'mylighthouse-booker'),
				'type' => Controls_Manager::SELECT,
				'options' => [ 'inline' => __('Inline', 'mylighthouse-booker'), 'stacked' => __('Stacked', 'mylighthouse-booker') ],
				'condition' => [ 'form_type' => 'hotel' ],
				'default' => 'inline',
			]
		);

		$this->add_control(
			'button_placement',
			[
				'label' => __('Button Placement', 'mylighthouse-booker'),
				'type' => Controls_Manager::SELECT,
				'options' => [ 'after' => __('Right (inline)', 'mylighthouse-booker'), 'below' => __('Below (full width)', 'mylighthouse-booker') ],
				'default' => 'after',
				'condition' => [ 'form_type' => 'hotel' ],
			]
		);

		$this->add_control(
			'prevent_auto_stack',
			[
				'label' => __('Prevent Auto-Stack on Small Widths', 'mylighthouse-booker'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Yes', 'mylighthouse-booker'),
				'label_off' => __('No', 'mylighthouse-booker'),
				'return_value' => 'yes',
				'default' => 'no',
				'condition' => [ 'layout' => 'inline', 'form_type' => 'hotel' ],
			]
		);

		$this->add_control(
			'fit_inline',
			[
				'label' => __('Auto Adjust Inline Widths (fit 100%)', 'mylighthouse-booker'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Yes', 'mylighthouse-booker'),
				'label_off' => __('No', 'mylighthouse-booker'),
				'return_value' => 'yes',
				'default' => 'yes',
				'condition' => [ 'layout' => 'inline', 'form_type' => 'hotel' ],
			]
		);

		// Field width trackbars (only when inline and not fit mode)
		$this->add_control(
			'hotel_span',
			[
				'label' => __('Hotel Select Width', 'mylighthouse-booker'),
				'type' => Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 4,
				'default' => 2,
				'condition' => [ 'layout' => 'inline', 'fit_inline!' => 'yes', 'form_type' => 'hotel' ],
				'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-span-hotel: {{VALUE}};' ],
				'description' => __('Total width of elements cannot be more than 5.', 'mylighthouse-booker'),
			]
		);
		$this->add_control(
			'date_span',
			[
				'label' => __('Date Picker Width', 'mylighthouse-booker'),
				'type' => Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 4,
				'default' => 3,
				'condition' => [ 'layout' => 'inline', 'fit_inline!' => 'yes', 'form_type' => 'hotel' ],
				'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-span-date: {{VALUE}};' ],
				'description' => __('Total width of elements cannot be more than 5.', 'mylighthouse-booker'),
			]
		);
		$this->add_control(
			'button_span',
			[
				'label' => __('Button Width', 'mylighthouse-booker'),
				'type' => Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 4,
				'default' => 1,
				'condition' => [ 'layout' => 'inline', 'fit_inline!' => 'yes', 'form_type' => 'hotel' ],
				'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-span-button: {{VALUE}};' ],
				'description' => __('Total width of elements cannot be more than 5.', 'mylighthouse-booker'),
			]
		);

		$this->add_responsive_control('field_height', [ 'label' => __('Form Fields Height', 'mylighthouse-booker'), 'type' => Controls_Manager::SLIDER, 'size_units' => ['px'], 'range' => [ 'px' => [ 'min' => 28, 'max' => 100 ] ], 'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-field-height: {{SIZE}}{{UNIT}};' ], 'condition' => [ 'form_type' => 'hotel' ] ]);
		$this->add_responsive_control('element_gap', [ 'label' => __('Gap', 'mylighthouse-booker'), 'type' => Controls_Manager::SLIDER, 'size_units' => ['px'], 'range' => [ 'px' => [ 'min' => 0, 'max' => 48 ] ], 'selectors' => [ '{{WRAPPER}} .mlb-booking-form .mlb-form' => 'gap: {{SIZE}}{{UNIT}};' ], 'condition' => [ 'form_type' => 'hotel' ] ]);
		$this->add_control('form_alignment', [
			'label' => __('Form Alignment', 'mylighthouse-booker'),
			'type' => Controls_Manager::CHOOSE,
			'options' => [
				'left' => [ 'title' => __('Left', 'mylighthouse-booker'), 'icon' => 'eicon-text-align-left' ],
				'center' => [ 'title' => __('Center', 'mylighthouse-booker'), 'icon' => 'eicon-text-align-center' ],
				'right' => [ 'title' => __('Right', 'mylighthouse-booker'), 'icon' => 'eicon-text-align-right' ],
			],
			'default' => 'left',
			'toggle' => false,
			'selectors_dictionary' => [
				'left' => 'margin-left: 0; margin-right: auto;',
				'center' => 'margin-left: auto; margin-right: auto;',
				'right' => 'margin-left: auto; margin-right: 0;',
			],
			'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '{{VALUE}}' ],
		]);

		$this->end_controls_section();

		// STYLE: Buttons
		$this->start_controls_section(
			'style_buttons',
			[
				'label' => __('Buttons', 'mylighthouse-booker'),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control('btn_bg', [
			'label' => __('Background', 'mylighthouse-booker'),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-btn-bg: {{VALUE}};' ],
		]);
		$this->add_control('btn_bg_hover', [
			'label' => __('Hover Background', 'mylighthouse-booker'),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-btn-bg-hover: {{VALUE}};' ],
		]);
		$this->add_control('btn_text', [
			'label' => __('Text Color', 'mylighthouse-booker'),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-btn-text: {{VALUE}};' ],
		]);
		$this->add_control('btn_text_hover', [
			'label' => __('Text Hover', 'mylighthouse-booker'),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-btn-text-hover: {{VALUE}};' ],
		]);
		$this->add_control('btn_border', [
			'label' => __('Border Color', 'mylighthouse-booker'),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-btn-border: {{VALUE}};' ],
		]);
		$this->add_control('btn_border_hover', [
			'label' => __('Border Hover', 'mylighthouse-booker'),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-btn-border-hover: {{VALUE}};' ],
		]);
		// Allow separate top/right/bottom/left border widths for the button
		$this->add_responsive_control('btn_border_width', [
			'label' => __('Border Width', 'mylighthouse-booker'),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => ['px'],
			'selectors' => [
				'{{WRAPPER}} .mlb-booking-form .mlb-submit-btn, {{WRAPPER}} .mlb-booking-form .mlb-form button' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		]);
		$this->add_responsive_control('btn_radius', [
			'label' => __('Border Radius', 'mylighthouse-booker'),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => ['px'],
			'selectors' => [
				'{{WRAPPER}} .mlb-booking-form .mlb-submit-btn, {{WRAPPER}} .mlb-booking-form .mlb-form button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		]);
		// Typography (Elementor global-compatible)
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'btn_typography',
				'label' => __('Button Typography', 'mylighthouse-booker'),
				'selector' => '{{WRAPPER}} .mlb-booking-form .mlb-submit-btn, {{WRAPPER}} .mlb-booking-form .mlb-form button',
			]
		);

		$this->end_controls_section();

		// STYLE: Fields (Hotel Select & Date) Styling & Typography
		$this->start_controls_section(
			'style_fields',
			[
				'label' => __('Fields Styling & Typography', 'mylighthouse-booker'),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [ 'form_type' => 'hotel' ],
			]
		);
		$this->add_control('field_bg', [ 'label' => __('Background', 'mylighthouse-booker'), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-input-bg: {{VALUE}};' ] ]);
		$this->add_control('field_bg_hover', [ 'label' => __('Hover Background', 'mylighthouse-booker'), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-input-bg-hover: {{VALUE}};' ] ]);
		$this->add_control('field_text', [ 'label' => __('Text Color', 'mylighthouse-booker'), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-input-text: {{VALUE}};' ] ]);
		$this->add_control('field_text_hover', [ 'label' => __('Text Hover', 'mylighthouse-booker'), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-input-text-hover: {{VALUE}};' ] ]);
		$this->add_control('field_border', [ 'label' => __('Border Color', 'mylighthouse-booker'), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-input-border: {{VALUE}}; --mlb-form-border: {{VALUE}};' ] ]);
		$this->add_control('field_border_hover', [ 'label' => __('Border Hover', 'mylighthouse-booker'), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-input-border-hover: {{VALUE}};' ] ]);
		// Allow separate top/right/bottom/left border widths for fields (inputs/selects)
		$this->add_responsive_control('field_border_width', [
			'label' => __('Border Width', 'mylighthouse-booker'),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => ['px'],
			'selectors' => [
				// Core inputs/selects
				'{{WRAPPER}} .mlb-booking-form .mlb-form input, {{WRAPPER}} .mlb-booking-form .mlb-form select' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				// Custom select toggle
				'{{WRAPPER}} .mlb-booking-form .mlb-custom-select__toggle' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				// Native hotel select with strong specificity and important in original CSS
				'{{WRAPPER}} .mlb-booking-form .hotel-selector select#mlb-hotel-select' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
			],
		]);
		$this->add_responsive_control('field_radius', [
			'label' => __('Border Radius', 'mylighthouse-booker'),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => ['px'],
			'selectors' => [
				// Core inputs/selects
				'{{WRAPPER}} .mlb-booking-form .mlb-form input, {{WRAPPER}} .mlb-booking-form .mlb-form select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				// Custom select toggle
				'{{WRAPPER}} .mlb-booking-form .mlb-custom-select__toggle' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				// Native hotel select with strong specificity and important in original CSS
				'{{WRAPPER}} .mlb-booking-form .hotel-selector select#mlb-hotel-select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
			],
		]);
		// Typography (Elementor global-compatible)
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'field_typography',
				'label' => __('Field Typography', 'mylighthouse-booker'),
				'selector' => '{{WRAPPER}} .mlb-booking-form .mlb-form input, {{WRAPPER}} .mlb-booking-form .mlb-form select, {{WRAPPER}} .mlb-booking-form .mlb-custom-select__toggle, {{WRAPPER}} .mlb-booking-form .mlb-custom-select__item',
			]
		);
		// Select / Dropdown specific styling
		$this->add_control('select_bg', [ 'label' => __('Select Background', 'mylighthouse-booker'), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-select-bg: {{VALUE}};' ] ]);
		$this->add_control('select_text', [ 'label' => __('Select Text Color', 'mylighthouse-booker'), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-select-text: {{VALUE}};' ] ]);
		$this->add_control('select_arrow_color', [ 'label' => __('Select Arrow Color', 'mylighthouse-booker'), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-select-arrow-color: {{VALUE}};' ] ]);
		$this->add_control('dropdown_bg', [ 'label' => __('Dropdown Background', 'mylighthouse-booker'), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-dropdown-bg: {{VALUE}};' ] ]);
		$this->add_control('dropdown_item_text', [ 'label' => __('Dropdown Item Text', 'mylighthouse-booker'), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-dropdown-item-text: {{VALUE}};' ] ]);
		$this->add_control('dropdown_item_hover_bg', [ 'label' => __('Dropdown Item Hover BG', 'mylighthouse-booker'), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-dropdown-item-hover-bg: {{VALUE}};' ] ]);
		$this->add_control('dropdown_item_hover_text', [ 'label' => __('Dropdown Item Hover Text', 'mylighthouse-booker'), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-dropdown-item-hover-text: {{VALUE}};' ] ]);

		$this->end_controls_section();

		// STYLE: Icon Styling
		$this->start_controls_section(
			'style_icons',
			[
				'label' => __('Icon Styling', 'mylighthouse-booker'),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [ 'form_type' => 'hotel' ],
			]
		);
		$this->add_control('icon_color', [ 'label' => __('Icon Color', 'mylighthouse-booker'), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-icon-color: {{VALUE}};' ] ]);
		$this->add_responsive_control('icon_size', [
			'label' => __('Field Icons Size', 'mylighthouse-booker'),
			'type' => Controls_Manager::SLIDER,
			'size_units' => ['px','em','rem'],
			'range' => [ 'px' => [ 'min' => 8, 'max' => 64 ], 'em' => [ 'min' => 0.25, 'max' => 4, 'step' => 0.05 ], 'rem' => [ 'min' => 0.25, 'max' => 4, 'step' => 0.05 ] ],
			'selectors' => [ '{{WRAPPER}} .mlb-booking-form' => '--mlb-icon-size: {{SIZE}}{{UNIT}};' ],
			'description' => __('Applies to the hotel and date field icons on the left.', 'mylighthouse-booker'),
		]);
		// Removed separator icon color/size/offset controls per request
		$this->end_controls_section();

		// Removed: STYLE Layout section (controls moved to Content > Layout & Visibility)
	}

	/**
	 * Render widget output
	 */
	protected function render()
	{
		$settings = $this->get_settings_for_display();

		// Ensure frontend assets are available
		if (! wp_style_is('fontawesome', 'enqueued')) {
			wp_enqueue_style('fontawesome');
		}
		if (! wp_style_is('easepick', 'enqueued')) {
			wp_enqueue_style('easepick');
		}
		if (! wp_style_is('mylighthouse-booker-components', 'enqueued')) {
			wp_enqueue_style('mylighthouse-booker-components');
		}
		// Enqueue legacy styles for backward compatibility
		if (! wp_style_is('mylighthouse-booker-frontend', 'enqueued')) {
			wp_enqueue_style('mylighthouse-booker-frontend');
		}
		if (! wp_style_is('mylighthouse-booker-modal', 'enqueued')) {
			wp_enqueue_style('mylighthouse-booker-modal');
		}

		// Enqueue modular component scripts (already loaded globally, but ensure they're available)
		if (! wp_script_is('easepick-wrapper', 'enqueued')) {
			wp_enqueue_script('easepick-wrapper');
		}
		if (! wp_script_is('mylighthouse-booker-date-picker', 'enqueued')) {
			wp_enqueue_script('mylighthouse-booker-date-picker');
		}
		if (! wp_script_is('mylighthouse-booker-booking-details', 'enqueued')) {
			wp_enqueue_script('mylighthouse-booker-booking-details');
		}
		if (! wp_script_is('mylighthouse-booker-booking-actions', 'enqueued')) {
			wp_enqueue_script('mylighthouse-booker-booking-actions');
		}
		if (! wp_script_is('mylighthouse-booker-booking-results-modal', 'enqueued')) {
			wp_enqueue_script('mylighthouse-booker-booking-results-modal');
		}

		// Enqueue appropriate widget script based on form type
		$widget_form_type = isset($settings['form_type']) ? $settings['form_type'] : 'hotel';
		switch ($widget_form_type) {
			case 'room':
				if (! wp_script_is('mylighthouse-booker-room-widget', 'enqueued')) {
					wp_enqueue_script('mylighthouse-booker-room-widget');
				}
				$script_handle = 'mylighthouse-booker-room-widget';
				break;
			case 'special':
				if (! wp_script_is('mylighthouse-booker-special-widget', 'enqueued')) {
					wp_enqueue_script('mylighthouse-booker-special-widget');
				}
				$script_handle = 'mylighthouse-booker-special-widget';
				break;
			default:
				// hotel form
				if (! wp_script_is('mylighthouse-booker-hotel-widget', 'enqueued')) {
					wp_enqueue_script('mylighthouse-booker-hotel-widget');
				}
				$script_handle = 'mylighthouse-booker-hotel-widget';
				break;
		}

		// Get hotels from database
		$hotels = Mylighthouse_Booker_Hotel::get_all_with_rooms('active');
		$booking_page_url = get_option('mlb_booking_page_url');

		// Check if hotels exist and have valid structure
		$valid_hotels = false;
		if (!empty($hotels) && is_array($hotels)) {
			foreach ($hotels as $hotel) {
				if (isset($hotel['hotel_id']) && !empty($hotel['hotel_id'])) {
					$valid_hotels = true;
					break;
				}
			}
		}

		// Only show error if truly not configured (not during Elementor editor changes)
		if (!$valid_hotels) {
			if (current_user_can('manage_options')) {
				echo '<div class="notice notice-warning"><p>' . esc_html__('No valid hotels configured. Please add hotels in Hotels -> Mylighthouse Booker.', 'mylighthouse-booker') . '</p></div>';
			}
			return;
		}

		if (empty($booking_page_url)) {
			if (current_user_can('manage_options')) {
				echo '<div class="notice notice-warning"><p>' . esc_html__('Booking Page URL is not set. Please configure it in Settings -> Mylighthouse Booker.', 'mylighthouse-booker') . '</p></div>';
			}
			return;
		}

		$form_type = isset($settings['form_type']) ? $settings['form_type'] : 'hotel';
		
		// Handle Room Form Type
		$room_id = '';
		$special_id = '';
		$selected_hotel_id = '';
		if ($form_type === 'room') {
			// Get selected hotel from room_hotel control
			$selected_hotel_id = !empty($settings['room_hotel']) ? sanitize_text_field($settings['room_hotel']) : '';
			
			// Get room ID from the hotel-specific room control
			if ($selected_hotel_id) {
				$room_control_key = 'room_id_' . $selected_hotel_id;
				if (!empty($settings[$room_control_key])) {
					$room_id = sanitize_text_field($settings[$room_control_key]);
				}
			}
			
			// Validate room form has required selections
			if (empty($selected_hotel_id) || empty($room_id)) {
				if (current_user_can('edit_posts')) {
					echo '<div class="notice notice-warning"><p>' . esc_html__('Room form requires selecting a hotel and room in the Source settings.', 'mylighthouse-booker') . '</p></div>';
				}
				return;
			}
			
			// Legacy room-booking script removed - using modular room-widget instead
		}

		// Handle Special Form Type
		if ($form_type === 'special') {
			$selected_hotel_id = !empty($settings['special_hotel']) ? sanitize_text_field($settings['special_hotel']) : '';

			if ($selected_hotel_id) {
				$spec_control_key = 'special_id_' . $selected_hotel_id;
				if (!empty($settings[$spec_control_key])) {
					$special_id = sanitize_text_field($settings[$spec_control_key]);
				}
			}

			if (empty($selected_hotel_id) || empty($special_id)) {
				if (current_user_can('edit_posts')) {
					echo '<div class="notice notice-warning"><p>' . esc_html__('Special form requires selecting a hotel and special in the Source settings.', 'mylighthouse-booker') . '</p></div>';
				}
				return;
			}

		// Special forms: Don't enqueue special-booking.js if skip-dates (handled by special-form.js fallback)
		// $show_date_picker is false for special forms (skip-dates), true for hotel forms
		$show_date_picker_check = ($form_type === 'hotel');
		if ($form_type === 'special' && !$show_date_picker_check) {
			// This is a skip-dates special form, don't enqueue special-booking.js
			// The skip-dates handler in special-form.js will handle the redirect
		}
	}		// Map settings to display logic
		$display_hotels = $hotels;
		$show_hotel_select = false;
		$default_hotel_id = '';
		$special_name = '';

		if ($form_type === 'room') {
			// ROOM FORM TYPE: Use backend selection, hide hotel dropdown on frontend
			$default_hotel_id = $selected_hotel_id;
			$show_hotel_select = false; // Always hidden for room forms
		} else {
			// HOTEL FORM TYPE: User selects on frontend, optional preselect
			if (isset($settings['preselect_hotel']) && $settings['preselect_hotel'] !== '') {
				$default_hotel_id = $settings['preselect_hotel'];
			}
			
			// Hotel dropdown visibility logic
			if (count($display_hotels) > 1) {
				$show_hotel_select = true; // Always show if multiple hotels
			} elseif (count($display_hotels) === 1) {
				$show_hotel_select = false; // Hide if only one hotel
				$default_hotel_id = $display_hotels[0]['hotel_id']; // Auto-select the only hotel
			}
		}

		// If this is a special form, prefer using the explicitly selected hotel
		if ($form_type === 'special' && !empty($selected_hotel_id)) {
			$default_hotel_id = $selected_hotel_id;
			$show_hotel_select = false;
		}

		// Styling is managed via the Elementor widget; do not read legacy DB option here.
		$style_opts = array();
		$button_label = $this->get_default_button_label($form_type);

		$layout = $settings['layout'] ?? (isset($style_opts['form_layout']['layout']) ? $style_opts['form_layout']['layout'] : 'inline');
		$placement = $settings['button_placement'] ?? (isset($style_opts['button_placement']) ? $style_opts['button_placement'] : 'after');

		// Get device-specific display mode settings
		$display_mode_mobile = get_option('mlb_display_mode_mobile', get_option('mlb_display_mode', 'modal'));
		$display_mode_tablet = get_option('mlb_display_mode_tablet', get_option('mlb_display_mode', 'modal'));
		$display_mode_desktop = get_option('mlb_display_mode_desktop', get_option('mlb_display_mode', 'modal'));
		// Legacy result_target for backwards compatibility (use desktop as default)
		$result_target = ($display_mode_desktop === 'modal') ? 'modal' : 'booking_page';

		// Prepare room and hotel names
		$hotel_name = '';
		$room_name = '';
		
		// Find hotel name
		foreach ($hotels as $h) {
			if ($h['hotel_id'] === $default_hotel_id) {
				$hotel_name = $h['name'];
				
				// If this is a room form, also find the room name from hotel's rooms
				if (!empty($room_id) && isset($h['rooms']) && is_array($h['rooms'])) {
					foreach ($h['rooms'] as $room) {
						if ($room['room_id'] === $room_id) {
							$room_name = $room['name'];
							break;
						}
					}
				}
				break;
			}
		}
		
		// Fallback if room name not found
		if (empty($room_name) && !empty($room_id)) {
			$room_name = 'Room ' . $room_id;
		}

		// For room forms, we have explicit hotel selection; for hotel forms, it's dynamic
		$explicit_hotel_id = ($form_type === 'room') ? $default_hotel_id : '';
		$form_data_id = ''; // Not used in Elementor widget context
		
		// Only hotel forms show an inline date picker; rooms and specials should skip it
		$show_date_picker = ($form_type === 'hotel');

		// If special form, find special name from hotels
		if ($form_type === 'special' && !empty($special_id)) {
			foreach ($hotels as $h) {
				if ($h['hotel_id'] === $selected_hotel_id) {
					if (!empty($h['specials']) && is_array($h['specials'])) {
						foreach ($h['specials'] as $spec) {
							if (($spec['special_id'] ?? '') === $special_id) {
								$special_name = $spec['name'] ?? '';
								break 2;
							}
						}
					}
				}
			}
		}

		$form_data = array(
			'hotels' => $display_hotels,
			'show_hotel_select' => $show_hotel_select,
			'default_hotel_id' => $default_hotel_id,
			'form_data_id' => $form_data_id,
			'explicit_hotel_id' => $explicit_hotel_id,
			'button_label' => $button_label,
			'layout' => $layout,
			'placement' => $placement,
			'booking_page_url' => $booking_page_url,
			'room_id' => $room_id,
			'room_name' => $room_name,
			'special_id' => $special_id,
			'special_name' => $special_name,
			'hotel_name' => $hotel_name,
			'show_date_picker' => $show_date_picker,
			'form_type' => $form_type,
		);

		// Legacy modal template and modal-trigger script removed
		// Using modular date picker component system instead

		// Render the form template directly (not via shortcode)
		$no_stack_class = (!empty($settings['prevent_auto_stack']) && $settings['prevent_auto_stack'] === 'yes') ? ' mlb-inline-no-stack' : '';
		$fit_class = (!empty($settings['fit_inline']) && $settings['fit_inline'] === 'yes') ? ' mlb-inline-fit' : '';
		$inline_style = '';
		if (($settings['layout'] ?? 'inline') === 'inline' && (empty($settings['fit_inline']) || $settings['fit_inline'] !== 'yes')) {
			$h = isset($settings['hotel_span']) ? floatval($settings['hotel_span']) : 2.0;
			$d = isset($settings['date_span']) ? floatval($settings['date_span']) : 3.0;
			$b = isset($settings['button_span']) ? floatval($settings['button_span']) : 1.0;
			$h = max(1.0, min(4.0, $h));
			$d = max(1.0, min(4.0, $d));
			$b = max(1.0, min(4.0, $b));
			$sum = $h + $d + $b;
			if ($sum > 6.0 && $sum > 0) {
				$scale = 6.0 / $sum;
				$h *= $scale; $d *= $scale; $b *= $scale;
			}
			$inline_style = sprintf(' style="--mlb-span-hotel: %.4f; --mlb-span-date: %.4f; --mlb-span-button: %.4f;"', $h, $d, $b);
		}
		echo '<div class="mlb-elementor-widget' . esc_attr($no_stack_class . $fit_class) . '"' . $inline_style . '>';
		ob_start();

		// Icons: render as Font Awesome <i> tags using the class value from the Icons control
		$hotel_icon_html = '';
		if (! empty($settings['hotel_icon']) && ! empty($settings['hotel_icon']['value'])) {
			$fa = esc_attr($settings['hotel_icon']['value']);
			$hotel_icon_html = '<i class="' . $fa . ' mlb-field-icon mlb-location-icon" aria-hidden="true"></i>';
		}
		$date_icon_html = '';
		if (! empty($settings['date_icon']) && ! empty($settings['date_icon']['value'])) {
			$fa = esc_attr($settings['date_icon']['value']);
			$date_icon_html = '<i class="' . $fa . ' mlb-field-icon mlb-calendar-icon" aria-hidden="true"></i>';
		}
		$form_data['hotel_icon_html'] = $hotel_icon_html;
		$form_data['date_icon_html'] = $date_icon_html;
		
		// Use new modular template for cleaner integration with component system
		Mylighthouse_Booker_Template_Loader::get_template('booking-form-modular.php', $form_data);
		echo ob_get_clean();
		echo '</div>';
	}

	/**
	 * Provide translated default CTA text based on the current form type.
	 *
	 * @param string $form_type
	 * @return string
	 */
	private function get_default_button_label($form_type)
	{
		switch ($form_type) {
			case 'room':
				return __('Book This Room', 'mylighthouse-booker');
			case 'special':
				return __('Book Special', 'mylighthouse-booker');
			default:
				return __('Check Availability', 'mylighthouse-booker');
		}
	}
}
