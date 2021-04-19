<?php

class Cardinity_Payments_Model_External
{
    public function setData($data)
    {
        $this->_getSession()->setData('crd_external_data', $data);
    }

    public function getData()
    {
        return $this->_getSession()->getData('crd_external_data');
    }



    public function setExternalRequired($amount)
    {
        $this->_getSession()->setData('crd_external_required', $amount);
    }

    public function getExternalRequired()
    {
        return $this->_getSession()->getData('crd_external_required');
    }


    public function setAmount($amount)
    {
        $this->_getSession()->setData('crd_external_amount', $amount);
    }

    public function getAmount()
    {
        return $this->_getSession()->getData('crd_external_amount');
    }

    public function setCancelUrl($cancel_url)
    {
        $this->_getSession()->setData('crd_external_cancel_url', $cancel_url);
    }

    public function getCancelUrl()
    {
        return $this->_getSession()->getData('crd_external_cancel_url');
    }

    public function setCountry($country)
    {
        $this->_getSession()->setData('crd_external_country', $country);
    }

    public function getCountry()
    {
        return $this->_getSession()->getData('crd_external_country');
    }

    public function setCurrency($currency)
    {
        $this->_getSession()->setData('crd_external_currency', $currency);
    }

    public function getCurrency()
    {
        return $this->_getSession()->getData('crd_external_currency');
    }

    public function setDescription($desc)
    {
        $this->_getSession()->setData('crd_external_desc', $desc);
    }

    public function getDescription()
    {
        return $this->_getSession()->getData('crd_external_desc');
    }

    public function setProjectId($project_id)
    {
        $this->_getSession()->setData('crd_external_project_id', $project_id);
    }

    public function getProjectId()
    {
        return $this->_getSession()->getData('crd_external_project_id');
    }

    public function setReturnUrl($return_url)
    {
        $this->_getSession()->setData('crd_external_return_url', $return_url);
    }

    public function getReturnUrl()
    {
        return $this->_getSession()->getData('crd_external_return_url');
    }

    public function setSignature($signature)
    {
        $this->_getSession()->setData('crd_external_signature', $signature);
    }

    public function getSignature()
    {
        return $this->_getSession()->getData('crd_external_signature');
    }

    
    public function setPaymentId($paymentId)
    {
        $this->_getSession()->setData('crd_payment_id', $paymentId);
    }

    public function getPaymentId()
    {
        return $this->_getSession()->getData('crd_payment_id');
    }

    //This is the orderID used on database
    public function setOrderId($orderId)
    {
        $this->_getSession()->setData('crd_order_id', $orderId);
    }

    public function getOrderId() 
    {
        return $this->_getSession()->getData('crd_order_id');
    }

    //This is the orderID used on website
    public function setRealOrderId($orderId)
    {
        $this->_getSession()->setData('crd_real_order_id', $orderId);
    }
 
    public function getRealOrderId()
    {
        return $this->_getSession()->getData('crd_real_order_id');
    }

    public function setSuccess($success)
    {
        $this->_getSession()->setData('crd_success', $success);
    }

    public function getSuccess()
    {
        return $this->_getSession()->getData('crd_success');
    }

    public function setFailure($success)
    {
        $this->_getSession()->setData('crd_failure', $success);
    }

    public function getFailure()
    {
        return $this->_getSession()->getData('crd_failure');
    }



    /**
     * Cleanup data
     */
    public function cleanup()
    {
        $this->_getSession()->setData('crd_external_amount', null);
        $this->_getSession()->setData('crd_external_cancel_url', null);
        $this->_getSession()->setData('crd_external_country', null);
        $this->_getSession()->setData('crd_external_currency', null);
        $this->_getSession()->setData('crd_external_desc', null);
        $this->_getSession()->setData('crd_external_project_id', null);
        $this->_getSession()->setData('crd_external_return_url', null);
        $this->_getSession()->setData('crd_external_signature', null);
        $this->_getSession()->setData('crd_external_secret', null);
        $this->_getSession()->setData('crd_external_required', null);

        

        $this->_getSession()->setData('crd_payment_id', null);
        $this->_getSession()->setData('crd_order_id', null);
        $this->_getSession()->setData('crd_real_order_id', null);
        $this->_getSession()->setData('crd_success', null);
        $this->_getSession()->setData('crd_failure', null);

    }

    public function dump(){
        return $this->_getSession()->getData();
    }
  

    private function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    } 
}