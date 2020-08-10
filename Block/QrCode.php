<?php

namespace AmeDigital\AME\Block;

class QrCode extends \Magento\Checkout\Block\Onepage\Success
{
    protected $checkoutSession;
    protected $customerSession;
    protected $_orderFactory;
    protected $_connection;
    protected $_amedigAPI;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \AmeDigital\AME\Helper\API $amedigAPI
    ) {
        parent::__construct($context, $checkoutSession,$orderConfig,$httpContext);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
        $this->_connection = $resource->getConnection();
        $this->_amedigAPI = $amedigAPI;
    }
    public function getCashbackValue(){
        $increment_id = $this->getOrderId();
        $sql = "SELECT cashback_amount FROM ame_order WHERE increment_id = ".$increment_id;
        $value = $this->_connection->fetchOne($sql);
        return $value * 0.01;
    }

    public function getPrice(){
        return $this->getOrder()->getGrandTotal();
    }
    public function getOrder()
    {
        return $this->_orderFactory->create()->loadByIncrementId($this->getOrderId());
    }

    public function getCustomerId()
    {
        return $this->customerSession->getCustomer()->getId();
    }
    public function getDeepLink(){
        $increment_id = $this->getOrderId();
        $sql = "SELECT deep_link FROM ame_order WHERE increment_id = ".$increment_id;
        $qr = $this->_connection->fetchOne($sql);
        return $qr;
    }
    public function getQrCodeLink(){
        $increment_id = $this->getOrderId();
        $sql = "SELECT qr_code_link FROM ame_order WHERE increment_id = ".$increment_id;
        $qr = $this->_connection->fetchOne($sql);
        return $qr;
    }
    public function getPaymentMethod(){
        return $this->getOrder()->getPayment()->getMethod();
    }
}
