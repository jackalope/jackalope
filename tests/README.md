# Tests

There are two kind of tests. The folder ``tests/phpcr-api`` contains the
[phpcr-api-tests](https://github.com/phpcr/phpcr-api-tests/) suite to test
against the specification. This is what you want to look at when using
jackalope as a PHPCR implementation.

The folder ``tests/Jackalope`` contains unit tests for the jackalope
implementation. You should only need those if you want to debug jackalope
itselves or implement new features. Again, make sure you have the test
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

## Troubleshooting

If you get :
```bash
FPHP Fatal error:  Uncaught exception 'PHPCR\NoSuchWorkspaceException' with message 'HTTP 409: tests' in /home/fabriceb/dev/sfcmf/jackalope/src/jackalope/transport/DavexClient.php:393
Stack trace:
#0 /home/fabriceb/dev/sfcmf/jackalope/src/jackalope/transport/DavexClient.php(104): jackalope\transport\DavexClient->getDomFromBackend('PROPFIND', 'http://localhos...', '<?xml version="...')
#1 /home/fabriceb/dev/sfcmf/jackalope/src/jackalope/Repository.php(57): jackalope\transport\DavexClient->login(Object(PHPCR_SimpleCredentials), 'tests')
#2 /home/fabriceb/dev/sfcmf/jackalope/api-test/bootstrap.php(67): jackalope\Repository->login(Object(PHPCR_SimpleCredentials), 'tests')
#3 /home/fabriceb/dev/sfcmf/jackalope/api-test/suite/inc/baseCase.php(25): getJCRSession(Array)
#4 [internal function]: jackalope_baseCase::setupBeforeClass()
#5 /usr/share/php/PHPUnit/Framework/TestSuite.php(648): call_user_func(Array)
#6 /usr/share/php/PHPUnit/Framework/TestSuite.php(688): PHPUnit_Framework_TestSuite->run(Object(PHPUnit_Framework_TestResult), fa in /home/fabriceb/dev/sfcmf/jackalope/src/jackalope/transport/DavexClient.php on line 393
```
Check that you have copied correctly the tests workspace into your jackrabbit/workspace directory *and* that you restarted the jackrabbit server afterwards



## Using JackrabbitFixtureLoader for load your own fixtures

Note that the best would be to implement the Session::importXML method

Until this happens, you can use the class JackrabbitFixtureLoader found in
inc/JackrabbitFixtureLoader.php to import fixtures in the JCR XML formats.
It relies on jack.jar. The class can be plugged in Symfony2 autoload mechanism
through autoload.php, which can be used to feed a MapFileClassLoader istance. E.g:

```php
$phpcr_loader = new MapFileClassLoader(
  __DIR__.'/../vendor/doctrine-phpcr-odm/lib/vendor/jackalope/inc/JackrabbitFixtureLoader.php'
);
$phpcr_loader->register();
```

## Note on JCR

It would be nice if we were able to run the relevant parts of the JSR-283
Technology Compliance Kit (TCK) against php implementations. Note that we would
need to have some glue for things that look different in php than in Java, like
the whole topic of Value and ValueFactory.
[https://jira.liip.ch/browse/JACK-24](https://jira.liip.ch/browse/JACK-24)

Once we manage to do that, we could hopefully also use the performance test suite
[https://jira.liip.ch/browse/JACK-23](https://jira.liip.ch/browse/JACK-23)
