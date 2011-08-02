# Jackalope

A powerful implementation of the [PHPCR API](http://phpcr.github.com).

You can use Jackalope with different storage backends. For now, we support
relational databases with the DoctrineDBAL backend, and the Jackrabbit server
with the Jackrabbit backend.
There is a different factory for each backend, see below for an introduction.


Discuss on jackalope-dev@googlegroups.com
or visit #jackalope on irc.freenode.net

License: This code is licenced under the apache license.
Please see the file LICENSE in this folder.


# Preconditions

* libxml version >= 2.7.0 (due to a bug in libxml [http://bugs.php.net/bug.php?id=36501](http://bugs.php.net/bug.php?id=36501))
* phpunit >= 3.5 (if you want to run the tests)


# Setup

See https://github.com/jackalope/jackalope/wiki/Downloads


## Tests

See the wiki pages for how to set up testing: [DoctrineDBAL](https://github.com/jackalope/jackalope/wiki/DoctrineDBAL) | [Jackrabbit](https://github.com/jackalope/jackalope/wiki/Setup-with-jackrabbit).


# Usage

The entry point is to create the repository factory. The factory specifies the
storage backend as well.

    $factoryclass = 'Jackalope\RepositoryFactoryJackrabbit'; // or 'Jackalope\RepositoryFactoryDoctrineDBAL'
    $factory = new $factoryclass;
    // see the Doctrine factory for available parameters for the doctrine backend
    $repository = $factory->getRepository(array('jackalope.jackrabbit_uri' => 'http://localhost:8080/server'));
    $credentials = new SimpleCredentials('username', 'password');
    $session = $repository->login($credentials, 'default');

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


# Implementation

See (doc/architecture.md) for an introduction how Jackalope is built. Have a
look at the source files and generate the phpdoc.


# Contributors

* Christian Stocker <chregu@liip.ch>
* David Buchmann <david@liip.ch>
* Tobias Ebnöther <ebi@liip.ch>
* Roland Schilter <roland.schilter@liip.ch>
* Uwe Jäger <uwej711@googlemail.com>
* Lukas Kahwe Smith <lukas@liip.ch>
* Benjamin Eberlei <kontakt@beberlei.de>
* Daniel Barsotti <daniel.barsotti@liip.ch>
