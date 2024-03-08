<?php 

namespace Gojiraf\Gojiraf\Model\Api;


class Version{
    private $moduleVersion = '1.3.0';

    // /rest/V1/gojiraf/version
    public function getVersion(){
        return $this->moduleVersion;
    }

}