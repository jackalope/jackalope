<?php

namespace Jackalope;

class PropertyTest extends TestCase
{
    public function testTypeInstances()
    {
        $this->markTestSkipped('Port this over to test property types and the helper type conversions');
        /*
        $val = new Value('undefined', '');
        $this->assertSame(\PHPCR_PropertyType::UNDEFINED, $val->getType());
        $val = new Value('String', '');
        $this->assertSame(\PHPCR_PropertyType::STRING, $val->getType());
        $this->setExpectedException('\Jackalope\NotImplementedException');
        $val = new Value('Binary', '');
        // $this->assertSame(\PHPCR_PropertyType::BINARY, $val->getType());
        $val = new Value('Long', '');
        $this->assertSame(\PHPCR_PropertyType::LONG, $val->getType());
        $val = new Value('Double', '');
        $this->assertSame(\PHPCR_PropertyType::DOUBLE, $val->getType());
        $this->setExpectedException('\Jackalope\NotImplementedException');
        $val = new Value('Date', '');
        // $this->assertSame(\PHPCR_PropertyType::DATE, $val->getType());
        $val = new Value('Boolean', '');
        $this->assertSame(\PHPCR_PropertyType::BOOLEAN, $val->getType());
        $val = new Value('Name', '');
        $this->assertSame(\PHPCR_PropertyType::NAME, $val->getType());
        $val = new Value('Path', '');
        $this->assertSame(\PHPCR_PropertyType::PATH, $val->getType());
        $val = new Value('Reference', '');
        $this->assertSame(\PHPCR_PropertyType::REFERENCE, $val->getType());
        $val = new Value('WeakReference', '');
        $this->assertSame(\PHPCR_PropertyType::WEAKREFERENCE, $val->getType());
        $val = new Value('URI', '');
        $this->assertSame(\PHPCR_PropertyType::URI, $val->getType());
        $val = new Value('Decimal', '');
        $this->assertSame(\PHPCR_PropertyType::DECIMAL, $val->getType());
        $this->setExpectedException('InvalidArgumentException');
        new Value('InvalidArgument', '');
        */
    }

    public function testBaseConversions()
    {
        $this->markTestSkipped('Port this over to test property types and the helper type conversions');
        /*
        $val = new Value('String', '1.1');
        $this->assertSame('1.1', $val->getString());
        $this->assertSame(1, $val->getLong());
        $this->assertSame(1.1, $val->getDecimal());
        $this->assertSame(1.1, $val->getDouble());
        $this->assertSame(false, $val->getBoolean());

        $val = new Value('String', 'TrUe');
        $this->assertSame(true, $val->getBoolean());
        */
    }
}
