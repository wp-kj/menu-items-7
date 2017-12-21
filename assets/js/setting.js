function upperCaseFirst(str){
    return str.charAt(0).toUpperCase() + str.substring(1);
}

function renderItemsOnBackend(selectedItemDay){
	jQuery.ajax({
		url: MI7AJAX.ajaxurl+'?action=mi7_render_items_on_backend&is_ajax=true&item_day='+selectedItemDay,
		dataType: 'json',			
		success: function(resp){
			//selectedItemDay - strtolower
			jQuery('#'+selectedItemDay.toLowerCase()+'-item').find('.mi7_col').html(resp.html);
		}
	});
}

jQuery(document).ready(function(){
	jQuery(document).on("click",".mi7_add",function(){
		//Add modal
		var $this = jQuery(this);
		var parentWrapper = $this.parent().attr('id').split('-');
		var selectedItemDay = upperCaseFirst(parentWrapper[0]);
		jQuery.ajax({
			method: "GET",
			dataType: 'json',
			url: MI7AJAX.ajaxurl,
			data: { 'action': 'mi7_load_item', 'selectedDay': selectedItemDay},
			success: function(resp) {
				var opt = {
					autoOpen: false,
					modal: true,
					width: 350,
					height:190,
					title: MI7AJAX.menu_item + ' '+ selectedItemDay
				};
				jQuery('#load_item').html(resp.output).dialog(opt).dialog('open');
				jQuery(".mi7_category_combo").autocomplete({
					source: MI7AJAX.ajaxurl+'?action=mi7_get_categories',
					minLength: 3
				});
				
				jQuery(".mi7_category_combo").on("autocompleteselect",function(e,ui){
					var selectedCategory = jQuery(this);
					jQuery.ajax({
						url: MI7AJAX.ajaxurl+'?action=mi7_get_items&cat='+ui.item.value+'&item_day='+selectedItemDay,
						dataType: 'json',			
						beforeSend: function() {
							selectedCategory.addClass("ui-autocomplete-loading");
						},
						success: function(resp){
							jQuery('#item').val(resp.items);
							selectedCategory.removeClass("ui-autocomplete-loading");
						}
					});
				});
				
				jQuery(".mi7_item_combo").autocomplete({
					source: MI7AJAX.ajaxurl+'?action=mi7_get_items',
					minLength: 3
				});
				
				jQuery("#load_item_form").validate({
					errorPlacement: function (error, element) {
                        if (element.attr("name") === undefined) {
                            error.insertAfter("#load_item_resp");
                        } else {
                            error.insertAfter(element.parent());
                        }
                    },
					submitHandler:function(form) {
						var _data = { 'action' : 'mi7_save_item'}
						jQuery(form).ajaxSubmit({
							dataType: 'json',
							data: _data,
							success: function(err) {
								if(err.error == false) {
									jQuery("#load_item_resp").html(MI7AJAX.saveitemmsg).fadeIn('ease').fadeOut(2000);
									renderItemsOnBackend(selectedItemDay,$this.parent());
								}
							}
						});
					}
				});				
			}
		});
		return false;
	});
	
	jQuery(document).on('mouseenter','.menu_items li',function(){
		jQuery(this).find("span").show();
	}).on('mouseleave','.menu_items li',function(){
		jQuery(this).find("span").hide();
	});
	
	jQuery(document).on('click','.item-remove',function(e){
		var itemId = jQuery(this).attr('id').split('-');
		//itemId[1];
		jQuery.ajax({
			url: MI7AJAX.ajaxurl+'?action=mi7_delete_item&item_day='+itemId[0]+'&item_id='+itemId[1],
			dataType: 'json',			
			success: function(resp){
				if(resp.error == false) {
					renderItemsOnBackend(itemId[0]);
				}
			}
		});		
		return false;
	});
});