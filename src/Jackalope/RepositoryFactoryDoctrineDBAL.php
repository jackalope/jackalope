<?php

namespace Jackalope;

use PHPCR\RepositoryFactoryInterface;

/**
 * This factory creates repositories with the Doctrine DBAL transport
 *
 * Use repository factory based on parameters (the parameters below are examples):
 *
 *    $parameters = array('jackalope.doctrine_dbal_connection' => $dbConn);
 *    $repo = \Jackalope\RepositoryFactoryDoctrineDBAL::getRepository($parameters);
 *
 * @api
 */
class RepositoryFactoryDoctrineDBAL implements RepositoryFactoryInterface
{
    /**
     * TODO: would be nice if alternatively one could also specify the parameters to let the factory build the connection
     * @var array
     */
    static private $required = array(
        'jackalope.doctrine_dbal_connection' => 'Doctrine\DBAL\Connection (required): connection instance',
    );

    /**
     * @var array
     */
    static private $optional = array(
        'jackalope.factory' => 'string or object: Use a custom factory class for Jackalope objects',
        'jackalope.check_login_on_server' => 'boolean: If to check if an initial PROPFIND should be send to check if repository exist',
        'jackalope.disable_transactions' => 'boolean: if set and not empty, transactions are disabled, otherwise transactions are enabled',
        'jackalope.disable_stream_wrapper' => 'boolean: if set and not empty, stream wrapper is disabled, otherwise the stream wrapper is enabled',
    );

    /**
     * Attempts to establish a connection to a repository using the given
     * parameters.
     *
     *
     *
     * @param array|null $parameters string key/value pairs as repository arguments or null if a client wishes
     *                               to connect to a default repository.
     * @return \PHPCR\RepositoryInterface a repository instance or null if this implementation does
     *                                    not understand the passed parameters
     * @throws \PHPCR\RepositoryException if no suitable repository is found or another error occurs.
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
            $factory = is_object($parameters['jackalope.factory']) ?
                                 $parameters['jackalope.factory'] :
                                 new $parameters['jackalope.factory'];
        } else {
            $factory = new Factory();
        }

        $dbConn = $parameters['jackalope.doctrine_dbal_connection'];

        $transport = $factory->get('Jackalope\Transport\DoctrineDBAL\Client', array($dbConn));
        if (isset($parameters['jackalope.check_login_on_server'])) {
            $transport->setCheckLoginOnServer($parameters['jackalope.check_login_on_server']);
        }

        $options['transactions'] = empty($parameters['jackalope.disable_transactions']);
        $options['stream_wrapper'] = empty($parameters['jackalope.disable_stream_wrapper']);
        return new Repository($factory, $transport, $options);
    }

    /**
     * Get the list of configuration options that can be passed to getRepository
     *
     * The description string should include whether the key is mandatory or
     * optional.
     *
     * @return array hash map of configuration key => english description
     */
    static public function getConfigurationKeys()
    {
        return array_merge(self::$required, self::$optional);
    }
}
