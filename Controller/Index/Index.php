<?php

namespace AmeDigital\AME\Controller\Index;

use \Magento\Framework\App\CsrfAwareActionInterface;
use \Magento\Framework\App\RequestInterface;
use \Magento\Framework\App\Request\InvalidRequestException;

class Index extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
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
    protected $_storeManager;

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
                                \Magento\Store\Model\StoreManagerInterface $storeManager,
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
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }
    public function execute()
    {
        $this->_mlogger->log("INFO","AME Callback starting...");
        $json = file_get_contents('php://input');
        $this->_dbAME->insertCallback($json);
        $input = json_decode($json,true);
        $this->_mlogger->log("INFO",print_r($input,true));
        $ame_order_id = $input['attributes']['orderId'];
        $incrId = $this->_dbAME->getOrderIncrementId($ame_order_id);
        if($input['status']=="AUTHORIZED") {
            $this->_dbAME->insertTransaction($input);
            $ame_transaction_id = $this->_dbAME->getTransactionIdByOrderId($ame_order_id);
            if ($this->_scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 2) {
                $this->_mlogger->log("INFO","AME Callback Calling step 2");
                $hash = $this->_dbAME->getCallback2Hash();
                $url = $this->getCallbackUrl() . '/step2/index/hash/' . $hash . '/id/' . $ame_transaction_id;
                $this->_mlogger->log("INFO", "Step 2 URL: " . $url);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                $result = curl_exec($ch);
                $this->_mlogger->log("INFO", "Step 2 call OK");
            }
        }
        else{
            $this->_mlogger->log("ERROR","Wrong Order status: ".$input['status']);
        }
        $this->_mlogger->log("INFO","AME Callback ended.");
        die();
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
    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
    public function getCallbackUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl() . "m2amecallbackendpoint";
    }
}
