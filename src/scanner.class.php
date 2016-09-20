<?php
namespace Umbrella;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Scanner
{

    /**
     * Get Whitelist
     * Get whitelist for the current WP version.
     * @return void
    */
    public function whitelist()
    {
        $locale = get_locale();
        $data_file = \Umbrella\Scanner::get_db_file();

        $ignored_files = $this->ignored_files();

        // Return whitelist as array if found for current WP version.
        if (file_exists($data_file)) {
            $data = parse_ini_file($data_file, true);

            if(is_array($ignored_files))
                return array_merge($data, $ignored_files);
            else
                return $data;
        }

        else
            return false;
    }


    public static function get_db_file() {
        global $wp_version;
        $version = str_replace('.','', $wp_version);
        return UMBRELLA__STORAGE_DIR . ".database-{$version}.ini";
    }

    /**
     * Get Ignored Files
     * Get Ignored Files for the current WP version.
     * @return void
    */
    public function ignored_files()
    {
        $wp_version = $this->wp_version();

        $ignored_files = '';
        $ignored_files = get_option('umbrella-sp-ignored-files');

          // If option is set, unserialize it into an array.
        if (!empty($ignored_files))
            $ignored_files = unserialize($ignored_files);

        if (isset($ignored_files[$wp_version]))
            return $ignored_files[$wp_version];
        else
            return false;
    }

    public static function google_safe_browsing_code()
    {
        /* Start request */
        $response = wp_remote_get(
            sprintf(
                'https://sb-ssl.google.com/safebrowsing/api/lookup?client=191233873456-jfbgdmrlv1kqlmot3559mnu1rrglggfa.apps.googleusercontent.com&key=%s&appver=1.5.2&pver=3.1&url=%s',
                'AIzaSyCaX3EIz6RzsF1MlDoJ8wJENsFg0v-dcXc',
                urlencode( get_bloginfo('url') )
            ),
            array(
                'sslverify' => false
            )
        );

        if (!is_wp_error($response))
            return $response['response']['code'];
        else
            return __('Unknown', UMBRELLA__TEXTDOMAIN);
    }

    /**
     * Vulnerabilities Scanner for Plugins
     * This will scan installed plugins for vulnerabilities.
     * @return array
    */
    public static function vulndb_plugins()
    {
        $all_plugins = get_plugins();

        $plugins = array();

        foreach ($all_plugins as $key => $plugin)
        {
            unset($merge);
            unset($json);

            $slug = explode('/', $key);
            $slug = reset($slug);

            if ($slug == 'hello.php')
                continue;

            if ( false === ( $json = get_transient( "umbrella_vulndb_{$slug}" ) ) ) {
                $json = wp_remote_get( "https://wpvulndb.com/api/v1/plugins/{$slug}" );

                if (!\is_wp_error($json))
                    set_transient( "umbrella_vulndb_{$slug}", $json, 300 );
            }

            if (!\is_wp_error($json))
                $merge = array('vulndb' => $json);
            else
                $merge['vulndb']['error']['code'] = '501';


            $plugins[] = array_merge($plugin,$merge);
        }

        return $plugins;
    }

    /**
     * Plugins Errors
     * Get errors from Vulndb for scanned plugins.
     * @return array
    */
    public static function vulndb_plugins_errors() {
        $plugins = Scanner::vulndb_plugins();

        $errors_total = 0;

        foreach ($plugins as $plugin)
        {
            $code = $plugin['vulndb']['response']['code'];

            if ($code == 200)
            {
                $vulndbs = json_decode($plugin['vulndb']['body']);
                if (is_object($vulndbs)) {
                    foreach($vulndbs->plugin->vulnerabilities as $v) {
                        $errors_total++;
                    }
                }
            }
        }

        return array(
            'errors_total' => $errors_total
        );
    }

    /**
     * Vulnerabilities Scanner for Themes
     * This will scan installed themes for vulnerabilities.
     * @return array
    */
    public static function vulndb_themes()
    {
        $all_themes = wp_get_themes();
        $themes = array();

        foreach ($all_themes as $slug => $theme)
        {
            unset($merge);
            unset($json);

            if ( false === ( $json = get_transient( "umbrella_vulndb_theme_{$slug}" ) ) ) {
                $json = wp_remote_get( "https://wpvulndb.com/api/v1/themes/{$slug}" );

                if (!\is_wp_error($json))
                    set_transient( "umbrella_vulndb_theme_{$slug}", $json, 300 );
            }

            $merge = array(
                'Name' => $theme->get('Name'),
                'Version' => $theme->get('Version'),
                'Author' => $theme->get('Author'),
            );

            if (!\is_wp_error($json))
                $merge['vulndb'] = $json;
            else {
                $merge['vulndb']['error']['code'] = '0';
            }

            $themes[] = $merge;

        }

        return $themes;
    }

    /**
     * Get Whitelist
     * Get whitelist for the current WP version.
     * @return json
    */
    public function api_get_files()
    {
        die( json_encode( $this->list_core_files() ) );
    }

    /**
     * Reverse Ip
     * Get all domains sharing the same ip
     * @return json
    */
    static public function reverse_ip()
    {
        $ip = $_SERVER['HTTP_HOST'];
        $url = 'http://api.hackertarget.com/reverseiplookup/?q=' . $ip;

        if ($ip == 'localhost')
            return 0;

        $data = wp_remote_get($url);

        if (!is_wp_error($data)) {
            $data = explode("\n", $data['body']);

            if (substr($data[0], 0, 10) == "No records")
                return 0;
        }
        else
            $data = array();

        return count($data);
    }

    /**
     * Has Cloudflare
     * Return true if site is protected by Cloud Flare.
     * @return bool
    */
    static public function has_cloudflare()
    {
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"]))
            return true;
        else
            false;
    }

    /**
     * Monitor files list
     * Return lists of files to monitor and not include in file scanner.
     * @return array
    */
    public function monitor_files_list()
    {
        return array(
            'wp-config-sample.php',
            'wp-includes/version.php',
            'wp-content/',
            'wp-config.php',
            'readme.html',
            '.txt',
            '/..',
            '/.',
        );
    }

    /**
     * List core files
     * List core files of the current install.
     * @return void
    */
    public function list_core_files()
    {

        $exclude = $this->monitor_files_list();

        // Get files and directories list.
        $files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( ABSPATH ) );

        // new empty output
        $output = array();

        foreach ($files as $file)
        {

            $file = (string) $file;

            $continue = 0;
            foreach ($exclude as $e)
            {
                if ( strpos($file, $e) !== false )
                    $continue = 1;
            }

            if ($continue == 0)
                $output[] = str_replace(ABSPATH, '', $file);
            else
                $continue = 1;

        }

        usort($output, array(&$this, 'sort'));

        return $output;

    }

    public function sort($a,$b){
        return strlen($a)-strlen($b);
    }


    /**
     * Compare File
     * Get whitelist for the current WP version.
     * @return void
    */
    public function compare( $file_path = '' )
    {
        global $wp_version;
        $whitelist = $this->whitelist();

        $file = str_replace(ABSPATH, '', $file_path);

        // File is unknown (not included in core)
        if (!isset($whitelist[$file]))
            return "File is not included in core";

        $svn_url = "https://core.svn.wordpress.org/tags/{$wp_version}/{$file}";
        $local_file_data = file_get_contents( ABSPATH . $file );

        $svn_request = wp_remote_get($svn_url);

        if (is_wp_error($svn_request))
            return __('Connection Problem', UMBRELLA__TEXTDOMAIN);

        $svn_file_data = $svn_request['body'];

        $diff = Diff::compare($svn_file_data, $local_file_data);
        return Diff::toTable($diff);

    }

    /**
     * Check File
     * Get whitelist for the current WP version.
     * @return void
    */
    public function check_file( $file = '' )
    {
        $whitelist = $this->whitelist();
        $file_path = ABSPATH . $file;

        // File is unknown (not included in core)
        if (!isset($whitelist[$file])) {

            $url = "admin.php?page=umbrella-scanner&action=ignore&file={$file}";
            $nonce_url =  wp_nonce_url( $url, "ignore_{$file}" );

            return array(
                'error' => array('code' => '0010', 'msg' => 'Unexpected file'),
                'file' => ABSPATH . $file,
                'file_path' => $file_path,
                'filesize' => filesize($file_path),
                'buttons' => array(
                    array(
                        'label' => __('Ignore', UMBRELLA__TEXTDOMAIN),
                        'href' => $nonce_url
                    ),
                )
            );

        }

        $original_size = $whitelist[$file];

        if (filesize($file_path) != $original_size)
        {

            $ignore_url = "admin.php?page=umbrella-scanner&action=ignore&file={$file}";
            $ignore_nonce_url =  wp_nonce_url( $ignore_url, "ignore_{$file}" );

            $compare_url = "#compare-results";

            return array(
                'error' => array('code' => '0020', 'msg' => 'Modified file'),
                'file' => $file,
                'file_path' => $file_path,
                'filesize' => filesize($file_path),
                'original_filesize' => $original_size,
                'buttons' => array(
                    array(
                        'label' => __('Ignore', UMBRELLA__TEXTDOMAIN),
                        'href' => $ignore_nonce_url
                    ),
                    array(
                        'label' => __('View Changes', UMBRELLA__TEXTDOMAIN),
                        'href' => $compare_url
                    ),
                )
            );
        }

        return false;

    }

    /**
     * Remove File
     * Completely remove a file from the system.
     * @return void
    */
    public function remove_file($file)
    {
        if (!wp_verify_nonce( $_GET['_wpnonce'], "remove_{$file}" )) die();

        global $wp_filesystem;

        $form_url = "admin.php?page=umbrella-scanner&action=remove&file=" . $file."&_wpnonce=".$_GET['_wpnonce'];

        if (false === ($creds = request_filesystem_credentials($form_url, 'ftp', false, ABSPATH))) {
            /**
             * if we comes here - we don't have credentials
             * so the request for them is displaying
             * no need for further processing
             **/
            return false;
        }

        $file = esc_attr($file);
        echo 'Remove file: ' . $file;
    }

    /**
     * Ignore File
     * Temporary ignore file modifications on file.
     * @return void
    */
    public function ignore_file($file)
    {

        $wp_version = $this->wp_version();
        $file = esc_attr($file);
        $file_path = ABSPATH . $file;

        // Get ignored files.
        $ignored_files = $this->ignored_files();

        // Get md5 checksum.
        $filesize = filesize($file_path);

        // Add string and md5.
        $ignored_files[$file] = $filesize;

        $option[$wp_version] = $ignored_files;

        // Add new file top option cache.
        update_option('umbrella-sp-ignored-files', serialize($option));
    }

    /**
     * WP Version
     * Get current WP version
     * @return void
    */
    public function wp_version()
    {
        global $wp_version;
        return str_replace('.','', $wp_version);
    }

    /**
     * Download core tree
     * Download file sizes from github commit
     * @return json
    */

    public function download_core_tree() {
        global $wp_version;

        try {
            // Download tags list
            $tags_list = wp_remote_get('https://api.github.com/repos/WordPress/WordPress/tags', array( 'timeout' => 60));
            $tags_list = json_decode($tags_list['body']);

            foreach($tags_list as $version) {
                if ($version->name == $wp_version) {
                    $commit_info = wp_remote_get($version->commit->url, array( 'timeout' => 60));
                    $commit_info = json_decode($commit_info['body']);

                    $tree = wp_remote_get($commit_info->commit->tree->url . '?recursive=1', array( 'timeout' => 60));
                    return json_decode($tree['body']);
                }
            }
            return FALSE;
        }
        catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * Build Core List
     * Get md5 checksums and build core list db file.
     * @return void
    */

    public function build_core_list() {
        global $wp_version;

        $data_list = array();
        $data_file = \Umbrella\Scanner::get_db_file();

        if (file_exists($data_file)) {
            return;
        }

        // Get any existing copy of our transient data
        if ( false === ( $core_tree_list = get_transient( 'core_tree_list_' . $wp_version ) ) ) {
            // It wasn't there, so regenerate the data and save the transient
            $core_tree_list = $this->download_core_tree();
            set_transient( 'core_tree_list_' . $wp_version, $core_tree_list, 1 * 60 );
        }

        if (!$core_tree_list)
            return false;

        foreach ($core_tree_list->tree as $file) {
            if (!isset($file->size))
                continue; // Continue if not a file (because then it's a directory)
            $data_list[$file->path] = $file->size;
        }

        $output = arr2ini($data_list);

        file_put_contents($data_file, $output);
    }

}

add_action( 'wp_ajax_umbrella_compare_file', function() {

    $scanner = new Scanner();
    $output = $scanner->compare($_POST['file_path']);
    die($output);

} );


add_action( 'wp_ajax_umbrella_build_core_list', function() {

    $scanner = new Scanner();
    $scanner->build_core_list();
    die(json_encode(array('status' => 'ok')));

} );


add_action( 'wp_ajax_umbrella_filescan', function() {

    delete_transient('umbrella-file-scan');

    $scanner = new Scanner();
    $files = $scanner->list_core_files();

    $output = array();
    foreach ($files as $file)
    {
        if ($scanner->check_file($file)) {
            $response = $scanner->check_file($file);
            $output[] = array('file' => $file, 'response' => $response, 'file_path' => $response['file_path']);
        }
    }

    // Cache scan for 30 minutes.
    set_transient('umbrella-file-scan', $output, 1800);
    set_transient('umbrella-latest-file-scan', $scanner->wp_version() . UMBRELLA__VERSION, 1800);

    die(json_encode($output));
} );


add_action('admin_init', function() {

    if (current_user_can('administrator')
        AND isset($_GET['page'])
        AND $_GET['page'] == 'umbrella-scanner'
    ){
        if (isset($_GET['action']))
        {
            $scanner = new Scanner;

            switch($_GET['action'])
            {
                case 'remove'; $scanner->remove_file($_GET['file']); break;
                case 'ignore'; $scanner->ignore_file($_GET['file']); break;
                case 'get_files'; $scanner->api_get_files(); break;
                case 'check_file';
                    echo json_encode($scanner->check_file($_GET['file']));
                    die();
                break;
            }
        }
    }

});

function arr2ini(array $a, array $parent = array())
{
    $out = '';
    foreach ($a as $k => $v)
    {
        if (is_array($v))
        {
            //subsection case
            //merge all the sections into one array...
            $sec = array_merge((array) $parent, (array) $k);
            //add section information to the output
            $out .= '[' . join('.', $sec) . ']' . PHP_EOL;
            //recursively traverse deeper
            $out .= arr2ini($v, $sec);
        }
        else
        {
            //plain key->value case
            $out .= "$k=$v" . PHP_EOL;
        }
    }
    return $out;
}