<?php

namespace Gojiraf\Gojiraf\Model\Api;

use Gojiraf\Gojiraf\Model\ResourceModel\Webhook\WebhookCollection;
use Gojiraf\Gojiraf\Model\ResourceModel\Webhook\WebhookConfig as WebhookResource;
use Gojiraf\Gojiraf\Model\WebhookConfig;

class Webhook
{
  protected $webhookCollection;
  protected $webhookResource;
  protected $webhook;

  public function __construct (
    WebhookCollection $webhookCollection,
    WebhookResource $webhookResource,
    WebhookConfig $webhook
  )
  {
    $this->webhookCollection = $webhookCollection;
    $this->webhookResource = $webhookResource;
    $this->webhook = $webhook;
  }

  public function get ()
  {
    try {
      $webhooks = [];
      $webhooksCollection = $this->webhookCollection->load();
      foreach ($webhooksCollection as $webhook) {
        array_push($webhooks, $webhook->getData());
      }
      return $webhooks;
    } catch (\Exception $error) {
      return $error->getMessage();
    }
  }

  public function create ($topic = "", $url = "")
  {
    try {
      if ($topic === "" || $url === "") {
        return 'Missing parameters';
      }
      $data = [
        'topic' => $topic,
        'url' => $url,
      ];
  
      $webhook = $this->webhook;
      $webhook->setData($data);
  
      $this->webhookResource->save($webhook);
      return 'ok';
    } catch (\Exception $error) {
      return $error->getMessage();
    }
  }

  public function delete ($id)
  {
    try {
      $webhook = $this->webhook->load($id);
      $webhook->delete();
      return 'ok';
    } catch (\Exception $error) {
      return $error->getMessage();
    }
  }
}