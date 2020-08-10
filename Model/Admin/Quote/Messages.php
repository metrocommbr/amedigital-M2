<?php

namespace AmeDigital\AME\Model\Admin\Quote;

use Magento\Security\Model\ResourceModel\AdminSessionInfo\Collection;
use Magento\Backend\Model\UrlInterface;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Notification\MessageInterface;

class Messages implements MessageInterface
{
    protected $backendUrl;
    private $adminSessionInfoCollection;
    protected $authSession;
    protected $_moduleLIst;
    protected $_cookieManager;
    protected $_cookieMetadataFactory;

    private $cookie_name = "AmeDigital";

    public function __construct(
        Collection $adminSessionInfoCollection,
        UrlInterface $backendUrl,
        Session $authSession,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->authSession = $authSession;
        $this->backendUrl = $backendUrl;
        $this->adminSessionInfoCollection = $adminSessionInfoCollection;
        $this->_moduleLIst = $moduleList;
        $this->_cookieManager = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
    }
    public function isDisplayed()
    {
        if(!$this->hasCookie()) {
            $current_version = $this->getCurrentVersion();
            $latest_version = $this->getLatestVersion();
            if (version_compare($current_version, $latest_version, "<")) {
                $this->setCookie();
                return true;
            } else return false;
        }
        else return false;
    }
    public function setCookie(){
        $metadata = $this->_cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration(31536000);
        $this->_cookieManager->setPublicCookie(
            $this->cookie_name,
            "1",
            $metadata
        );
        return;
    }
    public function hasCookie(){
        return $this->_cookieManager->getCookie($this->cookie_name);
    }
    public function getSeverity()
    {
        return \Magento\Framework\Notification\MessageInterface::SEVERITY_NOTICE;
    }
}
