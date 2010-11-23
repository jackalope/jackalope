<?php

namespace Jackalope\Transport\DavexClient\Requests;

class ReportTest extends \PHPUnit_Framework_TestCase
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
     * Provides an instance of the \Jackalope\Transport\DavexClient\Requests\Report class.
     *
     * @param array $arguments List of arguments to be processed.
     * @return \Jackalope\Transport\DavexClient\Requests\Report
     */
    public function getReportObject($arguments)
    {
        return new \Jackalope\Transport\DavexClient\Requests\Report($arguments);
    }

    /*************************************************************************/
    /* Tests
    /*************************************************************************/

    /**
     * @covers \Jackalope\Transport\DavexClient\Requests\Report::build
     */
    public function testBuild()
    {
        $arguments = array('name' => 'dcr:registerednamespaces');
        $request = $this->getReportObject($arguments);
        $request->build();

        $this->assertXmlStringEqualsXmlFile(
            $this->getFixtureFile('ReportBuildWithOneName.xml'),
            strval($request)
        );
    }

    /**
     * @covers \Jackalope\Transport\DavexClient\Requests\Report::build
     * @expectedException \InvalidArgumentException
     */
    public function testBuildExpectingInvalidArgumentException()
    {
        $nt = $this->getReportObject(array());
        $nt->build();
    }

    /*************************************************************************/
    /* Dataprovider
    /*************************************************************************/

}