<?php

namespace Jackalope\Transport\DavexClient\Requests;

class LocateTest extends \PHPUnit_Framework_TestCase
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
     * Provides an instance of the \Jackalope\Transport\DavexClient\Requests\Locate class.
     *
     * @param array $arguments List of arguments to be processed.
     * @return \Jackalope\Transport\DavexClient\Requests\Locate
     */
    public function getLocateObject($arguments)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        return new \Jackalope\Transport\DavexClient\Requests\Locate($dom, $arguments);
    }

    /*************************************************************************/
    /* Tests
    /*************************************************************************/

    /**
     * @covers \Jackalope\Transport\DavexClient\Requests\Locate::build
     */
    public function testBuild()
    {
        $arguments = array('uuid' => 'b58401da57c82346c4d3c01e1509a4b861a55114');
        $request = $this->getLocateObject($arguments);
        $request->build();
        $this->assertXmlStringEqualsXmlFile(
            $this->getFixtureFile('LocateBuildWithOneUuid.xml'),
            strval($request)
        );
    }

    /**
     * @covers \Jackalope\Transport\DavexClient\Requests\Locate::build
     * @expectedException \InvalidArgumentException
     */
    public function testBuildExpectingInvalidArgumentException()
    {
        $request = $this->getLocateObject(array());
        $request->build();
    }
}