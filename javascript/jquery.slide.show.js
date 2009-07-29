var $j = jQuery.noConflict(); 

		function slideSwitch() {
		    var $active = $j('#slideshow img.active');
		
		    if ( $active.length == 0 ) $active = $j('#slideshow img:last');
		
		    var $next =  $active.next().length ? $active.next()
		        : $j('#slideshow img:first');
		
		    $active.addClass('last-active');
		
		    $next.css({opacity: 0.0})
		        .addClass('active')
		        .animate({opacity: 1.0}, 1000, function() {
		            $active.removeClass('active last-active');
		        });
		}
		
		$j(document).ready(function() {
		    setInterval( "slideSwitch()", 5000 );
		});
	



