<?php
 
namespace Gojiraf\Gojiraf\Api;
 
interface CatalogInterface
{
    /**
     * GET for Post api
     * @param string $page
     * @param string $limit
     * @param string $searchTerm
     * @param mixed[] $ids
     * @return string
     */
 
    public function getProductList($page, $limit = 10, $searchTerm = NULL, $ids = "");
}