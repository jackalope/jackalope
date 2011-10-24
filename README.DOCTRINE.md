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
