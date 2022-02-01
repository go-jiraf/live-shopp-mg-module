<?php

namespace Gojiraf\Gojiraf\Controller\Catalog;

use \Magento\Framework\App\Action\Context;

class ProductList extends \Magento\Framework\App\Action\Action

{
    private $variantAttributes;
    private $imageHelper;

    public function execute()
    {
        $this->imageHelper = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('\Magento\Catalog\Helper\Image');

        $params = $this->getRequest()
            ->getParams();
        if (!isset($params["limit"]) || !isset($params["offset"]))
        {
            $this->getResponse()
                ->setStatusCode(\Magento\Framework\App\Response\Http::STATUS_CODE_400);
            return $this->displayError("NO_PAGINATION", "Favor de usar los parametros limit y offset.");
        }

        $productCollectionFactory = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $productCollection = $productCollectionFactory->create();
        $productCollection->addAttributeToSelect('*');
        $productCollection->addAttributeToFilter('type_id', 'configurable');
        $productCollection->getSelect()
            ->limit($params["limit"], $params["offset"]);
        //echo $productCollection->getSelect()->__toString();
        if (empty($productCollection->getData()))
        {
            $this->getResponse()
                ->setStatusCode(\Magento\Framework\App\Response\Http::STATUS_CODE_400);
            return $this->displayError("NO_PRODUCTS", "No se encontraron productos.");
        }

        $productList = array(
            "products" => []
        );
        foreach ($productCollection as $productModel)
        {
            $productArray = array(
                "id" => $productModel->getId() ,
                "sku" => $productModel->getSku() ,
                "description" => $productModel->getName() ,
                "variants" => array() ,
                "variantOptions" => array() ,
                "price" => "",
                "imageUrl" => ""
            );

            $this->variantAttributes = $productModel->getTypeInstance()
                ->getUsedProductAttributes($productModel);

            $variantsArray = array();
            $optionsArray = array();
            $childProducts = $productModel->getTypeInstance()
                ->getUsedProducts($productModel);
            foreach ($childProducts as $child)
            {
                //Acomodamos datos de las posibles variantes
                $option = array();
                foreach ($this->variantAttributes as $attribute)
                {
                    $attributeValue = $child->getResource()
                        ->getAttribute($attribute->getAttributeCode())
                        ->getFrontend()
                        ->getValue($child);
                    array_push($option, $attributeValue);
                    if (!isset($variantsArray[$attribute->getAttributeCode() ]))
                    {
                        $variantsArray[$attribute->getAttributeCode() ] = array();
                        array_push($variantsArray[$attribute->getAttributeCode() ], $attributeValue);
                    }
                    else
                    {
                        if (!in_array($attributeValue, $variantsArray[$attribute->getAttributeCode() ]))
                        {
                            array_push($variantsArray[$attribute->getAttributeCode() ], $attributeValue);
                        }
                    }
                }

                $imageUrl = $this->getProductImage($child);

                //Acomodamos datos del producto simple
                array_push($optionsArray, array(
                    "option" => $option,
                    "sku" => $child->getSku() ,
                    "price" => number_format($child->getFinalPrice() , 2, ",", "") ,
                    "imageUrl" => $imageUrl,
                    "originalPrice" => number_format($child->getPriceInfo()->getPrice('regular_price')->getValue() , 2, ",", "") ,
                    "description" => $child->getName()
                ));

            }

            foreach ($variantsArray as $key => $variants)
            {
                array_push($productArray["variants"], array(
                    "name" => $key,
                    "options" => $variants
                ));
            }

            //aca las variantOptions
            array_push($productArray["variantOptions"], $optionsArray);

            $productArray["price"] = number_format($productModel->getFinalPrice() , 2, ",", "");
            $productArray["imageUrl"] = $this->getProductImage($productModel);
            array_push($productList["products"], $productArray);
        }

        $jsonFactory = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Framework\Controller\Result\JsonFactory');
        $data = $productList;
        $result = $jsonFactory->create();
        $response = $result->setData($data);
        return $response;

    }

    public function getProductImage($product)
    {
        $imageUrl = $this
            ->imageHelper
            ->init($product, 'product_page_image')->setImageFile($product->getImage()) // image,small_image,thumbnail
            ->getUrl();
        return $imageUrl;
    }

    public function displayError($errorCode, $message)
    {
        $jsonFactory = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Framework\Controller\Result\JsonFactory');
        $data = ['error_code' => $errorCode, 'message' => $message];
        $result = $jsonFactory->create();
        $response = $result->setData($data);
        return $response;
    }

}

