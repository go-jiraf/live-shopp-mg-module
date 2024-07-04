<?php

namespace Gojiraf\Gojiraf\Model\Api\Catalog\Product;

use Gojiraf\Gojiraf\Model\Api\Catalog\Product\Product;
use Gojiraf\Gojiraf\Model\Api\Catalog\Product\ProductInterface;

class ConfigurableProductBuilder extends Product implements ProductInterface
{

  public function getProductData($productModel)
  {
    $this->variantAttributes = $productModel
      ->getTypeInstance()
      ->getUsedProductAttributes($productModel);

    $imageUrl = $this->getImage($productModel);

    [$variantOptions, $variants] = $this->getProductOptions($productModel, $imageUrl);
    [$originalPrice, $price] = $this->getBaseProductPrices($variantOptions);

    $productArray = array(
      "id" => $productModel->getId(),
      "sku" => $productModel->getSku(),
      "description" => $productModel->getName(),
      "price" => $price,
      "originalPrice" => $originalPrice,
      "imageUrl" => $imageUrl,
      "variants" => $variants,
      "variantOptions" => $variantOptions
    );

    return $productArray;
  }

  private function getProductOptions($productModel, $defaultImageUrl)
  {
    $childProducts = $productModel->getTypeInstance()->getUsedProducts($productModel);

    $optionsArray = array();
    $variantsArray = array();

    foreach ($childProducts as $child) {

      $option = array();
      foreach ($this->variantAttributes as $attribute) {
        $attributeValue = $child->getResource()
          ->getAttribute($attribute->getAttributeCode())
          ->getFrontend()
          ->getValue($child);
        $option[] = $attributeValue;
        if (!isset($variantsArray[$attribute->getFrontendLabel()])) {
          $variantsArray[$attribute->getFrontendLabel()] = array();
          array_push($variantsArray[$attribute->getFrontendLabel() ], $attributeValue);
        } else {
          if (!in_array($attributeValue, $variantsArray[$attribute->getFrontendLabel()])) {
            $variantsArray[$attribute->getFrontendLabel() ][] = $attributeValue;
          }
        }
      }
      $imageUrl = $this->getImage($child) ?? $defaultImageUrl;
      $childPrice = (float)number_format($child->getFinalPrice(), 2, ".", "");
      $childOriginalPrice = (float)number_format($child->getPriceInfo()->getPrice('regular_price')->getValue(), 2, ".", "");

      $optionsArray[] = array(
        "option" => $option,
        "sku" => $child->getSku(),
        "price" => $childPrice,
        "imageUrl" => $imageUrl,
        "originalPrice" => $childOriginalPrice,
        "description" => $child->getName(),
        "stock" => $this->getStock($child)
      );
    }

    $variants = array();

    foreach ($variantsArray as $key => $v) {
      $variants[] = array(
        "name" => $key,
        "options" => $v
      );
    }

    if (empty($optionsArray)) {
      return [[], []];
    }

    return [$optionsArray, $variants];
  }

  private function getBaseProductPrices($variantOptions)
  {
    if (count($variantOptions) < 1) {
      return [null, null];
    }
    $price = $variantOptions[0]["price"];
    $originalPrice = $variantOptions[0]["originalPrice"];
    foreach ($variantOptions as $option) {
      if ($option["price"] < $price) {
        $price = $option["price"];
        $originalPrice = $option["originalPrice"];
      }
    }
    return [$price, $originalPrice];
  }
}
