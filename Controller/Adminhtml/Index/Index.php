<?php

namespace AmeDigital\AME\Controller\Adminhtml\Index;

use \Zend\Barcode\Barcode;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
    protected $request;
    protected $orderRepository;
    protected $_amedigAPI;
    protected $_dbAME;
    protected $_formKey;

    public function __construct(\Magento\Framework\App\Action\Context $context,
                                \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \Magento\Framework\App\Request\Http $request,
                                \Magento\Sales\Model\OrderRepository $orderRepository,
                                \AmeDigital\AME\Helper\API $amedigAPI,
                                \AmeDigital\AME\Helper\AmeDB $dbAME,
                                \Magento\Framework\Data\Form\FormKey $formKey
                                )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->_amedigAPI = $amedigAPI;
        $this->_dbAME = $dbAME;
        $this->_formKey = $formKey;
        parent::__construct($context);
    }
    public function execute()
    {

        $id = $this->request->getParam('id');
        $url = "../../../../";
        header('Location: ../../../../order/index/id/'.$id);
        die();

        $order = $this->orderRepository->get($id);
        echo "Painel AME <br><br>\r";
        echo "Pedido Magento: ".$order->getIncrementId()."<br>\r";
        echo "Pedido AME: ".$this->_dbAME->getAmeIdByIncrementId($order->getIncrementId())."<br>\r";
        echo "<a href='".$url."capture/index/id/".$id."/'>Capturar</a> | ";
        echo "<a href='".$url."cancel/index/id/".$id."/'>Cancelar</a> | ";
        echo "<form method='post' action='".$url."refund/index/id/".$id."/'>";
        echo "<input type='text' name='valor'><input type='submit' value='Reembolsar'>Reembolsar";
        ?>
        <input name="form_key" type="hidden" value="<?php echo $this->getFormKey(); ?>">
        <?php
        echo "</form>";
        echo "<br>\n";
        $json = $this->_amedigAPI->consultOrder($this->_dbAME->getAmeIdByIncrementId($order->getIncrementId()));
        $json_array = json_decode($json,true);
        $json_string = json_encode($json_array, JSON_PRETTY_PRINT);
        echo "<br>\n";
        echo nl2br($json_string);
        echo "<br>\n";
        die();
        return;
    }
    public function getFormKey()
    {
        return $this->_formKey->getFormKey();
    }
}
