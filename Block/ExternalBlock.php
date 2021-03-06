<?php

namespace Cardinity\Magento\Block;

class ExternalBlock extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,

        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );

        $this->_urlBuilder = $context->getUrlBuilder();
        $this->_objectManager = $objectManager;

    }



    public function getExternalUrl(){
        return "https://checkout.cardinity.com";
    }

    public function getAmount()
    {
        return $this->_getExternalModel()->getAmount();
    }

    public function getCancelUrl()
    {
        return $this->_getExternalModel()->getCancelUrl();
    }

    public function getCountry()
    {
        return $this->_getExternalModel()->getCountry();
    }

    public function getCurrency()
    {
        return $this->_getExternalModel()->getCurrency();
    }

    public function getDescription()
    {
        return $this->_getExternalModel()->getDescription();
    }

    public function getOrderId()
    {
        return $this->_getExternalModel()->getOrderId();
    }

    public function getProjectId()
    {
        return $this->_getExternalModel()->getProjectId();
    }


    public function getReturnUrl()
    {
        return $this->_getExternalModel()->getReturnUrl();
    }

    public function getSignature()
    {
        return $this->_getExternalModel()->getSignature();
    }



    public function getRealOrderId()
    {
        return $this->_getExternalModel()->getRealOrderId();
    }

    public function getCallbackUrl()
    {
        return $this->_urlBuilder->getUrl('cardinity/payment/callbackexternal', ['_secure' => true]);
    }

    private function _getExternalModel()
    {
        return $this->_objectManager->create('Cardinity\Magento\Model\ExternalModel');
    }
}
