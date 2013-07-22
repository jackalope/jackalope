<?php

namespace Jackalope\ImportExport;

use Jackalope\TestCase;

class ImportExportTest extends TestCase
{
    /**
     * @dataProvider escapeDataProvider
     */
    public function testEscapeXmlName($input, $expectedOutput)
    {
        $this->assertEquals($expectedOutput, ImportExport::escapeXmlName($input));
    }

    /**
     * @dataProvider escapeDataProvider
     */
    public function testUnescapeXmlName($expectedOutput, $input)
    {
        $this->assertEquals($expectedOutput, ImportExport::unescapeXmlName($input, array()));
    }

    public function escapeDataProvider()
    {
        return array(
            // The escaped characters
            array(' ', '_x0020_'),
            array('<', '_x003c_'),
            array('>', '_x003e_'),
            array('"', '_x0022_'),
            array("'", '_x0027_'),
            // Some test data from the JCR-specs
            array('My Documents', 'My_x0020_Documents'),
            array('My_Documents', 'My_Documents'),
            array('My_x0020Documents', 'My_x005f_x0020Documents'),
            array('My_x0020_Documents', 'My_x005f_x0020_Documents'),
            array('My_x0020 Documents', 'My_x005f_x0020_x0020_Documents'),
            // Some combinations
            array('My "Documents"', 'My_x0020__x0022_Documents_x0022_'),
            array("<My 'Documents'>", "_x003c_My_x0020__x0027_Documents_x0027__x003e_"),
        );
    }
}
