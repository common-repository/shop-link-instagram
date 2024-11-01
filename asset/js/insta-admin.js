jQuery(document).ready(function($) {

	//Autofill the token and id
	var hash = window.location.hash,
        token = hash.substring(14),
        id = token.split('.')[0];
    //If there's a hash then autofill the token and id
    if(hash){
        $('#insta_feed_config').append('<div id="insta_feed_config_info"><p><b>Access Token: </b><input type="text" size=58 readonly value="'+token+'" onclick="this.focus();this.select()" title="To copy, click the field then press Ctrl + C (PC) or Cmd + C (Mac)."></p><p><b>User ID: </b><input type="text" size=12 readonly value="'+id+'" onclick="this.focus();this.select()" title="To copy, click the field then press Ctrl + C (PC) or Cmd + C (Mac)."></p><p><i class="fa fa-clipboard" aria-hidden="true"></i>&nbsp; <b><span style="color: red;">Important:</span> Copy and paste</b> these into the fields below and click <b>"Save Changes"</b>.</p></div>');
    }

	//Tooltips
	jQuery('#insta_feed_admin .insta_feed_tooltip_link').click(function(){
		jQuery(this).siblings('.insta_feed_tooltip').slideToggle();
	});

	//Shortcode labels
	jQuery('#insta_feed_admin label').click(function(){
    var $insta_feed_shortcode = jQuery(this).siblings('.insta_feed_shortcode');
    if($insta_feed_shortcode.is(':visible')){
      jQuery(this).siblings('.insta_feed_shortcode').css('display','none');
    } else {
      jQuery(this).siblings('.insta_feed_shortcode').css('display','block');
    }
  });
  jQuery('#insta_feed_admin label').hover(function(){
    if( jQuery(this).siblings('.insta_feed_shortcode').length > 0 ){
      jQuery(this).attr('title', 'Click for shortcode option').append('<code class="insta_feed_shortcode_symbol">[]</code>');
    }
  }, function(){
    jQuery(this).find('.insta_feed_shortcode_symbol').remove();
  });


  //Add the color picker
	if( jQuery('.insta_feed_colorpick').length > 0 ) jQuery('.insta_feed_colorpick').wpColorPicker();

	//Check User ID is numeric
	jQuery("#sb_instagram_user_id").change(function() {

		var insta_feed_user_id = jQuery('#sb_instagram_user_id').val(),
			$insta_feed_user_id_error = $(this).closest('td').find('.insta_feed_user_id_error');

		if (insta_feed_user_id.match(/[^0-9, _.-]/)) {
  			$insta_feed_user_id_error.fadeIn();
  		} else {
  			$insta_feed_user_id_error.fadeOut();
  		}

	});

	//Mobile width
	var sb_instagram_feed_width = jQuery('#insta_feed_admin #sb_instagram_width').val(),
			sb_instagram_width_unit = jQuery('#insta_feed_admin #sb_instagram_width_unit').val(),
			$sb_instagram_width_options = jQuery('#insta_feed_admin #sb_instagram_width_options');

	if (typeof sb_instagram_feed_width !== 'undefined') {

		//Show initially if a width is set
		if( (sb_instagram_feed_width.length > 1 && sb_instagram_width_unit == 'px') || (sb_instagram_feed_width !== '100' && sb_instagram_width_unit == '%') ) $sb_instagram_width_options.show();

		jQuery('#insta_feed_admin #sb_instagram_width, #insta_feed_admin #sb_instagram_width_unit').change(function(){
			sb_instagram_feed_width = jQuery('#insta_feed_admin #sb_instagram_width').val();
			sb_instagram_width_unit = jQuery('#insta_feed_admin #sb_instagram_width_unit').val();

			if( sb_instagram_feed_width.length < 2 || (sb_instagram_feed_width == '100' && sb_instagram_width_unit == '%') ) {
				$sb_instagram_width_options.slideUp();
			} else {
				$sb_instagram_width_options.slideDown();
			}
		});

	}

	//Scroll to hash for quick links
  jQuery('#insta_feed_admin a').click(function() {
    if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
      var target = jQuery(this.hash);
      target = target.length ? target : this.hash.slice(1);
      if (target.length) {
        jQuery('html,body').animate({
          scrollTop: target.offset().top
        }, 500);
        return false;
      }
    }
  });



    jQuery('#update-feed').click(function () {
        //var lastID= 5;
        var feed_status = '';
        if(jQuery("input[name=feed_status]").is(':checked') == true){ feed_status = 1; };
        $.ajax({
            type:'POST',
            url: my_custom_admin_script.ajaxurl,
            async: false,
            data:{action: "UpdateInstImage",id:jQuery("input[name=feed_id]").val(),
            feed_status:feed_status,
            feed_link:jQuery("input[name=feed_link]").val()
            },
            beforeSend:function(html){
                $('.load-more').show();
            },
            success:function(html){
                 jQuery('#feed-update-form').before(html);
                 window.location = '?page=isl-feed-list&&paged='+getUrlParameter('paged');
            }
        });
    })


    jQuery('.admin_add_shop_link').click(function () {

        var formid = 'admin_add_shop_link'+jQuery(this).attr('data-id');
        jQuery(this).hide();
        jQuery('#'+formid).show();

    })


    jQuery('.admin_add_shop_link_submit').click(function () {

       var id = jQuery(this).attr('data-id');
       var field = 'feed-link-text'+id;
       var value = jQuery("input[name="+field+"]").val();


       if ( value == '' ) {
           jQuery("#admin_add_shop_link"+id).prepend('<span class="isl-ad-error">Please enter url</span>');
       } else {

           $.ajax({
               type:'POST',
               url: my_custom_admin_script.ajaxurl,
               async: false,
               data:{action: "UpdateInstImageLink",
                    id:id,
                    feed_link:value
               },
               success:function(html){
                  jQuery("#admin_add_shop_link"+id).html(html);
                   window.location = '?page=isl-feed-list&paged='+getUrlParameter('paged');
               }
           });

       }
    })


    var getUrlParameter = function getUrlParameter(sParam) {
        var sPageURL = decodeURIComponent(window.location.search.substring(1)),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;

        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');

            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : sParameterName[1];
            }
        }
    };

    jQuery('.admin_add_shop_link_close').click(function () {

        var id = jQuery(this).attr('data-id');
        jQuery('#admin_add_shop_link'+id).hide();
        jQuery('#admin_add_shop_link'+id).prev().show();
    })


});