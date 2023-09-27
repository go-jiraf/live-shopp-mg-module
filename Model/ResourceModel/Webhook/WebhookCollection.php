<?php

namespace Gojiraf\Gojiraf\Model\ResourceModel\Webhook;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Gojiraf\Gojiraf\Model\WebhookConfig as Model;
use Gojiraf\Gojiraf\Model\ResourceModel\Webhook\WebhookConfig as ResourceModel;

class WebhookCollection extends AbstractCollection
{
  protected function _construct()
  {
    $this->_init(Model::class, ResourceModel::class);
  }
}