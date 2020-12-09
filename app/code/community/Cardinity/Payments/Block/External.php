<?php

class Cardinity_Payments_Block_External extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('cardinity/payment/external.phtml');
    }

    public function getAmount()
    {
        return $this->_getModel()->getAmount();
    }

    public function getCancelUrl()
    {
        return $this->_getModel()->getCancelUrl();
    }

    public function getCountry()
    {
        return $this->_getModel()->getCountry();
    }


    public function getCurrency()
    {
        return $this->_getModel()->getCurrency();
    }


    public function getDescription()
    {
        return $this->_getModel()->getDescription();
    }


    public function getRealOrderId()
    {
        return $this->_getModel()->getRealOrderId();
    }


    public function getProjectId()
    {
        return $this->_getModel()->getProjectId();
    }


    public function getReturnUrl()
    {
        return $this->_getModel()->getReturnUrl();
    }


    public function getSignature()
    {
        return $this->_getModel()->getSignature();
    }


    private function _getModel()
    {
        return Mage::getModel('cardinity/external');
    }
}
