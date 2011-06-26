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
    $dataSetBuilder->addRow('jcrworkspaces', array('id' => 1, 'name' => 'tests'));

    $nodes = $srcDom->getElementsByTagNameNS('http://www.jcp.org/jcr/sv/1.0', 'node');
    $seenPaths = array();
    if ($nodes->length > 0) {
        $id = \Jackalope\Helper::generateUUID();
        // system-view
        $dataSetBuilder->addRow("jcrnodes", array(
            'path' => '',
            'parent' => '-1',
            'workspace_id' => 1,
            'identifier' => $id,
            'type' => 'nt:unstructured',
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
                $id = \Jackalope\Helper::generateUUID();
            }

            $dataSetBuilder->addRow('jcrnodes', array(
                'path' => $path,
                'parent' => implode("/", array_slice(explode("/", $path), 0, -1)),
                'workspace_id' => 1,
                'identifier' => $id,
                'type' => $attrs['jcr:primaryType']['value'][0])
            );

            unset($attrs['jcr:primaryType']);
            foreach ($attrs AS $attr => $valueData) {
                $idx = 0;
                $data = array(
                    'path' => $path . '/' . $attr,
                    'workspace_id' => 1,
                    'name' => $attr,
                    'idx' => $idx,
                    'node_identifier' => $id,
                    'type' => 0,
                    'multi_valued' => 0,
                    'string_data' => null,
                    'int_data' => null,
                    'float_data' => null,
                    'clob_data' => null,
                    'datetime_data' => null,
                );
                if (isset($jcrTypes[$valueData['type']])) {
                    list($jcrTypeConst, $jcrTypeDbField) = $jcrTypes[$valueData['type']];
                    $data['type'] = $jcrTypeConst;
                    $data['multi_valued'] = $valueData['multiValued'] ? "1" : "0";
                    foreach ($valueData['value'] AS $value) {
                        $data[$jcrTypeDbField] = ($jcrTypeConst == 2) ? strlen(base64_decode($value)) : $value;
                        $dataSetBuilder->addRow('jcrprops', $data);

                        if ($jcrTypeConst == 2) {
                            $dataSetBuilder->addRow('jcrbinarydata', array(
                                'path' => $data['path'],
                                'workspace_id' => 1,
                                'idx' => $data['idx'],
                                'data' => base64_decode($value),
                            ));
                        }

                        $data['idx'] = ++$idx;
                    }
                } else {
                    throw new InvalidArgumentException("No type ".$valueData['type']);
                }

                
            }
        }
    } else {
        $id = \Jackalope\Helper::generateUUID();
        // document-view
        $dataSetBuilder->addRow("jcrnodes", array(
            'path' => '',
            'parent' => '-1',
            'workspace_id' => 1,
            'identifier' => $id,
            'type' => 'nt:unstructured',
        ));

        $nodes = $srcDom->getElementsByTagName('*');
        foreach ($nodes AS $node) {
            if ($node instanceof DOMElement) {
                $parent = $node;
                $path = "";
                do {
                    $path = "/" . $parent->tagName . $path;
                    $parent = $parent->parentNode;
                } while ($parent instanceof DOMElement);
                $path = ltrim($path, '/');

                $attrs = array();
                foreach ($node->attributes AS $attr) {
                    $name = ($attr->prefix) ? $attr->prefix.":".$attr->name : $attr->name;
                    $attrs[$name] = $attr->value;
                }

                if (!isset($attrs['jcr:primaryType'])) {
                    $attrs['jcr:primaryType'] = 'nt:unstructured';
                }

                if (isset($attrs['jcr:uuid'])) {
                    $id = $attrs['jcr:uuid'];
                    unset($attrs['jcr:uuid']);
                } else {
                    $id = \Jackalope\Helper::generateUUID();
                }

                if (!isset($seenPaths[$path])) {
                    $dataSetBuilder->addRow('jcrnodes', array(
                        'path' => $path,
                        'parent' => implode("/", array_slice(explode("/", $path), 0, -1)),
                        'workspace_id' => 1,
                        'identifier' => $id,
                        'type' => $attrs['jcr:primaryType'])
                    );
                    $seenPaths[$path] = $id;
                } else {
                    $id = $seenPaths[$path];
                }
                
                unset($attrs['jcr:primaryType']);
                foreach ($attrs AS $attr => $valueData) {
                    $dataSetBuilder->addRow('jcrprops', array(
                        'path' => $path . '/' . $attr,
                        'workspace_id' => 1,
                        'name' => $attr,
                        'node_identifier' => $id,
                        'type' => 1,
                        'multi_valued' => (in_array($attr, array('jcr:mixinTypes'))),
                        'string_data' => null,
                        'int_data' => null,
                        'float_data' => null,
                        'clob_data' => $valueData,
                        'datetime_data' => null,
                    ));
                }
            }
        }
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