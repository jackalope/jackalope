<?php
namespace Jackalope\Query;

use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryManagerInterface;
use PHPCR\Query\InvalidQueryException;

use Jackalope\ObjectManager;
use Jackalope\NotImplementedException;
use Jackalope\FactoryInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class QueryManager implements QueryManagerInterface
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
     * Create the query manager - acquire through the session.
     *
     * @param FactoryInterface $factory       the object factory
     * @param ObjectManager    $objectManager
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
        if (!in_array($language, $this->getSupportedQueryLanguages())) {
            throw new InvalidQueryException("Unsupported query language: $language");
        }
        switch ($language) {
            case QueryInterface::JCR_SQL2:
                return $this->factory->get('Query\SqlQuery', array($statement, $this->objectManager));
            case QueryInterface::XPATH:
                return $this->factory->get('Query\XpathQuery', array($statement, $this->objectManager));
            case QueryInterface::SQL:
                return $this->factory->get('Query\Sql1Query', array($statement, $this->objectManager));
            case QueryInterface::JCR_JQOM:
                throw new InvalidQueryException('Please use getQOMFactory to get the query object model factory. You can not build a QOM query from a string.');
            default:
                throw new InvalidQueryException("Transport supports this query language but jackalope not: $language");
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
        // Workspace checks if transport implements QueryInterface
        return $this->objectManager->getTransport()->getSupportedQueryLanguages();
    }
}
