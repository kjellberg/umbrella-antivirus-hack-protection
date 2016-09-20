<?php
/**
 * Partial view for registration form.
 * Displays an alert box with registration form.
 *
 * @package UmbrellaAntivirus
 */

$current_user = wp_get_current_user();
?>
<div class="notice update-nag umbrella">
<a href="admin.php?page=umbrella-sp-logging" class="button" style="float:right;padding: 5px;height:20px;line-height:10px;font-size:10px"><?php esc_attr_e( 'Close', 'umbrella-antivirus-hack-protection' ); ?></a>
	<strong>Umbrella Antivirus &amp; Hack Protection</strong><br>
	<hr style="margin: 10px 0;">

	<form action="#">
		<label for="title">
			Site URL:
			<input type="text" name="title"
			value="<?php echo esc_url( site_url() ); ?>" id="title" autocomplete="off">
		</label>
		<label for="email">
			Email:
			<input type="text" name="email" id="email"
			value="<?php echo esc_attr( $current_user->user_email ); ?>" autocomplete="off">
		</label>
		<label for="submit">
			<input type="submit" name="submit" id="submit" value="Register &amp; Activate" class="button button-primary">
		</label>
	</form>
<hr style="margin: 10px 0;">
	Register to receive free updates, email reports, and full plugin access.
	<strong>It's free and easy.</strong><br>

</div>
