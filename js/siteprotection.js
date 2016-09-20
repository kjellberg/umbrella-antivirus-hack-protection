jQuery(document).on('ready', function() {
	jQuery("#umbrella-site-protection .error.umbrella").fadeIn();
	jQuery(".logdetails").hide();
});

function toggleDetails(id) {
	jQuery(".logdetails.log-id-" + id).fadeToggle();
}