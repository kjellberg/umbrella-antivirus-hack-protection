<?php
namespace Umbrella;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Autoload
{
	// Hooks to autoload.
	protected $autoload = array( 'admin_notices', 'plugins_loaded', 'init', 'admin_init', 'admin_menu' );

	/**
	 * Autoload Helper
	 * This will init the plugin and autoload files.
	 * @return N/A
	*/
	function __construct() {
		$this->autoload();
	}

	/**
	 * Load hooks
	 * This will load everything :)
	 * @return N/A
	*/
	public function autoload() {

		// Get all hooks from protected var $autoload.
		$hooks = $this->autoload;

		// Loop trough hooks and add actions for those who have declared methods.
		foreach ($hooks as $hook) {
			if (method_exists($this, $hook))
				add_action($hook, array($this, $hook));
		}

		// Add filters to plugin row in plugins list.
		add_filter('plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2);
		add_filter('plugin_action_links_' . UMBRELLA__TEXTDOMAIN . '/init.php', array( &$this, 'action_links' ) );

		// Load all activated modules.
		$this->load_modules();

		// Check for updates of this plugin
		$this->check_for_updates();

		// Check if storage directory exists.
		$this->checkStorageDir();
	}

	/**
	 * Init
	 * This function will run when WordPress calls the hook "init".
	 * @return N/A
	*/
	public function init() {
		add_action('wp_ajax_validate_key', array('\Umbrella\Controller', 'ajax_validate_key'));
		add_action('wp_ajax_nopriv_validate_key', array('\Umbrella\Controller', 'ajax_validate_key'));
	}

	/**
	 * Check storage directory
	 * This function will create your storage directory if it dont exist.
	 * @return N/A
	*/
	public function checkStorageDir() {

	    // Create a folder in the Uploads Directory of WordPress to store Umbrella Files
	    if (!file_exists(UMBRELLA__STORAGE_DIR)) {
	        mkdir(UMBRELLA__STORAGE_DIR, 0775, true);
	    }

	    // Create index.html to prevent file listing.
	    if (!file_exists(UMBRELLA__STORAGE_DIR . 'index.html')) {
	    	touch(UMBRELLA__STORAGE_DIR . 'index.html');
	    }

	}

	/**
	 * Latest Version
	 * Get the latest available version number of this plugin.
	 * @return N/A
	*/
	public function latest_version() {

		$updates = get_site_transient('update_plugins');

		if (isset($updates->response['umbrella-antivirus-hack-protection/init.php'])) {
			$latest_version = $updates->response['umbrella-antivirus-hack-protection/init.php']->new_version;
		}
		else {
			$latest_version = UMBRELLA__VERSION;
		}

		return $latest_version;
	}

	/**
	 * Check for updates
	 * Check for updates and update if found.
	 * @since 1.4.1
	 * @return N/A
	*/
	public function check_for_updates() {

		if (UMBRELLA__VERSION < $this->latest_version()) {
			define('UMBRELLA_SP_UPDATE_AVAILABLE', $this->latest_version());
		}

		// Only update if automatic updates are not disabled.
		if (get_option('umbrella_sp_disable_auto_updates') != 1) {

			// Check for plugin updates.
			add_filter('auto_update_plugin', array(
	            $this,
	            'filter_auto_update_plugin'
	        ), 10, 2);
		}
	}

	/**
	 * Auto Update Plugin
	 * auto_update_plugin filter
	 * @since 1.4.1
	 * @return bool
	*/
	public function filter_auto_update_plugin( $update, $item ) {

		// Array of plugin slugs to always auto-update
	    $plugins = array( 'umbrella-antivirus-hack-protection' );

	    if ( in_array( $item->slug, $plugins ) ) {
	        return true; // Always update plugins in this array
	    } else {
	        return $update; // Else, use the normal API response to decide whether to update or not
	    }

	}

	/**
	 * Plugin Row Meta
	 * This function will run when WordPress calls the filter "plugin_row_meta".
	 * @return html
	*/
	public function plugin_row_meta($links, $file) {

		$file = plugin_dir_path( UMBRELLA__PLUGIN_DIR ) . $file;

		if ($file == UMBRELLA__PLUGIN_DIR . 'init.php') {
			// Add links under the description field
		}
		return $links;
	}

	/**
	 * Action links
	 * This function will run when WordPress calls the filter "action_links".
	 * @return html
	*/
	public function action_links($links) {

		$links['settings'] = '<a href="admin.php?page=umbrella-site-protection">' . __('Settings', UMBRELLA__TEXTDOMAIN) . '</a>';
		$links['logs'] = '<a href="admin.php?page=umbrella-sp-logging">' . __('Logs', UMBRELLA__TEXTDOMAIN) . '</a>';

		return $links;
	}

	/**
	 * Plugins Loaded
	 * This function will run when WordPress calls the hook "plugins_loaded".
	 * @return N/A
	*/
	public function plugins_loaded() {

	}

	/**
	 * Admin Notices
	 * This function will run when WordPress calls the hook "admin_notices".
	 * @return N/A
	*/
	public function admin_notices() {

		// @todo: Fix activation form when reached 5.000 users.
		// if (false === get_option( 'umbrella_hide_activation_form' ) AND
		// 	false === get_transient( 'umbrella_hide_activation_form' ) ) {
		// 	require_once( UMBRELLA__PLUGIN_DIR . 'views/partials/register-alert.view.php' );
		// }

		// Version updates.
		if (defined('UMBRELLA_SP_UPDATE_AVAILABLE') AND !isset($_GET['action'])) {
			$update_file = 'umbrella-antivirus-hack-protection/init.php';
			$url = wp_nonce_url(
						self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . $update_file ),
						'upgrade-plugin_' . $update_file
					);

			// Include alert view.
			require_once( UMBRELLA__PLUGIN_DIR . 'views/partials/_has_update_alert.view.php' );
		}

		// Log entries.
		$logs = Log::counter();
		if ($logs > 0 AND get_option('umbrella_sp_disable_notices') != 1):
			require_once( UMBRELLA__PLUGIN_DIR . 'views/partials/_log_notices_alert.view.php' );
		endif;

	}

	/**
	 * Admin Init
	 * This function will run when WordPress calls the hook "admin_init".
	 *
	 * @return N/A
	*/
	public function admin_init() {
		register_setting(
			'umbrella-settings', 'umbrella_load_modules',
			array( 'Umbrella\Modules', 'validate_modules' )
		);
	}

	/**
	 * Admin Menu
	 * This function will run when WordPress calls the hook "admin_menu".
	 * @return N/A
	*/
	public function admin_menu() {
		add_menu_page( __('Site Protection', UMBRELLA__TEXTDOMAIN), __('Site Protection', UMBRELLA__TEXTDOMAIN), 'administrator', 'umbrella-site-protection', array('Umbrella\controller', 'dashboard') , 'dashicons-shield', 3 );
		add_submenu_page( 'umbrella-site-protection', __('Site Protection by Umbrella Plugins', UMBRELLA__TEXTDOMAIN), __('General', UMBRELLA__TEXTDOMAIN), 'administrator', 'umbrella-site-protection', array('Umbrella\controller', 'dashboard') );

		add_submenu_page( 'umbrella-site-protection', __('Plugins & Themes', UMBRELLA__TEXTDOMAIN), __('Plugins & Themes', UMBRELLA__TEXTDOMAIN), 'administrator', 'umbrella-vulnerabilities', array('Umbrella\controller', 'vulnerabilities') );
		add_submenu_page( 'umbrella-site-protection', __('WordPress CORE', UMBRELLA__TEXTDOMAIN), __('WordPress CORE', UMBRELLA__TEXTDOMAIN), 'administrator', 'umbrella-scanner', array('Umbrella\controller', 'scanner') );
		add_submenu_page( 'umbrella-site-protection', __('Database Backup', UMBRELLA__TEXTDOMAIN), __('Database Backup', UMBRELLA__TEXTDOMAIN), 'administrator', 'umbrella-backup', array('Umbrella\controller', 'backup') );
		add_submenu_page( 'umbrella-site-protection', __('Logs', UMBRELLA__TEXTDOMAIN), __('Logs', UMBRELLA__TEXTDOMAIN), 'administrator', 'umbrella-sp-logging', array('Umbrella\controller', 'logging') );
	}

	/**
	 * Load Modules
	 * This will load all activated modules
	 * @return N/A
	*/
	public function load_modules() {

		// Get all available modules from module class.
		$modules = Modules::valid_modules();
		$options = get_option('umbrella_load_modules');

		// Loop trough all available modules and include them if they are activated.
		foreach ($modules as $mod)
		{
			// Check if modules is activated.
			if (isset($options[$mod[0]]))
			{
				// Include module if it exists.
				$path_to_file = UMBRELLA__PLUGIN_DIR . 'src/modules/' . $mod[0] . '.php';
				if (file_exists($path_to_file))
					require_once($path_to_file);
			}
		}

	}
}