(function($){

$j = jQuery.noConflict();	
	
$j(document).ready(function() {
			
			//Gallery			
			$j("a.galleryItem").bind("click",function(){
					
					$j("a.currentGalleryItem").removeClass("currentGalleryItem");
					$j("a#" + this.id).addClass("currentGalleryItem");
					
					ObjectID = this.id;
					
					$j("img.galleryShow").fadeOut("slow",function(){
						$("img#" + ObjectID + "Image").fadeIn("slow").removeClass("galleryHide").addClass("galleryShow");
						
					}).removeClass("galleryShow");

					return false;
					
					
			});	
			
		});
})
(jQuery);