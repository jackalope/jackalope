<?php

namespace Jackalope\NodeType;

use Jackalope\TestCase;
use PHPCR\PropertyType;
use PHPCR\Version\OnParentVersionAction;

class PropertyDefinitionTemplateTest extends TestCase
{
    /**
     * @covers \Jackalope\NodeType\PropertyDefinitionTemplate::__construct
     */
    public function testCreatePropertyDefinitionTemplateEmpty(): void
    {
        $ntm = $this->getNodeTypeManager();

        $ndt = $ntm->createPropertyDefinitionTemplate();

        // is empty as defined by doc
        $this->assertNull($ndt->getName());
        $this->assertFalse($ndt->isAutoCreated());
        $this->assertFalse($ndt->isMandatory());
        $this->assertSame(OnParentVersionAction::COPY, $ndt->getOnParentVersion());
        $this->assertFalse($ndt->isProtected());
        $this->assertSame(PropertyType::STRING, $ndt->getRequiredType());
        $this->assertCount(0, $ndt->getValueConstraints());
        $this->assertCount(0, $ndt->getDefaultValues());
        $this->assertFalse($ndt->isMultiple());
        $this->assertFalse($ndt->isFullTextSearchable());
        $this->assertFalse($ndt->isQueryOrderable());
    }
}
