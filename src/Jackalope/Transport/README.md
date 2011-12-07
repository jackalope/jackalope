# Jackalope Transport Layer

Jackalope encapsulates all communication with the storage backend with a
transport class that implements some or all of the interfaces in this folder.

Jackalope checks supported operations by means of interfaces and throws the
UnsupportedRepositoryOperationException in case a functionality not supported
by the current transport is required.

The interfaces are named following the JCR <a href="http://www.day.com/specs/jcr/2.0/24_Repository_Compliance.html">specification for repository compliance</a>. Note that not all chapters need anything from the
transport:

 * TransportInterface: 3. Repository Model, 4. Connecting, 5. Reading, 8 Node Type Discovery
 * QueryInterface: 6 Query
 * no interface needed: 7 Export
 * PermissionInterface: 9 Permissions and Capabilities
 * WritingInterface: 10 Writing
 * no interface needed: 11 Import
 * TODO: 12 Observation
 * WorkspaceManagementInterface: 13 Workspace Management
 * TODO: 14 Shareable Nodes
 * VersioningInterface: 15 Versioning
 * TODO: 16 Access Control Management
 * TODO: 17 Locking
 * TODO: 18 Lifecycle Management
 * NodeTypeManagementInterface and/or NodeTypeCndManagementInterface: 19 Node Type Management
 * TODO: 20 Retention and Hold
 * TransactionInterface: 21 Transactions
 * TODO: 22 Same-Name Siblings
 * TODO: 23 Orderable Child Nodes

# Notes for implementors of a transport

You can base yourself on the BaseTransport class to get some helper methods.

Implement as many of the interfaces as you can support. If there is the odd method
you can not handle, you can still throw the UnsupportedRepositoryOperationException
in the transport (but please document your limitations for the user). If you do not
implement an interface, Jackalope will not attempt to use that feature.

## Sanitize

Implementors can expect Jackalope to only pass normalized absolute paths to the
transport. What still has to be tested is if the paths contain no invalid characters
according to
<a href="http://www.day.com/specs/jcr/2.0/3_Repository_Model.html#3.2.2%20Local%20Names">the specification</a>
and backend specific restrictions.


## No caching!

Data MUST NOT be cached by the transport layer. The ObjectManager is responsible for
caching. If it asks the transport for something, this means the data has to be
fetched from storage.

## Bootstrap

Provide your own implementation of the PHPCR\RepositoryFactory for your transport.
Look at the existing ones - you can pass any required additional information in
the constructor of your transport.
