/**
 * Frontend JavaScript for booking form with modal date picker
 */

(function($) {
	'use strict';

	// JS gettext helper: prefer wp.i18n.__() when available
	function mlbGettext(str) {
		try {
			if (typeof wp !== 'undefined' && wp.i18n && typeof wp.i18n.__ === 'function') {
				return wp.i18n.__(str, 'mylighthouse-booker');
			}
		} catch (e) {}
		return str;
	}

	// Dispatcher: pick the correct modal init function for a form
	function initModalDatePicker($form){
		try{
			if(!$form || !$form.length) return;
			// Prioritize explicit dataset indicators (rateId/specialId -> special, roomId -> room)
			try {
				var ds = $form[0].dataset || {};
				if ((ds.rateId || ds.specialId) && typeof window.initSpecialModalDatePicker === 'function') {
					return window.initSpecialModalDatePicker($form);
				}
				if (ds.roomId && typeof window.initRoomModalDatePicker === 'function') {
					return window.initRoomModalDatePicker($form);
				}
			} catch (e) {}
			// Prefer explicit type classes
			if($form.hasClass('mlb-special-form-type') && typeof window.initSpecialModalDatePicker === 'function'){
				return window.initSpecialModalDatePicker($form);
			}
			if($form.hasClass('mlb-room-form-type') && typeof window.initRoomModalDatePicker === 'function'){
				return window.initRoomModalDatePicker($form);
			}
			// Fallbacks: try room then special
			if(typeof window.initRoomModalDatePicker === 'function') return window.initRoomModalDatePicker($form);
			if(typeof window.initSpecialModalDatePicker === 'function') return window.initSpecialModalDatePicker($form);
			console.warn('[MLB Init] No modal init function available for this form');
		}catch(e){ console.error('initModalDatePicker dispatcher error', e); }
	}

	// Global helper: read a value from a form element using multiple fallbacks
	// names: array of candidate keys like ['roomId','room_id','room-id']
	try {
		window.mlbGetFormValue = window.mlbGetFormValue || function(formEl, names) {
			try {
				if (!formEl) return '';
				var el = (formEl.jquery && formEl.length) ? formEl[0] : formEl;
				if (!el) return '';
				var ds = el.dataset || {};
				for (var i = 0; i < names.length; i++) {
					var key = names[i];
					// dataset camelCase
					var dsKey = key.replace(/[-_](.)/g, function(m, g) { return g.toUpperCase(); });
					if (ds[dsKey]) return ds[dsKey];
					if (ds[key]) return ds[key];
					// input[name]
					var inp = el.querySelector('input[name="' + key + '"]');
					if (inp && inp.value) return inp.value;
					// class-based
					var cls = el.querySelector('.mlb-' + key.replace(/[_]/g, '-'));
					if (cls) {
						if (cls.value) return cls.value;
						if (cls.textContent) return cls.textContent.trim();
					}
				}
				// check wrapper dataset
				var wrap = el.closest && el.closest('.mlb-booking-form');
				if (wrap) {
					var wds = wrap.dataset || {};
					for (var j = 0; j < names.length; j++) {
						var key2 = names[j];
						var dsKey2 = key2.replace(/[-_](.)/g, function(m, g) { return g.toUpperCase(); });
						if (wds[dsKey2]) return wds[dsKey2];
						if (wds[key2]) return wds[key2];
					}
				}
				return '';
			} catch (e) { return ''; }
		};
	} catch (e) {}

	// Ensure a form element has a unique id; returns the id string
	try {
		window.mlbEnsureFormId = window.mlbEnsureFormId || function(el) {
			try {
				if (!el) return '';
				if (!window._mlbFormIdCounter) window._mlbFormIdCounter = 1;
				if (!window._mlbFormIdSet) window._mlbFormIdSet = {};
				var id = el.id && String(el.id).trim();
				if (id && !window._mlbFormIdSet[id]) {
					window._mlbFormIdSet[id] = true;
					return id;
				}
				// assign a new unique id
				var newId;
				while (true) {
					newId = 'mlb-form-uid-' + (window._mlbFormIdCounter++);
					if (!window._mlbFormIdSet[newId]) break;
				}
				try { el.id = newId; } catch (e) {}
				window._mlbFormIdSet[newId] = true;
				return newId;
			} catch (e) { return el.id || ''; }
		};
	} catch (e) {}

	// Expose per-form initializer for booking forms. This replaces the previous
	// global document-ready batch initialization to let `form.js` control which
	// modules initialize each form.
	window.initBookingForm = window.initBookingForm || function(formEl) {
		try {
			var $form = (formEl && formEl.jquery) ? formEl : (typeof jQuery !== 'undefined' ? jQuery(formEl) : null);
			if (!$form || !$form.length) {
				if (formEl && formEl.nodeType === Node.ELEMENT_NODE && typeof jQuery !== 'undefined') {
					$form = jQuery(formEl);
				} else {
					return;
				}
			}

			// Ensure form has a unique id so overlays and selectors are unambiguous
			try { if ($form && $form.length && typeof window.mlbEnsureFormId === 'function') { window.mlbEnsureFormId($form[0]); } } catch (e) {}

			// Ensure forms that are special forms with a rate but missing dataset hotel id
			// behave like room forms: try to infer hotel from select, hide selector, and
			// switch to modal date picker flow.
			try{
				var preselected = $form.data('hotel-id') || '';
				var isSpecial = $form.hasClass('mlb-special-form-type');
				var rateId = $form.data('rate-id') || $form.data('special-id') || '';
				if((!preselected || preselected === '') && isSpecial && rateId){
					var native = $form.find('.mlb-hotel-select')[0];
					if(native){
						var selOpt = native.querySelector('option[selected][value]');
						if(!selOpt){
							var opts = Array.from(native.options).filter(function(o){ return o.value && !o.disabled; });
							if(opts.length === 1) selOpt = opts[0];
						}
						if(selOpt && selOpt.value){
							preselected = selOpt.value;
							// set dataset and hidden input
							try{ $form[0].dataset.hotelId = preselected; }catch(e){}
							var existingHidden = $form.find('input[name="hotel_id"]');
							if(!existingHidden.length){
								var h = document.createElement('input'); h.type = 'hidden'; h.name = 'hotel_id'; h.value = preselected; $form[0].appendChild(h);
							} else { existingHidden.val(preselected); }
							// hide the select UI
							$($form.find('.mlb-hotel-select')).hide();
						}
					}
					// If we've inferred a hotel, hide inline daterange and mark button to trigger modal
					if(preselected){
						var dr = $form.find('.mlb-daterange'); if(dr.length) dr.hide();
						var btn = $form.find('.mlb-submit-btn'); if(btn.length) btn.attr('data-trigger-modal','true');
						// Request modal init explicitly
						if(typeof initModalDatePicker === 'function') initModalDatePicker($form);
					}
				}
			}catch(e){ console.error('preselect hotel inference error', e); }

			const isRoomForm = $form.hasClass('mlb-room-form-type') || $form.find('[data-trigger-modal="true"]').length > 0;
			if (isRoomForm) {
				// Room forms use modal date picker
				initModalDatePicker($form);
			} else {
				// Hotel forms use inline date picker
				initInlineDatePicker($form);
			}

			// Per-form handling for buttons that should open modal overlays
			try {
				// Do not attach generic booking-form handlers for special or room-specific forms
				if ($form.hasClass('mlb-special-form-type') || $form.hasClass('mlb-room-form-type')) {
					// These forms initialize their own modal handlers in their specific scripts
				} else {
					$form.find('.mlb-submit-btn').each(function() {
						var $button = jQuery(this);
						try {
							var isModalTrigger = $button.attr && $button.attr('data-trigger-modal') === 'true';
						} catch (e) { isModalTrigger = false; }
						if (isModalTrigger) {
							$button.off('click.mlbOpenModal').on('click.mlbOpenModal', function(e) {
								e.preventDefault();
								var modalOverlay = null;
								try { modalOverlay = $form[0] && $form[0]._mlbModalOverlay ? $form[0]._mlbModalOverlay : null; } catch (e) { modalOverlay = null; }
								if (!modalOverlay) {
									var formId = $form.attr('id') || '';
									modalOverlay = document.querySelector('.mlb-calendar-modal-overlay[data-form-id="' + formId + '"]');
								}
								if (modalOverlay) {
									modalOverlay.classList.add('mlb-calendar-modal-show');
								} else {
									// If overlay not present, trigger the mlb-open-calendar event
									$form.trigger('mlb-open-calendar');
								}
							});
						}
					});
				}
			} catch (e) { /* ignore */ }
		} catch (err) {
			console.error('initBookingForm error', err);
		}
	};

	// Allow external code to request modal picker init for a specific form
	document.addEventListener('mlb-maybe-init-modal', function(e){
		try{
			var form = e && e.detail && e.detail.form;
			if(!form) return;
			if(typeof initModalDatePicker === 'function'){
				initModalDatePicker($(form));
			}
		}catch(err){ console.error('mlb-maybe-init-modal handler error', err); }
	});

	// ========================================================================
	// UTILITY FUNCTIONS
	// ========================================================================

	/**
	 * Format Date object to d-m-Y string
	 */
	function formatDMY(d) {
		const dd = String(d.getDate()).padStart(2, '0');
		const mm = String(d.getMonth() + 1).padStart(2, '0');
		const yyyy = d.getFullYear();
		return dd + '-' + mm + '-' + yyyy;
	}

	/**
	 * Copy CSS variables from source to target element
	 */
	function copyCSSVariables(source, target, variables) {
		const computedStyle = getComputedStyle(source);
		variables.forEach(function(varName) {
			const value = computedStyle.getPropertyValue(varName);
			if (value) {
				target.style.setProperty(varName, value);
			}
		});
	}

	/**
	 * Initialize inline date picker for hotel forms
	 */
	function initInlineDatePicker($form) {
		const formId = $form.attr('id');
		console.debug('[MLB Inline Picker] initInlineDatePicker called for form:', formId);
		const $daterangeInput = $form.find('.mlb-daterange');
		const $checkinHidden = $form.find('.mlb-checkin');
		const $checkoutHidden = $form.find('.mlb-checkout');

		if (!$daterangeInput.length || !$checkinHidden.length || !$checkoutHidden.length) {
			console.error('[MLB Inline Picker] Missing required inputs for form:', formId);
			return;
		}

		if ($form.hasClass('mlb-inline-picker-init')) {
			return;
		}
		$form.addClass('mlb-inline-picker-init');

		let attempts = 0;

		function initPicker() {
			attempts++;
			const easepickRef = window.easepick;

			if (!easepickRef || !easepickRef.Core) {
				if (attempts > 50) {
					console.error('[MLB Inline Picker] EasePick failed to load after 50 attempts');
					return;
				}
				setTimeout(initPicker, 100);
				return;
			}

			const CoreClass = easepickRef.Core || (easepickRef.easepick && easepickRef.easepick.Core) || easepickRef.create;
			if (!CoreClass) {
				console.error('[MLB Inline Picker] CoreClass not found');
				return;
			}

			try {
				// Create or reuse backdrop element by cloning the server-side template
				let $backdrop = $('.mlb-calendar-backdrop[data-form-id="' + formId + '"]');
				if (!$backdrop.length) {
					try {
						var tpl = document.getElementById('mlb-modal-backdrop');
						if (tpl && tpl.content && tpl.content.firstElementChild) {
							var clone = tpl.content.firstElementChild.cloneNode(true);
							clone.setAttribute('data-form-id', formId);
							document.body.appendChild(clone);
							$backdrop = $('.mlb-calendar-backdrop[data-form-id="' + formId + '"]');
						} else {
							// Fallback to creating via DOM API if template not present
							var d = document.createElement('div');
							d.className = 'mlb-calendar-backdrop';
							d.setAttribute('data-form-id', formId);
							document.body.appendChild(d);
							$backdrop = $('.mlb-calendar-backdrop[data-form-id="' + formId + '"]');
						}
					} catch (e) {
						// Last resort fallback
						var d = document.createElement('div');
						d.className = 'mlb-calendar-backdrop';
						d.setAttribute('data-form-id', formId);
						document.body.appendChild(d);
						$backdrop = $('.mlb-calendar-backdrop[data-form-id="' + formId + '"]');
					}

					// Close calendar when clicking backdrop
					$backdrop.on('click.mlbDatepicker', function() {
						if (window.mlbInlinePickers && window.mlbInlinePickers[formId]) {
							window.mlbInlinePickers[formId].hide();
						}
					});
				}

				// Build inline picker configuration
				const pickerConfig = {
					element: $daterangeInput[0],
					css: [
						'https://cdn.jsdelivr.net/npm/@easepick/core@1.2.1/dist/index.css',
						'/wp-content/plugins/mylighthouse-booker/assets/vendor/easepick/easepick.css'
					],
					plugins: [easepickRef.RangePlugin, easepickRef.LockPlugin],
					RangePlugin: {
						tooltip: true,
						locale: {
							one: 'night',
							other: 'nights'
						}
					},
					LockPlugin: {
						minDate: new Date(),
					},
					setup(picker) {
						// Set initial placeholder from per-form custom texts
						if (!$daterangeInput.val() || $daterangeInput.val() === '') {
							const arrivalTxt = $daterangeInput.data('arrival-text') || mlbGettext('Select Arrival Date');
							const departureTxt = $daterangeInput.data('departure-text') || mlbGettext('Select Departure Date');
							$daterangeInput.val(arrivalTxt + ' ⇢ ' + departureTxt);
						}

						// Show backdrop when calendar opens
						picker.on('show', () => {
							$backdrop.addClass('show');
						});

						// Hide backdrop when calendar closes
						picker.on('hide', () => {
							$backdrop.removeClass('show');
						});

						picker.on('select', (e) => {
							const { start, end } = e.detail;
							if (!start || !end) return;

							// Update hidden inputs
							$checkinHidden.val(formatDMY(start));
							$checkoutHidden.val(formatDMY(end));

							// Update visible input
							const startStr = start.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
							const endStr = end.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
							$daterangeInput.val(`${startStr} → ${endStr}`);
						});
					},
					lang: 'nl-NL',
					format: 'DD MMM - DD MMM YYYY',
				};

				const picker = new CoreClass(pickerConfig);
				
				// Store picker reference globally for backdrop click handler
				if (!window.mlbInlinePickers) {
					window.mlbInlinePickers = {};
				}
				window.mlbInlinePickers[formId] = picker;
				console.log('[MLB Inline Picker] Initialized inline picker for form:', formId);

			} catch (error) {
				console.error('[MLB Inline Picker] Error initializing:', error);
			}
		}

		initPicker();
	}

	/**
	 * Legacy modal date picker (kept as fallback). New dispatcher will call
	 * type-specific initializers in `room-form.js` / `special-form.js`.
	 */
	function initModalDatePicker_deprecated($form) {
		const formId = $form.attr('id');
		const $checkinHidden = $form.find('.mlb-checkin');
		const $checkoutHidden = $form.find('.mlb-checkout');
		// Visible daterange input (may be hidden for special forms that use modal)
		const $daterangeInput = $form.find('.mlb-daterange');
		const $bookRoomBtn = $form.find('.mlb-book-room-btn, [data-trigger-modal="true"]');

		if (!$checkinHidden.length || !$checkoutHidden.length) {
			console.error('[MLB Modal Picker] Missing hidden inputs');
			return;
		}
		
		if (!$bookRoomBtn.length) {
			console.error('[MLB Modal Picker] No room booking button found');
			return;
		}
		
		if ($form.hasClass('mlb-modal-picker-init')) {
			return;
		}
		$form.addClass('mlb-modal-picker-init');

		let attempts = 0;

		function initPicker() {
			attempts++;
			const easepickRef = window.easepick;

			if (!easepickRef || !easepickRef.Core) {
				if (attempts > 50) {
					console.error('[MLB Modal Picker] EasePick failed to load after 50 attempts');
					return;
				}
				setTimeout(initPicker, 100);
				return;
			}

			const CoreClass = easepickRef.Core || (easepickRef.easepick && easepickRef.easepick.Core) || easepickRef.create;
			if (!CoreClass) {
				console.error('[MLB Modal Picker] CoreClass not found');
				return;
			}

			try {
				// Create modal from a server-rendered <template> if available, otherwise fall back to cqb_params.modal_template
				let modalOverlay = null;
				try {
					const tpl = document.getElementById('mlb-modal-template-calendar');
					if (tpl && tpl.content && tpl.content.firstElementChild) {
						modalOverlay = tpl.content.firstElementChild.cloneNode(true);
					} else if (typeof cqb_params !== 'undefined' && cqb_params.modal_template) {
						console.debug('[MLB Datepicker] modal_template length:', cqb_params.modal_template ? cqb_params.modal_template.length : 0);
						try {
							var parser = new DOMParser();
							var doc = parser.parseFromString(cqb_params.modal_template || '', 'text/html');
							modalOverlay = doc.body ? doc.body.firstElementChild : doc.firstElementChild;
						} catch (err) {
							console.error('[MLB Datepicker] modal_template parse error', err);
						}
					}
				} catch (err) {
					console.error('[MLB Datepicker] template clone error', err);
				}

				if (!modalOverlay) {
					console.error('[MLB Datepicker] Modal template is empty or missing');
					return;
				}

				modalOverlay.setAttribute('data-form-id', formId);

				const modalContainer = modalOverlay.querySelector('.mlb-calendar-modal-container');
				const closeBtn = modalOverlay.querySelector('.mlb-calendar-modal-close');
				const contentWrapper = modalOverlay.querySelector('.mlb-modal-content-wrapper');
				const calendarDiv = modalOverlay.querySelector('.mlb-modal-calendar');

				// Debug: log existing overlays for this form
				const overlays = document.querySelectorAll('.mlb-calendar-modal-overlay[data-form-id="' + formId + '"]');
				if (overlays && overlays.length) {
					console.debug('[MLB Datepicker] existing overlays count for form', formId, overlays.length);
				}

				// Defensive: clear any stray text or leftover nodes in the calendar container
				// (sometimes inspection tools or template rendering can leave text nodes)
				if (calendarDiv) {
					try{
						console.debug('[MLB Datepicker] calendarDiv before clear:', calendarDiv.innerHTML);
					}catch(e){}
					while (calendarDiv.firstChild) { calendarDiv.removeChild(calendarDiv.firstChild); }
					try{
						console.debug('[MLB Datepicker] calendarDiv after clear:', calendarDiv.innerHTML);
					}catch(e){}
				}
				const rightColumn = modalOverlay.querySelector('.mlb-modal-right-column');
				const bookingDetailsDiv = modalOverlay.querySelector('.mlb-booking-details');
				const modalSubmitBtn = modalOverlay.querySelector('.mlb-modal-submit-btn');

				// Copy form styling to modal
				const formWrapper = $form.closest('.mylighthouse-booking-form');
				if (formWrapper.length) {
					copyCSSVariables(formWrapper[0], modalOverlay, [
						'--mlb-btn-bg',
						'--mlb-btn-text',
						'--mlb-btn-bg-hover',
						'--mlb-btn-text-hover',
						'--mlb-btn-radius',
						'--mlb-button-padding-vertical',
						'--mlb-button-padding-horizontal',
						'--mlb-button-font-size',
						'--mlb-button-font-weight',
						'--mlb-button-text-transform',
						'--mlb-calendar-startend-bg',
						'--mlb-calendar-startend-color',
						'--mlb-calendar-inrange-bg',
					]);
				}

				// Configure modal for room form
				if (contentWrapper) {
					contentWrapper.classList.add('room-form-modal');
					const hotelName = $form.data('hotel-name') || $form.data('hotel-id') || 'Hotel';
					const roomName = $form.data('room-name') || $form.data('room-id') || 'Room';
					const hotelNameSpan = modalOverlay.querySelector('.mlb-hotel-name');
					const roomNameSpan = modalOverlay.querySelector('.mlb-room-name');
					if (hotelNameSpan) hotelNameSpan.textContent = hotelName;
					if (roomNameSpan) roomNameSpan.textContent = roomName;
				}

				// Defensive: if a previous overlay for this form already exists, remove it
				const existingOverlay = document.querySelector('.mlb-calendar-modal-overlay[data-form-id="' + formId + '"]');
				if (existingOverlay && existingOverlay.parentNode) {
					existingOverlay.parentNode.removeChild(existingOverlay);
				}

				document.body.appendChild(modalOverlay);

				// Defensive scrub: remove any stray text nodes that contain date-like strings
				// (e.g. '16 Oct - 16 Oct 2025') which occasionally appear due to
				// template rendering or inspection-tool artifacts.
				setTimeout(function() {
					try {
						const cal = modalOverlay.querySelector('.mlb-modal-calendar');
						if (cal) {
							const nodes = Array.from(cal.childNodes);
							const dateLike = /\d{1,2}\s+[A-Za-z]{3}(?:\s+\d{4})?/;
							nodes.forEach(function(n) {
								if (n.nodeType === Node.TEXT_NODE) {
									const txt = n.textContent.trim();
									if (txt.length && dateLike.test(txt)) {
										n.parentNode.removeChild(n);
										console.debug('[MLB Datepicker] removed stray date text node:', txt);
									}
								}
							});
						}
					} catch (e) {
						// ignore
					}
				}, 0);

				// Modal controls
				function closeModal() {
					modalOverlay.classList.remove('mlb-calendar-modal-show');
				}

				modalOverlay.addEventListener('click', function(e) {
					if (e.target === modalOverlay) closeModal();
				});

				if (closeBtn) {
					closeBtn.addEventListener('click', closeModal);
				}

				const escapeHandler = function(e) {
					if (e.key === 'Escape' && modalOverlay.classList.contains('mlb-calendar-modal-show')) {
						closeModal();
					}
				};
				document.addEventListener('keydown', escapeHandler);
				modalOverlay._escapeHandler = escapeHandler;

				// Show modal on trigger (room forms only)
				$form.on('mlb-open-calendar', function(e) {
					e.preventDefault();
					modalOverlay.classList.add('mlb-calendar-modal-show');
				});

				if ($bookRoomBtn.length) {
					$bookRoomBtn.on('click', function(e) {
						e.preventDefault();
						console.log('[MLB Modal Picker] Opening calendar via book room button for form:', formId);
						modalOverlay.classList.add('mlb-calendar-modal-show');
					});
				}

				// Get calendar colors
				const calendarColors = (typeof cqb_params !== 'undefined' && cqb_params.calendar_colors)
					? cqb_params.calendar_colors
					: {
						startend_bg: '#fb003c',
						startend_color: '#fff',
						inrange_bg: '#e1f0f7'
					};

				// Build picker configuration
				// Create a hidden trigger input inside the calendar container so easepick
				// will update the input's value instead of setting innerText on the
				// calendar container (which created stray formatted date text nodes).
				let pickerElement = calendarDiv;
				if (calendarDiv) {
					try {
						const triggerInput = document.createElement('input');
						triggerInput.type = 'hidden';
						triggerInput.className = 'mlb-picker-trigger-input';
						calendarDiv.appendChild(triggerInput);
						pickerElement = triggerInput;
					} catch (e) {
						// fallback to calendarDiv if anything goes wrong
						pickerElement = calendarDiv;
					}
				}

				const pickerConfig = {
					element: pickerElement,
					css: [
						'https://cdn.jsdelivr.net/npm/@easepick/core@1.2.1/dist/index.css',
						'/wp-content/plugins/mylighthouse-booker/assets/vendor/easepick/easepick.css'
					],
					inline: true,
					plugins: [easepickRef.RangePlugin, easepickRef.LockPlugin],
					RangePlugin: {
						tooltip: true,
						locale: {
							one: 'nacht',
							other: 'nachten'
						}
					},
					LockPlugin: {
						minDate: new Date(),
					},
					setup(picker) {
						// Remove header for room forms
						setTimeout(function() {
							const headerEl = calendarDiv.querySelector('.header');
							if (headerEl) headerEl.remove();
						}, 0);

						picker.on('select', (e) => {
							const { start, end } = e.detail;
							if (!start || !end) return;

							$checkinHidden.val(formatDMY(start));
							$checkoutHidden.val(formatDMY(end));

							// Notify room booking component about date selection
							const dateSelectedEvent = new CustomEvent('mlb-dates-selected', {
									bubbles: true,
									detail: {
										arrivalDate: start,
										departureDate: end,
										bookingDetailsDiv: bookingDetailsDiv,
										rightColumn: rightColumn
									}
								});
								$form[0].dispatchEvent(dateSelectedEvent);

								// Update UI directly (room-booking.js can also handle this)
								if (bookingDetailsDiv) {
									const arrivalStr = start.toLocaleDateString('en-US', {
										day: 'numeric',
										month: 'short',
										year: 'numeric'
									});
									const departureStr = end.toLocaleDateString('en-US', {
										day: 'numeric',
										month: 'short',
										year: 'numeric'
									});

							const arrivalSpan = bookingDetailsDiv.querySelector('.mlb-arrival-date');
							const departureSpan = bookingDetailsDiv.querySelector('.mlb-departure-date');
							if (arrivalSpan) arrivalSpan.textContent = arrivalStr;
							if (departureSpan) departureSpan.textContent = departureStr;

							const hotelName = $form.data('hotel-name') || $form.data('hotel-id') || '';
							const roomName = $form.data('room-name') || $form.data('room-id') || '';
							const hotelNameSpan = bookingDetailsDiv.querySelector('.mlb-hotel-name');
							const roomNameSpan = bookingDetailsDiv.querySelector('.mlb-room-name');
							if (hotelNameSpan) hotelNameSpan.textContent = hotelName;
							if (roomNameSpan) roomNameSpan.textContent = roomName;

							setTimeout(function() {
								if (rightColumn) rightColumn.classList.add('mlb-expanded');
							}, 50);
						}

						if (modalSubmitBtn) modalSubmitBtn.disabled = false;
					});

					// Handle submit button in room form modal
					if (modalSubmitBtn) {
							modalSubmitBtn.disabled = true;
							modalSubmitBtn.addEventListener('click', function() {
								closeModal();
								try {
									var rateId = $form.data('rate-id') || $form.find('input[name="rate"]').val() || $form.find('input[name="special_id"]').val() || '';
									var evt = new CustomEvent('mlb-submit', { bubbles: true, detail: { rate: rateId } });
									$form[0].dispatchEvent(evt);
								} catch (e) {
									// fallback to plain event
									$form[0].dispatchEvent(new Event('mlb-submit'));
								}
							});
						}
				},
				lang: 'en-GB',
			};

			const picker = new CoreClass(pickerConfig);

				// Inject custom CSS into Shadow DOM
				setTimeout(function() {
					const shadowHost = calendarDiv.querySelector('.container');
					if (shadowHost && shadowHost.shadowRoot) {
						const shadowRoot = shadowHost.shadowRoot;
						const customStyle = document.createElement('style');
						customStyle.textContent = `
							.container.range-plugin .calendar > .days-grid > .day.start,
							.container.range-plugin .calendar > .days-grid > .day.end {
								background-color: ${calendarColors.startend_bg} !important;
								color: ${calendarColors.startend_color} !important;
							}
							.container.range-plugin .calendar > .days-grid > .day.in-range {
								background-color: ${calendarColors.inrange_bg} !important;
							}
							.container.range-plugin .calendar > .days-grid > .day.start::after,
							.container.range-plugin .calendar > .days-grid > .day.end::after {
								background-color: ${calendarColors.startend_bg} !important;
							}
						`;
						shadowRoot.appendChild(customStyle);
					}
				}, 100);

				$form.data('picker', picker);
				if ($daterangeInput && $daterangeInput.length) $daterangeInput.data('picker', picker);

			} catch (e) {
				console.error('[MLB Datepicker] Error during initialization:', e);
			}
		}

		initPicker();
	}

	/**
	 * Handle form submission
	 */
	function handleFormSubmission($form) {
		const checkin = $form.find('.mlb-checkin').val();
		const checkout = $form.find('.mlb-checkout').val();
		const hotelId = $form.find('[name="hotel_id"]').val();


		

		$form[0].submit();
	}

})(jQuery);
