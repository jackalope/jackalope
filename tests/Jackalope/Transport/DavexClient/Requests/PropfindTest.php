<?php

namespace Jackalope\Transport\DavexClient\Requests;

class PropfindTest extends \PHPUnit_Framework_TestCase {

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
     * Provides an instance of the \Jackalope\Transport\DavexClient\Requests\Propfind class.
     *
     * @param array $arguments List of arguments to be processed.
     * @return \Jackalope\Transport\DavexClient\Requests\Propfind
     */
    public function getPropfindObject($arguments) {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        return new \Jackalope\Transport\DavexClient\Requests\Propfind($dom, $arguments);
    }

    /*************************************************************************/
    /* Tests
    /*************************************************************************/

    /**
     * @covers \Jackalope\Transport\DavexClient\Requests\Propfind::__construct
     */
    public function testConstruct() {
        $arguments = array('properties' => array('D:workspace', 'dcr:workspaceName'));
        $request = $this->getPropfindObject($arguments);

        $this->assertAttributeEquals($arguments, 'arguments', $request);
        $this->assertAttributeInstanceOf('DOMDocument', 'dom', $request);
    }

    /**
     * @dataProvider buildDataprovider
     * @covers \Jackalope\Transport\DavexClient\Requests\Propfind::build
     * @covers \Jackalope\Transport\DavexClient\Requests\Propfind::__toString
     */
    public function testBuildWithMultipleProperties($arguments, $fixtureFilename) {
        $request = $this->getPropfindObject($arguments);
        $request->build();

        $this->assertXmlStringEqualsXmlFile(
            $this->getFixtureFile($fixtureFilename),
            strval($request)
        );
    }

    /**
     * @covers \Jackalope\Transport\DavexClient\Requests\Propfind::build
     * @expectedException \InvalidArgumentException
     */
    public function testBuildExpectingInvalidArgumentException() {
        $nt = $this->getPropfindObject(array());
        $nt->build();
    }

    /**
     * @covers \Jackalope\Transport\DavexClient\Requests\Propfind::getXml
     */
    public function testGetXml() {
        $arguments = array('properties' => array('D:workspace', 'dcr:workspaceName'));
        $request = $this->getPropfindObject($arguments);
        $request->build();

        $this->assertXmlStringEqualsXmlFile(
            $this->getFixtureFile('PropfindBuildWithMultipleProperties.xml'),
            $request->getXml()
        );
    }

    /*************************************************************************/
    /* Dataprovider
    /*************************************************************************/

    public static function buildDataprovider() {
        return array(
            'Multiple Properties' => array(
                array('properties' => array('D:workspace', 'dcr:workspaceName')),
                'PropfindBuildWithMultipleProperties.xml'
            ),
            'single Property as array' => array(
                array('properties' => array('D:workspace')),
                'PropfindBuildWithOneProperties.xml'
            ),
            'single Property as string ' => array(
                array('properties' => 'D:workspace'),
                'PropfindBuildWithOneProperties.xml'
            ),
        );
    }
}