<?php 

namespace Gojiraf\Gojiraf\Model\Api;
use Zend_Db_Expr;

class Catalog
{
    private $variantAttributes;
    private $imageHelper;
    private $isDefaultStock;

    protected $productCollectionFactory;
    protected $productVisibility;
    protected $productStatus;

    protected $objectManager;

    public $catalogVersion = "V.1.5.1";
    public function getCatalogVersion(){
        return $this->catalogVersion;
    }
    // /rest/V1/gojiraf/productlist/page/1?searchTerm=Camisa&limit=10&ids=23,31
    public function getProductList($page = 1, $limit = 10, $searchTerm = NULL, $ids = "")
    {

        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->imageHelper = $this->objectManager->get('\Magento\Catalog\Helper\Image');
        $this->stockRegistry = $this->objectManager->get('\Magento\CatalogInventory\Api\StockRegistryInterface');
        $this->productCollectionFactory = $this->objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $this->productStatus = $this->objectManager->get('\Magento\Catalog\Model\Product\Attribute\Source\Status');
        $this->productVisibility = $this->objectManager->get('\Magento\Catalog\Model\Product\Visibility');
        
        $this->isDefaultStock = $this->isDefaultStock();

        $productCollection = $this->prepareCollection();
        $filteredCollection = $this->filterCollection($productCollection, $page, $limit, $searchTerm, $ids);

        if (empty($filteredCollection->getData())){
            return [];
        }

        $productList = array();
        foreach ($filteredCollection as $productModel) {
            if ($productModel->getTypeId() == 'configurable') {
                $productData = $this->buildConfigProductData($productModel);
            } else { 
                $productData = $this->buildSimpleProductData($productModel);
            }
            if (!empty($productData)) {
                array_push($productList, $productData);
            }
        }
        return $productList;
    }

    private function prepareCollection()
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToSelect('*');
        $productCollection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
        $productCollection->setVisibility($this->productVisibility->getVisibleInSiteIds());

        if($this->isDefaultStock){
            $productCollection->setFlag('has_stock_status_filter', false);
            $productCollection->joinField(
                'stock_item', 
                'cataloginventory_stock_item', 
                'is_in_stock', 
                'product_id=entity_id', 
                'is_in_stock=1'
            );
        } else {
            $getStockIdForCurrentWebsite = $this->objectManager->get('Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite');
            $stockId = $getStockIdForCurrentWebsite->execute();
            $productCollection
            ->getSelect()
            ->join(
                array('stock_item' => new Zend_Db_Expr("(SELECT sku, is_salable, (initial_qty + reservation_qty - min_qty) AS qty FROM ( SELECT base.sku AS sku, base.is_salable, base.quantity AS initial_qty, IFNULL(reservation.quantity,0) AS reservation_qty, entity.entity_id, catalog.min_qty as min_qty FROM inventory_stock_".$stockId." as base LEFT JOIN inventory_reservation as reservation ON base.sku = reservation.sku LEFT JOIN catalog_product_entity as entity ON entity.sku = base.sku LEFT JOIN cataloginventory_stock_item AS catalog ON catalog.item_id = entity.entity_id) AS salable_quantity HAVING is_salable = TRUE AND qty > 0)")),
                'e.sku = stock_item.sku',
                array('')
            );
        }
        return $productCollection;
    }

    private function filterCollection($productCollection, $page, $limit, $searchTerm, $ids)
    {
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

    private function buildSimpleProductData($productModel)
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
        $productArray["imageUrl"] = $this->getProductImage($productModel);
        return $productArray;
    }
    
    private function buildConfigProductData($productModel)
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
        
        $highestPrice = 0;
        $variantsArray = array();
        $optionsArray = array();
        $childProducts = $productModel->getTypeInstance()
            ->getUsedProducts($productModel);
        foreach ($childProducts as $child)
        {
            //Si la variante no tiene stock, la ignoramos.
            $stockStatus = $this->stockRegistry->getStockItem($child->getId());
            
            if($this->isDefaultStock){
                if($stockStatus->getData('is_in_stock') == 0 || $stockStatus->getQty() == 0){
                    continue;
                }
            } else {
                if($this->getStock($child) == 0){
                    continue;
                }
            }

            //Acomodamos datos de las posibles variantes
            $option = array();
            foreach ($this->variantAttributes as $attribute)
            {
                $attributeValue = $child->getResource()
                ->getAttribute($attribute->getAttributeCode())
                ->getFrontend()
                ->getValue($child);
                array_push($option, $attributeValue);
                if (!isset($variantsArray[$attribute->getFrontendLabel() ]))
                {
                    $variantsArray[$attribute->getFrontendLabel() ] = array();
                    array_push($variantsArray[$attribute->getFrontendLabel() ], $attributeValue);
                }
                else
                {
                    if (!in_array($attributeValue, $variantsArray[$attribute->getFrontendLabel() ]))
                    {
                        array_push($variantsArray[$attribute->getFrontendLabel() ], $attributeValue);
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
                "description" => $child->getName(),
                "stock" => $this->getStock($child)
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
        if (empty($optionsArray)) {
            return array();
        }
        $productArray["variantOptions"] = $optionsArray;
        $configProductPrice = (float)number_format($child->getPriceInfo()->getPrice('regular_price')->getValue() , 2, ",", "");
        $productArray["price"] =  ($configProductPrice == 0 ) ? $highestPrice : $configProductPrice ;
        $productArray["imageUrl"] = $this->getProductImage($productModel);

        return $productArray;
    }
    
    private function getProductImage($product)
    {
        $imageUrl = $this
        ->imageHelper
        ->init($product, 'product_page_image')->setImageFile($product->getImage()) // image,small_image,thumbnail
        ->getUrl();
        return $imageUrl;
    }
    
    private function isDefaultStock()
    {
        $productMetadata = $this->objectManager->get('\Magento\Framework\App\ProductMetadataInterface');
        $magentoVersion = $productMetadata->getVersion();
        if(version_compare($magentoVersion, "2.3", '<')){
            return true;
        }
        
        $getStockIdForCurrentWebsite = $this->objectManager->get('Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite');
        $stockId = $getStockIdForCurrentWebsite->execute();
        $getSources = $this->objectManager->get('Magento\Inventory\Model\Source\Command\GetSourcesAssignedToStockOrderedByPriority');
        $sources = $getSources->execute($stockId);

        // If there are more than 1 source on active stock, is multisource
        if(count($sources) > 1){
            return false;
        }
        // If the active stock is not default, then is multisource
        if($sources[0]->getSourceCode() != 'default'){
            return false;
        }

        return true;
    }
    
    private function getStock($productModel)
    {
        if($this->isDefaultStock){
            return $this->stockRegistry->getStockItem($productModel->getId())->getQty();
        } else {
            $getStockIdForCurrentWebsite = $this->objectManager->get('Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite');
            $stockId = $getStockIdForCurrentWebsite->execute();
            $getProductSalableQty = $this->objectManager->get('Magento\InventorySales\Model\GetProductSalableQty');
            $qty = $getProductSalableQty->execute($productModel->getSku(), $stockId);
            return $qty;
        }
    }
}