<?php

namespace Jackalope\NodeType;

use Jackalope\TestCase;
use PHPCR\Version\OnParentVersionAction;

class NodeDefinitionTemplateTest extends TestCase
{
    /**
     * @covers \Jackalope\NodeType\NodeDefinitionTemplate::__construct
     */
    public function testCreateNodeDefinitionTemplateEmpty(): void
    {
        $ntm = $this->getNodeTypeManager();

        $ndt = $ntm->createNodeDefinitionTemplate();

        // is empty as defined by doc
        $this->assertNull($ndt->getName());
        $this->assertFalse($ndt->isAutoCreated());
        $this->assertFalse($ndt->isMandatory());
        $this->assertSame(OnParentVersionAction::COPY, $ndt->getOnParentVersion());
        $this->assertFalse($ndt->isProtected());
        $this->assertNull($ndt->getRequiredPrimaryTypeNames());
        $this->assertNull($ndt->getDefaultPrimaryTypeName());
        $this->assertFalse($ndt->allowsSameNameSiblings());
    }
}
