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
        $this->assertEquals($expectedOutput, ImportExport::unescapeXmlName($input, []));
    }

    public function escapeDataProvider()
    {
        return [
            // The escaped characters
            [' ', '_x0020_'],
            ['<', '_x003c_'],
            ['>', '_x003e_'],
            ['"', '_x0022_'],
            ["'", '_x0027_'],
            // Some test data from the JCR-specs
            ['My Documents', 'My_x0020_Documents'],
            ['My_Documents', 'My_Documents'],
            ['My_x0020Documents', 'My_x005f_x0020Documents'],
            ['My_x0020_Documents', 'My_x005f_x0020_Documents'],
            ['My_x0020 Documents', 'My_x005f_x0020_x0020_Documents'],
            // Some combinations
            ['My "Documents"', 'My_x0020__x0022_Documents_x0022_'],
            ["<My 'Documents'>", '_x003c_My_x0020__x0027_Documents_x0027__x003e_'],
        ];
    }
}
