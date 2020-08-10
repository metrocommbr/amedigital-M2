<?php

namespace AmeDigital\AME\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Config\Source\Order\Status;

class Pendingpayment extends Status
{
    protected $_stateStatuses = [Order::STATE_NEW];
    protected $_isInitializeNeeded = true;
    protected $_canAuthorize 				= false;
    protected $_canCapture 					= true;
    protected $_code 						= 'ame';
    protected $_canCapturePartial       	= true;
    protected $_canVoid                		= true;
    protected $_canCancel              		= true;
    protected $_canUseForMultishipping 		= false;
    protected $_canReviewPayment 			= true;
    protected $_countryFactory;
    protected $_supportedCurrencyCodes 		= ['BRL'];
    protected $_canUseInternal          	= false;
    protected $_cart;
    protected $_canFetchTransactionInfo 	= true;
}
