# Tests

To bootstrap for the jackrabbit tests, see the [wiki page](https://github.com/jackalope/jackalope/wiki/Setup-with-jackrabbit)

There are two kind of tests. The folder ``tests/phpcr-api`` contains the
[phpcr-api-tests](https://github.com/phpcr/phpcr-api-tests/) suite to test
against the specification. This is what you want to look at when using
jackalope as a PHPCR implementation.

The folder ``tests/Jackalope`` contains unit tests for the jackalope
implementation. You should only need those if you want to debug jackalope
itself or implement new features. Again, make sure you have the test
workspace in jackrabbit.


There is one bootstrap and one phpunit file per backend implementation.
The additional classes required to bootstrap the tests are found in inc/
Utility code is placed in bin/ and lib/.


The phpunit_*.xml.dist are configured to run all tests. You can limit the tests
to run by specifying the path to those tests to phpunit.

Note that the phpcr-api tests are skipped for features not implemented in
jackalope. Have a look at the tests/inc/*ImplementationLoader.php files to see
which features are skipped for what backend.


# Some notes on the jackalope-jackrabbit api testing.

## Using JackrabbitFixtureLoader for load your own fixtures

Note that the best would be to implement the Session::importXML method

Until this happens, you can use the class JackrabbitFixtureLoader found in
inc/JackrabbitFixtureLoader.php to import fixtures in the JCR XML formats.
It relies on jack.jar. The class can be plugged in Symfony2 autoload mechanism
through autoload.php, which can be used to feed a MapFileClassLoader instance. E.g:


    $phpcr_loader = new MapFileClassLoader(
        __DIR__.'/../vendor/doctrine-phpcr-odm/lib/vendor/jackalope/inc/JackrabbitFixtureLoader.php'
    );
    $phpcr_loader->register();


## Note on JCR

It would be nice if we were able to run the relevant parts of the JSR-283
Technology Compliance Kit (TCK) against php implementations. Note that we would
need to have some glue for things that look different in PHP than in Java, like
the whole topic of Value and ValueFactory.
[https://jira.liip.ch/browse/JACK-24](https://jira.liip.ch/browse/JACK-24)

Once we manage to do that, we could hopefully also use the performance test suite
[https://jira.liip.ch/browse/JACK-23](https://jira.liip.ch/browse/JACK-23)
