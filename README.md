# Jackalope

Implementation of a PHP client for the Jackrabbit server, an implementation of
the Java Content Repository JCR.

Implements the PHPCR interface
(see https://phpcr.github.com for more about PHPCR).

[http://liip.to/jackalope](http://liip.to/jackalope)

Discuss on jackalope-dev@googlegroups.com
or visit #jackalope on irc.freenode.net

TODO: license


# Preconditions

* libxml version >= 2.7.0 (due to a bug in libxml [http://bugs.php.net/bug.php?id=36501](http://bugs.php.net/bug.php?id=36501))


# Setup

Clone the jackalope project

    git clone git://github.com/jackalope/jackalope.git

Update submodules

    git submodule init
    git submodule update


Jackalope provides the PHPCR API for application. In order to actually store
data, it needs a storage backend. The current storage engine is Jackrabbit:


## Jackrabbit Storage Engine

Please download Jackrabbit here: http://jackrabbit.apache.org
You need the jackrabbit-standalone-2.x.jar

Once you have the jar, start it with
    $ java -jar jackrabbit*.jar

When you start it the first time, this will create a folder called "jackrabbit" with some subfolders.

Now you are ready to use the library. Have a look at api-tests/bootstrap.php
too see how to instantiate a repository.


# Tests

Our continuos integration server with coverage reports at:
[http://bamboo.liip.ch/browse/JACK](http://bamboo.liip.ch/browse/JACK)


## Running the api tests

Run phpunit with the configuration in api-tests
phpunit -c /path/to/jackalope/api-tests

You should see success, but we still have errors (mostly NotImplementedException)
and a few failures
If you have something like this, it works (yeah, FAILURES are ok):

    FAILURES!
    Tests: 375, Assertions: 672, Failures: 2, Errors: 26, Incomplete: 44, Skipped: 20.

There are two kind of tests. The folder *api-tests* contains the
phpcr-api-tests suite to test against the specification.
This is what you want to look at when using jackalope as a PHPCR implementation.

The folder *tests* contains unit tests for the jackalope implementation.
You should only need those if you want to debug jackalope itselves or implement
new features. Again, make sure you have the test workspace in jackrabbit.


### Jackrabbit/Jackalope Setup

To run the tests yourself, you need to do some steps:
* Make sure your submodules are up to date, as the test suite is included as submodule
* Add a jackrabbit test workspace:

See [http://jackrabbit.apache.org/jackrabbit-configuration.html#JackrabbitConfiguration-Workspaceconfiguration](http://jackrabbit.apache.org/jackrabbit-configuration.html#JackrabbitConfiguration-Workspaceconfiguration)

Alternatively, you can just clone the default workspace:

Go to the directory you started jackrabbit-standalone (eg. /opt/svn/jackrabbit/jackrabbit-standalone/target) and copy the default-workspace to a workspace called "test"

     cp -rp jackrabbit/workspaces/default jackrabbit/workspaces/tests

You then will have to adjust the jackrabbit/workspaces/tests/workspace.xml:

Change the following attribute:

     <Workspace name="default">
to

    <Workspace name="tests">

Then start jackrabbit again



# Contributors

* Christian Stocker <chregu@liip.ch>
* David Buchmann <david@liip.ch>
* Tobias Ebnöther <ebi@liip.ch>
* Roland Schilter <roland.schilter@liip.ch>
* Uwe Jäger <uwej711@googlemail.com>
* Lukas Kahwe Smith <lukas@liip.ch>
* Benjamin Eberlei <kontakt@beberlei.de>
* Daniel Barsotti <daniel.barsotti@liip.ch>
