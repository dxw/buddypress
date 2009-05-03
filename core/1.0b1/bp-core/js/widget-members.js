jQuery(document).ready( function() {
	jQuery("div#members-list-options a").livequery('click',
		function() { 
			jQuery('#ajax-loader-members').toggle();

			jQuery("div#members-list-options a").removeClass("selected");
			jQuery(this).addClass('selected');

			jQuery.post( ajaxurl, {
				action: 'widget_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce-members").val(),
				'max-members': jQuery("input#bp_core_widget_members_max").val(),
				'filter': jQuery(this).attr('id')
			},
			function(response)
			{	
				member_wiget_response(response);
			});
		
			return false;
		}
	);
});

function member_wiget_response(response) {
	response = response.substr(0, response.length-1);
	response = response.split('[[SPLIT]]');

	if ( response[0] != "-1" ) {
		jQuery("ul#members-list").fadeOut(200, 
			function() {
				jQuery('#ajax-loader-members').toggle();
				jQuery("ul#members-list").html(response[1]);
				jQuery("ul#members-list").fadeIn(200);
			}
		);

	} else {					
		jQuery("ul#members-list").fadeOut(200, 
			function() {
				jQuery('#ajax-loader-members').toggle();
				var message = '<p>' + response[1] + '</p>';
				jQuery("ul#members-list").html(message);
				jQuery("ul#members-list").fadeIn(200);
			}
		);
	}
}