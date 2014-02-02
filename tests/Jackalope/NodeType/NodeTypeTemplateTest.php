<?php

namespace Jackalope\NodeType;

use Jackalope\TestCase;

class NodeTypeTemplateTest extends TestCase
{
    /**
     * @var NodeTypeManager
     */
    protected $ntm;

    protected function setUp()
    {
        $this->ntm = $this->getNodeTypeManager();
    }

    /**
     * @covers Jackalope\NodeType\NodeTypeTemplate::__construct
     */
    public function testCreateNodeTypeTemplateEmpty()
    {
        $ntt = $this->ntm->createNodeTypeTemplate();

        // is empty as defined by doc
        $this->assertNull($ntt->getName());
        $this->assertSame(array('nt:base'), $ntt->getDeclaredSupertypeNames());
        $this->assertFalse($ntt->isAbstract());
        $this->assertFalse($ntt->isMixin());
        $this->assertFalse($ntt->hasOrderableChildNodes());
        $this->assertTrue($ntt->isQueryable());
        $this->assertNull($ntt->getPrimaryItemName(), 'primaryItemName is not null');
        $this->assertNull($ntt->getDeclaredPropertyDefinitions(), 'declaredPropertyDefinitions is not null');
        $this->assertNull($ntt->getDeclaredChildNodeDefinitions(), 'declaredChildNodeDefinitions is not null');
    }

    /**
     * @covers Jackalope\NodeType\NodeTypeTemplate::__construct
     * @covers Jackalope\NodeType\NodeTypeDefinition::fromNodeTypeDefinition
     */
    public function testCreateNodeTypeTemplateFromDefinition()
    {
        $nt = $this->ntm->getNodeType('nt:file');
        $ntt = $this->ntm->createNodeTypeTemplate($nt);

        $this->assertInstanceOf('Jackalope\NodeType\NodeTypeDefinition', $ntt);
        $this->assertInstanceOf('Jackalope\NodeType\NodeTypeTemplate', $ntt);
        $this->assertSame($nt->getName(),$ntt->getName());
        $this->assertEquals($nt->isAbstract(), $ntt->isAbstract());
        $this->assertEquals($nt->isMixin(), $ntt->isMixin());
        $this->assertEquals($nt->isQueryable(), $ntt->isQueryable());
        $this->assertEquals($nt->hasOrderableChildNodes(), $ntt->hasOrderableChildNodes());
        $this->assertEquals($nt->getPrimaryItemName(), $ntt->getPrimaryItemName());
        $this->assertEquals($nt->getDeclaredSupertypeNames(), $ntt->getDeclaredSupertypeNames());
        $this->assertEquals($nt->getDeclaredChildNodeDefinitions(), $ntt->getDeclaredChildNodeDefinitions());
        $this->assertEquals($nt->getDeclaredPropertyDefinitions(), $ntt->getDeclaredPropertyDefinitions());
    }

    /**
     * @covers Jackalope\NodeTYpe\NodeTypeTemplate::getNodeDefinitionTemplates
     */
    public function testEmptyNodeTypeTemplatesMutable()
    {
        $nt = $this->ntm->createNodeTypeTemplate();
        $childnt = $this->ntm->createNodeTypeTemplate();
        $childnt->setName('test:nodetype');

        $children = $nt->getNodeDefinitionTemplates();
        $this->assertInstanceOf('ArrayObject', $children);

        $children[] = $childnt;

        $childrenAgain = $nt->getNodeDefinitionTemplates();
        $this->assertInstanceOf('ArrayObject', $childrenAgain);
        $this->assertCount(1, $childrenAgain);
        $childrenArray = $childrenAgain->getArrayCopy();
        $this->assertSame($childnt, reset($childrenArray));
        $this->assertEquals('test:nodetype', reset($childrenArray)->getName());
    }

    /**
     * @covers Jackalope\NodeTYpe\NodeTypeTemplate::getNodeDefinitionTemplates
     */
    public function testNodeTypeTemplatesMutable()
    {
        $ntd = $this->ntm->getNodeType('nt:file');
        $nt = $this->ntm->createNodeTypeTemplate($ntd);
        $childnt = $this->ntm->createNodeTypeTemplate();
        $childnt->setName('test:nodetype');

        $children = $nt->getNodeDefinitionTemplates();
        $this->assertCount(1, $children);

        $children[] = $childnt;

        $childrenAgain = $nt->getNodeDefinitionTemplates();
        $this->assertInstanceOf('ArrayObject', $childrenAgain);
        $this->assertCount(2, $childrenAgain);
        $this->assertContains($childnt, $childrenAgain);

        $childrenArray = $childrenAgain->getArrayCopy();
        $this->assertEquals('test:nodetype', end($childrenArray)->getName());
    }

    /**
     * @covers Jackalope\NodeTYpe\NodeTypeTemplate::getPropertyDefinitionTemplates
     */
    public function testPropertyDefinitionTemplatesMutable()
    {
        $nt = $this->ntm->getNodeType('nt:file');
        $newnt = $this->ntm->createNodeTypeTemplate($nt);
        $property = $this->ntm->createPropertyDefinitionTemplate();
        $property->setName('test:propdef');

        $properties = $newnt->getPropertyDefinitionTemplates();
        $this->assertInstanceOf('ArrayObject', $properties);

        $properties[] = $property;

        $propertiesAgain = $newnt->getPropertyDefinitionTemplates();
        $this->assertInstanceOf('ArrayObject', $propertiesAgain);
        $this->assertCount(1, $propertiesAgain);

        $propertiesArray = $propertiesAgain->getArrayCopy();
        $this->assertSame($property, reset($propertiesArray));
        $this->assertEquals('test:propdef', reset($propertiesArray)->getName());
    }

    /**
     * @covers Jackalope\NodeTYpe\NodeTypeTemplate::getPropertyDefinitionTemplates
     */
    public function testEmptyPropertyDefinitionTemplatesMutable()
    {

        $nt = $this->ntm->createNodeTypeTemplate();
        $property = $this->ntm->createPropertyDefinitionTemplate();
        $property->setName('test:propdef');

        $this->assertNull($nt->getDeclaredPropertyDefinitions());

        $properties = $nt->getPropertyDefinitionTemplates();
        $this->assertInstanceOf('ArrayObject', $properties);

        $properties[] = $property;

        $propertiesAgain = $nt->getPropertyDefinitionTemplates();
        $this->assertInstanceOf('ArrayObject', $propertiesAgain);
        $this->assertCount(1, $propertiesAgain);

        $propertiesArray = $propertiesAgain->getArrayCopy();
        $this->assertSame($property, reset($propertiesArray));
        $this->assertEquals('test:propdef', reset($propertiesArray)->getName());
    }
}
