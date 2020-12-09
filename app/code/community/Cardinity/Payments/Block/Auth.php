<?php

class Cardinity_Payments_Block_Auth extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('cardinity/payment/auth.phtml');
    }

    public function getUrl()
    {
        return $this->_getModel()->getUrl();
    }

    public function getData()
    {
        return $this->_getModel()->getData();
    }

    public function getRealOrderId()
    {
        return $this->_getModel()->getRealOrderId();
    }

    public function getCallbackUrl()
    {
        return Mage::getUrl('cardinity/payment/callback', array('_secure' => true));
    }

    private function _getModel()
    {
        return Mage::getModel('cardinity/auth');
    }
}
