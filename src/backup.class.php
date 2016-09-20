<?php
namespace Umbrella;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Backup
{

	public static function database() {

		// Include the mysqldump library.
		require_once( UMBRELLA__PLUGIN_DIR . 'lib/mysqldump.lib.php' );

		// Create URL and DIR paths to new file.
		$dump_file_name = self::generateUniqueFileName('mysqldump-', 'sql');
		$dump_path = UMBRELLA__STORAGE_DIR . $dump_file_name;
		$dump_url = UMBRELLA__STORAGE_URL . $dump_file_name;

		// Connect to database and create the dump.
		$dump = new \Ifsnop\Mysqldump\Mysqldump( DB_NAME , DB_USER, DB_PASSWORD, DB_HOST );
		$dump->start ( $dump_path );

		// @todo: create success message.

	}

	public static function getDatabaseDumps() {
		$dir = UMBRELLA__STORAGE_DIR;
		$output = array();

		// Get all .sql files in datadir.
		$files = glob($dir . "*.sql");

		// Sort by file modified date.
		if(isset($files) AND count($files) > 0)
			usort($files, array('\Umbrella\Backup', 'sort'));

		// Prepare arrays for table listing.
		foreach ($files as $file) {
			$input['path'] = $file;
			$input['filesize'] = self::human_filesize(filesize($file));
			$input['url'] = str_replace(UMBRELLA__STORAGE_DIR, UMBRElLA__STORAGE_URL, $file);
			$input['md5'] = md5(file_get_contents($file));
			$input['created_at'] = date ("Y-m-d H:i:s", filemtime($file));
			$output[] = $input;
		}

		return $output;
	}

	// Helper function to sort file list.
	public static function sort( $a, $b ) {
		return filemtime($b) - filemtime($a);
	}

	// Human Readable File Size with PHP
	// Thanks to: http://jeffreysambells.com/2012/10/25/human-readable-filesize-php
	public static function human_filesize($bytes, $decimals = 2) {
	    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
	    $factor = floor((strlen($bytes) - 1) / 3);
	    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
	}

	// Generates an unique filename.
	public static function generateUniqueFileName( $prefix = '', $ext = 'sql', $length = 15 ) {

		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $randstring = '';

	    for ($i = 0; $i <= $length; $i++) {
	        $randstring .= $characters[rand(0, strlen($characters) -1)];
	    }

	    return $prefix . $randstring . '.' . $ext;
	}

}