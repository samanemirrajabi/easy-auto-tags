jQuery(document).ready(function() {
	jQuery('body').on('click', '.generate_tags', function() {
		jQuery(this).prop('disabled', true);
		jQuery(".generate_tags .spinner").css({'visibility': 'visible'});
		var check_title = jQuery('.check_title').val(),
		id=jQuery(this).data('id'),
		nonce=jQuery(this).data('nonce');
		if (check_title =='0'){
			jQuery(".results-easy-auto-tags").html('Save your post berfore this action! ');
			jQuery(".generate_tags .spinner").css({'visibility': 'hidden'});
		}
		else{
			jQuery(".results-easy-auto-tags").html('Please be pationet, we are working on it with Google service!');
			jQuery.post(easy_auto_tags_ajax.ajaxurl,{action:"smj_generate_auto_tags_by_google",id:id,_ajax_nonce:nonce},function(response){
				var response=response.substr(0,response.length-1);
				jQuery(".results-easy-auto-tags").html(response);
				jQuery(".generate_tags .spinner").css({'visibility': 'hidden'});
				jQuery('.generate_tags').removeAttr('disabled');
			});
		}
	});

	jQuery('body').on('click', '.add_tags .dashicons-plus', function() {
		var position = jQuery(this).position();
		jQuery('.overly').css({
			'left' : 0,
			'top' :  (position.top-7)+"px",
		}).show().delay(1000).fadeOut();
		jQuery(this).removeClass('dashicons-plus').addClass('dashicons-yes');
		var tag_name = jQuery(this).parents('tr').find('.tag').html(),
		post_id = jQuery(this).parent().attr('id'),
		array_id=jQuery(this).data('id'),
		nonce=jQuery(this).parents('table').data('nonce');
		jQuery.post(easy_auto_tags_ajax.ajaxurl,{action:"smj_save_easy_auto_tags",tag_name:tag_name,post_id:post_id,array_id:array_id,_ajax_nonce:nonce},function(response){
			var response=response.substr(0,response.length-1);
			jQuery('#new-tag-post_tag').val(tag_name);
			jQuery('.tagadd').trigger('click');
		});
	});
});

