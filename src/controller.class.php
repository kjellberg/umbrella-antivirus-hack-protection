<?php
namespace Umbrella;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class Controller
{
	/**
	 * Header
	 * Controller for header
	 * @return void
	*/
	static public function header( $data = array() ) {

		// Button functions
		if (isset($_GET['do'])) {

			switch ($_GET['do']) {

				case 'subscribe':
					@wp_remote_get("https://network.umbrellaplugins.com/subscribe?e=" . esc_attr($_GET['e']));
					$data['refresh_page'] = 1;
				break;

			}
		}

		$data['navbars'] = array(
			array('umbrella-site-protection', __('General', UMBRELLA__TEXTDOMAIN)),
			array('umbrella-vulnerabilities', __('Plugins & Themes', UMBRELLA__TEXTDOMAIN)),
			array('umbrella-scanner', __('WordPress CORE', UMBRELLA__TEXTDOMAIN)),
			array('umbrella-backup', __('Database Backup', UMBRELLA__TEXTDOMAIN)),
			array('umbrella-sp-logging', __('Logs', UMBRELLA__TEXTDOMAIN)),
		);

		self::make('header', $data);
	}

	/**
	 * Footer
	 * Controller for footer
	 * @return void
	*/
	static public function footer() {

		$data = array();
		self::make('footer', $data);
	}

	/**
	 * Logging
	 * Controller for Logging
	 * @return void
	*/
	static public function logging() {

		$data = array();

		// Empty logs function
		if (isset($_GET['do'])) {

			switch ($_GET['do']) {

				case 'empty-logs':
					Log::empty_logs();
					$data['refresh_page'] = 1;
				break;

				case 'disable-admin-notices':
					add_option('umbrella_sp_disable_notices', 1);
					$data['refresh_page'] = 1;
				break;

				case 'enable-admin-notices':
					delete_option('umbrella_sp_disable_notices');
					$data['refresh_page'] = 1;
				break;
			}
		}

		$data['logs'] = (isset($_GET['lp'])) ? Log::read_page($_GET['lp']) : Log::read_page();
		$data['ip'] = (isset($_GET['ip'])) ? esc_attr(urldecode($_GET['ip'])) : false;
		$data['unread'] = Log::counter();;

		self::make('logging', $data);
	}

	/**
	 * Dashboard
	 * Controller for view Dashboard
	 * @return void
	*/
	static public function dashboard() {

		// Button functions
		if (isset($_GET['do'])) {

			switch ($_GET['do']) {

				case 'disable-automatic-updates':
					add_option('umbrella_sp_disable_auto_updates', 1);
					$data['refresh_page'] = 1;
				break;

				case 'enable-automatic-updates':
					delete_option('umbrella_sp_disable_auto_updates');
					$data['refresh_page'] = 1;
				break;
			}
		}

		// Get web server
		$server_software = $_SERVER['SERVER_SOFTWARE'];
		$server_software = explode(' ', $server_software);
		$server_software = $server_software[0];

		$data['mysql']['user'] = DB_USER;
		$data['mysql']['host'] = DB_HOST;
		$data['mysql']['name'] = DB_NAME;

		// Get charset
		$server_protocol = $_SERVER['SERVER_PROTOCOL'];

		// Domains on shared ip
		$shared_domains = Scanner::reverse_ip();

		$data['safebrowsing_status_code'] = Scanner::google_safe_browsing_code();
		$data['available_options'] = Modules::valid_modules();
		$data['ip'] = $_SERVER['HTTP_HOST'];
		$data['software'] = $server_software;
		$data['shared_domains'] = $shared_domains;
		$data['version'] = phpversion();

		self::make('dashboard', $data);
	}

	/**
	 * Scanner
	 * Controller for view Scanner
	 * @return void
	*/
	static public function scanner() {

		wp_enqueue_script('umbrella-filescanner', UMBRELLA__PLUGIN_URL . 'js/filescanner.js');

		$scanner = new Scanner;
		$locale = get_locale();
		$data_file = \Umbrella\Scanner::get_db_file();

		// Show error page if no data file found.
		if (!file_exists($data_file))
			return self::make('scanner_no_md5_list');

		// Get data if cached.
		$fileslist = array();

		// Delete cache if updated plugin or core.
		if (get_transient('umbrella-latest-file-scan') != $scanner->wp_version() . UMBRELLA__VERSION) {
			delete_transient('umbrella-file-scan');
			delete_transient('umbrella-latest-file-scan');
		}

		if (null !== get_transient('umbrella-file-scan'))
			$fileslist = get_transient('umbrella-file-scan');

		// Remove temporary ignored files from files list.
   	$ignored_files = $scanner->ignored_files();

		if (is_array($fileslist)) {

			foreach ($fileslist as $index => $file)
			{
				if (isset($ignored_files[$file['file']])
					AND isset($file['response']['filesize'])
					AND $ignored_files[$file['file']] == $file['response']['filesize'])
						unset($fileslist[$index]);
			}
		}

    $data['fileslist'] = $fileslist;
		self::make('scanner', $data);

	}

	/**
	 * Vulnerabilities
	 * Controller for view Vulnerabilities
	 * @return void
	*/
	static public function vulnerabilities() {

		$plugins = Scanner::vulndb_plugins();
		$themes = Scanner::vulndb_themes();

		$data['plugins'] = $plugins;
		$data['themes'] = $themes;

		self::make('vulnerabilities', $data);
	}

	/**
	 * Backup
	 * Controller for view Backup
	 * @return void
	*/
	static public function backup() {

		global $wpdb;

		// Button functions
		if (isset($_GET['do'])) {

			switch ($_GET['do']) {

				case 'create-backup':
					\Umbrella\Backup::database();
					$data['refresh_page'] = 1;
				break;
			}
		}

		$size = 0;
		$results = $wpdb->get_results( 'SHOW TABLE STATUS' );

		foreach($results as $r)
			$size += $r->Data_length + $r->Index_length;

		$decimals = 2;
		$mbytes = number_format($size/(1024*1024),$decimals);

		$data['mysql']['user'] = DB_USER;
		$data['mysql']['host'] = DB_HOST;
		$data['mysql']['name'] = DB_NAME;
		$data['mysql']['size'] = $mbytes;

		$data['mysql_dumps'] = \Umbrella\Backup::getDatabaseDumps();

		self::make('backup', $data);
	}

	/**
	 * Permissions
	 * Controller for view Permissions
	 * @return void
	*/
	static public function permissions() {

		$filehandler = new FileHandler();
		$filelist = $filehandler->search(ABSPATH, 'is_writable', '1', false);

		$data['warning_list'] = $filelist;

		self::make('permissions', $data);
	}

	/**
	 * Make View
	 * This will help render the views.
	 * @return void
	*/
	static public function make( $view = '', $data = array() ) {

		wp_enqueue_style('umbrella-filescanner-css', UMBRELLA__PLUGIN_URL . 'css/layout.css');
		wp_enqueue_script('umbrella-sp-js', UMBRELLA__PLUGIN_URL . 'js/siteprotection.js');
		wp_enqueue_script('jquery-cookie', UMBRELLA__PLUGIN_URL . 'js/jquery.cookie.js', array('jquery'));

		$path_to_file = UMBRELLA__PLUGIN_DIR . 'views/' . $view . '.view.php';

		if (is_array($data))
			extract($data);

		if ( file_exists( $path_to_file ) )
			require_once($path_to_file);
		else
			echo "File " . $path_to_file . " does not exists.";

	}


}