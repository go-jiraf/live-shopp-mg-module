<?php
 
namespace Gojiraf\Gojiraf\Controller\Index;

use \Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action
{

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $cartId = $params["CART_ID"];
        if (empty($cartId)) {
            $this->getResponse()->setStatusCode(\Magento\Framework\App\Response\Http::STATUS_CODE_503);
            return $this->displayError("EMPTY_CART_ID","Ocurrió un error al asociar tu carrito de compras.");
        }

        $customerSession = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Customer\Model\Session');
        $guestCartModel = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Quote\Model\GuestCart\GuestCartRepository');
        
        try {
            $guestCart = $guestCartModel->get($cartId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->getResponse()->setStatusCode(\Magento\Framework\App\Response\Http::STATUS_CODE_503);
            return $this->displayError("GET_CART_ID","Ocurrió un error al asociar tu carrito de compras.");
        }

        foreach ($guestCart->getItems() as $item) {
            $this->addToFinalCart($item);
        }

        $this->redirectSuccess();
    }
    
    public function addToFinalCart($item)
    {
        $formKey = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Data\Form\FormKey');
        $productModel = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Catalog\Model\Product');
        $cartModel = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Checkout\Model\Cart');

        $params = array(
                    'form_key' => $formKey,
                    'product' => $item->getProductId(), 
                    'qty'   => $item->getQty()              
                );              
        $product = $productModel->load($item->getProductId());
        if ($product->isSaleable()) {
            $cartModel->addProduct($product, $params);
            $cartModel->save();
        }else{
            $message = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Framework\Message\ManagerInterface');
            $message->addNotice("El producto {$item->getName()} no tiene stock.");
        }

    }


    public function redirectSuccess(){
        $message = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Framework\Message\ManagerInterface');
        $message->addSuccess("Muchas gracias por su compra mediante el LiveCommerce de GoJiraf!");
        $this->getResponse()->setRedirect('/checkout/cart/');
    }

    public function displayError($errorCode, $message){
        $jsonFactory = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Framework\Controller\Result\JsonFactory');
        $data = ['error_code' => $errorCode, 'message' => $message];
        $result = $jsonFactory->create();
        $response = $result->setData($data);
        return $response;
    }


}