CHANGELOG
=========

1.3.1
-----

* bugfix #332 return early from Node::setProperty when value does not change. This avoids regressions with #307.

1.3.0
-----

* feature PHP 7 support.
* bugfix #329 pick most specific property definition on multiple wildcards.
* bugfix #323 Empty array properties no longer break queries.
* feature #307 register UUID immediately so that getNodeByIdentifier works even before saving the session. 
* feature #302 Added methods addVersionLabel and removeVersionLabel to VersioningInterface.
* feature #245 Added NodeProcessor class which can be used by implementations to validate and process nodes.
* bugfix #229 Userland node type filtering does not work.
