<?php

namespace Jackalope;

use PHPCR\RepositoryFactoryInterface;

/**
 * This factory creates repositories with the Doctrine DBAL transport
 *
 * Use repository factory based on parameters (the parameters below are examples):
 *
 * <pre>
 *    $parameters = array('jackalope.doctrine_dbal_connection' => $dbConn);
 *    $repository = \Jackalope\RepositoryFactoryDoctrineDBAL::getRepository($parameters);
 * </pre>
 *
 * @api
 */
class RepositoryFactoryDoctrineDBAL implements RepositoryFactoryInterface
{
    /**
     * list of required parameters for doctrine dbal
     *
     * TODO: would be nice if alternatively one could also specify the parameters to let the factory build the connection
     *
     * @var array
     */
    static private $required = array(
        'jackalope.doctrine_dbal_connection' => 'Doctrine\\DBAL\\Connection (required): connection instance',
    );

    /**
     * list of optional parameters for doctrine dbal
     * @var array
     */
    static private $optional = array(
        'jackalope.factory' => 'string or object: Use a custom factory class for Jackalope objects',
        'jackalope.check_login_on_server' => 'boolean: if set to empty or false, skip initial check whether repository exists. Enabled by default, disable to gain a few milliseconds off each repository instantiation.',
        'jackalope.disable_transactions' => 'boolean: if set and not empty, transactions are disabled, otherwise transactions are enabled. If transactions are enabled but not actively used, every save operation is wrapped into a transaction.',
        'jackalope.disable_stream_wrapper' => 'boolean: if set and not empty, stream wrapper is disabled, otherwise the stream wrapper is enabled and streams are only fetched when reading from for the first time. If your code always uses all binary properties it reads, you can disable this for a small performance gain.',
    );

    /**
     * Get a repository connected to the backend with the provided doctrine
     * dbal connection.
     *
     * {@inheritDoc}
     *
     * DoctrineDBAL repositories have no default repository, passing null as
     * parameters will always return null.
     *
     * @api
     */
    static public function getRepository(array $parameters = null)
    {
        if (null === $parameters) {
            return null;
        }

        // check if we have all required keys
        $present = array_intersect_key(self::$required, $parameters);
        if (count(array_diff_key(self::$required, $present))) {
            return null;
        }
        $defined = array_intersect_key(array_merge(self::$required, self::$optional), $parameters);
        if (count(array_diff_key($defined, $parameters))) {
            return null;
        }

        if (isset($parameters['jackalope.factory'])) {
            $factory = is_object($parameters['jackalope.factory'])
                ? $parameters['jackalope.factory'] : new $parameters['jackalope.factory'];
        } else {
            $factory = new Factory();
        }

        $dbConn = $parameters['jackalope.doctrine_dbal_connection'];

        $transport = $factory->get('Transport\DoctrineDBAL\Client', array($dbConn));
        if (isset($parameters['jackalope.check_login_on_server'])) {
            $transport->setCheckLoginOnServer($parameters['jackalope.check_login_on_server']);
        }

        $options['transactions'] = empty($parameters['jackalope.disable_transactions']);
        $options['stream_wrapper'] = empty($parameters['jackalope.disable_stream_wrapper']);
        return new Repository($factory, $transport, $options);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    static public function getConfigurationKeys()
    {
        return array_merge(self::$required, self::$optional);
    }
}
