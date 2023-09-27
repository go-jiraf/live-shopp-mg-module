<?php

namespace Gojiraf\Gojiraf\Model;

use Magento\Framework\Model\AbstractModel;
use Gojiraf\Gojiraf\Model\ResourceModel\Webhook\WebhookConfig as ResourceModel;

class WebhookConfig extends AbstractModel
{
  protected function _construct()
  {
    $this->_init(ResourceModel::class);
  }
}