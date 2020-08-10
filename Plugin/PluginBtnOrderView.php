<?php

namespace AmeDigital\AME\Plugin;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\ObjectManagerInterface;

class PluginBtnOrderView
{
    protected $object_manager;
    protected $_backendUrl;
    protected $_scopeConfig;

    public function __construct(
        ObjectManagerInterface $om,
        UrlInterface $backendUrl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->object_manager = $om;
        $this->_backendUrl = $backendUrl;
        $this->_scopeConfig = $scopeConfig;

    }
    public function beforeSetLayout( \Magento\Sales\Block\Adminhtml\Order\View $subject )
    {
        if ($this->_scopeConfig->getValue('ame/general/environment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) != 2) {
            $sendOrder = $this->_backendUrl->getUrl('ameroutes/order/index/id/' . $subject->getOrderId());
            $subject->addButton(
                'sendorderame',
                [
                    'label' => __('AME'),
                    'onclick' => "window.open('" . $sendOrder . "','_blank')",
                    'class' => 'ship primary'
                ]
            );
        }
        return null;
    }

}
