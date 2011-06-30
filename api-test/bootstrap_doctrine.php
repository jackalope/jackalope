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
$necessaryConfigValues = array('phpcr.doctrine.loader', 'phpcr.doctrine.commondir', 'phpcr.doctrine.dbaldir');
foreach ($necessaryConfigValues as $val) {
    if (empty($GLOBALS[$val])) {
        die('Please set '.$val.' in your phpunit.xml.' . "\n");
    }
}

require_once($GLOBALS['phpcr.doctrine.loader']);

$loader = new \Doctrine\Common\ClassLoader("Doctrine\Common", $GLOBALS['phpcr.doctrine.commondir']);
$loader->register();

$loader = new \Doctrine\Common\ClassLoader("Doctrine\DBAL", $GLOBALS['phpcr.doctrine.dbaldir']);
$loader->register();

/** autoloader: jackalope-api-tests relies on an autoloader.
 */
require_once(dirname(__FILE__) . '/../src/Jackalope/autoloader.php');

$dbConn = \Doctrine\DBAL\DriverManager::getConnection(array(
    'driver'    => $GLOBALS['phpcr.doctrine.dbal.driver'],
    'host'      => $GLOBALS['phpcr.doctrine.dbal.host'],
    'user'      => $GLOBALS['phpcr.doctrine.dbal.username'],
    'password'  => $GLOBALS['phpcr.doctrine.dbal.password'],
    'dbname'    => $GLOBALS['phpcr.doctrine.dbal.dbname']
));
$schema = \Jackalope\Transport\DoctrineDBAL\RepositorySchema::create();
foreach ($schema->toDropSql($dbConn->getDatabasePlatform()) AS $sql) {
    try {
        $dbConn->exec($sql);
    } catch(PDOException $e) {
        echo $e->getMessage();
    }
}
foreach ($schema->toSql($dbConn->getDatabasePlatform()) AS $sql) {
    try {
    $dbConn->exec($sql);
    } catch(PDOException $e) {
        echo $e->getMessage();
    }
}

/**
 * @return string classname of the repository factory
 */
function getRepositoryFactoryClass()
{
    return 'Jackalope\RepositoryFactoryDoctrineDBAL';
}

/**
 * @return hashmap to be used with the repository factory
 */
function getRepositoryFactoryParameters($config)
{
    global $dbConn;
    return array('jackalope.doctrine_dbal_connection' => $dbConn);
}

/**
 * Repository lookup is implementation specific.
 * @param config The configuration where to find the repository
 * @return the repository instance
 */
function getRepository($config) {
    global $dbConn;

    if (!$dbConn instanceof \Doctrine\DBAL\Connection || empty($config['transport'])) {
        return false;
    }
    if ($config['transport'] != 'doctrinedbal') {
        throw new Exception("Don't know how to handle transport other than doctrinedbal. (".$config['transport'].')');
    }

    $dbConn->insert('phpcr_workspaces', array('name' => 'tests'));
    $transport = new \Jackalope\Transport\DoctrineDBAL\DoctrineDBALTransport(new \Jackalope\Factory, $dbConn);
    $GLOBALS['pdo'] = $dbConn->getWrappedConnection();
    return new \Jackalope\Repository(null, null, $transport); //let jackalope factory create the transport
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
    return new DoctrineFixtureLoader($GLOBALS['pdo'], __DIR__ . "/fixtures/doctrine/");
}

require_once "suite/inc/importexport.php";
class DoctrineFixtureLoader implements phpcrApiTestSuiteImportExportFixtureInterface
{
    private $testConn;
    private $fixturePath;

    public function __construct($conn, $fixturePath)
    {
        $this->testConn = new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($conn, "tests");
        $this->fixturePath = $fixturePath;
    }

    public function import($file)
    {
        $file = $this->fixturePath . $file . ".xml";

        if (!file_exists($file)) {
            throw new PHPUnit_Framework_SkippedTestSuiteError();
        }

        $dataSet = new PHPUnit_Extensions_Database_DataSet_XmlDataSet($file);

        $tester = new PHPUnit_Extensions_Database_DefaultTester($this->testConn);
        $tester->setSetUpOperation(PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT());
        $tester->setTearDownOperation(PHPUnit_Extensions_Database_Operation_Factory::NONE());
        $tester->setDataSet($dataSet);
        try {
            $tester->onSetUp();
        } catch(PHPUnit_Extensions_Database_Operation_Exception $e) {
            throw new RuntimeException("Could not load fixture ".$file.": ".$e->getMessage());
        }
    }
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
