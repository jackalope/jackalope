<?php
require_once 'PHPUnit/Framework.php';
require_once(dirname(__FILE__) . '/../../src/jackalope/autoloader.php');

abstract class jackalope_baseCase extends PHPUnit_Framework_TestCase {
    protected $config;
    protected $configKeys = array('jcr.url', 'jcr.user', 'jcr.pass', 'jcr.workspace', 'jcr.transport');
    protected $credentials;

    protected function setUp() {
        foreach ($this->configKeys as $cfgKey) {
            $this->config[substr($cfgKey, 4)] = $GLOBALS[$cfgKey];
        }
        $this->credentials = new PHPCR_SimpleCredentials($this->config['user'], $this->config['pass']);
    }
}

