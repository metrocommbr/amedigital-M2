<?php

namespace AmeDigital\AME\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class MailerAME extends AbstractHelper
{
    protected $_directoryList;
    protected $_scopeConfig;

    public function __construct(Context $context,
                                \Magento\Framework\Filesystem\DirectoryList $directoryList,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
                                )
    {
        $this->_directoryList = $directoryList;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function mailSender($to,$subject,$message)
    {
        $email = $this->_scopeConfig->getValue('trans_email/ident_support/email',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $headers = "From: Magento Debug <".$email.">\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        mail($to,$subject,$message,$headers);
        return true;
    }
}
