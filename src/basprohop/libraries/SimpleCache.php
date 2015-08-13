<?php

namespace basprohop\libraries;

/*
 * SimpleCache v1.4.1
 *
 * By Gilbert Pellegrom
 * http://dev7studios.com
 *
 * Free to use and abuse under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 */
class SimpleCache {

    // Path to cache folder (with trailing /)
    public $cache_path = 'cache/';
    // Length of time to cache a file (in seconds)
    public $cache_time = 3600;
    // Cache file extension
    public $cache_extension = '.json';

    public function set_cache($label, $data)
    {
        file_put_contents($this->cache_path . $this->safe_filename($label) . $this->cache_extension, $data);
    }

    public function get_cache($label)
    {
        if($this->is_cached($label)){
            $filename = $this->cache_path . $this->safe_filename($label) . $this->cache_extension;
            return file_get_contents($filename);
        }

        return false;
    }

    public function remove_cache($label) {
        return unlink($this->cache_path . $this->safe_filename($label) . $this->cache_extension);
    }

    public function remove_all_cache() {
        $files = glob($this->cache_path . '*');
        foreach($files as $file){
            if(is_file($file)) {
                unlink($file);
            }
        }
    }

    public function is_cached($label)
    {
        $filename = $this->cache_path . $this->safe_filename($label) . $this->cache_extension;

        if(file_exists($filename) && (filemtime($filename) + $this->cache_time >= time())) return true;

        return false;
    }

    //Helper function for retrieving data from url
    public static function do_curl($url, $timeout, $userAgent)
    {
        if(function_exists("curl_init")){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
            $content = curl_exec($ch);
            curl_close($ch);
            return $content;
        } else {
            return file_get_contents($url);
        }
    }

    //Helper function to validate filenames
    private function safe_filename($filename)
    {
        return preg_replace('/[^0-9a-z\.\_\-]/i','', strtolower($filename));
    }
}
