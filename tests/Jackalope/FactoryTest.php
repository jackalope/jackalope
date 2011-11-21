<?php

namespace Jackalope;

class FactoryTest extends TestCase
{
    protected $factory;

    public function setUp()
    {
        $this->factory = new \Jackalope\Factory;
    }

    public function testJackalope()
    {
        $reg = $this->factory->get('NamespaceRegistry', array($this->getMock('Jackalope\Transport\TransportInterface')));
        $this->assertInstanceOf('Jackalope\NamespaceRegistry', $reg);
    }

    public function testOutside()
    {
        $dummy = $this->factory->get('Other\TestDummy');
        $this->assertInstanceOf('Other\TestDummy', $dummy);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNotexisting()
    {
        $this->factory->get('ClassNotExisting');
    }
}

namespace Other;
class TestDummy
{
    public function __construct($factory)
    {
        if (! $factory instanceof \Jackalope\Factory) {
            throw new \Exception('not a valid factory as first argument');
        }
    }
}
