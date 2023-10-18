<?php
namespace Gojiraf\Gojiraf\Controller\Cart;

use \Magento\Framework\App\Action\Context;
use \Magento\Checkout\Model\Session;
use \Magento\Checkout\Model\Cart;
use \Magento\Catalog\Model\ProductRepository;
use \Magento\Quote\Api\CartRepositoryInterface;
use \Magento\Framework\Message\ManagerInterface;
use \Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use \Magento\Framework\Controller\Result\JsonFactory;

class Redirect extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;
    protected $_cart;
    protected $_quote;
    protected $_productRepository;
    protected $_cartRepository;
    protected $_messageInterface;
    protected $_configurable;
    protected $_jsonFactory;
    protected $_maskedQuoteIdToQuoteId;

    public function __construct(Context $context,
        Session $checkoutSession,
        Cart $cart,
        ProductRepository $productRepository,
        CartRepositoryInterface $cartRepository,
        ManagerInterface $messageInterface,
        Configurable $configurable,
        JsonFactory $jsonFactory,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
    )
    {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_cart = $cart;
        $this->_cartRepository = $cartRepository;
        $this->_productRepository = $productRepository;
        $this->_messageInterface = $messageInterface;
        $this->_configurable = $configurable;
        $this->_jsonFactory = $jsonFactory;
        $this->_maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $maskedQuoteId = $params["CART_ID"];

        if (empty($maskedQuoteId)) {
            $this->getResponse()->setStatusCode(\Magento\Framework\App\Response\Http::STATUS_CODE_503);
            return $this->displayError("EMPTY_CART_ID","Ocurrió un error al asociar tu carrito de compras.");
        }

        $quoteId = $this->getQuoteIdFromMaskedQuoteId($maskedQuoteId);

        try {
            $guestCart = $this->_cartRepository->get($quoteId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->getResponse()->setStatusCode(\Magento\Framework\App\Response\Http::STATUS_CODE_503);
            return $this->displayError("GET_QUOTE_ID","Ocurrió un error al asociar tu carrito de compras.");
        }

        try {
            $this->_checkoutSession->setQuoteId($quoteId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->getResponse()->setStatusCode(\Magento\Framework\App\Response\Http::STATUS_CODE_503);
            return $this->displayError("SET_QUOTE_ID", "Ocurrió un error al asociar tu carrito de compras.");
        }

        $this->redirectSuccess();
    }

    private function getQuoteIdFromMaskedQuoteId ($maskedQuoteId) {
        return $this->_maskedQuoteIdToQuoteId->execute($maskedQuoteId);
    }
    
    public function redirectSuccess(){
        $this->getResponse()->setRedirect('/checkout/cart/');
    }

    public function displayError($errorCode, $message){
        $data = ['error_code' => $errorCode, 'message' => $message];
        $result = $this->_jsonFactory->create();
        return $result->setData($data);
    }
}