<?php

namespace Jackalope\Observation;

use Jackalope\TestCase,
    Jackalope\Observation\Event,
    Jackalope\Observation\Filter\NodeTypeEventFilter;


/**
 * Unit tests for the EventJournal
 */
class NodeTypeEventFilterTest extends TestCase
{
    public function testFilter()
    {
        // TODO: check this test. I am not completely sure this actually makes sense.
        // Basically it tests that the mock object returns what we asked it to return.

        $filter = $this->getFilter($this->getMyNodeMock(true), array('nt:unstructured'));
        $this->assertFilterMatch($filter, true);

        $filter = $this->getFilter($this->getMyNodeMock(false), array('nt:unstructured'));
        $this->assertFilterMatch($filter, false);
    }

    protected function assertFilterMatch(NodeTypeEventFilter $filter, $expectedResult)
    {
        $this->assertEquals($expectedResult, $filter->match(new Event()));
    }

    protected function getFilter($node, $nodeTypes)
    {
        return new NodeTypeEventFilter($this->getMySessionMock($node), $nodeTypes);
    }

    /**
     * Returns a mock object for the Session.
     * @param \PHPCR\NodeInterface $node The node returned by getNode
     * @return \PHPCR\SessionInterface
     */
    public function getMySessionMock($node)
    {
        $session = $this->getSessionMock(array('getNode'));
        $session
            ->expects($this->any())
            ->method('getNode')
            ->will($this->returnValue($node));
        return $session;
    }

    /**
     * Get a Jackalope\Node mock object that will return $isNodeTypeResult when
     * isNodeType is called on it.
     * @param string $isNodeTypeResult
     * @return object
     */
    protected function getMyNodeMock($isNodeTypeResult)
    {
        $node = $this->getNodeMock(array('isNodeType'));
        $node
            ->expects($this->any())
            ->method('isNodeType')
            ->will($this->returnValue($isNodeTypeResult));
        return $node;
    }

}
