<?php

namespace Jackalope\NodeType;

use Jackalope\FactoryInterface;
use Jackalope\TestCase;
use PHPCR\NodeType\NodeTypeInterface;
use PHPCR\PropertyType;
use PHPCR\Version\OnParentVersionAction;

class PropertyDefinitionTest extends TestCase
{
    private array $defaultData = [
        'declaringNodeType' => 'nt:unstructured',
        'name' => 'foo',
        'isAutoCreated' => false,
        'isMandatory' => false,
        'isProtected' => false,
        'onParentVersion' => OnParentVersionAction::COPY,
        'requiredType' => PropertyType::BINARY,
        'multiple' => false,
        'fullTextSearchable' => false,
        'queryOrderable' => false,
        'valueConstraints' => [],
        'availableQueryOperators' => [],
        'defaultValues' => '',
    ];

    public function testCreateFromArray(): void
    {
        $factory = $this->createMock(FactoryInterface::class);
        $nodeTypeManager = $this->getNodeTypeManagerMock();
        $propType = new PropertyDefinition($factory, $this->defaultData, $nodeTypeManager);

        $this->assertEquals('foo', $propType->getName());
        $this->assertFalse($propType->isAutoCreated());
        $this->assertFalse($propType->isMandatory());
        $this->assertFalse($propType->isProtected());
        $this->assertEquals(OnParentVersionAction::COPY, $propType->getOnParentVersion());
        $this->assertEquals(2, $propType->getRequiredType());
        $this->assertFalse($propType->isMultiple());
        $this->assertFalse($propType->isQueryOrderable());
        $this->assertFalse($propType->isFullTextSearchable());
    }

    public function testGetDeclaringNodeType(): void
    {
        $nodeType = $this->createMock(NodeTypeInterface::class);

        $factory = $this->createMock(FactoryInterface::class);
        $nodeTypeManager = $this->getNodeTypeManagerMock();
        $nodeTypeManager
            ->expects($this->once())
            ->method('getNodeType')
            ->with($this->equalTo('nt:unstructured'))
            ->willReturn($nodeType)
        ;
        $propType = new PropertyDefinition($factory, $this->defaultData, $nodeTypeManager);

        $this->assertSame($nodeType, $propType->getDeclaringNodeType());
    }
}
