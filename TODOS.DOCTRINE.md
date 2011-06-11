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
