jQuery(document).ready(function($) {
	// = Sort screen = //
	$( 'body' ).delegate( "#ag-sortable", 'mouseenter', function() {
		$( "#ag-sortable" ).sortable(
			{
			placeholder: "item ui-state-default my-placeholder",
		    start: function(event, ui) {
		        $(".my-placeholder").css( "width", ui.item.css("width") );
		    },
			stop: function() {
				var newOrderArray = $( "#ag-sortable .item" ).map( function() {
  					return $( this ).attr( "id" );
				}).toArray();
				// save new order
				var newOrderString = newOrderArray.join( "," );
				$( "#ag-sortable" ).attr( "rel", newOrderString );			
			}
		});
	});
	
	$( 'body' ).delegate( '#ag-sortable .close', 'click', function() {
		// hide item
		$( this ).parent().fadeOut( "fast" );
		// update order
		var oldOrderArray = $( "#ag-sortable" ).attr( "rel" ).split( "," );
		var idToRemove = $( this ).parent().attr( "id" );
		var idx = oldOrderArray.indexOf( idToRemove );
		oldOrderArray.splice( idx, 1 );
		// save new order
		var newOrderString = oldOrderArray.join( "," );
		$( "#ag-sortable" ).attr( "rel", newOrderString );
		$( this ).parent().remove();
		return false;
	});
	
	$( 'body' ).delegate( '#ag-save-state', 'click', function() {
		var oldLink = $( this ).attr( "rel" );
		var order = $( "#ag-sortable" ).attr( "rel" );
		if ( order == undefined ) order = "";
		$( this ).attr( "rel", oldLink.toString() + order.toString() + "&" );
		$( '#ag_detach_box .inside' ).slideUp(300);
		$.get( $( this ).attr( "rel" ), function( data ) {
			$( '#ag_detach_box .inside' ).html( data );
			$( '#ag_detach_box .inside' ).slideDown(300);
		});
		return false;
	});
	
	$( 'body' ).delegate( '#ag-add-images', 'click', function() {
		$( '#ag_detach_box .inside' ).slideUp(300);
		$.get( $( this ).attr( "rel" ), function( data ) {
			$( '#ag_detach_box .inside' ).html( data );
			$( '#ag_detach_box .inside' ).slideDown(300);
		});
		return false;
	});

	
	// = Add screen = //
	$( 'body' ).delegate( '#ag-add .ag-attachment-desription a.button', 'click', function() {
		// hide item
		$( this ).parent().parent().fadeOut( "fast" );
		// update order
		var oldOrderString = $( "#ag-add" ).attr( "rel" );
		if ( oldOrderString === "" ) {
			$( "#ag-add" ).attr( "rel", $(this).attr('id') );
		} else {
			var oldOrderArray = oldOrderString.split( "," );
			oldOrderArray.push( $(this).attr('id') );
			var newOrderString = oldOrderArray.join( "," );
			$( "#ag-add" ).attr( "rel", newOrderString );
		}
		return false;
	});
	
	$( 'body' ).delegate( '#ag-update-state', 'click', function() {
		var oldLink = $( this ).attr( "rel" );
		var order = $( "#ag-add" ).attr( "rel" );
		if ( order == undefined ) order = "";
		$( this ).attr( "rel", oldLink.toString() + order.toString() + "&" );
		$( '#ag_detach_box .inside' ).slideUp(300);
		$.get( $( this ).attr( "rel" ), function( data ) {
			$( '#ag_detach_box .inside' ).html( data );
			$( '#ag_detach_box .inside' ).slideDown(300);
		});
		return false;
	});
});