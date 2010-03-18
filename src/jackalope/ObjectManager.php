<?php

class jackalope_ObjectManager {
    protected $session;
    protected $transport;

    protected $objectsByPath;
    protected $objectsByUuid;

    public function __construct(jackalope_TransportInterface $transport,
                                PHPCR_SessionInterface $session) {
        $this->transport = $transport;
        $this->session = $session;
    }

    /**
     * Get the node identified by an absolute path.
     * Uses the factory to instantiate Node
     *
     * @param string $path The absolute path of the node to create
     * @return jackalope_Node
     */
    public function getNodeByPath($path) {
        if (empty($this->objectsByPath[$path])) {
            $this->objectsByPath[$path] = jackalope_Factory::get(
                'Node',
                array(
                    $this->transport->getItem($path),
                    $path,
                    $this->session,
                    $this
                )
            );
        }
        return $this->objectsByPath[$path];
    }
}
