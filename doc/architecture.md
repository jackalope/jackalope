# Architecture

Components

* PHPCR API Interfaces - defining the API
* API Tests - testing conformity of the implementation to the PHPCR API
* Jackalope
  * Storage independent layer
  * Storage layer


## PHPCR API Interfaces

These interfaces have to be implemented by all projects providing the content
repository API in PHP. These interface definitions are adapted from the Java
Content Repository JCR standard. The API is implementation-independent and
improved in cooperation with all implementors of PHPCR and the JCR community.

PHP code: https://github.com/phpcr/phpcr

Documentation: https://phpcr.github.com/


## PHPCR utilities

During development, we found a couple of useful things that can be provided on
top of the PHPCR API, regardless of the actual implementation. They are
collected in the phpcr-utils suite. Jackalope uses a few of those utils.

PHP code: https://github.com/phpcr/phpcr-utils


## PHPCR API Tests

A suite of functional tests for testing if your implementation is correctly
following the specification. Have a look at the README in the git repository
to see how to set the tests up for your own implementation.

PHP code: http://github.com/phpcr/phpcr-api-tests


## Jackalope storage independent layer

This layer implements the PHPCR interfaces in pure, framework-agnostic PHP
without dependencies on any third-party libraries. It provides all the
necessary code for a PHPCR library except the data storage layer.

This mechanism, similar to Jackrabbit's [SPI](http://jackrabbit.apache.org/jackrabbit-spi.html),
allows for easy adaptation to different storage backends, handling
implementation details such as transport layers not covered by the PHPCR
standard.

In the src folder, you find mainly classes with the names as defined by the
PHPCR API. The two important things not defined by the API are the class
``ObjectManager`` and the interfaces in the ``Transport`` namespace.

* ObjectManager caches nodes and talks to Transport. For write operations, it
   acts as "Unit of Work" handler.
* Transport is separated from the implementation by Interfaces (see below)

Please read the phpdoc comments for implementation details. Note that the
methods implementing PHPCR interfaces are documented in the interface php
files. Generate the html documentation according to doc/config/README to have
a combined doc of the API and implementation details.

PHP code: http://github.com/jackalope/jackalope

Documentation: https://jackalope.github.com/


## Storage layers: The transport interfaces

The storage layer is separated from the Jackalope application code by
interfaces. These interfaces define the basic operations needed to implement
the PHPCR operations. Implementing PHPCR support for a new storage engine is
easiest done by just implementing a new transport.

### DoctrineDBAL: All SQL databases supported by doctrine

The doctrine transport uses the doctrine database abstraction layer to talk to
any SQL database supported by doctrine.

PHP code: http://github.com/jackalope/jackalope-doctrine-dbal


### Jackrabbit: WebDAV/davex protocol to talk to Jackrabbit

The jackrabbit transport stores data into an Apache Jackrabbit server using
a pure PHP implementation of the WebDAV/davex remoting protocol.

PHP code: http://github.com/jackalope/jackalope-jackrabbit

To hack on jackalope-jackrabbit, you might want to read the documentation of
the protocol used between Jackalope and the Jackrabbit backend. We have 2 files
to download:

* "jcr-webdav.doc":https://fosswiki.liip.ch/download/attachments/11501816/jcr-webdav_read.doc
* "jcr-webdav-protocol.doc":https://fosswiki.liip.ch/download/attachments/11501816/JCR_Webdav_Protocol.doc