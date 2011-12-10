<?php

namespace Jackalope;

use PHPCR\RepositoryFactoryInterface;

/**
 * This factory creates repositories with the jackrabbit transport
 *
 * Use repository factory based on parameters (the parameters below are examples):
 *
 * <pre>
 *    $parameters = array('jackalope.jackrabbit_uri' => 'http://localhost:8080/server/');
 *    $repo = \Jackalope\RepositoryFactoryJackrabbit::getRepository($parameters);
 * </pre>
 *
 * @api
 */
class RepositoryFactoryJackrabbit implements RepositoryFactoryInterface
{
    /**
     * list of required parameters for jackrabbit
     * @var array
     */
    static private $required = array(
        'jackalope.jackrabbit_uri' => 'string (required): Path to the jackrabbit server',
    );

    /**
     * list of optional parameters for jackrabbit
     * @var array
     */
    static private $optional = array(
        'jackalope.factory' => 'string or object: Use a custom factory class for Jackalope objects',
        'jackalope.default_header' => 'string: Set a default header to send on each request to the backend (i.e. for load balancers to identify sessions)',
        'jackalope.jackrabbit_expect' => 'boolean: Send the "Expect: 100-continue" header on larger PUT and POST requests. Disabled by default to avoid issues with proxies and load balancers.',
        'jackalope.check_login_on_server' => 'boolean: if set to empty or false, skip initial check whether repository exists. Enabled by default, disable to gain a few milliseconds off each repository instantiation.',
        'jackalope.disable_transactions' => 'boolean: if set and not empty, transactions are disabled, otherwise transactions are enabled. If transactions are enabled but not actively used, every save operation is wrapped into a transaction.',
        'jackalope.disable_stream_wrapper' => 'boolean: if set and not empty, stream wrapper is disabled, otherwise the stream wrapper is enabled and streams are only fetched when reading from for the first time. If your code always uses all binary properties it reads, you can disable this for a small performance gain.',
    );

    /**
     * Get a repository connected to the jackrabbit backend specified in the
     * parameters.
     *
     * {@inheritDoc}
     *
     * Jackrabbit repositories have no default repository, passing null as
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
            $factory = is_object($parameters['jackalope.factory']) ?
                                 $parameters['jackalope.factory'] :
                                 new $parameters['jackalope.factory'];
        } else {
            $factory = new Factory();
        }

        $uri = $parameters['jackalope.jackrabbit_uri'];
        if ('/' !== substr($uri, -1, 1)) {
            $uri .= '/';
        }

        $transport = $factory->get('Transport\Jackrabbit\Client', array($uri));
        if (isset($parameters['jackalope.default_header'])) {
            $transport->addDefaultHeader($parameters['jackalope.default_header']);
        }
        if (isset($parameters['jackalope.jackrabbit_expect'])) {
            $transport->sendExpect($parameters['jackalope.jackrabbit_expect']);
        }
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
