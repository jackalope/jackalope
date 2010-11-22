<?php
namespace Jackalope\Transport\DavexClient\Requests;

class BaseTest extends \PHPUnit_Framework_TestCase {

    /*************************************************************************/
    /* Fixtures
    /*************************************************************************/

    /**
     * Generates the absolute path to a fixture file.
     *
     * @param string $filename Name of the file the path shall be generated for.
     * @return string The location of the file.
     */
    public function getFixtureFile($filename) {
        $path = __DIR__.'/../../../../fixtures/Requests/';
        return $path.$filename;
    }

    /**
     * Provides an instance of the \Jackalope\Transport\DavexClient\Requests\DummyBase class.
     *
     * The \Jackalope\Transport\DavexClient\Requests\DummyBase class is a dummy implementation
     * of the \Jackalope\Transport\DavexClient\Requests\Base class.
     *
     * @param array $arguments List of arguments to be processed.
     * @return \Jackalope\Transport\DavexClient\Requests\DummyBase
     */
    public function getBaseObject(array $arguments) {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        return new \Jackalope\Transport\DavexClient\Requests\DummyBase($dom, $arguments);
    }

    /**
     * Creates a document structure in the DOMDocument.
     *
     * @param \Jackalope\Transport\DavexClient\Requests\Base $request
     */
    public function createDOMDocumentStructureFixture($request) {
        $doc = $request->dom->createElement('linux', 'test XML');
        $request->dom->appendChild($doc);
    }

    /*************************************************************************/
    /* Tests
    /*************************************************************************/

    /**
     * @covers \Jackalope\Transport\DavexClient\Requests\Base::__construct
     */
    public function testConstruct() {
        $arguments = array('OS' => array('Beastie', 'Puffy'));
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $request = new \Jackalope\Transport\DavexClient\Requests\DummyBase($dom, $arguments);

        $this->assertAttributeEquals($arguments, 'arguments', $request);
        $this->assertAttributeInstanceOf('DOMDocument', 'dom', $request);
    }

    /**
     * @covers \Jackalope\Transport\DavexClient\Requests\Base::__toString
     */
    public function testToString() {
        $arguments = array('OS' => array('Beastie', 'Puffy'));
        $request = $this->getBaseObject($arguments);
        $this->createDOMDocumentStructureFixture($request);
        $expected = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<linux>test XML</linux>";
        $this->assertXmlStringEqualsXmlString($expected, strval($request));
    }

    /**
     * @covers \Jackalope\Transport\DavexClient\Requests\Base::getXml
     */
    public function testGetXml() {
        $arguments = array('OS' => array('Beastie', 'Puffy'));
        $request = $this->getBaseObject($arguments);
        $this->createDOMDocumentStructureFixture($request);
        $expected = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<linux>test XML</linux>";
        $this->assertXmlStringEqualsXmlString($expected, $request->getXML());
    }
}

class DummyBase extends \Jackalope\Transport\DavexClient\Requests\Base {

    /**
     * $dom needs to be exposed due to be able to create a custom DOMDocument.
     *
     * @var DOMDocument
     */
    public $dom = null;

    public function build() {}
}