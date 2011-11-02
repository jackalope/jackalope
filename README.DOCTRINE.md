# Jackalope Doctrine Transport

The Jackalope Doctrine transport implements the PHPCR API with a database backend using Doctrine DBAL as access and abstraction layer.

## Installation

You need both Jackalope with the DoctrineDBAL branch and Doctrine Common+DBAL installed on your machine.

## Bootstrapping

    <?php
    // autoloading
    require_once "path/to/doctrine-common/lib/Doctrine/Common/ClassLoader.php";
    require_once "path/to/src/Jackalope/autoloader.php";

    $loader = new \Doctrine\Common\ClassLoader("Doctrine\Common", "path/to/doctrine-common/lib");
    $loader->register();
    $loader = new \Doctrine\Common\ClassLoader("Doctrine\DBAL", "path/to/doctrine-dbal/lib");
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
        $dbConn->exec($sql);
    }

    // Create Jackalope
    $factory = new \Jackalope\RepositoryFactoryDoctrineDBAL();
    $repository = $factory->getRepository(array('jackalope.doctrine_dbal_connection' => $dbConn));

## Creating your first Workspace

The default workspace is automatically created when you first try to access it

    <?php
    //credentials where in dbConn, don't matter here
    $credentials = new \PHPCR\SimpleCredentials(null, null);
    $session = $repository->login($credentials, 'default'); 
    $workspace = $session->getWorkspace();
    $workspace->createWorkspace('myworkspace');

## Getting started

Now you have a 'default' workspace and can start changing stuff:

    <?php

    $session = $repository->login($credentials, "default"); // credentials dont matter
    $rootNode = $session->getNode("/");
    $whitewashing = $rootNode->addNode("www-whitewashing-de");
    $session->save();

    $posts = $whitewashing->addNode("posts");
    $session->save();

    $post = $posts->addNode("welcome-to-blog");
    $post->addMixin("mix:title");
    $post->setProperty("jcr:title", "Welcome to my Blog!");
    $post->setProperty("jcr:description", "This is the first post on my blog! Do you like it?");

    $session->save();

See https://github.com/phpcr/phpcr/blob/master/doc/Tutorial.md for how to use the PHPCR API

# Doctrine Todos

## Known Failures in the API Test

* Connecting_4_RepositoryTest::testDefaultWorkspace fails because fixtures are not loaded, and Doctrine fixtures include the workspaces.

* Reading_5_PropertyReadMethodsTest::testJcrCreated fails because NodeTypeDefinitions do not work inside DoctrineDBAL transport yet.

* NodeReadMethodsTest::testGetPropertiesValuesGlob() fails with "Failed asserting that <integer:2> matches expected <integer:1>.",
  because jcr:uuid is returned although the node is not mix:referencable. This is not checked in DoctrineDBAL::getNode() type.

* Reading_5_PropertyReadMethodsTest::testGetDateMulti and Reading_5_PropertyReadMethodsTest::testGetDate fail,
  because DoctrineDBAL does not support saving timezones of DateTime instances

## Failure count per testsuite

05_Reading
    Tests: 180, Assertions: 358, Failures: 5, Incomplete: 8, Skipped: 2.

10_Writing
    Tests: 120, Assertions: 294, Failures: 3, Errors: 10, Incomplete: 5, Skipped: 4.

## Todos

* Implement moving nodes, DoctrineTransport::modeNode() (and make sure not to violate any constraints during the process)
* Implement usage of NodeTypeDefintions to read/write validation and formatting data correctly (such as auto-creating values, forcing multi-values)
* Implement storage of custom/user-defined NodeTypeDefinitions and such into the database.
* Versioning support
* Refactor storage to implement one one table per database type?
* Optimize database storage more, using real ids and normalizing the uuids and paths?
* Implement parser for JCR-SQL2 and implement it in DoctrineTransport::querySQL().
* Implement parser for Jackrabbit CND syntax for node-type definitions.
