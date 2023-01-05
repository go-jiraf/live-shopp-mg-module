<?php

namespace Gojiraf\Gojiraf\Model\Api\Catalog\Product;

abstract class ProductBuilder
{
  protected $isDefaultStock;
  protected $imageHelper;
  private $getStockIdForCurrentWebsite;
  protected $variantAttributes;
  protected $stockRegistry;
  private $getProductSalableQty;
  
  public function __construct (
    $isDefaultStock, 
    \Magento\Catalog\Helper\Image $imageHelper,
    \Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
    \Magento\InventorySales\Model\GetProductSalableQty $getProductSalableQty,
    \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
  )
  {
    $this->isDefaultStock = $isDefaultStock;
    $this->imageHelper = $imageHelper;
    $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
    $this->getProductSalableQty = $getProductSalableQty;
    $this->stockRegistry = $stockRegistry;
  }

  protected function getProductImage($product)
  {
    $imageUrl = $this
      ->imageHelper
      ->init($product, 'product_page_image')->setImageFile($product->getImage()) // image,small_image,thumbnail
      ->getUrl();
    return $imageUrl;
  }

  protected function getStock($productModel)
  {
    if($this->isDefaultStock){
        return $this->stockRegistry->getStockItem($productModel->getId())->getQty();
    } else {
        $stockId = $this->getStockIdForCurrentWebsite->execute();
        $salableQuantity = $this->getProductSalableQty->execute($productModel->getSku(), $stockId);
        return $salableQuantity;
    }
  }

}