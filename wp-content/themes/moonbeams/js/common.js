jQuery(function() {
	jQuery('.menu-container ul').droppy();
    jQuery('.menu-container li ul li:has(ul)').find("a:first").append(' &raquo; ');
});
