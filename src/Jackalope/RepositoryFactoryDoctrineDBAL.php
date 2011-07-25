<?php

namespace Jackalope;

use PHPCR\RepositoryFactoryInterface;

/**
 * This factory creates repositories with the Doctrine DBAL transport
 *
 * Use repository factory based on parameters (the parameters below are examples):
 *
 *    $factory = new \Jackalope\RepositoryFactoryDoctrineDBAL;
 *
 *    $parameters = array('jackalope.doctrine_dbal_connection' => $dbConn);
 *    $repo = $factory->getRepository($parameters);
 *
 * @api
 */
class RepositoryFactoryDoctrineDBAL implements RepositoryFactoryInterface
{
    // TODO: would be nice if alternatively one could also specify the parameters to let the factory build the connection
    private $required = array(
        'jackalope.doctrine_dbal_connection' => 'Doctrine\DBAL\Connection (required): connection instance',
    );
    private $optional = array(
        'jackalope.factory' => 'string or object: Use a custom factory class for Jackalope objects',
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
    function getRepository(array $parameters = null) {
        if (null === $parameters) {
            return null;
        }
        // TODO: check if all required parameters specified

        if (isset($parameters['jackalope.factory'])) {
            $factory = is_object($parameters['jackalope.factory']) ?
                                 $parameters['jackalope.factory'] :
                                 new $parameters['jackalope.factory'];
        } else {
            $factory = new Factory();
        }

        $dbConn = $parameters['jackalope.doctrine_dbal_connection'];

        $transport = $factory->get('Jackalope\Transport\DoctrineDBAL\Client', array($dbConn));

        $transactions = !empty($parameters['jackalope.transactions']);
        return new Repository($factory, $transport, $transactions);
    }

    /**
     * Get the list of configuration options that can be passed to getRepository
     *
     * The description string should include whether the key is mandatory or
     * optional.
     *
     * @return array hash map of configuration key => english description
     */
    function getConfigurationKeys() {
        return array_merge($this->required, $this->optional);
    }
}
