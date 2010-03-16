<?php
require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class jackalope_tests_Value extends jackalope_baseCase {

    public function testTypeInstances() {
        $val = new jackalope_Value('undefined', '');
        $this->assertSame(PHPCR_PropertyType::UNDEFINED, $val->getType());
        $val = new jackalope_Value('String', '');
        $this->assertSame(PHPCR_PropertyType::STRING, $val->getType());
        $this->setExpectedException('jackalope_NotImplementedException');
        $val = new jackalope_Value('Binary', '');
        // $this->assertSame(PHPCR_PropertyType::BINARY, $val->getType());
        $val = new jackalope_Value('Long', '');
        $this->assertSame(PHPCR_PropertyType::LONG, $val->getType());
        $val = new jackalope_Value('Double', '');
        $this->assertSame(PHPCR_PropertyType::DOUBLE, $val->getType());
        $this->setExpectedException('jackalope_NotImplementedException');
        $val = new jackalope_Value('Date', '');
        // $this->assertSame(PHPCR_PropertyType::DATE, $val->getType());
        $val = new jackalope_Value('Boolean', '');
        $this->assertSame(PHPCR_PropertyType::BOOLEAN, $val->getType());
        $val = new jackalope_Value('Name', '');
        $this->assertSame(PHPCR_PropertyType::NAME, $val->getType());
        $val = new jackalope_Value('Path', '');
        $this->assertSame(PHPCR_PropertyType::PATH, $val->getType());
        $val = new jackalope_Value('Reference', '');
        $this->assertSame(PHPCR_PropertyType::REFERENCE, $val->getType());
        $val = new jackalope_Value('WeakReference', '');
        $this->assertSame(PHPCR_PropertyType::WEAKREFERENCE, $val->getType());
        $val = new jackalope_Value('URI', '');
        $this->assertSame(PHPCR_PropertyType::URI, $val->getType());
        $val = new jackalope_Value('Decimal', '');
        $this->assertSame(PHPCR_PropertyType::DECIMAL, $val->getType());
        $this->setExpectedException('InvalidArgumentException');
        new jackalope_Value('InvalidArgument', '');
    }
    
    public function testBaseConversions() {
        $val = new jackalope_Value('String', '1.1');
        $this->assertSame('1.1', $val->getString());
        $this->assertSame(1, $val->getLong());
        $this->assertSame(1.1, $val->getDecimal());
        $this->assertSame(1.1, $val->getDouble());
        $this->assertSame(true, $val->getBoolean());
        
    }
}
