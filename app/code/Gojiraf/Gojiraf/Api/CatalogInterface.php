<?php
 
namespace Gojiraf\Gojiraf\Api;
 
interface CatalogInterface
{
    /**
     * GET for Post api
     * @param string $value
     * @return string
     */
 
    public function getProductList($offset);
}