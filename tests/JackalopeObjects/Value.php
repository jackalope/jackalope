<?php
namespace jackalope\tests\JackalopeObjects;

require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class Value extends \jackalope\baseCase {

    public function testTypeInstances() {
        $val = new \jackalope\Value('undefined', '');
        $this->assertSame(\PHPCR_PropertyType::UNDEFINED, $val->getType());
        $val = new \jackalope\Value('String', '');
        $this->assertSame(\PHPCR_PropertyType::STRING, $val->getType());
        $this->setExpectedException('\jackalope\NotImplementedException');
        $val = new \jackalope\Value('Binary', '');
        // $this->assertSame(\PHPCR_PropertyType::BINARY, $val->getType());
        $val = new \jackalope\Value('Long', '');
        $this->assertSame(\PHPCR_PropertyType::LONG, $val->getType());
        $val = new \jackalope\Value('Double', '');
        $this->assertSame(\PHPCR_PropertyType::DOUBLE, $val->getType());
        $this->setExpectedException('\jackalope\NotImplementedException');
        $val = new \jackalope\Value('Date', '');
        // $this->assertSame(\PHPCR_PropertyType::DATE, $val->getType());
        $val = new \jackalope\Value('Boolean', '');
        $this->assertSame(\PHPCR_PropertyType::BOOLEAN, $val->getType());
        $val = new \jackalope\Value('Name', '');
        $this->assertSame(\PHPCR_PropertyType::NAME, $val->getType());
        $val = new \jackalope\Value('Path', '');
        $this->assertSame(\PHPCR_PropertyType::PATH, $val->getType());
        $val = new \jackalope\Value('Reference', '');
        $this->assertSame(\PHPCR_PropertyType::REFERENCE, $val->getType());
        $val = new \jackalope\Value('WeakReference', '');
        $this->assertSame(\PHPCR_PropertyType::WEAKREFERENCE, $val->getType());
        $val = new \jackalope\Value('URI', '');
        $this->assertSame(\PHPCR_PropertyType::URI, $val->getType());
        $val = new \jackalope\Value('Decimal', '');
        $this->assertSame(\PHPCR_PropertyType::DECIMAL, $val->getType());
        $this->setExpectedException('InvalidArgumentException');
        new \jackalope\Value('InvalidArgument', '');
    }

    public function testBaseConversions() {
        $val = new \jackalope\Value('String', '1.1');
        $this->assertSame('1.1', $val->getString());
        $this->assertSame(1, $val->getLong());
        $this->assertSame(1.1, $val->getDecimal());
        $this->assertSame(1.1, $val->getDouble());
        $this->assertSame(false, $val->getBoolean());

        $val = new \jackalope\Value('String', 'TrUe');
        $this->assertSame(true, $val->getBoolean());
    }
}
