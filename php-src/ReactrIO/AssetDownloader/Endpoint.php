<?php

namespace ReactrIO\AssetDownloader;

class Endpoint
{
    const VERSION='v1';

    protected static $_instances = array();

    // Returns an instance of the endpoint class
    public static function get_instance($endpoint_name){
        if (!isset(self::$_instances[$endpoint_name])) {
            $klass = get_called_class();
            self::$_instances[$endpoint_name] = new $klass($endpoint_name);
        }
        return self::$_instances[$endpoint_name];
    }

    protected function __construct($endpoint_name)
    {
        add_action('rest_api_init', function() use ($endpoint_name) {
            register_rest_route( $endpoint_name . '/' . self::VERSION, '/download', array(
                'methods' => 'POST,GET',
                'callback' => array($this, 'endpoint'),
                'permission_callback' => function(){
                    return \current_user_can('administrator');
                }
            ));
        });
    }

    protected function validate_request(\WP_REST_Request $request)
    {
        // Ensure that the request data is suffiicient
        $params = $request->get_json_params();
        if (!is_array($params) || !isset($params['asset'])) {
            throw E_AssetDownloader::create("Missing asset", $params);
        }

        // Ensure that we can write to the dest path
        if (!is_writable($this->get_dest_folder())) {
            throw E_AssetDownloader::create("Cannot store assets in {$this->get_dest_folder()}. No write access.");
        }
        
        return $params['asset'];
    }

    function endpoint(\WP_REST_Request $request)
    {
        do_action('imagely-asset-downloader-request');

        try {
            $asset = $this->validate_request($request);
            return array(
                'success' => $this->download_url($asset)
            );
        }
        catch (E_AssetDownloader $ex) {
            return $ex->getContext();
        }
        catch (\Exception $ex) {
            return array(
                'error_msg'     => $ex->getMessage(),
                'error_code'    => 'Fatal'
            );
        }
    }

    /**
     * Provides a wrapper around the download_file() method
     * which WP provides
     */
    protected function _fetch_file($url)
    {
        $error = FALSE;
        $retval = NULL;

        // Determine if SSL verification should be disabled
        $ssl_verification_disabled = preg_match("#(\.(local|dev))|localhost#", $url);

        // Define a hook which disables SSL verification
        $hook = function($args) {
            $args['sslverify'] = FALSE;
            return $args;
        };

        // Download the url.
        // If we're to not verify SSL, we need to add a filter before the request,
        // and then remove that filter. Because we don't have a "finally" keyword in PHP 5.4
        // we we have to capture any exception thrown, and then rethrow it
        if ($ssl_verification_disabled) add_filter('http_request_args', $hook);
        try {
            $retval =  \download_url($url);
        }
        catch (\Exception $ex) {
            $error = $ex;
        }
        if ($ssl_verification_disabled) remove_filter('http_request_args', $hook);
        
        if ($error) throw $error;
        
        return $retval;
    }

    function download_url($params)
    {
        // Fetch information about the asset
        $data = AssetManager::get($params);

        // Try downloading the file
        $url            = $data['url'];
        $filename       = $data['filename'];

        include_once(ABSPATH.'/wp-admin/includes/file.php');
        $tmp_filename   = $this->_fetch_file($url);
        if (is_wp_error($tmp_filename)) {
            throw E_AssetDownloader::create("Could not download {$data['url']}", array(
                'keys'  => $params,
                'data'  => $data
            ));
        }

        // Get the destination abspath
        $dest_filename = $this->get_dest_abspath($filename);

        // Move the downloaded file to the appropriate destination
        if (!@rename($tmp_filename, $dest_filename)) {
            throw E_AssetDownloader::create("Could not store {$url} as {$filename}", array(
                'keys'          => $params,
                'data'          => $data,
                'tmp_filename'  => $tmp_filename
            ));
        }

        return TRUE;
    }

    function get_dest_abspath($filename)
    {
        return path_join($this->get_dest_folder(), $filename);
    }

    function get_dest_folder()
    {
        return apply_filters('imagely-asset-downloader-dest', wp_upload_dir()['basedir']);
    }
}