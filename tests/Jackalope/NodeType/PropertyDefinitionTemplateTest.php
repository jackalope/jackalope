<?php

namespace Jackalope\NodeType;

use Jackalope\TestCase;

class PropertyDefinitionTemplateTest extends TestCase
{
    /**
     * @covers \Jackalope\NodeType\PropertyDefinitionTemplate::__construct
     */
    public function testCreatePropertyDefinitionTemplateEmpty()
    {
        $ntm = $this->getNodeTypeManager();

        $ndt = $ntm->createPropertyDefinitionTemplate();

        // is empty as defined by doc
        $this->assertNull($ndt->getName());
        $this->assertFalse($ndt->isAutoCreated());
        $this->assertFalse($ndt->isMandatory());
        $this->assertSame(\PHPCR\Version\OnParentVersionAction::COPY, $ndt->getOnParentVersion());
        $this->assertFalse($ndt->isProtected());
        $this->assertSame(\PHPCR\PropertyType::STRING, $ndt->getRequiredType());
        $this->assertNull($ndt->getValueConstraints());
        $this->assertNull($ndt->getDefaultValues());
        $this->assertFalse($ndt->isMultiple());
        $this->assertFalse($ndt->isFullTextSearchable());
        $this->assertFalse($ndt->isQueryOrderable());
    }

}
