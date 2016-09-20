<div class="error umbrella">
<a href="admin.php?page=umbrella-sp-logging" class="button button-primary" style="float:right;margin-top: 3px;"><?php _e( 'View logs', UMBRELLA__TEXTDOMAIN ); ?></a>
<p>
	<a href="admin.php?page=umbrella-sp-logging"><strong><?php _e( 'Site Protection', UMBRELLA__TEXTDOMAIN ); ?></strong></a>:
	<?php printf( __( 'You have <strong>%d</strong> unread log message(s).', UMBRELLA__TEXTDOMAIN ), $logs); ?>
</p>
</div>