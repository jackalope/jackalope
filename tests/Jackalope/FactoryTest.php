<?php

namespace Jackalope;

class FactoryTest extends TestCase
{
    /**
     * @var Factory
     */
    protected $factory;

    public function setUp()
    {
        $this->factory = new Factory;
    }

    public function testJackalope()
    {
        $reg = $this->factory->get('NamespaceRegistry', array($this->getTransportStub()));
        $this->assertInstanceOf('Jackalope\NamespaceRegistry', $reg);
    }

    public function testOutside()
    {
        $dummy = $this->factory->get('Other\TestDummy');
        $this->assertInstanceOf('Other\TestDummy', $dummy);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNotexisting()
    {
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
