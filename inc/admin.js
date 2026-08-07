/* Hivemind Mixer — admin fields: repeatable rows, media pickers, gallery. */
( function ( $ ) {
	'use strict';

	/* Repeater: add row from template. */
	$( document ).on( 'click', '.hvm-row-add', function () {
		var $rep = $( this ).closest( '.hvm-repeater' );
		var tpl  = $rep.find( '.hvm-row-tpl' ).first().html();
		var idx  = $rep.find( '.hvm-rows .hvm-row' ).length;
		// Give the new row a unique index so names don't collide.
		var html = tpl.replace( /__i__/g, 'n' + Date.now() + idx );
		$rep.find( '.hvm-rows' ).append( html );
	} );

	/* Repeater: remove row. */
	$( document ).on( 'click', '.hvm-row-remove', function () {
		$( this ).closest( '.hvm-row' ).remove();
	} );

	/* Single image picker. */
	$( document ).on( 'click', '.hvm-image-select', function ( e ) {
		e.preventDefault();
		var $wrap = $( this ).closest( '.hvm-image' );
		var frame = wp.media( { title: 'Select image', multiple: false, library: { type: 'image' } } );
		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			var url = ( att.sizes && att.sizes.thumbnail ) ? att.sizes.thumbnail.url : att.url;
			$wrap.find( '.hvm-image-id' ).val( att.id );
			$wrap.find( '.hvm-image-preview' ).attr( 'src', url ).show();
			$wrap.find( '.hvm-image-remove' ).show();
		} );
		frame.open();
	} );

	$( document ).on( 'click', '.hvm-image-remove', function ( e ) {
		e.preventDefault();
		var $wrap = $( this ).closest( '.hvm-image' );
		$wrap.find( '.hvm-image-id' ).val( '' );
		$wrap.find( '.hvm-image-preview' ).attr( 'src', '' ).hide();
		$( this ).hide();
	} );

	/* Gallery picker (multiple). */
	$( document ).on( 'click', '.hvm-gallery-select', function ( e ) {
		e.preventDefault();
		var $wrap = $( this ).closest( '.hvm-gallery' );
		var existing = ( $wrap.find( '.hvm-gallery-ids' ).val() || '' ).split( ',' ).filter( Boolean );
		var frame = wp.media( { title: 'Select gallery images', multiple: 'add', library: { type: 'image' } } );
		frame.on( 'open', function () {
			var sel = frame.state().get( 'selection' );
			existing.forEach( function ( id ) {
				var a = wp.media.attachment( id );
				a.fetch();
				sel.add( a );
			} );
		} );
		frame.on( 'select', function () {
			var ids = [], html = '';
			frame.state().get( 'selection' ).toJSON().forEach( function ( att ) {
				ids.push( att.id );
				var url = ( att.sizes && att.sizes.thumbnail ) ? att.sizes.thumbnail.url : att.url;
				html += '<img src="' + url + '" />';
			} );
			$wrap.find( '.hvm-gallery-ids' ).val( ids.join( ',' ) );
			$wrap.find( '.hvm-gallery-preview' ).html( html );
		} );
		frame.open();
	} );

	$( document ).on( 'click', '.hvm-gallery-clear', function ( e ) {
		e.preventDefault();
		var $wrap = $( this ).closest( '.hvm-gallery' );
		$wrap.find( '.hvm-gallery-ids' ).val( '' );
		$wrap.find( '.hvm-gallery-preview' ).empty();
	} );

	/* Sortable rows if jQuery UI is present. */
	if ( $.fn.sortable ) {
		$( '.hvm-rows' ).sortable( { handle: '.hvm-row-handle', items: '> .hvm-row' } );
	}
} )( jQuery );
