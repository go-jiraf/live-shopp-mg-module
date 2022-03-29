<?php 

namespace Gojiraf\Gojiraf\Model\Api;
//use Magento\Framework\Exception\InputException;


class Catalog{

    private $variantAttributes;
    private $imageHelper;
    // /rest/V1/gojiraf/productlist/page/1?searchTerm=Camisa&limit=10&ids=23,31
    public function getProductList($page = 1, $limit = 10, $searchTerm = NULL, $ids = ""){

        $this->imageHelper = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Catalog\Helper\Image');

        $productCollection = $this->prepareCollection();
        $filteredCollection = $this->filterCollection($productCollection, $page, $limit, $searchTerm, $ids);

        if (empty($filteredCollection->getData())){
            return [];
			//throw new InputException(__("No se encontraron productos."));
        }

        $productList = array();
        foreach ($filteredCollection as $productModel)
        {
            $productData = $this->buildProductData($productModel);
            array_push($productList, $productData);
        }

        return $productList;
    }


    public function prepareCollection(){
        $productCollectionFactory = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $productCollection = $productCollectionFactory->create();
        $productCollection->addAttributeToSelect('*');
        $productCollection->addAttributeToFilter('type_id', 'configurable');
        return $productCollection;
    }


    public function filterCollection($productCollection, $page, $limit, $searchTerm, $ids){
        $offset = ($page == 0 || $page == 1) ? 0 : $page * $limit - 1;

        // Si pide IDs de productos especificos, los filtramos.
        if (!empty($ids) && $ids != "undefined") {
            $productCollection->addAttributeToFilter('entity_id', array('in' => explode(",", $ids)));
        }
        if (!empty($searchTerm) && $searchTerm != "undefined") {
            $productCollection->addAttributeToFilter('name', array('like' => "%" .$searchTerm. "%"));
        }
        $productCollection->getSelect()
            ->limit($limit, $offset);

        return $productCollection;
    }


    public function buildProductData($productModel){
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

            $highestPrice = 0;
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
                $childPrice = (float)number_format($child->getFinalPrice() , 2, ",", "");
                $childOriginalPrice = (float)number_format($child->getPriceInfo()->getPrice('regular_price')->getValue() , 2, ",", "");
                if ($childOriginalPrice > $highestPrice) {
                    $highestPrice = $childOriginalPrice;
                }
                //Acomodamos datos del producto simple
                array_push($optionsArray, array(
                    "option" => $option,
                    "sku" => $child->getSku() ,
                    "price" => $childPrice ,
                    "imageUrl" => $imageUrl,
                    "originalPrice" => $childOriginalPrice,
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
            $productArray["variantOptions"] = $optionsArray;
            $configProductPrice = (float)number_format($productModel->getFinalPrice() , 2, ",", "");
            $productArray["price"] =  ($configProductPrice == 0 ) ? $highestPrice : $configProductPrice ;
            $productArray["imageUrl"] = $this->getProductImage($productModel) ;
            return $productArray;
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