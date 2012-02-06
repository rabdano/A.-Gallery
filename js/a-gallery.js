var leftCount = [];
var rightCount = [];
var itemCWidth = [];

jQuery( document ).ready( function( $ ) {
	$( ".a-gallery-left" ).click( function() {
		postId = $( this ).parent().attr( "rel" );
		if ( leftCount[ postId ] > 0 ) {
			$( this ).parent().children( ".a-gallery-container" ).children( ".slider" ).animate( {"left": "+=" + itemCWidth[ postId ] + "px" }, "normal");
			rightCount[ postId ] = rightCount[ postId ] + 1;
			leftCount[ postId ] = leftCount[ postId ] - 1;
		}
	});
	
	$( ".a-gallery-right" ).click( function() {
		postId = $( this ).parent().attr( "rel" );
		if ( rightCount[ postId ] > 0) {
			$( this ).parent().children( ".a-gallery-container" ).children( ".slider" ).animate( {"left": "-=" + itemCWidth[ postId ] + "px"}, "normal");
			rightCount[ postId ] = rightCount[ postId ] - 1;
			leftCount[ postId ] = leftCount[ postId ] + 1;
		}
	});
});