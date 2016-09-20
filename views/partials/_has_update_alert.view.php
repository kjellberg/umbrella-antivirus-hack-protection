<div class="error umbrella">
 	<a href="<?php echo esc_url($url); ?>" class="button button-primary" style="float:right;margin-top: 3px;"><?php _e( 'Update Now', UMBRELLA__TEXTDOMAIN ); ?></a>
    <p>
    	<a href="admin.php?page=umbrella-site-protection"><strong><?php _e( 'Site Protection', UMBRELLA__TEXTDOMAIN ); ?></strong></a>:
    	<?php printf( __( 'A new version of Umbrella Site Protection is available: <strong>%s</strong>. Please update now for better protection.', UMBRELLA__TEXTDOMAIN ), UMBRELLA_SP_UPDATE_AVAILABLE); ?>
	</p>
</div>