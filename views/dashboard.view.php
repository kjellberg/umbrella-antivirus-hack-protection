<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
Umbrella\Controller::header($data); ?>

<div class="umbrella-sidebar" style="width: 25%;float: right;">


	<h3><?php _e('General', UMBRELLA__TEXTDOMAIN); ?></h3>
	<table class="wp-list-table widefat plugins">
		<tbody id="the-list">

			<tr>
				<th style="font-weight:bold;width:50%"><?php _e('Domain Name', UMBRELLA__TEXTDOMAIN); ?></th>
				<td style="text-align:right;"><?php echo $ip; ?></td>
			</tr>

			<th style="font-weight:bold;width:50%"><?php _e('Safe Browsing', UMBRELLA__TEXTDOMAIN); ?></th>
				<td style="text-align:right;"><?php echo $safebrowsing_status_code; ?>
				<?php if ($safebrowsing_status_code==204): ?>
					<strong style="color:green">OK</strong>
				<?php endif; ?>
			</td>

			<tr>
				<th style="font-weight:bold;width:50%"><?php _e('Plugin Version', UMBRELLA__TEXTDOMAIN); ?></th>
				<td style="text-align:right;"><?php echo UMBRELLA__VERSION; ?></td>
			</tr>

			<?php if (isset($shared_domains) AND $shared_domains != 0): ?>
			<tr>
				<th style="font-weight:bold;width:50%"><?php _e('Domains on IP', UMBRELLA__TEXTDOMAIN); ?></th>
					<td style="text-align:right;"><?php echo number_format($shared_domains); ?> domains</td>
			</tr>
			<?php endif; ?>

		</tbody>
	</table>

	<h3><?php _e('Server', UMBRELLA__TEXTDOMAIN); ?></h3>
	<table class="wp-list-table widefat plugins">
		<tbody id="the-list">

			<tr>
				<th style="font-weight:bold;width:50%"><?php _e('Software', UMBRELLA__TEXTDOMAIN); ?></th>
				<td style="text-align:right;"><?php echo $software; ?></td>
			</tr>

			<tr>
				<th style="font-weight:bold;width:50%"><?php _e('PHP Version', UMBRELLA__TEXTDOMAIN); ?></th>
				<td style="text-align:right;"><?php echo $version; ?></td>
			</tr>

		</tbody>
	</table>

	<h3><?php _e('Database', UMBRELLA__TEXTDOMAIN); ?></h3>
	<table class="wp-list-table widefat plugins">
		<tbody id="the-list">
		<tr>
			<th style="font-weight:bold;width:50%"><?php _e('Database Type', UMBRELLA__TEXTDOMAIN); ?></th>
			<td style="text-align:right;">mySQL</td>
		</tr>
		<tr>
			<th style="font-weight:bold;width:50%"><?php _e('Database Host', UMBRELLA__TEXTDOMAIN); ?></th>
			<td style="text-align:right;"><?php echo $mysql['host']; ?></td>
		</tr>
		<tr>
			<th style="font-weight:bold;width:50%"><?php _e('Database Name', UMBRELLA__TEXTDOMAIN); ?></th>
			<td style="text-align:right;"><?php echo $mysql['name']; ?></td>
		</tr>
		<tr>
			<th style="font-weight:bold;width:50%"><?php _e('Database User', UMBRELLA__TEXTDOMAIN); ?></th>
			<td style="text-align:right;"><?php echo $mysql['user']; ?></td>
		</tr>
		</tbody>
	</table>

	<h3><?php _e('CDN &amp; DDOS-Protection', UMBRELLA__TEXTDOMAIN); ?></h3>
	<table class="wp-list-table widefat plugins">
		<tbody id="the-list">
		<tr>
			<th style="font-weight:bold;">
				<?php if (true === Umbrella\Scanner::has_cloudflare()): ?>
					<?php _e('Protected by CloudFlare', UMBRELLA__TEXTDOMAIN); ?>
				<?php else: ?>
					 <a target="_blank" href="https://www.cloudflare.com/" style="color:green;"><?php _e('Set up with CloudFlare.com', UMBRELLA__TEXTDOMAIN); ?></a>
				<?php endif; ?>
			</th>
		</tr>
		</tbody>
	</table>
</div>

<div style="width: 70%; float:left;">

	<h3 style="clear:both;"><?php _e('Settings', UMBRELLA__TEXTDOMAIN); ?></h3>
	<p>
		<?php _e('This plugin does nothing by default, and that\'s because we want you to know whats happening behind the scenes.
		With that said, please choose wich modules and settings you want to use with Umbrella. Even if nothing is loaded by default,
		we recommend you to activate them all for best protection.', UMBRELLA__TEXTDOMAIN); ?>
	</p>

	<h4 style="clear:both;"><?php _e('Enable/disable settings', UMBRELLA__TEXTDOMAIN); ?></h4>
	<form method="post" action="options.php">

	    <?php
	    	settings_fields( 'umbrella-settings' );
	     	do_settings_sections( 'umbrella-settings' );
	     	$load_modules = get_option( 'umbrella_load_modules' );
	 	?>

	 	<table class="wp-list-table widefat plugins">
			<thead>
				<tr>
					<td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Välj alla</label><input id="cb-select-all-1" type="checkbox"></td>					<th style="width: 150px;"><?php _e('Name', UMBRELLA__TEXTDOMAIN); ?></th>
					<th><?php _e('Description', UMBRELLA__TEXTDOMAIN); ?></th>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Välj alla</label><input id="cb-select-all-1" type="checkbox"></td>					<th style="width: 150px;"><?php _e('Name', UMBRELLA__TEXTDOMAIN); ?></th>
					<th><?php _e('Description', UMBRELLA__TEXTDOMAIN); ?></th>
				</tr>
			</tfoot>

			<tbody id="the-list">

				<?php
				// Modules
				foreach($available_options as $opt): ?>
					<tr class="alternate <?php

						if (isset( $load_modules[$opt[0]] ) AND $load_modules[$opt[0]] == 1)
							echo 'active';
						else
							echo 'inactive';

					?>">
						<th scope="col" id="cb" class="manage-column column-cb check-column" style="">
							<input name="umbrella_load_modules[<?php echo $opt[0]; ?>]" type="checkbox" value="1"
							<?php checked( isset( $load_modules[$opt[0]] ) ); ?> />
						</th>
						<td class="settings-name"><?php echo $opt[1]; ?></td>
						<td><?php echo $opt[2]; ?></td>
					</tr>
				<?php
				endforeach;
				// End Modules
				?>
			</tbody>
		</table>
	    <br>
		<input type="submit" name="submit" id="update_modules" class="button button-primary" value="<?php _e('Save Changes', UMBRELLA__TEXTDOMAIN); ?>">
	</form>
</div>

<div style="clear:both;"></div>
<br><hr>

<div style="float:left;width: 24%;">
	<h3><?php _e('Automatic Plugin Updates.', UMBRELLA__TEXTDOMAIN); ?></h3>
	<p><?php _e('Automatic updates of this plugin is enabled by default.', UMBRELLA__TEXTDOMAIN); ?></p>
	<?php if(get_option('umbrella_sp_disable_auto_updates') AND get_option('umbrella_sp_disable_auto_updates') == 1): ?>
		<a href="?page=umbrella-site-protection&amp;do=enable-automatic-updates" class="button button-primary"><?php _e('Enable automatic updates', UMBRELLA__TEXTDOMAIN); ?></a>
	<?php else: ?>
		<a href="?page=umbrella-site-protection&amp;do=disable-automatic-updates" class="button"><?php _e('Disable automatic updates', UMBRELLA__TEXTDOMAIN); ?></a>
	<?php endif; ?>
</div>

<?php Umbrella\Controller::footer(); ?>