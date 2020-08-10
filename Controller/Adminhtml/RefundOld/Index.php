<?php

namespace AmeDigital\AME\Controller\Adminhtml\Refund;

use \Zend\Barcode\Barcode;

use \Magento\Framework\App\CsrfAwareActionInterface;
use \Magento\Framework\App\Request\InvalidRequestException;
use \Magento\Framework\App\RequestInterface;

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
        $valor = $this->request->getParam('valor');
        $valor = str_replace(",",".",$valor);
        $valor = $valor * 100;
        $order = $this->orderRepository->get($id);
        $ame_order_id = $this->_dbAME->getAmeIdByIncrementId($order->getIncrementId());
        $ame_transaction_id = $this->_dbAME->getTransactionIdByOrderId($ame_order_id);
        $order_amount = $this->_dbAME->getTransactionAmount($ame_transaction_id);




        echo "Painel AME - reembolsar pedido<br><br>\r\n";
        echo "Pedido Magento: ".$order->getIncrementId()."<br>\r\n";
        echo "Pedido AME: ".$this->_dbAME->getAmeIdByIncrementId($order->getIncrementId())."<br>\r\n";
        echo "Valor: ".$valor."<br>";

        if($valor>$order_amount){
            echo "Valor superior ao valor total do pedido. Não é possível fazer o reembolso.";
            die();
        }
        $already_refunded = $this->_dbAME->getRefundedSumByTransactionId($ame_transaction_id);
        if($valor > $order_amount-$already_refunded){
            echo "Valor superior ao valor restante do pedido. Não é possível fazer o reembolso.";
            die();
        }
        die();


        $refund = $this->_amedigAPI->refundOrder($this->_dbAME->getAmeIdByIncrementId($order->getIncrementId()),$valor);
        echo "Refund order executado<br>";
        if(!$refund){
            echo "ERROR";
            die();
        }
        $json = $refund[0];
        $json_array = json_decode($json,true);
        $json_string = json_encode($json_array, JSON_PRETTY_PRINT);
        echo "<br>\n";
        echo nl2br($json_string);
        echo "<br>\n";
        $this->_dbAME
            ->insertRefund($this->_dbAME->getAmeIdByIncrementId($order->getIncrementId()),
                $refund[1],
                $json_array['operationId'],
                $valor,
                $json_array['status']);
        die();
    }
}
