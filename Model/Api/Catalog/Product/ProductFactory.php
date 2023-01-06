<?php

namespace Gojiraf\Gojiraf\Model\Api\Catalog\Product;

class ProductFactory
{
  private $imageHelper;
  private $getStockIdForCurrentWebsite;
  private $getProductSalableQty;
  private $stockRegistry;
  protected $productType;
  protected $configurableProductType;

  public function __construct(
    \Magento\Catalog\Helper\Image $imageHelper,
    \Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
    \Magento\InventorySales\Model\GetProductSalableQty $getProductSalableQty,
    \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
    \Magento\Catalog\Model\Product\Type $productType,
    \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableProductType
  )
  {
    $this->imageHelper = $imageHelper;
    $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
    $this->getProductSalableQty = $getProductSalableQty;
    $this->stockRegistry = $stockRegistry;
    $this->productType = $productType;
    $this->configurableProductType = $configurableProductType;
  }

  public function create (String $productType, Bool $isDefaultStock)
  {
    switch($productType){
      case $this->productType::TYPE_SIMPLE:
        return new SimpleProductBuilder(
          $isDefaultStock, 
          $this->imageHelper, 
          $this->getStockIdForCurrentWebsite,
          $this->getProductSalableQty,
          $this->stockRegistry
        );
        break;
      case $this->configurableProductType::TYPE_CODE:
        return new ConfigurableProductBuilder(
          $isDefaultStock, 
          $this->imageHelper, 
          $this->getStockIdForCurrentWebsite,
          $this->getProductSalableQty,
          $this->stockRegistry
        );
        break;
    }
  }
}