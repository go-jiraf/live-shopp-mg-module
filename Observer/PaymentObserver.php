<?php
namespace Gojiraf\Gojiraf\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\HTTP\Client\Curl;
use Gojiraf\Gojiraf\Model\ResourceModel\Webhook\WebhookCollection;
use Psr\Log\LoggerInterface;

class PaymentObserver implements ObserverInterface
{
    protected $httpClient;
    protected $webhookCollection;
    protected $webhookTopic;
    protected $logger;

    public function __construct(
        Curl $httpClient,
        WebhookCollection $webhookCollection,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->webhookCollection = $webhookCollection;
        $this->logger = $logger;
        $this->webhookTopic = 'order_payment/pay';
    }

    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getPayment()->getOrder()->getData();
            if ($order) {
                $webhookCollection = $this->webhookCollection;
                $webhookCollection->addFieldToFilter('topic', ['eq' => $this->webhookTopic]);
                $webhooksResult = $webhookCollection->load();
                
                foreach ($webhooksResult as $webhook) {
                    $this->logger->debug("Sending webhook to: ".$webhook->getData('url'));
                    $url = $webhook->getData('url');
                    $this->httpClient->post($url, $order);
                }
            }
        } catch (\Exception $e) {
            $this->logger->debug('Error on Gojiraf PaymentObserver: '.$e->getMessage());
        }
    }
}
