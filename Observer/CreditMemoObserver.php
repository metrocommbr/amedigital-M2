<?php

namespace AmeDigital\AME\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class CreditMemoObserver implements ObserverInterface
{
    protected $_amedigAPI;
    protected $_order;
    protected $_dbAME;

    public function __construct(
        \AmeDigital\AME\Helper\API $api,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \AmeDigital\AME\Helper\AmeDB $dbAME
    )
    {
        $this->_amedigAPI = $api;
        $this->_order = $order;
        $this->_dbAME = $dbAME;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $refund = $observer->getEvent()->getCreditmemo();
        $order = $refund->getOrder();
        $payment = $order->getPayment();
        $method = $payment->getMethod();
        if($method=="ame") {
            $valor = $refund->getGrandTotal() * 100;
            $refund = $this->_amedigAPI->refundOrder($this->_dbAME->getAmeIdByIncrementId($order->getIncrementId()), $valor);
            if ($refund) {
                $refund[0] = json_decode($refund[0], true);
                $this->_dbAME->insertRefund($this->_dbAME->getAmeIdByIncrementId($order->getIncrementId()), $refund[1], $refund[0]['operationId'], $valor, $refund[0]['status']);
            } else {
                throw new LocalizedException(__('Houve um erro efetuando o reembolso.'));
            }
        }
        return $this;

    }
}
