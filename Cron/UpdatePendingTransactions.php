<?php

namespace AmeDigital\Tracking\Cron;

class UpdatePendingTransactions
{
    protected $_mlogger;
    protected $_order;
    protected $_dbAME;
    protected $_storeManager;

    public function __construct(
        \Psr\Log\LoggerInterface $mlogger,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \AmeDigital\AME\Helper\AmeDB $dbAME,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_mlogger = $mlogger;
        $this->_order = $order;
        $this->_dbAME = $dbAME;
        $this->_storeManager = $storeManager;
    }
    public function execute()
    {
        $num = 10;
        $transactions = $this->_dbAME->getFirstPendingCaptureTransactions($num);
        foreach($transactions as $transaction){
            $hash = $this->_dbAME->getCallback2Hash();
            $url = $this->getCallbackUrl() . '/step2/index/hash/' . $hash . '/id/' . $ame_transaction_id;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $result = curl_exec($ch);
        }
        $transactions = $this->_dbAME->getFirstPendingTransactions($num);
        foreach($transactions as $transaction){
            if($capture) $this->_dbAME->setCaptured2($transaction['ame_transaction_id']);
            $this->_dbAME->setTransactionUpdated($transaction['ame_transaction_id']);
        }
    }
    public function getCallbackUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl() . "m2amecallbackendpoint";
    }
}
