<?php
/**
 * Core Scanner
 * This is the core plugin for UmbrellaAntivirus Scanner
 *
 * @since 2.0
 * @package UmbrellaAntivirus
 */

namespace Umbrella;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

/**
 * Scanner
 * Scans core files
 *
 * @package UmbrellaAntivirus
 */
class CoreScanner extends UmbrellaAntivirus {

	/**
	 * Whitelabel autoload actions/methods
	 * List of valid methods/hooks.
	 *
	 * @since 2.0
	 * @var array
	 */
	protected $autoload = array(
		'admin_init',
		'wp_ajax_core_scanner',
		'wp_ajax_update_core_db',
		'wp_ajax_compare_file',
	);


	/**
	 * Excluded paths
	 * Files and directories that will not be included in a core scan.
	 *
	 * @since 2.0
	 * @var array
	 */
	protected $excluded_paths = array(
		'wp-config-sample.php',
		'wp-includes/version.php',
		'wp-content/',
		'wp-config.php',
		'readme.html',
		'.txt',
		'/..',
		'/.',
	);

	/**
	 * Admin Init
	 * This function will run when WordPress calls the hook "admin_init".
	 */
	public function admin_init() {
		add_filter( 'umbrella-scanner-steps', array( $this, 'register_scanner' ) );
		add_filter( 'scanner-buttons', array( $this, 'add_buttons' ) );
	}

	/**
	 * Register Scanner
	 * Register CoreScanner to Scanner steps.
	 *
	 * @param array $steps Default scanner steps.
	 */
	public function register_scanner( $steps ) {

		global $wp_version;

		$steps[] = array(
			'action' => 'update_core_db',
			'log' => "Downloading core files list for WordPress {$wp_version}..",
		);

		$steps[] = array(
			'action' => 'core_scanner',
			'log' => 'Scanning core files..',
		);
		return $steps;
	}

	/**
	 * Has Core Files List
	 * Check if there is a core lists transient.
	 */
	public function has_core_files_list() {
		global $wp_version;
		return false !== get_transient( 'core_tree_list_' . $wp_version );
	}


	/**
	 * Add button.
	 * Add compare button to scanner results.
	 *
	 * @param array $buttons List of default buttons.
	 */
	public function add_buttons( $buttons ) {
		$buttons[] = '<a href="#file-comparision" ng-if="result.error_code==0020" ng-click="compareFile(result.file)" class="button button-primary">COMPARE</a>';
		return $buttons;
	}

	/**
	 * Core Files List
	 * Returns a list of core files default file sizes from transient.
	 */
	public function core_files_list() {
		global $wp_version;
		return get_transient( 'core_tree_list_' . $wp_version );
	}

	/**
	 * Build Core List
	 * Get file sizes and build core list.
	 */
	public function build_core_list() {

		global $wp_version;

		// Check if there is any existing copy in transients.
		if ( false === ( $core_tree_list = $this->core_files_list() ) ) {

			// It wasn't there, so regenerate the data and save the transient.
			if ( ! $core_tree_list = API::download_core_tree() ) {
				return false; // Couldn't download core list.
			}

			$hours = 60 * 60 * 4; // 60 seconds * 60 = 4 hours.
			set_transient( 'core_tree_list_' . $wp_version, $core_tree_list, $hours );
		}

		return true;

	}

	/**
	 * Get Whitelist
	 * Get whitelist for the current WP version.
	 */
	public function whitelist() {

		$whitelist = array();

		foreach ( $this->core_files_list() as $file ) {
			$whitelist[ $file->file ] = $file->size;
		}

		return $whitelist;
	}

	/**
	 * Check file
	 * Check a specific file.
	 *
	 * @param string $file Which file to check.
	 */
	public function check_file( $file ) {

		global $umbrella_antivirus;

		$file_path = ABSPATH . $file;

		$scanner = $umbrella_antivirus->scanner;
		$whitelist = $this->whitelist();

		$file_size = filesize( $file_path );

		// File is unknown (not included in core).
		if ( ! isset( $whitelist[ $file ] ) ) {
			return $scanner->add_result(
				'core_scanner',
				$file, // Relative file path.
				$file_size,
				'0010', // Error code.
				'Unexpected file' // Error Message.
			);
		}

		$original_size = $whitelist[ $file ];

		if ( $file_size != $original_size ) {
			return $scanner->add_result(
				'core_scanner',
				$file, // Relative file path.
				$file_size,
				'0020', // Error code.
				'Modified file' // Error Message.
			);
		}

	}

	/**
	 * Scan Core Files
	 * Initialize the core file scanner.
	 */
	public function scan_core_files() {

		$system_files = $this->system_files();

		foreach ( $system_files as $file ) {
			$this->check_file( $file );
		}

		return count( $system_files );
	}

	/**
	 * System files
	 * Returns a list of all files to scan
	 */
	public function system_files() {

		$exclude = $this->excluded_paths;
		$output = array();

		// Get files and directories recursive from ABSPATH.
		$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( ABSPATH ) );

		foreach ( $files as $file ) {

			$file = (string) $file;
			$continue = 0;

			// Continue if file is in $excluded_paths.
			foreach ( $exclude as $e ) {
				if ( strpos( $file, $e ) !== false ) {
					$continue = 1;
				}
			}

			if ( 0 == $continue ) {
				$output[] = str_replace( ABSPATH, '', $file );
			} else {
				$continue = 1;
			}
		}

		return $output;
	}

	/**
	 * AJAX: Init Core Scanner
	 * Initializes a core scan
	 */
	public function wp_ajax_core_scanner() {

		$this->only_admin(); // Die if not admin.

		check_ajax_referer( 'umbrella_ajax_nonce', 'security' ); // Check nonce.

		$number_of_files = $this->scan_core_files(); // Scan all core files.
		$number_of_files = number_format( $number_of_files );

		$output = array(
			'status' => 'success',
			'logs' => array(
				"Core scanner finished. Scanned {$number_of_files} files."
			),
		);

		$this->render_json( $output );
	}

	/**
	 * AJAX: Update Core Database
	 * Initializes an update of CORE database.
	 */
	public function wp_ajax_update_core_db() {

		$this->only_admin(); // Die if not admin.

		check_ajax_referer( 'umbrella_ajax_nonce', 'security' ); // Check nonce.

		if ( $this->build_core_list() ) {
			$output = array(
				'status' => 'success',
				'logs' => array(
					'Update database finished.',
				),
			);
		} else {
			$output = array(
				'status' => 'error',
				'logs' => array(
					'Could not build core list.',
				),
			);
		}

		$this->render_json( $output );
	}

	/**
	 * AJAX: Compare file
	 * Compare a file with core SVN
	 */
	public function wp_ajax_compare_file() {

		global $wp_version;

		$this->only_admin(); // Die if not admin.

		check_ajax_referer( 'umbrella_ajax_nonce', 'security' ); // Check nonce.

		$whitelist = $this->whitelist();

		if ( isset( $_POST['file'] ) ) {
			$file = sanitize_text_field( wp_unslash( $_POST['file'] ) );
		} else {
			die( 'File is not included in core' );
		}

		// File is unknown or user trying to hack (not included in core).
		if ( ! isset( $whitelist[ $file ] ) ) {
			die( 'File is not included in core' );
		}

		$svn_url = "https://core.svn.wordpress.org/tags/{$wp_version}/{$file}";
		$svn_request = wp_remote_get( $svn_url );

		if ( is_wp_error( $svn_request ) ) {
			$this->render_json( array( 'status' => 'error', 'message' => 'Could not connect to Wordpress SVN' ) );
			die();
		}

		// Get contents of local file.
		$local_file_data = file_get_contents( ABSPATH . $file );

		// Get contents of remote file.
		$svn_file_data = $svn_request['body'];

		$diff = Diff::compare( $svn_file_data, $local_file_data );
		$html = Diff::toTable( $diff );

		$this->render_json( array( 'status' => 'success', 'html' => $html ) );
	}
}
