<?php
// autoloading
require_once $GLOBALS['phpcr.doctrine.loader'];
require_once "../src/Jackalope/autoloader.php";
require_once "Jackalope/TestCase.php";

$loader = new \Doctrine\Common\ClassLoader("Doctrine\Common", $GLOBALS['phpcr.doctrine.commondir']);
$loader->register();
$loader = new \Doctrine\Common\ClassLoader("Doctrine\DBAL", $GLOBALS['phpcr.doctrine.dbaldir']);
$loader->register();

// Bootstrap Doctrine
$dbConn = \Doctrine\DBAL\DriverManager::getConnection(array(
    'driver'    => "pdo_mysql",
    'host'      => "localhost",
    'user'      => "root",
    'password'  => "",
    'dbname'    => "jackalope",
));

// only necessary on the first run, creates the database:
$schema = \Jackalope\Transport\DoctrineDBAL\RepositorySchema::create();
foreach ($schema->toSQL($dbConn->getDatabasePlatform()) AS $sql) {
    try {
        $dbConn->exec($sql);
    } catch (\PDOException $e) {
    }
}

// Create Jackalope
$transport = new \Jackalope\Transport\DoctrineDBAL\DoctrineDBALTransport($dbConn);
$repository = new \Jackalope\Repository(null, null, $transport);
