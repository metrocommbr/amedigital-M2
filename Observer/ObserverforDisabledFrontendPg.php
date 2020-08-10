<?php

namespace AmeDigital\AME\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;

class ObserverforDisabledFrontendPg implements ObserverInterface
{
    protected $_appState;

    public function __construct(
        \Magento\Framework\App\State $appState
    )
    {
        $this->_appState = $appState;
    }

    protected function getDisableAreas()
    {
        return array(\Magento\Framework\App\Area::AREA_FRONTEND, \Magento\Framework\App\Area::AREA_WEBAPI_REST);
    }
}
