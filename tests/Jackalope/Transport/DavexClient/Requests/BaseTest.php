<?php
namespace Jackalope\Transport\DavexClient\Requests;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    /*************************************************************************/
    /* Fixtures
    /*************************************************************************/

    /**
     * Generates the absolute path to a fixture file.
     *
     * @param string $filename Name of the file the path shall be generated for.
     * @return string The location of the file.
     */
    public function getFixtureFile($filename)
    {
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
    public function getBaseObject(array $arguments)
    {
        return new \Jackalope\Transport\DavexClient\Requests\DummyBase($arguments);
    }

    /*************************************************************************/
    /* Tests
    /*************************************************************************/

    /**
     * @covers \Jackalope\Transport\DavexClient\Requests\Base::__construct
     */
    public function testConstruct()
    {
        $arguments = array('OS' => array('Beastie', 'Puffy'));
        $request = new \Jackalope\Transport\DavexClient\Requests\DummyBase($arguments);

        $this->assertAttributeEquals($arguments, 'arguments', $request);
    }

    /**
     * @covers \Jackalope\Transport\DavexClient\Requests\Base::__toString
     */
    public function testToString()
    {
        $arguments = array('OS' => array('Beastie', 'Puffy'));
        $request = $this->getBaseObject($arguments);
        $request->build();
        $expected = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><os><Beastie /><Puffy /></os>";
        $this->assertXmlStringEqualsXmlString($expected, strval($request));
    }

    /**
     * @covers \Jackalope\Transport\DavexClient\Requests\Base::getXml
     */
    public function testGetXml()
    {
        $arguments = array('OS' => array('Beastie', 'Puffy'));
        $request = $this->getBaseObject($arguments);
        $request->build();
        $expected = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><os><Beastie /><Puffy /></os>";
        $this->assertXmlStringEqualsXmlString($expected, $request->getXML());
    }
}

class DummyBase extends \Jackalope\Transport\DavexClient\Requests\Base
{
    public function build()
    {
        $this->xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $this->xml .= '<os>';
        foreach ($this->arguments['OS'] as $elem) {
            $this->xml .= '<'.$elem.' />';
        }
        $this->xml .= '</os>';
    }
}