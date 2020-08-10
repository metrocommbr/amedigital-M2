<?php

namespace AmeDigital\AME\Model\Config;

class Environment implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 2, 'label' => __('Produção')],
        ];
    }
}
