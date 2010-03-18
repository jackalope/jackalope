<?php

class jackalope_ObjectManager {
    protected $session;
    protected $transport;
    
    protected $objectsByPath;
    protected $objectsByUuid;
    
    public function __construct(jackalope_TransportInterface $transport, PHPCR_SessionInterface $session) {
        $this->transport = $transport;
        $this->session = $session;
    }
    
    public function getNodeByPath($path) {
        if (empty($this->objectsByPath[$path])) {
            $data = $this->transport->getItem($path);
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
