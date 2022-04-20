<?php

namespace Jackalope;

use Jackalope\Transport\TransportInterface;
use PHPCR\NamespaceException;

class NamespaceRegistryTest extends TestCase
{
    /* Fixtures
    /*************************************************************************/

    protected $defaultNamespaces = [
        'jcr' => 'http://www.jcp.org/jcr/1.0',
        'sv' => 'http://www.jcp.org/jcr/sv/1.0',
        'nt' => 'http://www.jcp.org/jcr/nt/1.0',
        'mix' => 'http://www.jcp.org/jcr/mix/1.0',
        'xml' => 'http://www.w3.org/XML/1998/namespace',
        '' => '',
    ];

    /**
     * Create an object of the namespaceRegistry.
     *
     * @param array $namespaces
     * @param bool  $getNamespaceHasToBeCalled
     *
     * @return NamespaceRegistry
     */
    public function getNamespaceRegistry($namespaces, $getNamespaceHasToBeCalled = true)
    {
        if ($getNamespaceHasToBeCalled) {
            $expects = $this->once();
        } else {
            $expects = $this->any();
        }

        $factory = new Factory();
        $transport = $this->getTransportStub();
        $transport
            ->expects($expects)
            ->method('getNamespaces')
            ->will($this->returnValue($namespaces))
        ;

        return new NamespaceRegistry($factory, $transport);
    }

    /* Tests
    /*************************************************************************/

    /**
     * @dataProvider constructorDataprovider
     * @covers \Jackalope\NamespaceRegistry::__construct
     */
    public function testConstruct($expected, $namespaces)
    {
        $nsr = $this->getNamespaceRegistry($namespaces, false);
        $reflection = new \ReflectionClass($nsr);
        $transport = $reflection->getProperty('transport');
        $transport->setAccessible(true);
        $this->assertInstanceOf(TransportInterface::class, $transport->getValue($nsr));

        $userNamespaces = $reflection->getProperty('userNamespaces');
        $userNamespaces->setAccessible(true);
        // after construct, userNamespaces is supposed to be null due to lazyLoading
        $this->assertNull($userNamespaces->getValue($nsr));
        // after we get the prefixes, userNamespaces is supposed to have the userNamespaces
        $nsr->getPrefixes();
        $this->assertSame($expected, $userNamespaces->getValue($nsr));
    }

    /**
     * @covers \Jackalope\NamespaceRegistry::registerNamespace
     */
    public function testRegisterNamespace()
    {
        $this->expectException(NotImplementedException::class);

        $this->markTestIncomplete('Write operations are currently not supported.');
    }

    /**
     * @covers \Jackalope\NamespaceRegistry::unregisterNamespace
     */
    public function testUnregisterNamespace()
    {
        $this->expectException(NotImplementedException::class);

        $this->markTestIncomplete('Write operations are currently not supported.');
    }

    /**
     * @covers \Jackalope\NamespaceRegistry::getPrefixes
     */
    public function testGetPrefixes()
    {
        $namespaces = ['beastie' => 'http://beastie.lo/beastie/1.0'];

        $nsr = $this->getNamespaceRegistry($namespaces);
        $expected = ['jcr', 'sv', 'nt', 'mix', 'xml', '', 'beastie'];

        $this->assertEquals($expected, $nsr->getPrefixes());
    }

    /**
     * @covers \Jackalope\NamespaceRegistry::getPrefix
     */
    public function testGetPrefixFromDefaultNamespace()
    {
        $nsr = $this->getNamespaceRegistry([], false);
        $this->assertEquals('xml', $nsr->getPrefix('http://www.w3.org/XML/1998/namespace'));
    }

    /**
     * @covers \Jackalope\NamespaceRegistry::getPrefix
     */
    public function testGetPrefixFromUserNamespace()
    {
        $namespaces = ['beastie' => 'http://beastie.lo/beastie/1.0'];
        $nsr = $this->getNamespaceRegistry($namespaces);
        $this->assertEquals('beastie', $nsr->getPrefix('http://beastie.lo/beastie/1.0'));
    }

    /**
     * @covers \Jackalope\NamespaceRegistry::getPrefix
     */
    public function testGetPrefixExpectingNamespaceException()
    {
        $this->expectException(NamespaceException::class);

        $namespaces = ['beastie' => 'http://beastie.lo/beastie/1.0'];
        $nsr = $this->getNamespaceRegistry($namespaces);
        $nsr->getPrefix('InvalidURI');
    }

    /**
     * @covers \Jackalope\NamespaceRegistry::getURIs
     */
    public function testGetUris()
    {
        $namespaces = ['beastie' => 'http://beastie.lo/beastie/1.0'];

        $nsr = $this->getNamespaceRegistry($namespaces);
        $expected = [
            'http://www.jcp.org/jcr/1.0',
            'http://www.jcp.org/jcr/sv/1.0',
            'http://www.jcp.org/jcr/nt/1.0',
            'http://www.jcp.org/jcr/mix/1.0',
            'http://www.w3.org/XML/1998/namespace',
            '',
            'http://beastie.lo/beastie/1.0',
        ];

        $this->assertEquals($expected, $nsr->getURIs());
    }

    /**
     * @covers \Jackalope\NamespaceRegistry::getURI
     */
    public function testGetUriFromDefaultNamespace()
    {
        $nsr = $this->getNamespaceRegistry([]);
        $this->assertEquals('http://www.w3.org/XML/1998/namespace', $nsr->getURI('xml'));
    }

    /**
     * @covers \Jackalope\NamespaceRegistry::getURI
     */
    public function testGetUriFromUserNamespace()
    {
        $namespaces = ['beastie' => 'http://beastie.lo/beastie/1.0'];

        $nsr = $this->getNamespaceRegistry($namespaces);
        $this->assertEquals('http://beastie.lo/beastie/1.0', $nsr->getURI('beastie'));
    }

    /**
     * @covers \Jackalope\NamespaceRegistry::getURI
     */
    public function testGetUriExpectingNamespaceException()
    {
        $this->expectException(NamespaceException::class);

        $nsr = $this->getNamespaceRegistry([]);
        $nsr->getURI('beastie');
    }

    /**
     * @covers \Jackalope\NamespaceRegistry::checkPrefix
     */
    public function testCheckPrefix()
    {
        $prefix = 'beastie';
        $ns = $this->getNamespaceRegistry([], false);

        $this->assertNull($ns->checkPrefix($prefix));
    }

    /**
     * @dataProvider checkPrefixDataprovider
     * @covers \Jackalope\NamespaceRegistry::checkPrefix
     */
    public function testCheckPrefixExpexctingNamespaceException($prefix)
    {
        $this->expectException(NamespaceException::class);

        $ns = $this->getNamespaceRegistry([], false);
        $ns->checkPrefix($prefix);
    }

    /* Dataproivder
    /*************************************************************************/

    public static function constructorDataprovider()
    {
        return [
            'prefix not in default namespaces' => [
                ['beastie' => 'http://beastie.lo/beastie/1.0'],
                ['beastie' => 'http://beastie.lo/beastie/1.0'],
            ],
            'prefix in default namespaces' => [
                [],
                ['xml' => 'http://beastie.lo/xml/1.0'],
            ],
        ];
    }

    public static function checkPrefixDataprovider()
    {
        return [
            'XML as prefix' => ['xml'],
            'prefix in list of default namespaces' => ['jcr'],
            'empty prefix' => [''],
        ];
    }
}

class NamespaceRegistryProxy extends NamespaceRegistry
{
    public function checkPrefix($prefix)
    {
        return parent::checkPrefix($prefix);
    }
}
