<?php

namespace Jackalope\ImportExport;

use PHPCR\AccessDeniedException;
use PHPCR\ImportUUIDBehaviorInterface;
use PHPCR\InvalidSerializedDataException;
use PHPCR\ItemExistsException;
use PHPCR\ItemNotFoundException;
use PHPCR\NamespaceException;
use PHPCR\NamespaceRegistryInterface;
use PHPCR\NodeInterface;
use PHPCR\NodeType\ConstraintViolationException;
use PHPCR\PropertyInterface;
use PHPCR\PropertyType;
use PHPCR\RepositoryException;
use PHPCR\SessionInterface;
use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\Util\NodeHelper;

/**
 * Helper class with static methods to import and export data.
 *
 * Implements the uuid behavior interface to import the constants.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 * @author David Buchmann <david@liip.ch>
 */
class ImportExport implements ImportUUIDBehaviorInterface
{
    /**
     * Map of invalid xml names to escaping according to jcr/phpcr spec
     * TODO: more invalid characters?
     */
    public static array $escaping = [
        ' ' => '_x0020_',
        '<' => '_x003c_',
        '>' => '_x003e_',
        '"' => '_x0022_',
        "'" => '_x0027_',
    ];

    /**
     * Recursively export data to an xml stream.
     *
     * @param NodeInterface              $node       The node to start exporting at
     * @param NamespaceRegistryInterface $ns         The namespace registry to export namespaces too
     * @param resource                   $stream     as in exportSystemView
     * @param bool                       $skipBinary as in exportSystemView
     * @param bool                       $noRecurse  as in exportSystemView
     *
     * @throws \InvalidArgumentException
     * @throws RepositoryException
     *
     * @see SessionInterface::exportSystemView
     */
    public static function exportSystemView(NodeInterface $node, NamespaceRegistryInterface $ns, $stream, bool $skipBinary, bool $noRecurse): void
    {
        fwrite($stream, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
        self::exportSystemViewRecursive($node, $ns, $stream, $skipBinary, $noRecurse, true);
    }

    /**
     * Recursively export data to an xml stream in document view format.
     *
     * @param NodeInterface              $node       The node to start exporting at
     * @param NamespaceRegistryInterface $ns         The namespace registry to export namespaces too
     * @param resource                   $stream     as in exportDocumentView
     * @param bool                       $skipBinary as in exportDocumentView
     * @param bool                       $noRecurse  as in exportDocumentView
     *
     * @throws RepositoryException
     *
     * @see SessionInterface::exportDocumentView
     */
    public static function exportDocumentView(NodeInterface $node, NamespaceRegistryInterface $ns, $stream, bool $skipBinary, bool $noRecurse): void
    {
        fwrite($stream, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
        self::exportDocumentViewRecursive($node, $ns, $stream, $skipBinary, $noRecurse, true);
    }

    /**
     * Import the xml document from the stream into the repository.
     *
     * @param NodeInterface              $parentNode   as in importXML
     * @param NamespaceRegistryInterface $ns           as in importXML
     * @param string                     $uri          as in importXML
     * @param int                        $uuidBehavior as in importXML
     *
     * @throws \RuntimeException
     * @throws \Exception
     *
     * @see SessionInterface::importXML
     */
    public static function importXML(NodeInterface $parentNode, NamespaceRegistryInterface $ns, string $uri, int $uuidBehavior): void
    {
        $use_errors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        if (!file_exists($uri)) {
            throw new \RuntimeException("File $uri does not exist or is not readable");
        }

        $xml = new FilteredXMLReader();
        $options = 0;
        if (LIBXML_VERSION >= 20700) {
            $options = LIBXML_PARSEHUGE;
        }
        $xml->open($uri, null, $options);
        if (libxml_get_errors()) {
            libxml_use_internal_errors($use_errors);
            throw new InvalidSerializedDataException("Invalid xml file $uri");
        }

        $xml->read();
        try {
            if ('node' === $xml->localName && NamespaceRegistryInterface::NAMESPACE_SV === $xml->namespaceURI) {
                // TODO: validate with DTD?
                self::importSystemView($parentNode, $ns, $xml, $uuidBehavior);
            } else {
                self::importDocumentView($parentNode, $ns, $xml, $uuidBehavior);
            }
        } catch (\Exception $e) {
            // restore libxml setting
            libxml_use_internal_errors($use_errors);
            // and rethrow exception to not hide it
            throw $e;
        }

        libxml_use_internal_errors($use_errors);
    }

    /**
     * Helper method for escaping node and property names into valid xml
     * element and attribute names according to the jcr specification.
     *
     * @param string $name a name possibly containing characters illegal
     *                     in an XML document
     *
     * @return string The name encoded to be valid xml
     */
    public static function escapeXmlName(string $name): string
    {
        $name = preg_replace('/_(x[0-9a-fA-F]{4})/', '_x005f_\\1', $name);

        return str_replace(array_keys(self::$escaping), self::$escaping, $name);
    }

    /**
     * Helper method to unescape node names that encoded invalid things for
     * xml. At the same time, change document namespace prefix to repository
     * prefix if needed.
     *
     * @param string   $name         A name encoded with escapeXmlName
     * @param string[] $namespaceMap
     *
     * @return string the decoded name
     */
    public static function unescapeXmlName(string $name, array $namespaceMap): string
    {
        foreach (self::$escaping as $raw => $escaped) {
            // Used a negative look behind to only replace non-escaped escape characters
            $name = preg_replace(sprintf('/(?<!_x005f)%s/', preg_quote($escaped)), $raw, $name);
        }

        // Now replace all escape characters with a regular underscore
        $name = preg_replace('/_x005f_(x[0-9a-zA-F])/', '_\1', $name);

        return self::cleanNamespace($name, $namespaceMap);
    }

    /**
     * Helper function for importing to ensure prefix is same as in repository.
     *
     * @param string   $name         potentially namespace prefixed xml name
     * @param string[] $namespaceMap of document prefix => repository prefix
     */
    private static function cleanNamespace(string $name, array $namespaceMap): string
    {
        if ($pos = strpos($name, ':')) {
            // map into repo namespace prefix
            $prefix = substr($name, 0, $pos);
            if (array_key_exists($prefix, $namespaceMap)) {
                // the values we remap are not xml names but attribute values.
                // the namespace declaration is not obligatory
                str_replace($prefix, $namespaceMap[$prefix], $name);
            }
        }

        return $name;
    }

    /**
     * Helper method for importing to add a node with the proper uuid behavior.
     *
     * @param string $type         the primary type name to use
     * @param int    $uuidBehavior one of the constants of ImportUUIDBehaviorInterface
     *
     * @throws RepositoryException
     * @throws ItemExistsException          if IMPORT_UUID_COLLISION_THROW and duplicate id
     * @throws ConstraintViolationException if behavior is remove or replace and the node with the uuid is in the parent path
     */
    private static function addNode(NodeInterface $parentNode, string $nodename, string $type, array $properties, int $uuidBehavior): NodeInterface
    {
        $forceReferenceable = false;
        if (array_key_exists('jcr:uuid', $properties)) {
            try {
                $existing = $parentNode->getSession()->getNodeByIdentifier($properties['jcr:uuid']['values']);
                switch ($uuidBehavior) {
                    case self::IMPORT_UUID_CREATE_NEW:
                        unset($properties['jcr:uuid']);
                        $forceReferenceable = true;
                        break;
                    case self::IMPORT_UUID_COLLISION_THROW:
                        throw new ItemExistsException('There already is a node with uuid '.$properties['jcr:uuid']['values'].' in this workspace.');
                    case self::IMPORT_UUID_COLLISION_REMOVE_EXISTING:
                    case self::IMPORT_UUID_COLLISION_REPLACE_EXISTING:
                        if (self::IMPORT_UUID_COLLISION_REPLACE_EXISTING === $uuidBehavior
                            && 'jcr:root' === $nodename
                            && 0 === $existing->getDepth()
                        ) {
                            break;
                        }
                        if (!strncmp($existing->getPath().'/', $parentNode->getPath()."/$nodename", strlen($existing->getPath().'/'))) {
                            throw new ConstraintViolationException('Trying to remove/replace parent of the path we are adding to. '.$existing->getIdentifier().' at '.$existing->getPath());
                        }
                        if (self::IMPORT_UUID_COLLISION_REPLACE_EXISTING === $uuidBehavior) {
                            // replace the found node. spec is not precise: do we keep the name or use the one of existing?
                            $parentNode = $existing->getParent();
                        }
                        $existing->remove();
                        break;
                    default:
                        // @codeCoverageIgnoreStart
                        throw new RepositoryException("Unexpected type $uuidBehavior");
                        // @codeCoverageIgnoreEnd
                }
            } catch (ItemNotFoundException $e) {
                // nothing to do, we can add the node without conflict
            }
        }

        /* we add a jcr:root somewhere in the tree (either create new ids or
         * the root was not referenceable. do not make jackrabbit think it
         * would be the real root node.
         */
        if ('jcr:root' === $nodename && 'rep:root' === $type) {
            $type = 'nt:unstructured';
        }

        if ('jcr:root' === $nodename
            && isset($existing)
            && self::IMPORT_UUID_COLLISION_REPLACE_EXISTING === $uuidBehavior
            && 0 === $existing->getDepth()
        ) {
            // update the root node properties
            // http://www.day.com/specs/jcr/2.0/11_Import.html#11.9%20Importing%20%3CI%3Ejcr:root%3C/I%3E
            NodeHelper::purgeWorkspace($parentNode->getSession());
            $node = $existing;
        } else {
            $node = $parentNode->addNode($nodename, $type);
        }

        foreach ($properties as $name => $info) {
            if ('jcr:primaryType' === $name) {
                // handled in node constructor
            } elseif ('jcr:mixinTypes' === $name) {
                if (is_array($info['values'])) {
                    foreach ($info['values'] as $type) {
                        $node->addMixin($type);
                    }
                } else {
                    $node->addMixin($info['values']);
                }
            } elseif ('jcr:created' === $name || 'jcr:createdBy' === $name) {
                // skip PROTECTED properties. TODO: get the names from node type instead of hardcode
            } elseif ('jcr:uuid' === $name) {
                // The root node is not removed and thus UUID needs not be updated
                if (0 !== $node->getDepth()) {
                    // Set the UUID on this node. Validate false to allow setting the UUID manually.
                    $node->setProperty($name, $info['values'], $info['type'], false);
                }
            } else {
                $node->setProperty($name, $info['values'], $info['type']);
            }
        }

        if ($forceReferenceable && !$node->isNodeType('mix:referenceable')) {
            $node->addMixin('mix:referenceable');
        }

        return $node;
    }

    /**
     * Recursively output node and all its children into the file in the system
     * view format.
     *
     * @param resource $stream     The stream resource (i.e. acquired with fopen) to which the XML serialization of the
     *                             subgraph will be output. Must support the fwrite method
     * @param bool     $skipBinary whether binary properties are to be serialized
     * @param bool     $noRecurse  whether the children of $node are to be serialized or not
     * @param bool     $root       whether this is the root node of the resulting document, meaning the namespace
     *                             declarations have to be included in it
     *
     * @throws RepositoryException
     * @throws \InvalidArgumentException
     */
    private static function exportSystemViewRecursive(NodeInterface $node, NamespaceRegistryInterface $ns, $stream, bool $skipBinary, bool $noRecurse, bool $root = false): void
    {
        fwrite($stream, '<sv:node');
        if ($root) {
            self::exportNamespaceDeclarations($ns, $stream);
        }
        fwrite($stream, ' sv:name="'.(0 === $node->getDepth() ? 'jcr:root' : htmlspecialchars($node->getName())).'">');

        // the order MUST be primary type, then mixins, if any, then jcr:uuid if its a referenceable node
        fwrite($stream, '<sv:property sv:name="jcr:primaryType" sv:type="Name"><sv:value>'.htmlspecialchars($node->getPropertyValue('jcr:primaryType')).'</sv:value></sv:property>');
        if ($node->hasProperty('jcr:mixinTypes')) {
            fwrite($stream, '<sv:property sv:name="jcr:mixinTypes" sv:type="Name" sv:multiple="true">');
            foreach ($node->getPropertyValue('jcr:mixinTypes') as $type) {
                fwrite($stream, '<sv:value>'.htmlspecialchars($type).'</sv:value>');
            }
            fwrite($stream, '</sv:property>');
        }
        if ($node->isNodeType('mix:referenceable')) {
            fwrite($stream, '<sv:property sv:name="jcr:uuid" sv:type="String"><sv:value>'.$node->getIdentifier().'</sv:value></sv:property>');
        }

        foreach ($node->getProperties() as $name => $property) {
            /* @var $property PropertyInterface */

            if (in_array($name, ['jcr:primaryType', 'jcr:mixinTypes', 'jcr:uuid'], true)) {
                // explicitly handled before
                continue;
            }
            if ($skipBinary && PropertyType::BINARY === $property->getType()) {
                // do not output binary data in the xml
                continue;
            }
            fwrite($stream, '<sv:property sv:name="'.htmlentities($name).'" sv:type="'
                .PropertyType::nameFromValue($property->getType()).'"'
                .($property->isMultiple() ? ' sv:multiple="true"' : '')
                .'>');
            $values = $property->isMultiple() ? $property->getString() : [$property->getString()];

            foreach ($values as $value) {
                if (PropertyType::BINARY === $property->getType()) {
                    $val = base64_encode($value);
                } elseif (PropertyType::BOOLEAN === $property->getType()) {
                    $val = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
                } else {
                    $val = htmlspecialchars($value);
                    // TODO: can we still have invalid characters after this? if so base64 and property, xsi:type="xsd:base64Binary"
                }
                fwrite($stream, "<sv:value>$val</sv:value>");
            }
            fwrite($stream, '</sv:property>');
        }
        if (!$noRecurse) {
            foreach ($node as $child) {
                if (!(1 === $child->getDepth() && NodeHelper::isSystemItem($child))) {
                    self::exportSystemViewRecursive($child, $ns, $stream, $skipBinary, $noRecurse);
                }
            }
        }
        fwrite($stream, '</sv:node>');
    }

    /**
     * Recursively output node and all its children into the file in the
     * document view format.
     *
     * @param resource $stream     the resource to write data out to
     * @param bool     $skipBinary whether binary properties are to be serialized
     * @param bool     $noRecurse  whether the children of $node are to be serialized or not
     * @param bool     $root       whether this is the root node of the resulting document, meaning the namespace
     *                             declarations have to be included in it
     *
     * @throws RepositoryException
     */
    private static function exportDocumentViewRecursive(NodeInterface $node, NamespaceRegistryInterface $ns, $stream, bool $skipBinary, bool $noRecurse, bool $root = false): void
    {
        $nodename = self::escapeXmlName($node->getName());
        fwrite($stream, "<$nodename");
        if ($root) {
            self::exportNamespaceDeclarations($ns, $stream);
        }
        foreach ($node->getProperties() as $name => $property) {
            /** @var $property PropertyInterface */
            if ($property->isMultiple()) {
                // skip multiple properties. jackrabbit does this too. cheap but whatever. use system view for a complete export
                continue;
            }
            if (PropertyType::BINARY === $property->getType()) {
                if ($skipBinary) {
                    continue;
                }
                $value = base64_encode($property->getString());
            } elseif (PropertyType::BOOLEAN === $property->getType()) {
                $value = filter_var($property->getValue(), FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
            } else {
                $value = htmlspecialchars($property->getString());
            }
            fwrite($stream, ' '.self::escapeXmlName($name).'="'.$value.'"');
        }
        if ($noRecurse || !$node->hasNodes()) {
            fwrite($stream, '/>');
        } else {
            fwrite($stream, '>');
            foreach ($node as $child) {
                if (!(1 === $child->getDepth() && NodeHelper::isSystemItem($child))) {
                    self::exportDocumentViewRecursive($child, $ns, $stream, $skipBinary, $noRecurse);
                }
            }
            fwrite($stream, "</$nodename>");
        }
    }

    /**
     * Helper method to produce the xmlns:... attributes of the root node from a namespace registry.
     *
     * @param resource $stream the output stream to write the namespaces to
     */
    private static function exportNamespaceDeclarations(NamespaceRegistryInterface $ns, $stream): void
    {
        foreach ($ns as $key => $uri) {
            if (!empty($key)) { // no ns declaration for empty namespace
                fwrite($stream, " xmlns:$key=\"$uri\"");
            }
        }
    }

    /**
     * Import document in system view.
     *
     * @param string[] $namespaceMap hashmap of prefix => uri for namespaces in the document
     *
     * @throws RepositoryException
     * @throws \InvalidArgumentException
     * @throws InvalidSerializedDataException
     * @throws ConstraintViolationException
     * @throws ItemExistsException
     * @throws AccessDeniedException
     * @throws NamespaceException
     * @throws UnsupportedRepositoryOperationException
     */
    private static function importSystemView(NodeInterface $parentNode, NamespaceRegistryInterface $ns, FilteredXMLReader $xml, int $uuidBehavior, array $namespaceMap = []): void
    {
        while ($xml->moveToNextAttribute()) {
            if ('xmlns' === $xml->prefix) {
                try {
                    $prefix = $ns->getPrefix($xml->value);
                } catch (NamespaceException $e) {
                    $prefix = $xml->localName;
                    $ns->registerNamespace($prefix, $xml->value);
                }
                // @codeCoverageIgnoreStart
                if ('jcr' === $prefix && 'jcr' !== $xml->localName) {
                    throw new RepositoryException('Can not handle a document where the {http://www.jcp.org/jcr/1.0} namespace is not mapped to jcr');
                }
                if ('nt' === $prefix && 'nt' !== $xml->localName) {
                    throw new RepositoryException('Can not handle a document where the {http://www.jcp.org/jcr/nt/1.0} namespace is not mapped to nt');
                }
                // @codeCoverageIgnoreEnd
                $namespaceMap[$xml->localName] = $prefix;
            } elseif (NamespaceRegistryInterface::NAMESPACE_SV === $xml->namespaceURI
                && 'name' === $xml->localName
            ) {
                $nodename = $xml->value;
            }
        }
        if (!isset($nodename)) {
            throw new InvalidSerializedDataException('there was no sv:name attribute in an element');
        }

        // now get jcr:primaryType
        if (!$xml->read()) {
            throw new InvalidSerializedDataException('missing information to create node');
        }

        if ('property' !== $xml->localName || NamespaceRegistryInterface::NAMESPACE_SV !== $xml->namespaceURI) {
            throw new InvalidSerializedDataException('first child of node must be sv:property for jcr:primaryType. Found {'.$xml->namespaceURI.'}'.$xml->localName.'="'.$xml->value.'"'.$xml->nodeType);
        }

        if (!$xml->moveToAttributeNs('name', NamespaceRegistryInterface::NAMESPACE_SV)) {
            throw new InvalidSerializedDataException('first child of node must have a sv:name property');
        }

        if ('jcr:primaryType' !== $xml->value) {
            throw new InvalidSerializedDataException('first child of node must be the sv:property node with a jcr:primaryType. Found {'.$xml->namespaceURI.'}'.$xml->localName.'="'.$xml->value);
        }

        $xml->read(); // value child of property jcr:primaryType
        $xml->read(); // text content
        $nodetype = $xml->value;

        $nodename = self::cleanNamespace($nodename, $namespaceMap);

        $properties = [];

        $xml->read(); // </value>
        $xml->read(); // </property>

        $xml->read(); // next thing

        // read the properties of the node. they must come first.
        while (\XMLReader::END_ELEMENT !== $xml->nodeType && 'property' === $xml->localName) {
            $xml->moveToAttributeNs('name', NamespaceRegistryInterface::NAMESPACE_SV);
            $name = $xml->value;
            $xml->moveToAttributeNs('type', NamespaceRegistryInterface::NAMESPACE_SV);
            $type = PropertyType::valueFromName($xml->value);
            if ($xml->moveToAttributeNs('multiple', NamespaceRegistryInterface::NAMESPACE_SV)) {
                $multiple = 0 === strcasecmp($xml->value, 'true');
            } else {
                $multiple = false;
            }
            $values = [];

            // go to the value child. if empty property, brings us to closing
            // property tag. if self-closing, brings us to the next property or
            // node closing tag
            $xml->read();

            while ('value' === $xml->localName) {
                if ($xml->isEmptyElement) {
                    $values[] = '';
                } else {
                    $xml->read();
                    if (\XMLReader::END_ELEMENT === $xml->nodeType) {
                        // this is an empty tag
                        $values[] = '';
                    } else {
                        if (PropertyType::BINARY === $type) {
                            $values[] = base64_decode($xml->value);
                        } elseif (PropertyType::BOOLEAN === $type) {
                            $values[] = filter_var($xml->value, FILTER_VALIDATE_BOOLEAN);
                        } else {
                            $values[] = $xml->value;
                        }
                        $xml->read(); // consume the content
                    }
                }
                $xml->read(); // consume closing tag
            }

            if (!$multiple && 1 === count($values)) {
                $values = reset($values); // unbox if it does not need to be multivalue
            }
            $name = self::cleanNamespace($name, $namespaceMap);

            $properties[$name] = ['values' => $values, 'type' => $type];

            /* only consume closing property, but not the next element if we
             * had an empty multiple property with no value children at all
             * and don't consume the closing node tag after a self-closing
             * empty property
             */
            if (\XMLReader::END_ELEMENT === $xml->nodeType && 'property' === $xml->localName) {
                $xml->read();
            }
        }
        $node = self::addNode($parentNode, $nodename, $nodetype, $properties, $uuidBehavior);

        // if there are child nodes, they all come after the properties

        while (\XMLReader::END_ELEMENT !== $xml->nodeType && 'node' === $xml->localName) {
            self::importSystemView($node, $ns, $xml, $uuidBehavior, $namespaceMap);
        }

        if (\XMLReader::END_ELEMENT !== $xml->nodeType) {
            throw new InvalidSerializedDataException('Unexpected element "'.$xml->localName.'" type "'.$xml->nodeType.'" with content "'.$xml->value.'" after '.$node->getPath());
        }
        $xml->read(); // </node>
    }

    /**
     * Import document in system view.
     *
     * @param string[] $namespaceMap hashmap of prefix => uri for namespaces in the document
     *
     * @throws RepositoryException
     */
    private static function importDocumentView(NodeInterface $parentNode, NamespaceRegistryInterface $ns, FilteredXMLReader $xml, int $uuidBehavior, array $namespaceMap = []): void
    {
        $nodename = $xml->name;
        $properties = [];
        $hasAttributes = false; // track whether there was any xml attribute on the element to calculate depth correctly

        while ($xml->moveToNextAttribute()) {
            $hasAttributes = true;
            if ('xmlns' === $xml->prefix) {
                try {
                    $prefix = $ns->getPrefix($xml->value);
                } catch (NamespaceException $e) {
                    $prefix = $xml->localName;
                    $ns->registerNamespace($prefix, $xml->value);
                }
                $namespaceMap[$xml->localName] = $prefix;
            } else {
                $properties[self::unescapeXmlName($xml->name, $namespaceMap)] = ['values' => $xml->value, 'type' => null];
            }
        }

        $prefix_jcr = array_search(NamespaceRegistryInterface::PREFIX_JCR, $namespaceMap);
        if (false === $prefix_jcr) {
            $namespaceMap[NamespaceRegistryInterface::PREFIX_JCR] = NamespaceRegistryInterface::PREFIX_JCR;
        } elseif (NamespaceRegistryInterface::PREFIX_JCR !== $prefix_jcr) {
            throw new RepositoryException('Can not handle a document where the {http://www.jcp.org/jcr/1.0} namespace is not mapped to jcr');
        }

        $prefix_nt = array_search(NamespaceRegistryInterface::PREFIX_NT, $namespaceMap);
        if (false === $prefix_nt) {
            $namespaceMap[NamespaceRegistryInterface::PREFIX_NT] = NamespaceRegistryInterface::PREFIX_NT;
        } elseif (NamespaceRegistryInterface::PREFIX_NT !== $prefix_nt) {
            throw new RepositoryException('Can not handle a document where the {http://www.jcp.org/jcr/nt/1.0} namespace is not mapped to nt');
        }

        $nodename = self::unescapeXmlName($nodename, $namespaceMap);

        if (isset($properties['jcr:primaryType'])) {
            $type = $properties['jcr:primaryType']['values'];
            unset($properties['jcr:primaryType']);
        } else {
            $type = 'nt:unstructured';
        }

        $node = self::addNode($parentNode, $nodename, $type, $properties, $uuidBehavior);

        // get current depth to detect self-closing tag. unfortunately, we do
        // not get an END_ELEMENT for self-closing tags but read() just jumps
        // to the next element, even moving up in the tree,
        $depth = $xml->depth;
        if (!$hasAttributes) {
            // we where on an empty element, thus not inside its attributes. change depth to 1 deeper
            // thanks XMLReader, great work :-(
            ++$depth;
        }
        $xml->read(); // move out of current node to next

        // TODO: what about significant whitespace? maybe the read above should not even skip significant empty whitespace...

        // while we are on element and at same depth, these are children of the current node
        while (\XMLReader::ELEMENT === $xml->nodeType && $xml->depth === $depth) {
            self::importDocumentView($node, $ns, $xml, $uuidBehavior, $namespaceMap);
        }

        if (\XMLReader::END_ELEMENT !== $xml->nodeType && $xml->depth !== $depth - 1) {
            throw new InvalidSerializedDataException('Unexpected element in stream: '.$xml->name.'="'.$xml->value.'"');
        }

        if (\XMLReader::END_ELEMENT === $xml->nodeType) {
            $xml->read(); // end of element
        } // otherwise the previous element was self-closing and we are already on the next one
    }
}
