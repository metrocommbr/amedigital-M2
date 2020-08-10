<?php

namespace AmeDigital\AME\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class OrderCreate implements ObserverInterface
{
    protected $_ame;
    protected $_order;

    public function __construct(
        \AmeDigital\AME\Helper\API $api,
        \Magento\Sales\Api\Data\OrderInterface $order
    )
    {
        $this->_ame = $api;
        $this->_order = $order;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if(!$order){
            $orderids = $observer->getEvent()->getOrderIds();
            foreach($orderids as $orderid){
                $order = $this->_order->load($orderid);
            }
        }
        $payment = $order->getPayment();
        $method = $payment->getMethod();
        if($method=="ame") {
            $order->setState('new')->setStatus('pending');
            $order->save();
            $this->_ame->createOrder($order);
        }
    }
}
