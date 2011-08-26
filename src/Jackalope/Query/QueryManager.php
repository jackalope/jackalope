<?php
namespace Jackalope\Query;

use Jackalope\ObjectManager, Jackalope\NotImplementedException;

// inherit all doc
/**
 * @api
 */
class QueryManager implements \PHPCR\Query\QueryManagerInterface
{
    /**
     * The factory to instantiate objects
     * @var \Jackalope\Factory
     */
    protected $factory;

    /**
     * @var \Jackalope\ObjectManager
     */
    protected $objectmanager;

    /**
     * Create the query manager - akquire through the session.
     *
     * @param object $factory an object factory implementing "get" as
     *      described in \Jackalope\Factory
     * @param ObjectManager $objectManager
     */
    public function __construct($factory, ObjectManager $objectmanager)
    {
        $this->factory = $factory;
        $this->objectmanager = $objectmanager;
    }

    // inherit all doc
    /**
     * @api
     */
    public function createQuery($statement, $language)
    {
        switch($language) {
            case \PHPCR\Query\QueryInterface::JCR_SQL2:
                return $this->factory->get('Query\SqlQuery', array($statement, $this->objectmanager));
            case \PHPCR\Query\QueryInterface::JCR_JQOM:
                throw new NotImplementedException();
            default:
                throw new \PHPCR\Query\InvalidQueryException("No such query language: $language");
        }
    }

    // inherit all doc
    /**
     * @api
     */
    public function getQOMFactory()
    {
        return new \Jackalope\Query\QOM\QueryObjectModelFactory();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getQuery($node)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * Jackalope supports Query.JCR_SQL2 and Query.JCR_JQOM
     *
     * @api
     */
    public function getSupportedQueryLanguages()
    {
        return array(\PHPCR\Query\QueryInterface::JCR_SQL2, \PHPCR\Query\QueryInterface::JCR_JQOM);
    }
}
