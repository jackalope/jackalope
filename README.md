# Jackalope

Implementation of a PHP client for the Jackrabbit server, an implementation of
the Java Content Repository JCR.

Implements the PHPCR interface
(see https://phpcr.github.com for more about PHPCR).

[http://liip.to/jackalope](http://liip.to/jackalope)

Discuss on jackalope-dev@googlegroups.com
or visit #jackalope on irc.freenode.net

License: This code is licenced under the apache license.
Please see the file LICENSE in this folder.


# Preconditions

* libxml version >= 2.7.0 (due to a bug in libxml [http://bugs.php.net/bug.php?id=36501](http://bugs.php.net/bug.php?id=36501))
* phpunit >= 3.5 (if you want to run the tests)

# Setup

See https://github.com/jackalope/jackalope/wiki/Downloads


# Tests

Our continuos integration server with coverage reports at:
[http://bamboo.liip.ch/browse/JACK](http://bamboo.liip.ch/browse/JACK)


## Running the api tests

Run phpunit with the configuration in api-tests
phpunit -c /path/to/jackalope/api-tests

You should see mostly success, but there might be the odd error or failure

There are two kind of tests. The folder *api-tests* contains the
phpcr-api-tests suite to test against the specification.
This is what you want to look at when using jackalope as a PHPCR implementation.

The folder *tests* contains unit tests for the jackalope implementation.
You should only need those if you want to debug jackalope itselves or implement
new features. Again, make sure you have the test workspace in jackrabbit.

The phpunit.xml in api-tests runs all tests, both the unit and the api tests.
The phpunit.xml in tests runs only the unit tests.


# Contributors

* Christian Stocker <chregu@liip.ch>
* David Buchmann <david@liip.ch>
* Tobias Ebnöther <ebi@liip.ch>
* Roland Schilter <roland.schilter@liip.ch>
* Uwe Jäger <uwej711@googlemail.com>
* Lukas Kahwe Smith <lukas@liip.ch>
* Benjamin Eberlei <kontakt@beberlei.de>
* Daniel Barsotti <daniel.barsotti@liip.ch>
