<?php

namespace Jackalope;

use DOMDocument;
use XMLReader;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPCR\ImportUUIDBehaviourInterface;
use PHPCR\NamespaceRegistryInterface;

/**
 * Helper class with static methods to import and export data.
 *
 * Implements the uuid behaviour interface to import the constants.
 *
 * TODO: should we move this to phpcr-utils? it has no dependency on jackalope at all
 */
class ImportExport implements ImportUUIDBehaviourInterface
{
    /**
     * Map of invalid xml names to escaping according to jcr/phpcr spec
     * TODO: more invalid characters?
     *
     * @var array
     */
    public static $escaping = array(
        ' ' => '_x0020_',
        '<' => '_x003c_',
        '>' => '_x003e_',
        '"' => '_x0022_',
        "'" => '_x0027_',
    );

    /**
     * Recursively export data to an xml stream.
     *
     * @param NodeInterface $node The node to start exporting at
     * @param NamespaceRegistryInterface $ns The namespace registry to export namespaces too
     * @param resource $stream as in exportSystemView
     * @param boolean $skipBinary as in exportSystemView
     * @param boolean $noRecurse as in exportSystemView
     *
     * @see PHPCR\SessionInterface::exportSystemView
     */
    public static function exportSystemView(NodeInterface $node, NamespaceRegistryInterface $ns, $stream, $skipBinary, $noRecurse)
    {
        fwrite($stream, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
        self::exportSystemViewRecursive($node, $ns, $stream, $skipBinary, $noRecurse, true);
    }

    /**
     * Recursively export data to an xml stream in document view format
     *
     * @param NodeInterface $node The node to start exporting at
     * @param NamespaceRegistryInterface $ns The namespace registry to export namespaces too
     * @param resource $stream as in exportDocumentView
     * @param boolean $skipBinary as in exportDocumentView
     * @param boolean $noRecurse as in exportDocumentView
     *
     * @see PHPCR\SessionInterface::exportDocumentView
     */
    public static function exportDocumentView(NodeInterface $node, NamespaceRegistryInterface $ns, $stream, $skipBinary, $noRecurse)
    {
        fwrite($stream, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
        self::exportDocumentViewRecursive($node, $ns, $stream, $skipBinary, $noRecurse, true);
    }

    /**
     * Import the xml document from the stream into the repository
     *
     * @param NodeInterface $parentNode as in importXML
     * @param string $uri as in importXML
     * @param integer $uuidBehavior as in importXML
     *
     * @see PHPCR\SessionInterface::importXML
     */
    public static function importXML(NodeInterface $parentNode, NamespaceRegistryInterface $ns, $uri, $uuidBehavior)
    {
        $use_errors = libxml_use_internal_errors(true);

        $xml = new XMLReader2;
        $xml->open($uri);
        if (libxml_get_errors()) {
            libxml_use_internal_errors($use_errors);
            throw new \PHPCR\InvalidSerializedDataException("Invalid xml file $uri");
        }
        $xml->read();

        try {
            if ('node' == $xml->localName && NamespaceRegistryInterface::NAMESPACE_SV == $xml->namespaceURI) {
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
     * @param string $name A name possibly containing characters illegal
     *      in an XML document.
     *
     * @return string The name encoded to be valid xml
     */
    public static function escapeXmlName($name)
    {
        $name = preg_replace('/_(x[0-9a-fA-F]{4})/', '_x005f_\\1', $name);
        return str_replace(array_keys(self::$escaping), self::$escaping, $name);
    }

    /**
     * Helper method to unescape node names that encoded invalid things for
     * xml. At the same time, change document namespace prefix to repository
     * prefix if needed.
     *
     * @param string $name A name encoded with escapeXmlName
     *
     * @return string the decoded name
     */
    public static function unescapeXmlName($name, $namespaceMap)
    {
        // FIXME this is not respecting the escaping properly
        $name = str_replace(self::$escaping, array_keys(self::$escaping), $name);
        $name = preg_replace('/_x005f_/', '', $name);
        return self::cleanNamespace($name, $namespaceMap);
    }

    /**
     * Helper function for importing to ensure prefix is same as in repository
     *
     * @param string $name potentially namespace prefixed xml name
     * @param array $namespaceMap of document prefix => repository prefix
     */
    private static function cleanNamespace($name, $namespaceMap)
    {
        if ($pos = strpos($name, ':')) {
            // map into repo namespace prefix
            $prefix = substr($name, 0, $pos);
            str_replace($prefix, $namespaceMap[$prefix], $name);
        }
        return $name;
    }

    /**
     * Helper method for importing to add a node with the proper uuid behaviour
     *
     * @param \PHPCR\NodeInterface $parentNode the node to add this node to
     * @param string $nodename the node name to use
     * @param string $type the primary type name to use
     * @param int $uuidBehaviour one of the constants of ImportUUIDBehaviourInterface
     *
     * @return NodeInterface the created node
     *
     * @throws ItemExistsException if IMPORT_UUID_COLLISION_THROW and
     *      duplicate id
     * @throws ConstraintViolationException if behaviour is remove or
     *      replace and the node with the uuid is in the parent path.
     */
    private static function addNode(NodeInterface $parentNode, $nodename, $type, $properties, $uuidBehaviour)
    {
        $forceReferenceable = false;
        if (isset($properties['jcr:uuid'])) {
            if (self::IMPORT_UUID_CREATE_NEW == $uuidBehaviour) {
                unset($properties['jcr:uuid']);
                $forceReferenceable = true;
            } else {
                try {
                    $existing = $parentNode->getSession()->getNodeByIdentifier($properties['jcr:uuid']['values']);
                    switch ($uuidBehaviour) {
                        case self::IMPORT_UUID_COLLISION_THROW:
                            throw new \PHPCR\ItemExistsException('There already is a node with uuid '.$properties['jcr:uuid']['values'].' in this workspace.');
                        case self::IMPORT_UUID_COLLISION_REMOVE_EXISTING:
                        case self::IMPORT_UUID_COLLISION_REPLACE_EXISTING:
                            if (! strncmp($existing->getPath().'/', $parentNode->getPath()."/$nodename", strlen($existing->getPath().'/'))) {
                                throw new \PHPCR\NodeType\ConstraintViolationException('Trying to remove/replace parent of the path we are adding to');
                            }
                            if (self::IMPORT_UUID_COLLISION_REPLACE_EXISTING == $uuidBehaviour) {
                                if ('jcr:root' == $nodename && $existing->getDepth() == 0) {
                                    // special case replace root node properties with the properties we get here
                                    // TODO http://www.day.com/specs/jcr/2.0/11_Import.html
                                } else {
                                    // replace the found node. spec is not precise: do we keep the name or use the one of existing?
                                    $parentNode = $existing->getParent();
                                }
                            }
                            $existing->remove();
                            break;
                        default:
                            throw new \PHPCR\UnsupportedRepositoryOperationException("Unexpected type $uuidBehaviour");
                    }
                } catch (\PHPCR\ItemNotFoundException $e) {
                    // nothing to do, we can add the node without conflict
                }
            }
        }

        // TODO logging echo "Adding node $nodename ($type)\n";
        $node = $parentNode->addNode($nodename, $type);

        foreach ($properties as $name => $info) {
            // TODO logging echo "Adding property $name\n";
            if ('jcr:primaryType' == $name) {
                // handled in node constructor
            } else if ('jcr:mixinTypes' == $name) {
                if (is_array($info['values'])) {
                    foreach ($info['values'] as $type) {
                        $node->addMixin($type);
                    }
                } else {
                    $node->addMixin($info['values']);
                }
            } else {
                $node->setProperty($name, $info['values'], $info['type']);
            }
        }
        if ($forceReferenceable && ! $node->isNodeType('mix:referenceable')) {
            $node->addMixin('mix:referenceable');
        }

        return $node;
    }


    /**
     * Recursively output node and all its children into the file in the system
     * view format
     *
     * @param NodeInterface $node the node to output
     * @param resource $stream The stream resource (i.e. aquired with fopen) to
     *      which the XML serialization of the subgraph will be output. Must
     *      support the fwrite method.
     * @param boolean $skipBinary A boolean governing whether binary properties
     *      are to be serialized.
     * @param boolean $noRecurse A boolean governing whether the subgraph at
     *      absPath is to be recursed.
     * @param boolean $root Whether this is the root node of the resulting
     *      document, meaning the namespace declarations have to be included in
     *      it.
     *
     * @return void
     */
    private static function exportSystemViewRecursive(NodeInterface $node, NamespaceRegistryInterface $ns, $stream, $skipBinary, $noRecurse, $root=false)
    {
        fwrite($stream, '<sv:node');
        if ($root) {
            self::exportNamespaceDeclarations($ns, $stream);
        }
        fwrite($stream, ' sv:name="'.($node->getPath() == '/' ? 'jcr:root' : htmlspecialchars($node->getName())).'">');

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
            if ($name == 'jcr:primaryType' || $name == 'jcr:mixinTypes' || $name == 'jcr:uuid') {
                // explicitly handled before
                continue;
            }
            if (PropertyType::BINARY == $property->getType() && $skipBinary) {
                // do not output binary data in the xml
                continue;
            }
            fwrite($stream, '<sv:property sv:name="'.htmlentities($name).'" sv:type="'
                . PropertyType::nameFromValue($property->getType()).'"'
                . ($property->isMultiple() ? ' sv:multiple="true"' : '')
                . '>');
            $values = $property->isMultiple() ? $property->getString() : array($property->getString());

            foreach ($values as $value) {
                if (PropertyType::BINARY == $property->getType()) {
                    $val = base64_encode($value);
                } else {
                    $val = htmlspecialchars($value);
                    //TODO: can we still have invalid characters after this? if so base64 and property, xsi:type="xsd:base64Binary"
                }
                fwrite($stream, "<sv:value>$val</sv:value>");
            }
            fwrite($stream, "</sv:property>");
        }
        if (! $noRecurse) {
            foreach ($node as $child) {
                self::exportSystemViewRecursive($child, $ns, $stream, $skipBinary, $noRecurse);
            }
        }
        fwrite($stream, '</sv:node>');
    }


    /**
     * Recursively output node and all its children into the file in the
     * document view format
     *
     * @param NodeInterface $node the node to output
     * @param NamespaceRegistryInterface $ns The namespace registry to export namespaces too
     * @param resource $stream the resource to write data out to
     * @param boolean $skipBinary A boolean governing whether binary properties
     *      are to be serialized.
     * @param boolean $noRecurse A boolean governing whether the subgraph at
     *      absPath is to be recursed.
     * @param boolean $root Whether this is the root node of the resulting
     *      document, meaning the namespace declarations have to be included in
     *      it.
     *
     * @return void
     */
    private static function exportDocumentViewRecursive(NodeInterface $node, NamespaceRegistryInterface $ns, $stream, $skipBinary, $noRecurse, $root=false)
    {
        //TODO: encode name according to spec
        $nodename = self::escapeXmlName($node->getName());
        fwrite($stream, "<$nodename");
        if ($root) {
            self::exportNamespaceDeclarations($ns, $stream);
        }
        foreach ($node->getProperties() as $name => $property) {
            if ($property->isMultiple()) {
                // skip multiple properties. jackrabbit does this too. cheap but whatever. use system view for a complete export
                continue;
            }
            if (PropertyType::BINARY == $property->getType()) {
                if ($skipBinary) {
                    continue;
                }
                $value = base64_encode($property->getString());
            } else {
                $value = htmlspecialchars($property->getString());
            }
            fwrite($stream, ' '.self::escapeXmlName($name).'="'.$value.'"');
        }
        if ($noRecurse || ! $node->hasNodes()) {
            fwrite($stream, '/>');
        } else {
            fwrite($stream, '>');
            foreach ($node as $child) {
                self::exportDocumentViewRecursive($child, $ns, $stream, $skipBinary, $noRecurse);
            }
            fwrite($stream, "</$nodename>");
        }
    }

    /**
     * Helper method to produce the xmlns:... attributes of the root node from
     * the built-in namespace registry.
     *
     * @param NamespaceRegistry $ns the registry with the namespaces to export
     * @param resource $stream the ouptut stream to write the namespaces to
     *
     * @return void
     */
    private static function exportNamespaceDeclarations(NamespaceRegistryInterface $ns, $stream)
    {
        foreach ($ns as $key => $uri) {
            if (! empty($key)) { // no ns declaration for empty namespace
                fwrite($stream, " xmlns:$key=\"$uri\"");
            }
        }
    }

    /**
     * Import document in system view
     *
     * @param NodeInterface $parentNode
     * @param NamespaceRegistryInterface $ns
     * @param XMLReader2 $xml
     * @param int $uuidBehavior
     * @param array $documentNamespaces hashmap of prefix => uri for namespaces in the document
     */
    private static function importSystemView(NodeInterface $parentNode, NamespaceRegistryInterface $ns, XMLReader2 $xml, $uuidBehavior, $namespaceMap = array())
    {
        // TODO logging echo "Adding child to ".$parentNode->getPath()."\n";
        while ($xml->moveToNextAttribute()) {
            if ('xmlns' == $xml->prefix) {
                try {
                    $prefix = $ns->getPrefix($xml->value);
                } catch(\PHPCR\NamespaceException $e) {
                    $prefix = $xml->localName;
                    $ns->registerNamespace($prefix, $xml->value);
                }
                $namespaceMap[$xml->localName] = $prefix;
            } elseif (NamespaceRegistryInterface::NAMESPACE_SV == $xml->namespaceURI
                && 'name' == $xml->localName
            ) {
                $nodename = $xml->value;
            }
        }
        if (! array_search('jcr', $namespaceMap)) {
            throw new \PHPCR\RepositoryException('Can not handle a document where the {http://www.jcp.org/jcr/1.0} namespace is not mapped to jcr');
        }
        if (! array_search('nt', $namespaceMap)) {
            throw new \PHPCR\RepositoryException('Can not handle a document where the {http://www.jcp.org/jcr/nt/1.0} namespace is not mapped to nt');
        }

        // now get jcr:primaryType
        if (! $xml->read()) {
            throw new \PHPCR\InvalidSerializedDataException('missing information to create node');
        }
        if ('property' != $xml->localName || NamespaceRegistryInterface::NAMESPACE_SV != $xml->namespaceURI) {
            throw new \PHPCR\InvalidSerializedDataException('first child of node must be sv:property for jcr:primaryType. Found {'.$xml->namespaceURI.'}'.$xml->localName.'="'.$xml->value.'"'.$xml->nodeType);
        }
        if (! $xml->moveToAttributeNs('name', NamespaceRegistryInterface::NAMESPACE_SV) || 'jcr:primaryType' != $xml->value) {
            throw new \PHPCR\InvalidSerializedDataException('first child of node must be sv:property for jcr:primaryType. Found {'.$xml->namespaceURI.'}'.$xml->localName.'="'.$xml->value.'"');
        }
        $xml->read(); // value child of property jcr:primaryType
        $xml->read(); // text content
        $nodetype = $xml->value;

        $nodename = self::cleanNamespace($nodename, $namespaceMap);

        $properties = array();

        $xml->read(); // </value>
        $xml->read(); // </property>

        $xml->read(); // next thing

        // read the properties of the node. they must come first.
        while (XMLReader::END_ELEMENT != $xml->nodeType && 'property' == $xml->localName) {
            $xml->moveToAttributeNs('name', NamespaceRegistryInterface::NAMESPACE_SV);
            $name = $xml->value;
            $xml->moveToAttributeNs('type', NamespaceRegistryInterface::NAMESPACE_SV);
            $type = PropertyType::valueFromName($xml->value);
            if ($xml->moveToAttributeNs('multiple', NamespaceRegistryInterface::NAMESPACE_SV)) {
                $multiple = strcasecmp($xml->value, 'true') === 0;
            } else {
                $multiple = false;
            }
            $values = array();

            $xml->moveToNextElement(); // go to the value child

            while ('value' == $xml->localName) {
                $xml->read();
                $values[] = (PropertyType::BINARY == $type) ? base64_decode($xml->value) : $xml->value;
                $xml->read(); // </value>
                $xml->read(); // <value> or </property>
            }

            if (! $multiple && count($values) == 1) {
                $values = reset($values); // unbox if it does not need to be multivalue
            }
            $name = self::cleanNamespace($name, $namespaceMap);

            $properties[$name] = array('values' => $values, 'type' => $type);
            $xml->read();
        }

        $node = self::addNode($parentNode, $nodename, $nodetype, $properties, $uuidBehavior);

        // if there are child nodes, they all come after the properties

        while (XMLReader::END_ELEMENT != $xml->nodeType && 'node' == $xml->localName) {
            self::importSystemView($node, $ns, $xml, $uuidBehavior, $namespaceMap);
        }

        if (XMLReader::END_ELEMENT != $xml->nodeType) {
            throw new \PHPCR\InvalidSerializedDataException('Unexpected element in stream '.$xml->localName.'="'.$xml->value.'"');
        }
        $xml->read(); // </node>
        // TODO logging echo "Done adding to ".$parentNode->getPath()."\n";
    }

    /**
     * Import document in system view
     *
     * @param NodeInterface $parentNode
     * @param NamespaceRegistryInterface $ns
     * @param XMLReader2 $xml
     * @param int $uuidBehavior
     * @param array $documentNamespaces hashmap of prefix => uri for namespaces in the document
     */
    private static function importDocumentView(NodeInterface $parentNode, NamespaceRegistryInterface $ns, XMLReader2 $xml, $uuidBehavior, $namespaceMap = array())
    {
        // TODO logging echo "Adding child to ".$parentNode->getPath()."\n";
        $nodename = $xml->name;
        $properties = array();

        while ($xml->moveToNextAttribute()) {
            if ('xmlns' == $xml->prefix) {
                try {
                    $prefix = $ns->getPrefix($xml->value);
                } catch(\PHPCR\NamespaceException $e) {
                    $prefix = $xml->localName;
                    $ns->registerNamespace($prefix, $xml->value);
                }
                $namespaceMap[$xml->localName] = $prefix;
            } else {
                $properties[self::unescapeXmlName($xml->name, $namespaceMap)] = array('values' => $xml->value, 'type' => null);
            }
        }
        if (! array_search('jcr', $namespaceMap)) {
            throw new \PHPCR\RepositoryException('Can not handle a document where the {http://www.jcp.org/jcr/1.0} namespace is not mapped to jcr');
        }
        if (! array_search('nt', $namespaceMap)) {
            throw new \PHPCR\RepositoryException('Can not handle a document where the {http://www.jcp.org/jcr/nt/1.0} namespace is not mapped to nt');
        }

        $nodename = self::unescapeXmlName($nodename, $namespaceMap);

        if (isset($properties['jcr:primaryType'])) {
            $type = $properties['jcr:primaryType']['values'];
            unset($properties['jcr:primaryType']);
        } else {
            $type = 'nt:unstructured';
        }

        $node = self::addNode($parentNode, $nodename, $type, $properties, $uuidBehavior);

        $xml->read(); // get to next element if there is one

        while (XMLReader::ELEMENT == $xml->nodeType) {
            self::importDocumentView($node, $ns, $xml, $uuidBehavior, $namespaceMap);
        }

        if (XMLReader::END_ELEMENT != $xml->nodeType) {
            throw new \PHPCR\InvalidSerializedDataException('Unexpected element in stream '.$xml->localName.'="'.$xml->value.'"');
        }

        // TODO logging echo "Done adding to ".$parentNode->getPath()."\n";
    }
}

/**
 * An XML reader that skips whitespace.
 *
 * (Can't we tell XMLReader or libxml2 to do that?
 */
class XMLReader2 extends XMLReader
{
    public function read()
    {
        while (parent::read()) {
            if (self::WHITESPACE !== $this->nodeType &&
                ! (self::SIGNIFICANT_WHITESPACE == $this->nodeType && '' == trim($this->value)) &&
                self::COMMENT !== $this->nodeType
            ) {
                return true;
            }
        }
        return false;
    }
    public function moveToNextElement()
    {
        while (parent::read()) {
            if (self::ELEMENT == $this->nodeType) {
                return true;
            }
        }
        return false;
    }
}