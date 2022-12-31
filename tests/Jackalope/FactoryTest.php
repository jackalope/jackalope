<?php

namespace Jackalope;

use Other\TestDummy;

class FactoryTest extends TestCase
{
    /**
     * @var Factory
     */
    protected $factory;

    public function setUp(): void
    {
        $this->factory = new Factory();
    }

    public function testJackalope()
    {
        $reg = $this->factory->get(NamespaceRegistry::class, [$this->getTransportStub()]);
        $this->assertInstanceOf(NamespaceRegistry::class, $reg);
    }

    public function testOutside()
    {
        $dummy = $this->factory->get(TestDummy::class);
        $this->assertInstanceOf(TestDummy::class, $dummy);
    }

    public function testNotexisting()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->factory->get('ClassNotExisting');
    }
}

namespace Other;

class TestDummy
{
    public function __construct()
    {
    }
}
