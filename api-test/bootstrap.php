<?php

// PHPUnit 3.4 compat
if (method_exists('PHPUnit_Util_Filter', 'addDirectoryToFilter')) {
    PHPUnit_Util_Filter::addDirectoryToFilter(__DIR__);
    PHPUnit_Util_Filter::addFileToFilter(__DIR__.'/../src/Jackalope/Transport/curl.php');
}

/**
 * Bootstrap file for jackalope
 *
 * This file does some basic stuff that's project specific.
 *
 * function getRepository(config) which returns the repository
 * function getPHPCRSession(config) which returns the session
 *
 * TODO: remove the following once it has been moved to a base file
 * function getSimpleCredentials(user, password) which returns simpleCredentials
 *
 * constants necessary to the JCR 1.0/JSR-170 and JSR-283 specs
 */

// Make sure we have the necessary config
$necessaryConfigValues = array('phpcr.url', 'phpcr.user', 'phpcr.pass', 'phpcr.workspace', 'phpcr.transport');
foreach ($necessaryConfigValues as $val) {
    if (empty($GLOBALS[$val])) {
        die('Please set '.$val.' in your phpunit.xml.' . "\n");
    }
}

/**
 * autoloader: tests rely on an autoloader.
 */
require __DIR__.'/../src/Jackalope/autoloader.php';

### Load two classes needed for jackalope unit tests ###
require __DIR__.'/../tests/Jackalope/TestCase.php';
require __DIR__.'/../tests/Jackalope/Transport/DoctrineDBAL/DoctrineDBALTestCase.php';

/**
 * @return string classname of the repository factory
 */
function getRepositoryFactoryClass()
{
    return 'Jackalope\RepositoryFactoryJackrabbit';
}

/**
 * @return hashmap to be used with the repository factory
 */
function getRepositoryFactoryParameters($config)
{
    return array('jackalope.jackrabbit_uri' => $config['url']);
}


/**
 * Repository lookup is implementation specific.
 * @param config The configuration where to find the repository
 * @return the repository instance
 */
function getRepository($config) {
    if (empty($config['url']) || empty($config['transport'])) {
        return false;
    }
    if ($config['transport'] != 'davex') {
        throw new Exception("Don't know how to handle transport other than davex. (".$config['transport'].')');
    }
    return new \Jackalope\Repository(null, $config['url'], null); //let jackalope factory create the transport
}

/**
 * @param user The user name for the credentials
 * @param password The password for the credentials
 * @return the simple credentials instance for this implementation with the specified username/password
 */
function getSimpleCredentials($user, $password) {
    return new \PHPCR\SimpleCredentials($user, $password);
}

/**
 * Get a session for this implementation.
 * @param config The configuration that is passed to getRepository
 * @param credentials The credentials to log into the repository. If omitted, $config['user'] and $config['pass'] is used with getSimpleCredentials
 * @return A session resulting from logging into the repository found at the $config path
 */
function getPHPCRSession($config, $credentials = null) {
    $repository = getRepository($config);
    if (isset($config['pass']) || isset($credentials)) {
        if (empty($config['workspace'])) {
            $config['workspace'] = null;
        }
        if (empty($credentials)) {
            $credentials = getSimpleCredentials($config['user'], $config['pass']);
        }
        return $repository->login($credentials, $config['workspace']);
    } elseif (isset($config['workspace'])) {
        throw new \PHPCR\RepositoryException(phpcr_suite_baseCase::NOTSUPPORTEDLOGIN);
        //return $repository->login(null, $config['workspace']);
    } else {
        throw new \PHPCR\RepositoryException(phpcr_suite_baseCase::NOTSUPPORTEDLOGIN);
        //return $repository->login(null, null);
    }
}

function getFixtureLoader($config)
{
    require_once "suite/inc/importexport.php";
    return new jackrabbit_importexport(__DIR__."/suite/fixtures/", (isset($config['jackalope_jar']) ? $config['jackalope_jar'] : null));
}

/** some constants */

define('SPEC_VERSION_DESC', 'jcr.specification.version');
define('SPEC_NAME_DESC', 'jcr.specification.name');
define('REP_VENDOR_DESC', 'jcr.repository.vendor');
define('REP_VENDOR_URL_DESC', 'jcr.repository.vendor.url');
define('REP_NAME_DESC', 'jcr.repository.name');
define('REP_VERSION_DESC', 'jcr.repository.version');
define('LEVEL_1_SUPPORTED', 'level.1.supported');
define('LEVEL_2_SUPPORTED', 'level.2.supported');
define('OPTION_TRANSACTIONS_SUPPORTED', 'option.transactions.supported');
define('OPTION_VERSIONING_SUPPORTED', 'option.versioning.supported');
define('OPTION_OBSERVATION_SUPPORTED', 'option.observation.supported');
define('OPTION_LOCKING_SUPPORTED', 'option.locking.supported');
define('OPTION_QUERY_SQL_SUPPORTED', 'option.query.sql.supported');
define('QUERY_XPATH_POS_INDEX', 'query.xpath.pos.index');
define('QUERY_XPATH_DOC_ORDER', 'query.xpath.doc.order');

