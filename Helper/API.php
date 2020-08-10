<?php

namespace AmeDigital\AME\Helper;

use \Ramsey\Uuid\Uuid;

class API
{
    protected $url;
    protected $_logger;
    protected $_mlogger;
    protected $_connection;
    protected $_scopeConfig;
    protected $_storeManager;
    protected $_dbAME;
    protected $_email;

    public function __construct(\AmeDigital\AME\Helper\LoggerAME $logger,
                                \Psr\Log\LoggerInterface $mlogger,
                                \Magento\Framework\App\ResourceConnection $resource,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                \Magento\Store\Model\StoreManagerInterface $storeManager,
                                \AmeDigital\AME\Helper\AmeDB $dbAME,
                                \AmeDigital\AME\Helper\MailerAME $email,
                                \AmeDigital\AME\Helper\Mlogger $nmlogger
    )
    {
        $this->url = "https://api.hml.amedigital.com/api";
        $this->_logger = $logger;
        $this->_mlogger = $mlogger;
        $this->_connection = $resource->getConnection();
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_dbAME = $dbAME;

        if(!$this->_scopeConfig->getValue('ame/general/debug_log', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)){
            $this->_mlogger = $nmlogger;
        }
        if ($this->_scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 0) {
            $this->url = "https://api.dev.amedigital.com/api";
        }
        if ($this->_scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 1) {
            $this->url = "https://api.hml.amedigital.com/api";
        }
        if ($this->_scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 2) {
            $this->url = "https://api.amedigital.com/api";
        }
        $this->_email = $email;
    }
    public function refundOrder($ame_id, $amount)
    {
        $this->_mlogger->info("AME REFUND ORDER:" . $ame_id);
        $this->_mlogger->info("AME REFUND amount:" . $amount);

        $transaction_id = $this->_dbAME->getTransactionIdByOrderId($ame_id);
        $this->_mlogger->info("AME REFUND TRANSACTION:" . $transaction_id);

        $refund_id = Uuid::uuid4()->toString();
        while($this->_dbAME->refundIdExists($refund_id)){
            $refund_id = Uuid::uuid4()->toString();
        }
        $this->_mlogger->info("AME REFUND ID:" . $refund_id);
        $url = $this->url . "/payments/" . $transaction_id . "/refunds/Athos-" . $refund_id;
        $this->_mlogger->info("AME REFUND URL:" . $url);
        $json_array['amount'] = $amount;
        $json = json_encode($json_array);
        $this->_mlogger->info("AME REFUND JSON:" . $json);
        $result[0] = $this->ameRequest($url, "PUT", $json);
        $this->_mlogger->info("AME REFUND Result:" . $result[0]);
        if ($this->hasError($result[0], $url, $json)) return false;
        $result[1] = $refund_id;
        return $result;
    }
    public function cancelOrder($ame_id)
    {
        $transaction_id = $this->_dbAME->getTransactionIdByOrderId($ame_id);
        if (!$transaction_id) {
            return false;
        }
        $url = $this->url . "/wallet/user/payments/" . $transaction_id . "/cancel";
        $result = $this->ameRequest($url, "PUT", "");
        if ($this->hasError($result, $url, "")) return false;
        return true;
    }
    public function consultOrder($ame_id)
    {
        $url = $this->url . "/orders/" . $ame_id;
        $result = $this->ameRequest($url, "GET", "");
        if ($this->hasError($result, $url)) return false;
        return $result;
    }
    public function captureOrder($ame_id)
    {
        $ame_transaction_id = $this->_dbAME->getTransactionIdByOrderId($ame_id);
        $url = $this->url . "/wallet/user/payments/" . $ame_transaction_id . "/capture";
        $result = $this->ameRequest($url, "PUT", "");
        if ($this->hasError($result, $url)) return false;
        $result_array = json_decode($result, true);

        return $result_array;
    }
    public function createOrder($order)
    {
        $url = $this->url . "/orders";

        $shippingAmount = $order->getShippingAmount();
        $productsAmount = $order->getGrandTotal() - $shippingAmount;
        $amount = intval($order->getGrandTotal() * 100);
        $cashbackAmountValue = intval($this->getCashbackPercent() * $amount * 0.01);

        $json_array['title'] = "ATHOS Pedido " . $order->getIncrementId();
        $json_array['description'] = "Pedido " . $order->getIncrementId();
        $json_array['amount'] = $amount;
        $json_array['currency'] = "BRA";
        $json_array['attributes']['cashbackamountvalue'] = $cashbackAmountValue;
        $json_array['attributes']['transactionChangedCallbackUrl'] = $this->getCallbackUrl();
        $json_array['attributes']['items'] = [];

        $items = $order->getAllItems();
        $amount = 0;
        $total_discount = 0;
        foreach ($items as $item) {
            if (isset($array_items)) unset($array_items);
            $array_items['description'] = $item->getName() . " - SKU " . $item->getSku();
            $array_items['quantity'] = intval($item->getQtyOrdered());
            $array_items['amount'] = intval(($item->getRowTotal() - $item->getDiscountAmount()) * 100);
            $products_amount = $amount + $array_items['amount'];
            $total_discount = $total_discount + abs($item->getDiscountAmount());
            array_push($json_array['attributes']['items'], $array_items);
        }
        if($total_discount){
            $amount = intval($products_amount + $shippingAmount * 100);
            $json_array['amount'] = $amount;
            $cashbackAmountValue = intval($this->getCashbackPercent() * $products_amount * 0.01);
            $json_array['attributes']['cashbackamountvalue'] = $cashbackAmountValue;
        }

        $json_array['attributes']['customPayload']['ShippingValue'] = intval($order->getShippingAmount() * 100);
        $json_array['attributes']['customPayload']['shippingAddress']['country'] = "BRA";

        $number_line = $this->_scopeConfig->getValue('ame/address/number', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $json_array['attributes']['customPayload']['shippingAddress']['number'] = $order->getShippingAddress()->getStreet()[$number_line];

        $json_array['attributes']['customPayload']['shippingAddress']['city'] = $order->getShippingAddress()->getCity();

        $street_line = $this->_scopeConfig->getValue('ame/address/street', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $json_array['attributes']['customPayload']['shippingAddress']['street'] = $order->getShippingAddress()->getStreet()[$street_line];

        $json_array['attributes']['customPayload']['shippingAddress']['postalCode'] = $order->getShippingAddress()->getPostcode();

        $neighborhood_line = $this->_scopeConfig->getValue('ame/address/neighborhood', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $json_array['attributes']['customPayload']['shippingAddress']['neighborhood'] = $order->getShippingAddress()->getStreet()[$neighborhood_line];

        $json_array['attributes']['customPayload']['shippingAddress']['state'] = $this->codigoUF($order->getShippingAddress()->getRegion());

        $json_array['attributes']['customPayload']['billingAddress'] = $json_array['attributes']['customPayload']['shippingAddress'];
        $json_array['attributes']['customPayload']['isFrom'] = "MAGENTO";
        $json_array['attributes']['paymentOnce'] = true;
        $json_array['attributes']['riskHubProvider'] = "SYNC";
        $json_array['attributes']['origin'] = "ECOMMERCE";

        $json = json_encode($json_array);
        $result = $this->ameRequest($url, "POST", $json);

        if ($this->hasError($result, $url, $json)) return false;
        $this->_logger->log($result, "info", $url, $json);
        $result_array = json_decode($result, true);

        $this->_dbAME->insertOrder($order,$result_array);

        $this->_logger->log($result, "info", $url, $json);
        return $result;
    }
    public function getCallbackUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl() . "m2amecallbackendpoint";
    }
    public function hasError($result, $url, $input = "")
    {
        $result_array = json_decode($result, true);
        if (is_array($result_array)) {
            
        } else {
            $this->_mlogger->info("ameRequest hasError:" . $result);
            return true;
        }
        return false;
    }
    public function getCashbackPercent()
    {
        return $this->_scopeConfig->getValue('ame/general/cashback_percent', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    public function getStoreName()
    {
        return $this->_scopeConfig->getValue('ame/general/store_name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    public function ameRequest($url, $method = "GET", $json = "")
    {
        $this->_mlogger->info("ameRequest starting...");
        $_token = $this->getToken();
        if (!$_token) return false;
        $method = strtoupper($method);
        $this->_mlogger->info("ameRequest URL:" . $url);
        $this->_mlogger->info("ameRequest METHOD:" . $method);
        if ($json) {
            $this->_mlogger->info("ameRequest JSON:" . $json);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . $_token));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($method == "POST" || $method == "PUT") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        $this->_mlogger->info("ameRequest OUTPUT:" . $result);
        $this->_logger->log(curl_getinfo($ch, CURLINFO_HTTP_CODE), "header", $url, $json);
        $this->_logger->log($result, "info", $url, $json);
        curl_close($ch);
        return $result;
    }
    public function getToken()
    {
        $this->_mlogger->info("ameRequest getToken starting...");
        if($token = $this->_dbAME->getToken()){
            return $token;
        }
        $username = $this->_scopeConfig->getValue('ame/general/api_user', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $password = $this->_scopeConfig->getValue('ame/general/api_password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (!$username || !$password) {
            $this->_logger->log("user/pass not found on db", "error", "-", "-");
            return false;
        }
        $url = $this->url . "/auth/oauth/token";
        $ch = curl_init();
        $post = "grant_type=client_credentials";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
        ));
        $result = curl_exec($ch);
        if ($this->hasError($result, $url, $post)) return false;
        $result_array = json_decode($result, true);
        if(!array_key_exists('access_token',$result_array)) return false;
        $this->_logger->log($result, "info", $url, $username . ":" . $password);

        $expires_in = (int)time() + intval($result_array['expires_in']);
        $this->_dbAME->updateToken($expires_in,$result_array['access_token']);
        return $result_array['access_token'];
    }
    public function codigoUF($txt_uf)
    {
        $array_ufs = array("Rondônia" => "RO",
            "Acre" => "AC",
            "Amazonas" => "AM",
            "Roraima" => "RR",
            "Pará" => "PA",
            "Amapá" => "AP",
            "Tocantins" => "TO",
            "Maranhão" => "MA",
            "Piauí" => "PI",
            "Ceará" => "CE",
            "Rio Grande do Norte" => "RN",
            "Paraíba" => "PB",
            "Pernambuco" => "PE",
            "Alagoas" => "AL",
            "Sergipe" => "SE",
            "Bahia" => "BA",
            "Minas Gerais" => "MG",
            "Espírito Santo" => "ES",
            "Rio de Janeiro" => "RJ",
            "São Paulo" => "SP",
            "Paraná" => "PR",
            "Santa Catarina" => "SC",
            "Rio Grande do Sul (*)" => "RS",
            "Mato Grosso do Sul" => "MS",
            "Mato Grosso" => "MT",
            "Goiás" => "GO",
            "Distrito Federal" => "DF");
        $uf = "RJ";
        foreach ($array_ufs as $key => $value) {
            if ($key == $txt_uf) {
                $uf = $value;
                break;
            }
        }
        return $uf;
    }
}
