<?php
require_once 'PHPUnit/Framework.php';

abstract class jackalope_baseCase extends PHPUnit_Framework_TestCase {
    protected $path = ''; // Describes the path to the test

    protected $config;
    protected $configKeys = array('jcr.url', 'jcr.user', 'jcr.pass', 'jcr.workspace', 'jcr.transport');

    protected function setUp() {
        foreach ($this->configKeys as $cfgKey) {
//            $this->config[substr($cfgKey, 4)] = $GLOBALS[$cfgKey];
        }
    }

    /** try to create credentials from this user/password */
    protected function assertSimpleCredentials($user, $password) {
        $cr = getSimpleCredentials($user, $password);
        $this->assertTrue(is_object($cr));
        $this->assertTrue($cr instanceOf phpCR_CredentialsInterface);
        return $cr;
    }

    /** try to create a session with the config and credentials */
    protected function assertSession($cfg, $credentials = null) {
        $ses = getJCRSession($cfg, $credentials);
        $this->assertTrue(is_object($ses));
        $this->assertTrue($ses instanceOf phpCR_SessionInterface);
        return $ses;
    }
}
