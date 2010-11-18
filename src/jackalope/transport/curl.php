<?php
namespace jackalope\transport;

//TODO: Write phpt tests

/** Capsulate curl as an object */
class curl {
    protected $curl;
    
    /**
     * @param   string      $url        If provided, sets the CURLOPT_URL
     * @see curl_init
     */
    public function __construct($url = null) {
        $this->curl = curl_init($url);
    }
    
    /**
     * @param   int     $option
     * @param   mixed   $value
     * @see curl_setopt
     */
    public function setopt($option, $value) {
        return curl_setopt($this->curl, $option, $value);
    }
    
    /**
     * @param   array   $options
     * @see curl_setopt_array
     */
    public function setopt_array($options) {
        return curl_setopt_array($this->curl, $options);
    }
    
    /**
     * @return  bool    FALSE on failure otherwise TRUE or string (if CURLOPT_RETURNTRANSFER option is set)
     * @see curl_exec
     */
    public function exec() {
        return curl_exec($this->curl);
    }
    
    /**
     * @return  string
     * @see curl_error
     */
    public function error() {
        return curl_error($this->curl);
    }
    
    /**
     * @return  int
     * @see curl_errno
     */
    public function errno() {
        return curl_errno($this->curl);
    }
    
    /**
     * @param   int     $option
     * @return  string|array    Returns a string if options is given otherwise associative array
     * @see curl_getinfo
     */
    public function getinfo($option = null) {
        return curl_getinfo($this->curl, $option);
    }
    
    /**
     * @see curl_close
     */
    public function close() {
        return curl_close($this->curl);
    }
}
