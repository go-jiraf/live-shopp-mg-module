<?php

namespace Gojiraf\Gojiraf\Model\Api\Catalog\Product;

use Gojiraf\Gojiraf\Model\Api\Catalog\Product\Product;
use Gojiraf\Gojiraf\Model\Api\Catalog\Product\ProductInterface;

class SimpleProductBuilder extends Product implements ProductInterface{

  public function getProductData($productModel)
  {
      $productArray = array(
          "id" => $productModel->getId() ,
          "sku" => $productModel->getSku() ,
          "description" => $productModel->getName() ,
          "price" => "",
          "originalPrice" => "",
          "imageUrl" => "",
          "stock" => $this->getStock($productModel)
      );

      $productArray["price"] = (float)number_format($productModel->getFinalPrice() , 2, ",", "");
      $productArray["originalPrice"] = (float)number_format($productModel->getPriceInfo()->getPrice('regular_price')->getValue() , 2, ",", "");
      $productArray["imageUrl"] = $this->getImage($productModel);

      return $productArray;
  }
}