<?php
namespace Gojiraf\Gojiraf\Controller\Cart;

use \Magento\Framework\App\Action\Context;
use \Magento\Checkout\Model\Session;
use \Magento\Checkout\Model\Cart;
use \Magento\Catalog\Model\ProductRepository;
use \Magento\Quote\Api\CartRepositoryInterface;
use \Magento\Quote\Model\GuestCart\GuestCartRepository;
use \Magento\Framework\Message\ManagerInterface;
use \Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use \Magento\Framework\Controller\Result\JsonFactory;

class Redirect extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;
    protected $_cart;
    protected $_quote;
    protected $_productRepository;
    protected $_cartRepository;
    protected $_guestCartRepository;
    protected $_messageInterface;
    protected $_configurable;
    protected $_jsonFactory;

    public function __construct(Context $context,
        Session $checkoutSession,
        Cart $cart,
        ProductRepository $productRepository,
        CartRepositoryInterface $cartRepository,
        GuestCartRepository $guestCartRepository,
        ManagerInterface $messageInterface,
        Configurable $configurable,
        JsonFactory $jsonFactory,
    )
    {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_cart = $cart;
        $this->_cartRepository = $cartRepository;
        $this->_productRepository = $productRepository;
        $this->_guestCartRepository = $guestCartRepository;
        $this->_messageInterface = $messageInterface;
        $this->_configurable = $configurable;
        $this->_jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $cartId = $params["CART_ID"];

        $this->_quote = $this->_checkoutSession->getQuote();
        if (empty($cartId)) {
            $this->getResponse()->setStatusCode(\Magento\Framework\App\Response\Http::STATUS_CODE_503);
            return $this->displayError("EMPTY_CART_ID","Ocurrió un error al asociar tu carrito de compras.");
        }

        try {
            $guestCart = $this->_guestCartRepository->get($cartId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->getResponse()->setStatusCode(\Magento\Framework\App\Response\Http::STATUS_CODE_503);
            return $this->displayError("GET_CART_ID","Ocurrió un error al asociar tu carrito de compras.");
        }

        foreach ($guestCart->getItems() as $item) {
            try {
                $this->addProductToCart($item);
            } catch (\Exception $e) {
                $this->displayError("ADD_PRODUCT", "Ocurrió un error al intentar agregar uno de los productos al carrito.");
            }
        }
        $this->_cart->save();
        $this->_quote->save();
        $this->_quote->collectTotals();
        $this->_cartRepository->save($this->_quote);
        $this->_cart->getQuote()->setTotalsCollectedFlag(false)->collectTotals()->save();
        $this->redirectSuccess();
    }

    private function addProductToCart ($item) {
        $productId = $item->getProductId();
        $product = $this->_productRepository->getById($productId);

        $params = array(
                    'product' => $item->getProductId(),
                    'qty' => $item->getQty(),
                );
        
        $parentProductIds = $this->_configurable->getParentIdsByChild($productId);

        if (isset($parentProductIds[0])) {
            $parentProductId = $parentProductIds[0];
            $params['product'] = $parentProductId;

            $options = [];
            $parentProduct = $this->_productRepository->getById($parentProductId);
            $productAttributeOptions = $parentProduct->getTypeInstance(true)->getConfigurableAttributesAsArray($parentProduct);
            foreach ($productAttributeOptions as $option) {
                $options[$option['attribute_id']] = $product->getData($option['attribute_code']);
            }
            $params['super_attribute'] = $options;
        }

        if ($product->isSaleable()) {
            $this->_cart->addProduct($product, $params);
        } else {
            $this->_messageInterface->addNotice("El producto {$item->getName()} no tiene stock.");
        }
    }
    
    public function redirectSuccess(){
        $this->getResponse()->setRedirect('/checkout/cart/');
    }

    public function displayError($errorCode, $message){
        $data = ['error_code' => $errorCode, 'message' => $message];
        $result = $this->_jsonFactory->create();
        $response = $result->setData($data);
        return $response;
    }
}
