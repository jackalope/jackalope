<?php
/**
 * Convert Jackalope Document or System Views into PHPUnit DBUnit Fixture XML files
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */

require_once __DIR__ . "/../lib/phpcr/src/PHPCR/Util/UUIDHelper.php";

$srcDir = __DIR__ . "/suite/fixtures";
$destDir = __DIR__ . "/fixtures/doctrine";

$jcrTypes = array(
    "string"        => array(1, "clob_data"),
    "binary"        => array(2, "int_data"),
    "long"          => array(3, "int_data"),
    "double"        => array(4, "float_data"),
    "date"          => array(5, "datetime_data"),
    "boolean"       => array(6, "int_data"),
    "name"          => array(7, "string_data"),
    "path"          => array(8, "string_data"),
    "reference"     => array(9, "string_data"),
    "weakreference" => array(10, "string_data"),
    "uri"           => array(11, "string_data"),
    "decimal"       => array(12, "string_data"),
);

$rdi = new RecursiveDirectoryIterator($srcDir);
$ri = new RecursiveIteratorIterator($rdi);

libxml_use_internal_errors(true);
foreach ($ri AS $file) {
    if (!$file->isFile()) { continue; }

    $newFile = str_replace($srcDir, $destDir, $file->getPathname());

    $srcDom = new DOMDocument('1.0', 'UTF-8');
    $srcDom->load($file->getPathname());

    if (libxml_get_errors()) {
        echo "Errors in " . $file->getPathname()."\n";
        continue;
    }

    echo "Importing " . str_replace($srcDir, "", $file->getPathname())."\n";
    $dataSetBuilder = new PHPUnit_Extensions_Database_XmlDataSetBuilder();
    $dataSetBuilder->addRow('phpcr_workspaces', array('id' => 1, 'name' => 'tests'));

    $nodes = $srcDom->getElementsByTagNameNS('http://www.jcp.org/jcr/sv/1.0', 'node');
    $seenPaths = array();
    $nodeId = 1;
    if ($nodes->length > 0) {
        $id = \PHPCR\Util\UUIDHelper::generateUUID();
        // system-view
        $dataSetBuilder->addRow("phpcr_nodes", array(
            'id' => $nodeId++,
            'path' => '',
            'parent' => '-1',
            'workspace_id' => 1,
            'identifier' => $id,
            'type' => 'nt:unstructured',
            'props' => '<?xml version="1.0" encoding="UTF-8"?>
<sv:node xmlns:crx="http://www.day.com/crx/1.0"
         xmlns:lx="http://flux-cms.org/2.0"
         xmlns:test="http://liip.to/jackalope"
         xmlns:mix="http://www.jcp.org/jcr/mix/1.0"
         xmlns:sling="http://sling.apache.org/jcr/sling/1.0"
         xmlns:nt="http://www.jcp.org/jcr/nt/1.0"
         xmlns:fn_old="http://www.w3.org/2004/10/xpath-functions"
         xmlns:fn="http://www.w3.org/2005/xpath-functions"
         xmlns:vlt="http://www.day.com/jcr/vault/1.0"
         xmlns:xs="http://www.w3.org/2001/XMLSchema"
         xmlns:new_prefix="http://a_new_namespace"
         xmlns:jcr="http://www.jcp.org/jcr/1.0"
         xmlns:sv="http://www.jcp.org/jcr/sv/1.0"
         xmlns:rep="internal" />'
        ));
        foreach ($nodes AS $node) {
            /* @var $node DOMElement */
            $parent = $node;
            $path = "";
            do {
                if ($parent->tagName == "sv:node") {
                    $path = "/" . $parent->getAttributeNS('http://www.jcp.org/jcr/sv/1.0', 'name') . $path;
                }
                $parent = $parent->parentNode;
            } while ($parent instanceof DOMElement);
            $path = ltrim($path, '/');

            $attrs = array();
            foreach ($node->childNodes AS $child) {
                if ($child instanceof DOMElement && $child->tagName == "sv:property") {
                    $name = $child->getAttributeNS('http://www.jcp.org/jcr/sv/1.0', 'name');

                    $value = array();
                    foreach ($child->getElementsByTagNameNS('http://www.jcp.org/jcr/sv/1.0', 'value') AS $nodeValue) {
                        $value[] = $nodeValue->nodeValue;
                    }

                    $attrs[$name] = array(
                        'type' =>  strtolower($child->getAttributeNS('http://www.jcp.org/jcr/sv/1.0', 'type')),
                        'value' => $value,
                        'multiValued' => (in_array($name, array('jcr:mixinTypes'))) || count($value) > 1,
                    );
                }
            }

            if (isset($attrs['jcr:uuid']['value'][0])) {
                $id = (string)$attrs['jcr:uuid']['value'][0];
                unset($attrs['jcr:uuid']['value'][0]);
            } else {
                $id = \PHPCR\Util\UUIDHelper::generateUUID();
            }

            $namespaces = array(
                'mix' => "http://www.jcp.org/jcr/mix/1.0",
                'nt' => "http://www.jcp.org/jcr/nt/1.0",
                'xs' => "http://www.w3.org/2001/XMLSchema",
                'jcr' => "http://www.jcp.org/jcr/1.0",
                'sv' => "http://www.jcp.org/jcr/sv/1.0",
                'rep' => "internal"
            );

            $dom = new \DOMDocument('1.0', 'UTF-8');
            $rootNode = $dom->createElement('sv:node');
            foreach ($namespaces as $namespace => $uri) {
                $rootNode->setAttribute('xmlns:' . $namespace, $uri);
            }
            $dom->appendChild($rootNode);

            $binaryData = null;
            $idx = 0;
            foreach ($attrs AS $attr => $valueData) {
                if (isset($jcrTypes[$valueData['type']])) {
                    $jcrTypeConst = $jcrTypes[$valueData['type']][0];
                    
                    $propertyNode = $dom->createElement('sv:property');
                    $propertyNode->setAttribute('sv:name', $attr);
                    $propertyNode->setAttribute('sv:type', $jcrTypeConst); // TODO: Name! not int
                    $propertyNode->setAttribute('sv:multi-valued', $valueData['multiValued'] ? "1" : "0");

                     foreach ($valueData['value'] AS $value) {
                        switch ($valueData['type']) {
                            case 'binary':
                                $value = strlen(base64_decode($value));
                                break;
                            case 'boolean':
                                $value = 'true' === $value ? '1' : '0';
                                break;
                            case 'date':
                                $datetime = \DateTime::createFromFormat('Y-m-d\TH:i:s.uP', $value);
                                $datetime->setTimezone(new DateTimeZone('UTC'));
                                $value = $datetime->format('Y-m-d\TH:i:s.uP');
                                break;
                        }
                        $propertyNode->appendChild($dom->createElement('sv:value', $value));

                        if ('binary' === $valueData['type']) {
                            $dataSetBuilder->addRow('phpcr_binarydata', array(
                                'node_id' => $nodeId,
                                'property_name' => $attr,
                                'workspace_id' => 1,
                                'idx' => $idx++,
                                'data' => $value,
                            ));
                        }
                    }

                    $rootNode->appendChild($propertyNode);
                } else {
                    throw new InvalidArgumentException("No type ".$valueData['type']);
                }          
            }
            
            $dataSetBuilder->addRow('phpcr_nodes', array(
                'id' => $nodeId,
                'path' => $path,
                'parent' => implode("/", array_slice(explode("/", $path), 0, -1)),
                'workspace_id' => 1,
                'identifier' => $id,
                'type' => $attrs['jcr:primaryType']['value'][0],
                'props' => $dom->saveXML(),
            ));

            
            $nodeId++;
        }
    } else {
        continue; // document view not supported
    }

    @mkdir (dirname($newFile), 0777, true);
    file_put_contents($newFile, $dataSetBuilder->asXml());
}


class PHPUnit_Extensions_Database_XmlDataSetBuilder
{
    private $dom;

    private $tables = array();

    public function __construct()
    {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $dataset = $this->dom->createElement('dataset');
        $this->dom->appendChild($dataset);
    }

    public function addRow($tableName, array $data)
    {
        if (!isset($this->tables[$tableName])) {
            $table = $this->dom->createElement('table');
            $table->setAttribute('name', $tableName);
            foreach ($data AS $k => $v) {
                $table->appendChild($this->dom->createElement('column', $k));
            }
            $this->tables[$tableName] = $table;
            $this->dom->documentElement->appendChild($table);
        }

        $row = $this->dom->createElement('row');
        foreach ($data AS $k => $v) {
            if ($v === null) {
                $row->appendChild($this->dom->createElement('null'));
            } else {
                $row->appendChild($this->dom->createElement('value', $v));
            }
        }
        $this->tables[$tableName]->appendChild($row);
    }

    public function asXml()
    {
        $this->dom->formatOutput = true;
        return $this->dom->saveXml();
    }
}
