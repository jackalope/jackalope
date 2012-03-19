<?php

namespace Jackalope;

use DOMDocument;
use XMLReader;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPCR\NamespaceRegistryInterface;

/**
 * Helper class with static methods to import and export data.
 *
 * TODO: should we move this to phpcr-utils? it has no dependency on jackalope at all
 */
class ImportExport
{
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
     * Helper method for escaping node names into valid xml according to
     * the specification.
     *
     * @param string $name A node name possibly containing characters illegal
     *      in an XML document.
     *
     * @return string The name encoded to be valid xml
     */
    public static function escapeXmlName($name)
    {
        $name = preg_replace('/_(x[0-9a-fA-F]{4})/', '_x005f_\\1', $name);
        return str_replace(
            array(' '      , '<'      , '>'      , '"'      , "'"      ),
            array('_x0020_', '_x003c_', '_x003e_', '_x0022_', '_x0027_'),
            $name); // TODO: more invalid characters?
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

        $xml = new XMLReader;
        $xml->open($uri);
        //$xml->setParserProperty(XMLReader::VALIDATE, true);
        if (libxml_get_errors()) {// || ! $xml->isValid()) {
            libxml_use_internal_errors($use_errors);
            throw new \PHPCR\InvalidSerializedDataException;
        }
        $xml->read();

        if ('node' == $xml->localName && NamespaceRegistryInterface::NAMESPACE_SV == $xml->namespaceURI) {
            self::importSystemView($parentNode, $ns, $xml, $uuidBehavior);
        } else {
            self::importDocumentView($parentNode, $ns, $xml, $uuidBehavior);
        }

        libxml_use_internal_errors($use_errors);
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
            fwrite($stream, '<sv:property sv:name="jcr:mixinTypes" sv:type="Name">');
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
     * @param XMLReader $xml
     * @param int $uuidBehavior
     * @param array $documentNamespaces hashmap of prefix => uri for namespaces in the document
     */
    private static function importSystemView(NodeInterface $parentNode, NamespaceRegistryInterface $ns, XMLReader $xml, $uuidBehavior, $namespaceMap = array())
    {
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
        if (! self::xmlReaderNextElement($xml)) {
            throw new \PHPCR\InvalidSerializedDataException('missing information to create node');
        }
        if ('property' != $xml->localName || NamespaceRegistryInterface::NAMESPACE_SV != $xml->namespaceURI) {
            throw new \PHPCR\InvalidSerializedDataException('first child of node must be sv:property for jcr:primaryType. Found {'.$xml->namespaceURI.'}'.$xml->localName);
        }
        if (! $xml->moveToAttributeNs('name', NamespaceRegistryInterface::NAMESPACE_SV) || 'jcr:primaryType' != $xml->value) {
            throw new \PHPCR\InvalidSerializedDataException('first child of node must be sv:property for jcr:primaryType. Found {'.$xml->namespaceURI.'}'.$xml->localName);
        }

        self::xmlReaderNextElement($xml); // value child of property jcr:primaryType
        $type = $xml->readInnerXml();

        if ('jcr:root' == $nodename) {
            // TODO http://www.day.com/specs/jcr/2.0/11_Import.html
        }
        $nodename = self::cleanNamespace($nodename, $namespaceMap);

        echo "Adding node $nodename\n";
        $node = $parentNode->addNode($nodename, $type);

        while (self::xmlReaderNextElement($xml)) {
            if ('node' == $xml->localName) {
                self::importSystemView($node, $ns, $xml, $uuidBehavior, $namespaceMap);
            } elseif ('property' == $xml->localName) {
                $xml->moveToAttributeNs('name', NamespaceRegistryInterface::NAMESPACE_SV);
                $name = $xml->value;
                $xml->moveToAttributeNs('type', NamespaceRegistryInterface::NAMESPACE_SV);
                $type = PropertyType::valueFromName($xml->value);
                if ($xml->moveToAttributeNs('multiple', NamespaceRegistryInterface::NAMESPACE_SV)) {
                    $multiple = strcasecmp($xml->value, 'true') === 0;
                    $values = array();
                } else {
                    $multiple = false;
                }
                self::xmlReaderNextElement($xml);
                if ($multiple) {
                    while ('value' == $xml->localName) {
                        $values[] = (PropertyType::BINARY == $type) ? base64_decode($xml->value) : $xml->value;
                        self::xmlReaderNextElement($xml);
                    }
                } else {
                    $values = (PropertyType::BINARY == $type) ? base64_decode($xml->value) : $xml->value;
                }
                $name = self::cleanNamespace($name, $namespaceMap);

echo "Adding property $name\n";
                $node->setProperty($name, $values, $type);
            }
        }
    }

    /**
     * Helper function to ensure prefix is same as in repository
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
     * Helper function because I could not figure out how to tell XMLReader to
     * skip irrelevant whitespace
     *
     * @param \XMLReader $xml
     */
    private static function xmlReaderNextElement(XMLReader $xml)
    {
        while ($xml->read()) {
            if (XMLReader::ELEMENT == $xml->nodeType) {
                return true;
            }
        }
        return false;
    }

    private static function importDocumentView()
    {

    }
}