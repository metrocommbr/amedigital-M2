<?php

namespace AmeDigital\AME\Controller\Adminhtml\Cancel;

use \Zend\Barcode\Barcode;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
    protected $request;
    protected $orderRepository;
    protected $_amedigAPI;
    protected $_dbAME;

    public function __construct(\Magento\Framework\App\Action\Context $context,
                                \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \Magento\Framework\App\Request\Http $request,
                                \Magento\Sales\Model\OrderRepository $orderRepository,
                                \AmeDigital\AME\Helper\API $amedigAPI,
                                \AmeDigital\AME\Helper\AmeDB $dbAME
                                )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->_amedigAPI = $amedigAPI;
        $this->_dbAME = $dbAME;
        parent::__construct($context);
    }
    public function execute()
    {
        $id = $this->request->getParam('id');
        $order = $this->orderRepository->get($id);
        echo "Painel AME <br><br>\r";
        echo "Pedido Magento: ".$order->getIncrementId()."<br>\r";
        echo "Pedido AME: ".$this->_dbAME->getAmeIdByIncrementId($order->getIncrementId())."<br>\r";
        $cancel = $this->_amedigAPI->cancelOrder($this->_dbAME->getAmeIdByIncrementId($order->getIncrementId()));
        if(!$cancel){
            echo "ERROR";
            die();
        }
        $json = $cancel;
        $json_array = json_decode($json,true);
        $json_string = json_encode($json_array, JSON_PRETTY_PRINT);
        echo "<br>\n";
        echo nl2br($json_string);
        echo "<br>\n";
        die();
        return;
    }
}
