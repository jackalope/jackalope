<?php
namespace Jackalope\Query;

use PHPCR\Query\QueryInterface;
use PHPCR\Query\InvalidQueryException;

use Jackalope\ObjectManager;
use Jackalope\NotImplementedException;
use Jackalope\FactoryInterface;

/**
 * {@inheritDoc}
 *
 * @api
 */
class QueryManager implements \PHPCR\Query\QueryManagerInterface
{
    /**
     * The factory to instantiate objects
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var \Jackalope\ObjectManager
     */
    protected $objectManager;

    /**
     * Create the query manager - akquire through the session.
     *
     * @param FactoryInterface $factory the object factory
     * @param ObjectManager $objectManager
     */
    public function __construct(FactoryInterface $factory, ObjectManager $objectManager)
    {
        $this->factory = $factory;
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createQuery($statement, $language)
    {
        switch ($language) {
            case QueryInterface::JCR_SQL2:
                return $this->factory->get('Query\SqlQuery', array($statement, $this->objectManager));
            case QueryInterface::JCR_XPATH:
                return $this->factory->get('Query\XpathQuery', array($statement, $this->objectManager));
            case QueryInterface::JCR_SQL:
                return $this->factory->get('Query\Sql1Query', array($statement, $this->objectManager));
            case QueryInterface::JCR_JQOM:
                throw new InvalidQueryException('Please use getQOMFactory to get the query object model factory. You can not build a QOM query from a string.');
            default:
                throw new InvalidQueryException("No such query language: $language");
        }
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getQOMFactory()
    {
        return $this->factory->get('Query\QOM\QueryObjectModelFactory', array($this->objectManager));
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getQuery($node)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSupportedQueryLanguages()
    {
        return array(QueryInterface::JCR_SQL2, QueryInterface::JCR_JQOM);
    }
}
