<?php

namespace AmeDigital\AME\Plugin;
use \Magento\Framework\View\Element\Template;

class ProductListPlugin {

    protected $template;

    public function __construct(\Magento\Framework\View\Element\Template $template)
    {
        $this->template = $template;
    }

    public function afterGetProductPrice($subject, $result, $product)
    {
        $html = $this->template->getLayout()->createBlock('AmeDigital\AME\Block\CashbackText')->setKey($product)->setTemplate('AmeDigital_AME::cashbacktext.phtml')->toHtml();

        return $result.$html;
    }

}
