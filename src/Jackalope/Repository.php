<?php

namespace Jackalope;

use ReflectionClass;

use Jackalope\Transport\TransportInterface;
use Jackalope\Transport\TransactionInterface;
use PHPCR\CredentialsInterface;
use PHPCR\RepositoryException;
use PHPCR\RepositoryInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class Repository implements RepositoryInterface
{
    /**
     * The descriptor key for the version of the specification
     * that this repository implements. For JCR 2.0
     * the value of this descriptor is the string "2.0".
     * @api
     */
    const JACKALOPE_OPTION_STREAM_WRAPPER = "jackalope.option.stream_wrapper";

    /**
     * flag to call stream_wrapper_register only once
     */
    protected static $binaryStreamWrapperRegistered;

    /**
     * The factory to instantiate objects
     *
     * @var object
     */
    protected $factory;

    /**
     * The transport to use
     * @var TransportInterface
     */
    protected $transport;

    /**
     * List of supported options
     * @var array
     */
    protected $options = array(
        // this is OPTION_TRANSACTIONS_SUPPORTED
        'transactions' => true,
        // this is JACKALOPE_OPTION_STREAM_WRAPPER
        'stream_wrapper' => true,
    );

    /**
     * Cached array of repository descriptors. Each is either a string or an
     * array of strings.
     *
     * @var array
     */
    protected $descriptors;

    /**
     * Create repository with the option to overwrite the factory and bound to
     * a transport.
     *
     * Use RepositoryFactoryDoctrineDBAL or RepositoryFactoryJackrabbit to
     * instantiate this class.
     *
     * @param FactoryInterface $factory the object factory to use. If this is
     *      null, the \Jackalope\Factory is instantiated. Note that the
     *      repository is the only class accepting null as factory.
     * @param TransportInterface $transport transport implementation
     * @param array              $options   defines optional features to enable/disable (see
     *      $options property)
     */
    public function __construct(FactoryInterface $factory = null, TransportInterface $transport, array $options = null)
    {
        $this->factory = is_null($factory) ? new Factory : $factory;
        $this->transport = $transport;
        $this->options = array_merge($this->options, (array) $options);
        $this->options['transactions'] = $this->options['transactions'] && $transport instanceof TransactionInterface;
        // register a stream wrapper to lazily load binary property values
        if (null === self::$binaryStreamWrapperRegistered) {
            self::$binaryStreamWrapperRegistered = $this->options['stream_wrapper'];
            if (self::$binaryStreamWrapperRegistered) {
                stream_wrapper_register('jackalope', 'Jackalope\\BinaryStreamWrapper');
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function login(CredentialsInterface $credentials = null, $workspaceName = null)
    {
        if (! $workspaceName = $this->transport->login($credentials, $workspaceName)) {
            throw new RepositoryException('transport failed to login without telling why');
        }

        $session = $this->factory->get('Session', array($this, $workspaceName, $credentials, $this->transport));
        if ($this->options['transactions']) {
            $utx = $this->factory->get('Transaction\\UserTransaction', array($this->transport, $session, $session->getObjectManager()));
            $session->getWorkspace()->setTransactionManager($utx);
        }

        return $session;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescriptorKeys()
    {
        if (null === $this->descriptors) {
            $this->loadDescriptors();
        }

        return array_keys($this->descriptors);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isStandardDescriptor($key)
    {
        $ref = new ReflectionClass('PHPCR\\RepositoryInterface');
        $consts = $ref->getConstants();

        return in_array($key, $consts);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescriptor($key)
    {
        // handle some of the keys locally
        switch ($key) {
            case self::JACKALOPE_OPTION_STREAM_WRAPPER:
                return $this->options['stream_wrapper'];
            case self::OPTION_TRANSACTIONS_SUPPORTED:
                return $this->options['transactions'];
            // TODO: return false for everything we know is not implemented in jackalope
        }

        // handle the rest by the transport to allow non-feature complete transports
        // or use interface per capability?
        if (null === $this->descriptors) {
            $this->descriptors = $this->transport->getRepositoryDescriptors();
        }

        return (isset($this->descriptors[$key])) ?  $this->descriptors[$key] : null;
        //TODO: is this the proper behaviour? Or what should happen on inexisting key?
    }

    /**
     * Load the descriptors from the transport and cache them
     */
    protected function loadDescriptors()
    {
        $this->descriptors = $this->transport->getRepositoryDescriptors();
    }
}
