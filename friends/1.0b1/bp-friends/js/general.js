
jQuery(document).ready( function() {
	jQuery("form#friend-search-form").submit(
		function() {
			return false;
		}
	);
	
	jQuery("div#pag a").livequery('click',
		function() { 
			jQuery('#ajax-loader').toggle();

			var fpage = jQuery(this).attr('href');
			fpage = fpage.split('=');

			jQuery.post( ajaxurl, {
				action: 'friends_search',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				'initiator_id': jQuery("input#initiator").val(),
				'fpage': fpage[1],
				'num': 10,

				'friend-search-box': jQuery("#friend-search-box").val()
			},
			function(response)
			{	
				
				response = response.substr( 0, response.length - 1 );

				jQuery("div#friends-loop").fadeOut(200, 
					function() {
						jQuery('#ajax-loader').toggle();
						jQuery("div#friends-loop").html(response);
						jQuery("div#friends-loop").fadeIn(200);
					}
				);
			});
			
			return false;
		}
	);
	
	jQuery("input#friend-search-box").keyup(	
		function(e) {
			if ( e.which == 13 ) {
				jQuery('#ajax-loader').toggle();
				
				jQuery.post( ajaxurl, {
					action: 'friends_search',
					'cookie': encodeURIComponent(document.cookie),
					'_wpnonce': jQuery("input#_wpnonce").val(),

					'friend-search-box': jQuery("#friend-search-box").val()
				},
				function(response)
				{
					response = response.substr( 0, response.length - 1 );

					jQuery("div#friends-loop").fadeOut(200, 
						function() {
							jQuery('#ajax-loader').toggle();
							jQuery("div#friends-loop").html(response);
							jQuery("div#friends-loop").fadeIn(200);
						}
					);
					
				});

				return false;
			}
		}
	);
});