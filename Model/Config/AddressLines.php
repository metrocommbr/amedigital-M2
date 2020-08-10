<?php

namespace AmeDigital\AME\Model\Config;

class AddressLines implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Rua')],
            ['value' => 1, 'label' => __('Numero')],
            ['value' => 2, 'label' => __('Bairro')],
            ['value' => 3, 'label' => __('Complemento')]
        ];
    }
}
