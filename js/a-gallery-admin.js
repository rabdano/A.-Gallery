jQuery(document).ready(function($) {
	
	$('#ag-sortable .close').click(function(){
		$(this).parent().fadeOut("normal", function() { $(this).remove(); } );
	});
	
	$(function() {
		$( "#ag-sortable" ).sortable({
			stop: function(){
				var inputs = $('.ui-state-default input[type="hidden"]');
				for (var i=0; i < inputs.length; i++) {
					var currentName = $(inputs[i]).attr('name');
					var currentCount = $(inputs[i]).attr('rel');
					var newName = currentName.replace(currentCount,i);
					$(inputs[i]).attr('name',newName);
					$(inputs[i]).attr('rel',i);
				};
			}
		});
	});
	
	$('#media-upload .savebutton #save').click(function(){
		parent.eval('tb_remove();location.reload(true);');
	});
	
});