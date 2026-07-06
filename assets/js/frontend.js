
( function () {
	'use strict';

	const cfg      = cbmFrontend;
	const wrapper  = document.getElementById( 'cbm-booking-wrapper' );
	if ( ! wrapper ) return;

	const resourceId   = parseInt( wrapper.dataset.resourceId, 10 ) || 1;
	const form         = document.getElementById( 'cbm-booking-form' );
	const dateInput    = document.getElementById( 'cbm-booking-date' );
	const slotsWrapper = document.getElementById( 'cbm-slots-wrapper' );
	const timeFrom     = document.getElementById( 'cbm-time-from' );
	const timeTo       = document.getElementById( 'cbm-time-to' );
	const toWrapper    = document.getElementById( 'cbm-time-to-wrapper' );
	const submitBtn    = document.getElementById( 'cbm-submit' );
	const msgBox       = document.getElementById( 'cbm-form-message' );
	const successBox   = document.getElementById( 'cbm-success-message' );
	const successText  = document.getElementById( 'cbm-success-text' );
	const tokenInput   = document.getElementById( 'cbm-submit-token' );
	const overlay      = document.getElementById( 'cbm-modal-overlay' );
	const modal        = document.getElementById( 'cbm-modal' );
	const modalClose   = document.getElementById( 'cbm-modal-close' );
	const modalDate    = document.getElementById( 'cbm-modal-date' );

	function newToken() {
		return Math.random().toString( 36 ).slice( 2 ) + Date.now();
	}
	tokenInput.value = newToken();

	let availableSlots = [];
	let busyRanges  = [];
	let workEnd     = '';
	let slotMinutes = 60;
	let dayStatus = {};
	let fp        = null;

	function toMinutes( hhmm ) {
		const p = hhmm.split( ':' ).map( Number );
		return p[ 0 ] * 60 + p[ 1 ];
	}

	function toHHMM( mins ) {
		return String( Math.floor( mins / 60 ) ).padStart( 2, '0' ) + ':' + String( mins % 60 ).padStart( 2, '0' );
	}

	function addParams( url, params ) {
		const separator = url.indexOf( '?' ) !== -1 ? '&' : '?';
		const qs = Object.keys( params )
			.map( function ( k ) {
				return encodeURIComponent( k ) + '=' + encodeURIComponent( params[ k ] );
			} )
			.join( '&' );
		return url + separator + qs;
	}

	function formatDate( d ) {
		const y   = d.getFullYear();
		const m   = String( d.getMonth() + 1 ).padStart( 2, '0' );
		const day = String( d.getDate() ).padStart( 2, '0' );
		return y + '-' + m + '-' + day;
	}

	function formatDatePL( d ) {
		const day = String( d.getDate() ).padStart( 2, '0' );
		const m   = String( d.getMonth() + 1 ).padStart( 2, '0' );
		return day + '.' + m + '.' + d.getFullYear();
	}

	let lastFocus = null;

	function openModal() {
		lastFocus = document.activeElement;
		overlay.style.display     = 'flex';
		document.body.style.overflow = 'hidden';
		modalClose.focus();
	}

	function closeModal() {
		overlay.style.display        = 'none';
		document.body.style.overflow = '';
		if ( fp ) {
			fp.clear();
		}
		if ( lastFocus && typeof lastFocus.focus === 'function' ) {
			lastFocus.focus();
		}
	}

	function resetForm() {
		form.reset();
		clearErrors();
		hideMessage();
		successBox.style.display = 'none';
		form.style.display       = '';
		submitBtn.disabled       = false;
		submitBtn.textContent    = cfg.i18n.submit;
		tokenInput.value         = newToken();
		slotsWrapper.style.display = 'none';
		toWrapper.style.display    = 'none';
	}

	function initFlatpickr() {
		fp = flatpickr( dateInput, {
			inline:     true,
			dateFormat: 'Y-m-d',
			minDate:    'today',
			locale: {
				firstDayOfWeek: cfg.calendar.firstDay,
				weekdays: {
					shorthand: cfg.calendar.weekdaysShort,
					longhand:  cfg.calendar.weekdaysLong,
				},
				months: {
					shorthand: cfg.calendar.monthsShort,
					longhand:  cfg.calendar.monthsLong,
				},
			},
			disable: [
				function ( date ) {
					const s = dayStatus[ formatDate( date ) ];
					return s === 'disabled' || s === 'past' || s === 'fully_booked';
				},
			],
			onDayCreate: function ( dObj, dStr, fpInst, dayElem ) {
				const date = formatDate( dayElem.dateObj );
				const s    = dayStatus[ date ];
				if ( s ) {
					dayElem.classList.add( 'cbm-day--' + s );
				}
			},
			onMonthChange: function ( dObj, dStr, fpInst ) {
				const year  = fpInst.currentYear;
				const month = String( fpInst.currentMonth + 1 ).padStart( 2, '0' );
				fetchMonthStatus( year + '-' + month ).then( function () {
					fpInst.redraw();
				} );
			},
			onChange: function ( selectedDates ) {
				if ( ! selectedDates.length ) return;
				const d      = selectedDates[ 0 ];
				const status = dayStatus[ formatDate( d ) ];
				if ( status !== 'available' && status !== 'partial' ) return;
				const dateStr = formatDate( d );
				resetForm();
				dateInput.value       = dateStr;
				modalDate.textContent = cfg.i18n.bookingOn.replace( '%s', formatDatePL( d ) );
				openModal();
				fetchAvailability( dateStr );
			},
		} );
	}

	function fetchMonthStatus( month ) {
		const url = addParams( cfg.endpoints.monthStatus, { resource_id: resourceId, month: month } );
		return fetch( url )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) {
				if ( data.days && typeof data.days === 'object' ) {
					Object.assign( dayStatus, data.days );
				}
			} )
			.catch( function () {} );
	}

	function fetchAvailability( date ) {
		slotsWrapper.style.display = 'block';
		toWrapper.style.display    = 'none';
		timeFrom.innerHTML         = '<option value="">' + cfg.i18n.loading + '</option>';
		timeFrom.disabled          = true;
		timeTo.innerHTML           = '';
		timeTo.disabled            = true;

		fetch( addParams( cfg.endpoints.availability, { resource_id: resourceId, date: date } ) )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) {
				availableSlots = data.available || [];
				busyRanges     = data.busy_ranges || [];
				workEnd        = data.work_end || '';
				slotMinutes    = data.slot_minutes || 60;

				if ( data.day_blocked || availableSlots.length === 0 ) {
					timeFrom.innerHTML = '<option value="">' + cfg.i18n.noSlots + '</option>';
					timeFrom.disabled  = true;
					return;
				}

				timeFrom.innerHTML = '<option value="">' + cfg.i18n.chooseFrom + '</option>';
				availableSlots.forEach( function ( slot ) {
					const opt       = document.createElement( 'option' );
					opt.value       = slot;
					opt.textContent = slot;
					timeFrom.appendChild( opt );
				} );
				timeFrom.disabled = false;
			} )
			.catch( function () {
				timeFrom.innerHTML = '<option value="">' + cfg.i18n.loadError + '</option>';
				timeFrom.disabled  = true;
			} );
	}

	timeFrom.addEventListener( 'change', function () {
		const selectedFrom = this.value;
		timeTo.innerHTML   = '';
		timeTo.disabled    = true;
		toWrapper.style.display = 'none';

		if ( ! selectedFrom ) return;

		const fromMins = toMinutes( selectedFrom );
		let maxEndMins = workEnd ? toMinutes( workEnd ) : fromMins;

		busyRanges.forEach( function ( range ) {
			const busyFrom = toMinutes( range.from );
			if ( busyFrom >= fromMins && busyFrom < maxEndMins ) {
				maxEndMins = busyFrom;
			}
		} );
		toWrapper.style.display = 'block';

		const minDuration = 180;
		if ( maxEndMins < fromMins + minDuration ) {
			timeTo.innerHTML = '<option value="">' + cfg.i18n.noEndAvailable + '</option>';
			timeTo.disabled  = true;
			return;
		}

		timeTo.innerHTML = '<option value="">' + cfg.i18n.chooseTo + '</option>';
		for ( let m = fromMins + minDuration; m <= maxEndMins; m += slotMinutes ) {
			const label     = toHHMM( m );
			const opt       = document.createElement( 'option' );
			opt.value       = label;
			opt.textContent = label;
			timeTo.appendChild( opt );
		}

		timeTo.disabled = false;
	} );

	modalClose.addEventListener( 'click', closeModal );

	overlay.addEventListener( 'click', function ( e ) {
		if ( e.target === overlay ) closeModal();
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && overlay.style.display !== 'none' ) closeModal();
	} );

	overlay.addEventListener( 'keydown', function ( e ) {
		if ( e.key !== 'Tab' ) return;
		const nodes = modal.querySelectorAll( 'button, [href], input:not([type=hidden]), select, textarea, [tabindex]:not([tabindex="-1"])' );
		const list  = Array.prototype.filter.call( nodes, function ( el ) {
			return ! el.disabled && el.offsetParent !== null;
		} );
		if ( ! list.length ) return;
		const first = list[ 0 ];
		const last  = list[ list.length - 1 ];
		if ( e.shiftKey && document.activeElement === first ) {
			e.preventDefault();
			last.focus();
		} else if ( ! e.shiftKey && document.activeElement === last ) {
			e.preventDefault();
			first.focus();
		}
	} );

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		clearErrors();

		if ( ! validateForm() ) return;

		submitBtn.disabled    = true;
		submitBtn.textContent = cfg.i18n.sending;
		hideMessage();

		const payload = {
			resource_id:      parseInt( form.querySelector( '[name=resource_id]' ).value, 10 ),
			customer_name:    form.querySelector( '[name=customer_name]' ).value.trim(),
			customer_phone:   form.querySelector( '[name=customer_phone]' ).value.trim(),
			customer_email:   form.querySelector( '[name=customer_email]' ).value.trim(),
			customer_company: form.querySelector( '[name=customer_company]' ).value.trim(),
			location:         form.querySelector( '[name=location]' ).value.trim(),
			booking_date:     dateInput.value,
			time_from:        timeFrom.value,
			time_to:          timeTo.value,
			notes:            form.querySelector( '[name=notes]' ).value.trim(),
			submit_token:     tokenInput.value,
			cf_turnstile_response: form.querySelector( '[name="cf-turnstile-response"]' ) ? form.querySelector( '[name="cf-turnstile-response"]' ).value : '',
		};

		fetch( cfg.endpoints.bookings, {
			method:  'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce':   cfg.nonce,
			},
			body: JSON.stringify( payload ),
		} )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) {
				if ( data.success ) {
					form.style.display       = 'none';
					successBox.style.display = 'block';
					successText.textContent  = data.message;
				} else {
					showMessage( data.message || cfg.i18n.errorGeneral, 'error' );
					submitBtn.disabled    = false;
					submitBtn.textContent = cfg.i18n.submit;
					tokenInput.value = newToken();
				}
			} )
			.catch( function () {
				showMessage( cfg.i18n.errorGeneral, 'error' );
				submitBtn.disabled    = false;
				submitBtn.textContent = cfg.i18n.submit;
			} );
	} );

	function validateForm() {
		let valid = true;

		if ( ! form.querySelector( '[name=customer_name]' ).value.trim() ) {
			setError( 'customer_name', cfg.i18n.nameRequired );
			valid = false;
		}
		if ( ! form.querySelector( '[name=customer_phone]' ).value.trim() ) {
			setError( 'customer_phone', cfg.i18n.phoneRequired );
			valid = false;
		}
		const email = form.querySelector( '[name=customer_email]' ).value.trim();
		if ( ! email || ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email ) ) {
			setError( 'customer_email', cfg.i18n.emailInvalid );
			valid = false;
		}
		if ( ! form.querySelector( '[name=location]' ).value.trim() ) {
			setError( 'location', cfg.i18n.locationRequired );
			valid = false;
		}
		if ( ! timeFrom.value ) {
			setError( 'time_from', cfg.i18n.timeFromRequired );
			valid = false;
		}
		if ( ! timeTo.value ) {
			setError( 'time_to', cfg.i18n.timeToRequired );
			valid = false;
		}
		return valid;
	}

	function setError( field, msg ) {
		const el = document.getElementById( 'cbm-error-' + field );
		if ( el ) el.textContent = msg;
	}

	function clearErrors() {
		document.querySelectorAll( '.cbm-field__error' ).forEach( function ( el ) {
			el.textContent = '';
		} );
	}

	function showMessage( text, type ) {
		msgBox.textContent   = text;
		msgBox.className     = 'cbm-message cbm-message--' + type;
		msgBox.style.display = 'block';
	}

	function hideMessage() {
		msgBox.style.display = 'none';
	}

	const now   = new Date();
	const month = now.getFullYear() + '-' + String( now.getMonth() + 1 ).padStart( 2, '0' );

	fetchMonthStatus( month ).then( initFlatpickr );

} )();
