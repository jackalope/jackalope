<?php

namespace Jackalope;

use DOMDocument;
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
}