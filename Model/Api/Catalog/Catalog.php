<?php 

namespace Gojiraf\Gojiraf\Model\Api\Catalog;

use Gojiraf\Gojiraf\Model\Api\Catalog\Product\ProductFactory;
use Zend_Db_Expr;

class Catalog
{
    private $isDefaultStock;
    protected $productCollectionFactory;
    protected $productVisibility;
    protected $productStatus;
    protected $getStockIdForCurrentWebsite;
    protected $productMetadata;
    protected $getSources;
    protected $productFactory;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Inventory\Model\Source\Command\GetSourcesAssignedToStockOrderedByPriority $getSources,
        \Gojiraf\Gojiraf\Model\Api\Catalog\Product\ProductFactory $productFactory,
    )
    {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
        $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
        $this->productMetadata = $productMetadata;
        $this->getSources = $getSources;
        $this->productFactory = $productFactory;
    }

    /**
     * Returns an objects array with [simple or with variants] product details: sku, description, prices, stock, and images
     * @param string $page
     * @param string $limit
     * @param string $searchTerm
     * @param mixed[] $ids
     * @param bool $filterByStock
     * @return array
     * rest/V1/gojiraf/productlist/page/1?searchTerm=Camisa&limit=10&ids=23,31
     */
    public function getProductList($page = 1, $limit = 10, $searchTerm = NULL, $ids = "", $filterByStock = true)
    {
        
        $this->isDefaultStock = $this->getIsDefaultStock();

        $productCollection = $this->prepareCollection($filterByStock);
        $filteredCollection = $this->filterCollection($productCollection, $page, $limit, $searchTerm, $ids);

        if ($filteredCollection->count() === 0){
            return [];
        }

        $productList = array();
        foreach ($filteredCollection as $productModel) {
            $productType = $productModel->getTypeId();
            $product = $this->productFactory->create($productType, $this->isDefaultStock);
            $productData = $product->getProductData($productModel);
            array_push($productList, $productData);
        }
        return $productList;
    }

    protected function prepareCollection($filterByStock)
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToSelect('*');
        $productCollection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
        $productCollection->setVisibility($this->productVisibility->getVisibleInSiteIds());
        
        if($filterByStock){
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
                $stockId = $this->getStockIdForCurrentWebsite->execute();
                $productCollection
                ->getSelect()
                ->join(
                    array('stock' => new Zend_Db_Expr("(
                        SELECT 
                            sku,
                            quantity AS initial_qty
                        FROM
                            inventory_stock_".$stockId."
                    )"
                )),
                    'e.sku = stock.sku',
                    array('stock.initial_qty')
                )
                ->joinLeft(
                    array('reservation' => new Zend_Db_Expr("(
                        SELECT
                            sku,
                            SUM(quantity) as reservation_qty
                        FROM
                            inventory_reservation
                        WHERE
                            stock_id = ".$stockId."
                    )"
                    )),
                        'e.sku = reservation.sku',
                        array('reservation.reservation_qty')
                )
                ->where('(initial_qty + IFNULL(reservation_qty,0)) > ?', 0)
                ;
            }
        } else {
            $productCollection->addAttributeToFilter('is_saleable', 1)->load();
        }
        return $productCollection;
    }

    protected function filterCollection($productCollection, $page, $limit, $searchTerm, $ids)
    {
        $offset = ($page == 0) ? 0 : $page * ($limit);

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

    
    protected function getIsDefaultStock()
    {
        $magentoVersion = $this->productMetadata->getVersion();
        if(version_compare($magentoVersion, "2.3", '<')){
            return true;
        }
        
        $stockId = $this->getStockIdForCurrentWebsite->execute();
        $sources = $this->getSources->execute($stockId);

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
    
}
