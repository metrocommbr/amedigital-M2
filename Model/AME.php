<?php

namespace AmeDigital\AME\Model;

/**
 * Pay In Store payment method model
 */
class AME extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'ame';
    protected $_methodCode = 'ame';
    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;
}
