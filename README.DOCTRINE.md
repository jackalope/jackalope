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
    $schema = \Jackalope\Transport\Doctrine\RepositorySchema::create();
    foreach ($schema->toSQL($dbConn->getDatabasePlatform()) AS $sql) {
        $dbConn->exec($sql);
    }

    // Create Jackalope
    $transport = new \Jackalope\Transport\Doctrine\DoctrineTransport($dbConn);
    $repository = new \Jackalope\Repository(null, null, $transport);

## Creating your first Workspace

Using $transport from above you have to create the initial workspace:

    <?php
    $transport->createWorkspace('default');

## Getting started

Now you have a 'default' workspace and can start changing stuff:

    <?php

    $session = $repository->login(new \PHPCR\SimpleCredentials("foo", "bar"), "default"); // credentials dont matter
    $rootNode = $session->getNode("/");
    $whitewashing = $rootNode->addNode("www-whitewashing-de");
    $session->save();

    $posts = $whitewashing->addNode("posts");
    $session->save();

    $post = $posts->addNode("welcome-to-blog");
    $post->addMixin("mix:title");
    $post->setProperty("jcr:title", "Welcome to my Blog!");
    $post->setProperty("jcr:description", "This is the first post on my blog!Do you like it?");

    $session->save();

