<?php

namespace Jackalope\Transport\DavexClient\Requests;

class NodeTypesTest extends \PHPUnit_Framework_TestCase
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
     * Provides an instance of the \Jackalope\Transport\DavexClient\Requests\NodeTypes class.
     *
     * @param array $arguments List of arguments to be processed.
     * @return \Jackalope\Transport\DavexClient\Requests\NodeTypes
     */
    public function getNodeTypesObject($arguments)
    {
        return new \Jackalope\Transport\DavexClient\Requests\NodeTypes($arguments);
    }

    /*************************************************************************/
    /* Tests
    /*************************************************************************/

    /**
     * @dataProvider buildDataprovider
     * @covers \Jackalope\Transport\DavexClient\Requests\NodeTypes::build
     */
    public function testBuildWithNodetypes($arguments, $fixtureFilename)
    {
        $nt = $this->getNodeTypesObject($arguments);
        $nt->build();

        $this->assertXmlStringEqualsXmlFile(
            $this->getFixtureFile($fixtureFilename),
            strval($nt)
        );
    }

    /**
     * @covers \Jackalope\Transport\DavexClient\Requests\NodeTypes::build
     * @expectedException \InvalidArgumentException
     */
    public function testBuildExpectingInvalidArgumentException()
    {
        $nt = $this->getNodeTypesObject(array());
        $nt->build();
    }

    /*************************************************************************/
    /* Dataprovider
    /*************************************************************************/

    public static function buildDataprovider()
    {
        return array(
            'Multiple Nodetypes' => array(
                array('nodetypes' => array('Beastie', 'Puffy')),
                'NodeTypesBuildWithMultipleNodetypes.xml'
            ),
            'single Nodetypes' => array(
                array('nodetypes' => array('Beastie')),
                'NodeTypesBuildWithOneNodetype.xml'
            ),
            'empty Nodetypes' => array(
                array('nodetypes' => array()),
                'NodeTypesBuildWithEmptyNodetype.xml'
            ),
        );
    }
}