<?php

namespace AmeDigital\AME\Block;

class CashbackText extends \Magento\Framework\View\Element\Template
{
    protected $_scopeConfig;
    protected $_helper;
    protected $_registry;

    public function __construct(\Magento\Framework\View\Element\Template\Context $context,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                \Magento\Catalog\Helper\Data $helper,
                                \Magento\Framework\Registry $registry
                                )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_helper = $helper;
        $this->_registry = $registry;
        parent::__construct($context);
    }
    public function getCashbackPercent()
    {
        return $this->_scopeConfig->getValue("ame/general/cashback_percent", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    public function getCashbackValue(){
        $product = $this->getKey();
        return $product->getFinalPrice() * $this->getCashbackPercent() * 0.01;
    }
}

