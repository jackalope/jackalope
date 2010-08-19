<?php
require_once dirname(__FILE__) . '/../../src/jackalope/Helper.php';
require_once 'PHPUnit/Framework.php';

class jackalope_tests_Helper extends PHPUnit_Framework_TestCase {

    /**
     * @dataProvider dataproviderAbsolutePath
     * @covers jackalope_Helper::absolutePath
     * @covers jackalope_Helper::normalizePath
     */
    public function testAbsolutePath($inputRoot, $inputRelPath, $output) {
        $this->assertEquals($output, jackalope_Helper::absolutePath($inputRoot, $inputRelPath));
    }

    public static function dataproviderAbsolutePath() {
        return array(
            array('/',      'foo',  '/foo'),
            array('/',      '/foo', '/foo'),
            array('',       'foo',  '/foo'),
            array('',       '/foo', '/foo'),
            array('/foo',   'bar',  '/foo/bar'),
            array('/foo',   '',     '/foo'),
            array('/foo/',  'bar',  '/foo/bar'),
            array('/foo/',  '/bar', '/foo/bar'),
            array('foo',    'bar',  '/foo/bar'),

            // normalization is also part of ::absolutePath
            array('/',          '../foo',       '/foo'),
            array('/',          'foo/../bar',   '/bar'),
            array('/',          'foo/./bar',    '/foo/bar'),
            array('/foo/nope',  '../bar',       '/foo/bar'),
            array('/foo/nope',  '/../bar',      '/foo/bar'),
        );
    }
}
