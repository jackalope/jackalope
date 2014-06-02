# Jackalope [![Build Status](https://secure.travis-ci.org/jackalope/jackalope.png?branch=master)](http://travis-ci.org/jackalope/jackalope)

A powerful implementation of the [PHPCR API](http://phpcr.github.com).

You can use Jackalope with different storage backends. For now, we support:

* *relational databases* with the DoctrineDBAL backend. Works with any
    database supported by doctrine (mysql, postgres, ...) and has no dependency
    on java or jackrabbit. For the moment, it is less feature complete.
    See [jackalope-doctrine-dbal](https://github.com/jackalope/jackalope-doctrine-dbal)
* *Jackrabbit* server backend supports many features and requires you to simply
    install a .jar file for the data store component.
    See [jackalope-jackrabbit](https://github.com/jackalope/jackalope-jackrabbit)


Discuss on jackalope-dev@googlegroups.com
or visit #jackalope on irc.freenode.net

## License

This code is dual licensed under the MIT license and the Apache License Version
2.0. Please see the file LICENSE in this folder.


# Install instructions

Go to the repository of the storage backend you want and follow the instructions there.

* [jackalope-doctrine-dbal](https://github.com/jackalope/jackalope-doctrine-dbal)
* [jackalope-jackrabbit](https://github.com/jackalope/jackalope-jackrabbit)


# Implementation notes

See [doc/architecture.md](https://github.com/jackalope/jackalope/blob/master/doc/architecture.md)
for an introduction how Jackalope is built. Have a look at the source files and
generate the phpdoc.


# Contributors

* Christian Stocker <chregu@liip.ch>
* David Buchmann <david@liip.ch>
* Tobias Ebnöther <ebi@liip.ch>
* Roland Schilter <roland.schilter@liip.ch>
* Uwe Jäger <uwej711@googlemail.com>
* Lukas Kahwe Smith <smith@pooteeweet.org>
* Benjamin Eberlei <kontakt@beberlei.de>
* Daniel Barsotti <daniel.barsotti@liip.ch>
* [and many others](https://github.com/jackalope/jackalope/contributors)
