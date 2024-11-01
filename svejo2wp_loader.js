if (svejo2wp_loaderpath!=undefined && svejo2wp_postid!=undefined && !isNaN(parseInt(svejo2wp_postid))) {
	function svejo2wp_load_comments() {
			var xmlhttp=false;
			/*@cc_on @*/
			/*@if (@_jscript_version >= 5)
			try {
				xmlhttp = new ActiveXObject('Msxml2.XMLHTTP');
			} catch (e) {
				try {
					xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
				} catch (E) {
					xmlhttp = false;
				}
			}
			@end @*/
			if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
				try {
					xmlhttp = new XMLHttpRequest();
				} catch (e) {
					xmlhttp=false;
				}
			}
			if (!xmlhttp && window.createRequest) {
				try {
					xmlhttp = window.createRequest();
				} catch (e) {
					xmlhttp=false;
				}	
			}
			if (xmlhttp==false)
				return;

			xmlhttp.open('GET', svejo2wp_loaderpath+'svejo2wp_comments_load.php?post_id='+parseInt(svejo2wp_postid),true);
			xmlhttp.send(null);
		}

	if (window.addEventListener) {
		window.addEventListener ('load',svejo2wp_load_comments,false);
	} else if (window.attachEvent) {
		window.attachEvent ('onload',svejo2wp_load_comments);
	} else {
		window.onload = svejo2wp_load_comments;
	}
}
