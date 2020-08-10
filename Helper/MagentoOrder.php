<?php

namespace AmeDigital\AME\Helper;

class MagentoOrder
{
    protected $_orderRepository;
    protected $_invoiceService;
    protected $_transaction;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction
    ) {
        $this->_orderRepository = $orderRepository;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
    }
    public function invoiceOrder($orderId)
    {
        $order = $this->_orderRepository->get($orderId);
        if($order->canInvoice()) {
            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->save();
            $transactionSave = $this->_transaction->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            );
            $transactionSave->save();
            $order
                ->addStatusHistoryComment('AME payment success - invoice #%1.', $invoice->getId())
                ->save();
            $order = $this->_orderRepository->get($orderId);
            $order->setState('processing')->setStatus('processing')->save();
        }
    }
}
