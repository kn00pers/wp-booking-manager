
( function ( $ ) {
	'use strict';

	const cfg = cbmAdmin;

	function doAction( action, extraData, onSuccess ) {
		$.post( cfg.ajaxUrl, Object.assign( { action, nonce: cfg.nonce }, extraData ), function ( res ) {
			if ( res.success ) {
				showNotice( res.message, 'success' );
				if ( typeof onSuccess === 'function' ) onSuccess( res );
			} else {
				showNotice( res.message || cfg.i18n.errorGeneral, 'error' );
			}
		} ).fail( function () {
			showNotice( cfg.i18n.connectionError, 'error' );
		} );
	}

	function showNotice( msg, type ) {
		const el = $( '#cbm-notice' );
		if ( ! el.length ) return;
		const cls = type === 'success' ? 'notice-success' : 'notice-error';
		el.html( '<div class="notice ' + cls + ' is-dismissible"><p>' + escHtml( msg ) + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">' + escHtml( cfg.i18n.dismiss ) + '</span></button></div>' );
		el.find( '.notice-dismiss' ).on( 'click', function () { $( this ).closest( '.notice' ).remove(); } );
		$( 'html,body' ).animate( { scrollTop: el.offset().top - 40 }, 200 );
	}

	function escHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}
	$( document ).on( 'click', '.cbm-approve', function () {
		if ( ! confirm( cfg.i18n.confirmApprove ) ) return;
		const id  = $( this ).data( 'id' );
		const row = $( this ).closest( 'tr' );
		doAction( 'cbm_approve_booking', { id }, function () {
			row.find( '.cbm-status' ).removeClass().addClass( 'cbm-status cbm-status--approved' ).text( cfg.i18n.statusApproved );
			row.find( '.cbm-approve, .cbm-reject' ).remove();
		} );
	} );
	let rejectId = null;

	$( document ).on( 'click', '.cbm-reject', function () {
		rejectId = $( this ).data( 'id' );
		$( '#cbm-reject-note' ).val( '' );
		$( '#cbm-reject-modal' ).show();
	} );

	$( '#cbm-reject-cancel' ).on( 'click', function () {
		$( '#cbm-reject-modal' ).hide();
		rejectId = null;
	} );

	$( '#cbm-reject-confirm' ).on( 'click', function () {
		if ( ! rejectId ) return;
		const note = $( '#cbm-reject-note' ).val();
		const row  = $( '.cbm-reject[data-id="' + rejectId + '"]' ).closest( 'tr' );
		doAction( 'cbm_reject_booking', { id: rejectId, note }, function () {
			row.find( '.cbm-status' ).removeClass().addClass( 'cbm-status cbm-status--rejected' ).text( cfg.i18n.statusRejected );
			row.find( '.cbm-approve, .cbm-reject' ).remove();
		} );
		$( '#cbm-reject-modal' ).hide();
		rejectId = null;
	} );
	$( document ).on( 'click', '.cbm-delete', function () {
		if ( ! confirm( cfg.i18n.confirmDelete ) ) return;
		const id  = $( this ).data( 'id' );
		const row = $( this ).closest( 'tr' );
		doAction( 'cbm_delete_booking', { id }, function () {
			row.addClass( 'cbm-row--deleted' );
			row.find( '.cbm-delete, .cbm-approve, .cbm-reject' ).remove();
		} );
	} );
	$( document ).on( 'click', '.cbm-restore', function () {
		if ( ! confirm( cfg.i18n.confirmRestore ) ) return;
		const id = $( this ).data( 'id' );
		doAction( 'cbm_restore_booking', { id }, function () { location.reload(); } );
	} );
	$( document ).on( 'click', '.cbm-delete-block', function () {
		if ( ! confirm( cfg.i18n.confirmDeleteBlock ) ) return;
		const id  = $( this ).data( 'id' );
		const row = $( this ).closest( 'tr' );
		doAction( 'cbm_delete_unavailability', { id }, function () { row.remove(); } );
	} );
	$( '#cbm-unavail-form' ).on( 'submit', function ( e ) {
		e.preventDefault();
		const data = {
			action:      'cbm_save_unavailability',
			nonce:       cfg.nonce,
			resource_id: $( '#cbm-unavail-resource' ).val(),
			date_from:   $( '#cbm-unavail-from' ).val(),
			date_to:     $( '#cbm-unavail-to' ).val(),
			time_from:   $( '#cbm-unavail-time-from' ).val(),
			time_to:     $( '#cbm-unavail-time-to' ).val(),
			reason:      $( '#cbm-unavail-reason' ).val(),
		};
		$.post( cfg.ajaxUrl, data, function ( res ) {
			showNotice( res.message, res.success ? 'success' : 'error' );
			if ( res.success ) { $( '#cbm-unavail-form' )[ 0 ].reset(); location.reload(); }
		} );
	} );
	$( document ).on( 'submit', '#cbm-edit-form', function ( e ) {
		e.preventDefault();
		const $form = $( this );
		const $btn  = $form.find( '[type=submit]' );

		if ( $btn.prop( 'disabled' ) ) return;
		$btn.prop( 'disabled', true ).text( cfg.i18n.saving );

		const formData = $form.serializeArray();
		const data     = { action: 'cbm_admin_save_booking', nonce: cfg.saveNonce };
		formData.forEach( function ( f ) { data[ f.name ] = f.value; } );

		$.post( cfg.ajaxUrl, data, function ( res ) {
			showNotice( res.message, res.success ? 'success' : 'error' );
			$btn.prop( 'disabled', false ).text( data.booking_id ? cfg.i18n.saveChanges : cfg.i18n.addBooking );
			if ( res.success && ! data.booking_id ) {
				setTimeout( function () {
					window.location.href = cfg.ajaxUrl.replace( 'admin-ajax.php', '' ) + 'admin.php?page=cbm-bookings';
				}, 1200 );
			}
		} ).fail( function () {
			showNotice( cfg.i18n.connectionError, 'error' );
			$btn.prop( 'disabled', false ).text( data.booking_id ? cfg.i18n.saveChanges : cfg.i18n.addBooking );
		} );
	} );
	$( '#cbm-export-csv' ).on( 'click', function () {
		const form = document.createElement( 'form' );
		form.method = 'POST';
		form.action = cfg.ajaxUrl;
		const fields = {
			action:    'cbm_export_csv',
			nonce:     cfg.exportNonce,
			status:    $( '[name=status]' ).val() || '',
			date_from: $( '[name=date_from]' ).val() || '',
			date_to:   $( '[name=date_to]' ).val() || '',
		};
		Object.keys( fields ).forEach( function ( k ) {
			const inp = document.createElement( 'input' );
			inp.type  = 'hidden';
			inp.name  = k;
			inp.value = fields[ k ];
			form.appendChild( inp );
		} );
		document.body.appendChild( form );
		form.submit();
		document.body.removeChild( form );
	} );

} )( jQuery );
