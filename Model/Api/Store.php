<?php

namespace Gojiraf\Gojiraf\Model\Api;

class Store
{
  protected $objectManager;
  protected $scopeConfig;
  protected $storeManager;
  protected $websiteCollectionFactory;

  public function __construct(
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \Magento\Store\Model\ResourceModel\Website\CollectionFactory $websiteCollectionFactory,
  )
  {
    $this->scopeConfig = $scopeConfig;
    $this->storeManager = $storeManager;
    $this->websiteCollectionFactory = $websiteCollectionFactory;
  }
  
  public function getStoreData()
  {

    $storeEmail = $this->scopeConfig->getValue(
      'trans_email/ident_sales/email',
      \Magento\Store\Model\ScopeInterface::SCOPE_STORE
    );

    $storeName = $this->storeManager->getStore()->getName();

    $storesCollection = $this->storeManager->getStores();
    
    $stores = [];

    foreach($storesCollection as $store){
      array_push($stores, [
        'name' => $store->getBaseUrl()
      ]);
    };

    return json_encode([
      'email' => $storeEmail,
      'name' => $storeName,
      'websites' => $stores
    ]);
  }
}