<?php

namespace Gojiraf\Gojiraf\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;

class Logger extends AbstractHelper
{
    protected $logArray = array();


    public function addLog($messageToLog){
        array_push($this->logArray, $messageToLog);
    }

    public function getLogs(){
        return $this->logArray;
    }
}