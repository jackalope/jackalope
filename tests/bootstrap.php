<?php
/** make sure we get ALL infos from php */
error_reporting(E_ALL | E_STRICT);

/**
 * Bootstrap file for jackalope
 */

/**
 * autoloader: tests rely on an autoloader.
 */
require __DIR__.'/../src/autoload.dist.php';

### Load classes needed for jackalope unit tests ###
require 'Jackalope/TestCase.php';

/*
 * constants for the repository descriptor test for JCR 1.0/JSR-170 and JSR-283 specs
 */

define('SPEC_VERSION_DESC', 'jcr.specification.version');
define('SPEC_NAME_DESC', 'jcr.specification.name');
define('REP_VENDOR_DESC', 'jcr.repository.vendor');
define('REP_VENDOR_URL_DESC', 'jcr.repository.vendor.url');
define('REP_NAME_DESC', 'jcr.repository.name');
define('REP_VERSION_DESC', 'jcr.repository.version');
define('OPTION_TRANSACTIONS_SUPPORTED', 'option.transactions.supported');
define('OPTION_VERSIONING_SUPPORTED', 'option.versioning.supported');
define('OPTION_OBSERVATION_SUPPORTED', 'option.observation.supported');
define('OPTION_LOCKING_SUPPORTED', 'option.locking.supported');
