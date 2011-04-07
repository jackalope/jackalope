Architecture
============

Components
* PHPCR API Interfaces - defining the API
* API Tests - testing conformity of the implementation to the PHPCR API
* Jackalope
  * Storage independent layer
  * Storage layer


phpCR API Interfaces
----

These interfaces should be implemented by all projects providing a JCR programming interface in PHP. These interface definitions are project-independent and are developed in close collaboration with the [[FLOW3|http://flow3.typo3.org/]] project, who initially provided the source code.

PHP code: https://github.com/jackalope/phpcr


Jackalope API Tests
----

A suite of functional tests for testing if your implementation works correctly. It tests against the phpCR API and is meant to be used in any project implementing the phpCR API interfaces.

PHP code: http://github.com/jackalope/jackalope-api-tests


Storage independent layer
----

PHP code: http://github.com/jackalope/jackalope

This layer implements the phpCR interfaces in pure, framework-agnostic PHP. It provides all the necessary code needed for making a JCR client work in PHP only, without storage- or vendor-specific additions. This mechanism, similar to Jackrabbit's [[SPI|http://jackrabbit.apache.org/jackrabbit-spi.html]], allows for easy adaptation to different content stores, handling implementation details such as transport layers not covered by the standard. The underlying content store can thus be switched or updated without having to rewrite any higher level features.

See the README files in the main folder for how to set up the project.

In the src folder, you find mainly classes with the names as defined by the PHPCR API. The two important classes not defined by the API are the ObjectManager and TransportInterface.
 * ObjectManager caches nodes and talks to Transport. For write operations, it acts as "Unit of Work" handler.
 * Transport is again capsulated with an interface. (See below)


Storage layer using the WebDAV/davex protocol to talk to Jackrabbit
----

This layer handles the communication with an Apache Jackrabbit server. It uses the WebDAV/davex remoting feature and is implemented in pure, framework-agnostic PHP.

Transport/Davex/Client implements the HTTP communication with Jackrabbit. To implement other storage backends, it would probably be enough to implement a new Transport class.

PHP code: http://github.com/jackalope/jackalope (see Transport folder)
