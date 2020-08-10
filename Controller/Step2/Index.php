<?php

namespace AmeDigital\AME\Controller\Step2;

use \Magento\Framework\App\CsrfAwareActionInterface;
use \Magento\Framework\App\RequestInterface;
use \Magento\Framework\App\Request\InvalidRequestException;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $_session;
    protected $_request;
    protected $_scopeConfig;
    protected $_orderRepository;
    protected $_dbAME;
    protected $_mailerAME;
    protected $_invoiceService;
    protected $_transactionFactory;
    protected $_api;
    protected $_mlogger;
    protected $_email;

    public function __construct(\Magento\Framework\App\Action\Context $context,
                                \Magento\Framework\App\Request\Http $request,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                \Magento\Sales\Model\OrderRepository $orderRepository,
                                \AmeDigital\AME\Helper\AmeDB $dbAME,
                                \AmeDigital\AME\Helper\MailerAME $mailerAME,
                                \Magento\Sales\Model\Service\InvoiceService $invoiceService,
                                \Magento\Framework\DB\TransactionFactory $transactionFactory,
                                \AmeDigital\AME\Helper\API $api,
                                \Psr\Log\LoggerInterface $mlogger,
                                \AmeDigital\AME\Helper\MailerAME $email,
                                array $data = []
                                )
    {
        $this->_request = $request;
        $this->_scopeConfig = $scopeConfig;
        $this->_orderRepository = $orderRepository;
        $this->_dbAME = $dbAME;
        $this->_mailerAME = $mailerAME;
        $this->_invoiceService = $invoiceService;
        $this->_transactionFactory = $transactionFactory;
        $this->_api = $api;
        $this->_mlogger = $mlogger;
        $this->_email = $email;
        parent::__construct($context);
    }
    public function execute()
    {
        $this->_mlogger->log("INFO","AME Callback step 2 starting...");
        $hash = $this->_request->getParam('hash');
        $callback2_hash = $this->_dbAME->getCallback2Hash();
        $ame_transaction_id = $this->_request->getParam('id');
        $ame_order_id = $this->_dbAME->getAmeOrderIdByTransactionId($ame_transaction_id);
        $incrId = $this->_dbAME->getOrderIncrementId($ame_order_id);
        $this->_mlogger->log("INFO","AME Callback getting Magento Order for ".$incrId);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orderInterface = $objectManager->create('Magento\Sales\Api\Data\OrderInterface');
        $order = $orderInterface->loadByIncrementId($incrId);
        $orderId = $order->getId();
        $this->_mlogger->log("INFO","Order ID: ".$orderId);
        $order = $this->_orderRepository->get($orderId);
        $this->_mlogger->log("INFO","AME Callback invoicing Magento order ".$incrId);
        $this->_mlogger->log("INFO", "AME Callback capturing...");
        $capture = $this->_api->captureOrder($ame_order_id);
        if(!$capture) die();
        $this->invoiceOrder($order);
        $this->_dbAME->setCaptured($ame_transaction_id);
        $ame_transaction_id = $this->_dbAME->getTransactionIdByOrderId($ame_order_id);
        $amount = $this->_dbAME->getTransactionAmount($ame_transaction_id);
    }
    public function cancelOrder($order){
        $order->cancel()->save();
    }
    public function invoiceOrder($order){
        $invoice = $this->_invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
        $invoice->register();
        $transaction = $this->_transactionFactory->create()
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transaction->save();
        $order->setState('processing')->setStatus('processing');
        $order->save();
    }
    public function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
