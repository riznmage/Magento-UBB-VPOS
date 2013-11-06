<?php

class Configuration
{
    private $filename;
    private $config;
    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->load();
    }
    public function __destruct()
    {
        // $this->save();
    }
    public function load()
    {
    	if (file_exists ( $this->filename )) {
	        $this->config = parse_ini_file($this->filename, true);
    	} else {
    	    $this->config = array();
    	}
    }
    public function save()
    {
        $f = fopen($this->filename, 'w');
        foreach( $this->config as $section => $keys ) {
            fwrite($f, "[$section]\n");
            foreach( $keys as $key => $value ) {
                fwrite($f, "$key = \"$value\"\n");
            }
            fwrite($f, "\n");
        }
        fclose($f);
    }
    public function get($key)
    {
        $key = strtolower($key);
        $keys = explode('.', $key);
        if ( count($keys) != 2 )
        {
            die('Required format is section.key');
        }
        if (strlen($section) == 0) {
            $section = "nonkeyedsection";
        }
        $section = $keys[0];
        $key = $keys[1];
        if ( array_key_exists($section, $this->config) && array_key_exists($key, $this->config[$section]) )
        {
            return $this->config[$section][$key];
        }
        else
        {
            return "";
        }
    }
    public function set($key, $value)
    {
        $key = strtolower($key);
        $keys = explode('.', $key);
        if ( count($keys) != 2 ) {
            die('Required format is section.key');
        }
        $section = $keys[0];
        if (strlen($section) == 0) {
            $section = "nonkeyedsection";
        }

        $key = $keys[1];
        $this->config[$section][$key] = $value;
    }
}
?>