<?php

namespace Gojiraf\Gojiraf\Model\ResourceModel\Webhook;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class WebhookConfig extends AbstractDb
{
  protected function _construct()
  {
    $this->_init('gojiraf_webhook_configs', 'id');
  }
}