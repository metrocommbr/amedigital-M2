<?php


namespace AmeDigital\AME\Controller\Adminhtml\Capture;

use \Zend\Barcode\Barcode;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
    protected $request;
    protected $orderRepository;
    protected $_amedigAPI;
    protected $_dbAME;
    protected $_transactionFactory;

    public function __construct(\Magento\Framework\App\Action\Context $context,
                                \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \Magento\Framework\App\Request\Http $request,
                                \Magento\Sales\Model\OrderRepository $orderRepository,
                                \AmeDigital\AME\Helper\API $amedigAPI,
                                \AmeDigital\AME\Helper\AmeDB $dbAME,
                                \Magento\Sales\Model\Service\InvoiceService $invoiceService
                                )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->_amedigAPI = $amedigAPI;
        $this->_dbAME = $dbAME;
        $this->_transactionFactory = $invoiceService;
        parent::__construct($context);
    }
    public function execute()
    {
        $id = $this->request->getParam('id');
        $order = $this->orderRepository->get($id);
        echo "Painel AME - capturar pedido<br><br>\r";
        echo "Pedido Magento: ".$order->getIncrementId()."<br>\r";
        echo "Pedido AME: ".$this->_dbAME->getAmeIdByIncrementId($order->getIncrementId())."<br>\r";
        $capture = $this->_amedigAPI->captureOrder($this->_dbAME->getAmeIdByIncrementId($order->getIncrementId()));
        if(!$capture){
            echo "ERROR";
            die();
        }
        $this->invoiceOrder($order);
        $this->_mlogger->log("INFO", "AME Callback capturing...");
        $capture = $this->_api->captureOrder($ame_order_id);

        $json_array = $capture;
        $json_string = json_encode($json_array, JSON_PRETTY_PRINT);
        echo "<br>\n";
        echo nl2br($json_string);
        echo "<br>\n";
        die();
        return;
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

}
