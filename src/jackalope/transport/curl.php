<?php
PHPUnit_Util_Filter::addFileToFilter(__FILE__);
//TODO: Write phpt tests

class jackalope_transport_curl {
    protected $curl;
    
    public function __construct($str = null) {
        $this->curl = curl_init($str);
    }
    
    public function setopt($option, $value) {
        return curl_setopt($this->curl, $option, $value);
    }
    
    public function setopt_array($options) {
        return curl_setopt_array($this->curl, $options);
    }
    
    public function exec() {
        return curl_exec($this->curl);
    }
    
    public function error() {
        return curl_error($this->curl);
    }
    
    public function errno() {
        return curl_errno($this->curl);
    }
    
    public function getinfo($option = null) {
        return curl_getinfo($this->curl, $option);
    }
    
    public function close() {
        return curl_close($this->curl);
    }
}
