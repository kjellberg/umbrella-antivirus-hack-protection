<?php
/**
 * Plugin Name: Umbrella Antivirus & Hack protection
 * Plugin URI: https://www.umbrellaantivirus.com
 * Description: WordPress Antivirus and Hack protection. With features as vulnerability scanner, file scanner, hide versions, disable pings, captcha login and more.
 * Author: Umbrella Antivirus
 * Version: 1.8.5
 * Author URI: https://www.umbrellaantivirus.com
 * Text Domain: umbrella-antivirus-hack-protection
 * Domain Path: /languages
 * License: GPL
 * Copyright: Rasmus Kjellberg
 *
 * @package UmbrellaAntivirus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

$up_dir = wp_upload_dir();

// Define some constants.
define( 'UMBRELLA__VERSION', '1.8.5' );
define( 'UMBRELLA__LATEST_WP_VERSION', '4.6.1' );
define( 'UMBRELLA__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'UMBRELLA__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UMBRELLA__PLUGIN_TMPURL', UMBRELLA__PLUGIN_URL . 'data/tmp/' );
define( 'UMBRELLA__PLUGIN_TMPDIR', UMBRELLA__PLUGIN_DIR . 'data/tmp/' );
define( 'UMBRELLA__STORAGE_DIR', $up_dir['basedir'] . '/umbrella/' );
define( 'UMBRELLA__STORAGE_URL', $up_dir['baseurl'] . '/umbrella/' );
define( 'UMBRELLA__TEXTDOMAIN', 'umbrella-antivirus-hack-protection' );

/**
 * Plugin Init
 * Load WordPress text domain for translation.
 *
 * @since 1.8.5
 */
function umbrella_plugin_init() {
	load_plugin_textdomain( UMBRELLA__TEXTDOMAIN, false, UMBRELLA__TEXTDOMAIN . '/languages' );
}

add_action( 'init', 'umbrella_plugin_init' );

// Include all libraries (third-party classes).
require_once( UMBRELLA__PLUGIN_DIR . 'lib/whois.lib.php' );
require_once( UMBRELLA__PLUGIN_DIR . 'lib/diff.lib.php' );
require_once( UMBRELLA__PLUGIN_DIR . 'lib/really-simple-captcha/really-simple-captcha.php' );

// Include source files.
require_once( UMBRELLA__PLUGIN_DIR . 'src/scanner.class.php' );
require_once( UMBRELLA__PLUGIN_DIR . 'src/logging.class.php' );
require_once( UMBRELLA__PLUGIN_DIR . 'src/modules.class.php' );
require_once( UMBRELLA__PLUGIN_DIR . 'src/backup.class.php' );
require_once( UMBRELLA__PLUGIN_DIR . 'src/controller.class.php' );
require_once( UMBRELLA__PLUGIN_DIR . 'src/autoload.class.php' );

// Run the autoloader.
new Umbrella\Autoload();
