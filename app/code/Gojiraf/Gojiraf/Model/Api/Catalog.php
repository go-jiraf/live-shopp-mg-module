<?php 

namespace Gojiraf\Gojiraf\Model\Api;
use Magento\Framework\Exception\InputException;


class Catalog{

    private $variantAttributes;
    private $imageHelper;
    private $limit;
    // rest/V1/gojiraf/productlist/offset/0
    public function getProductList($page){
        $this->limit = 25;
        $this->imageHelper = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('\Magento\Catalog\Helper\Image');

        //var_dump($page); die;
        if (!isset($page))
        {
			throw new InputException(__("Favor de usar el parametro [page]."));
        }

        $productCollectionFactory = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $productCollection = $productCollectionFactory->create();
        $productCollection->addAttributeToSelect('*');
        $productCollection->addAttributeToFilter('type_id', 'configurable');
        $productCollection->getSelect()
            ->limit($this->limit, $page * $this->limit);
        //echo $productCollection->getSelect()->__toString();
        if (empty($productCollection->getData())){
            return [];
			//throw new InputException(__("No se encontraron productos."));
        }

        $productList = array();
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
            array_push($productList, $productArray);
        }

        return $productList;

    }

    public function getProductImage($product)
    {
        $imageUrl = $this
            ->imageHelper
            ->init($product, 'product_page_image')->setImageFile($product->getImage()) // image,small_image,thumbnail
            ->getUrl();
        return $imageUrl;
    }

}