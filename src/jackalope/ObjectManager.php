<?php

class jackalope_ObjectManager {
    protected $transport;
    
    protected $objectsByPath;
    protected $objectsByUuid;
    
    public function __construct(jackalope_TransportInterface $transport) {
        $this->transport = $transport;
    }
    
    public function getNodeByPath($path) {
        $data = $this->transport->getItem($path);
        
        var_dump($data[1]->item(0)->ownerDocument->saveXML());
        // var_dump($data->);
        // $obj = jackalope_Factory::get('Node', $this->transport->getItem($path));
    }
}
