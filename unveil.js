(function($) {
	$.fn.unveil = function(threshold, callback) {
		var $w = $(window),
        th = threshold || 0,
        retina = window.devicePixelRatio > 1,
        attrib = retina? "data-src-retina" : "data-src",
        images = this,
        loaded;
		this.one("unveil", function() {
			var source = this.getAttribute(attrib);
			source = source || this.getAttribute("data-src");
			if (source) {
				var screen_width = jQuery(window).innerWidth() - 32;
				if(jQuery(this).width() >= (screen_width-5) && source.match(/image=/)){
					source = source+"&new_width="+screen_width;
					jQuery(this).css('height','auto').attr('height', '');
				}
				jQuery(this).attr('src',source);
				if (typeof callback === "function") callback.call(this);
				jQuery('img[data-src], img[data-src-retina]').each(function(){
					if((jQuery(this).attr('data-src') == jQuery(this).attr('src') || jQuery(this).attr('data-src-retina') == jQuery(this).attr('src')) && this.complete){
						jQuery(this).css('background','none');
					}
				});
			}
	    });
	    function unveil() {
	      var inview = images.filter(function() {
	        var $e = $(this);
	        if ($e.is(":hidden")) return;
	        var wt = $w.scrollTop(),
	            wb = wt + $w.height(),
	            et = $e.offset().top,
	            eb = et + $e.height();
	        return eb >= wt - th && et <= wb + th;
	      });
	      loaded = inview.trigger("unveil");
	      images = images.not(loaded);
	      jQuery('img[data-src], img[data-src-retina]').each(function(){
				if((jQuery(this).attr('data-src') == jQuery(this).attr('src') || jQuery(this).attr('data-src-retina') == jQuery(this).attr('src')) && this.complete){
					jQuery(this).css('background','none');
				}
		  });
	    }
	    $w.on("scroll.unveil resize.unveil lookup.unveil", unveil);
	    unveil();
	    return this;
	};
})(window.jQuery || window.Zepto);

jQuery(document).ready(function($) {
	$("img").unveil(1000);
});