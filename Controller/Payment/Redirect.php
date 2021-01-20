<?php

namespace Cardinity\Magento\Controller\Payment;

class Redirect extends \Cardinity\Magento\Controller\Payment
{
    public function execute()
    {
        $authModel = $this->_getAuthModel();
        $externalModel = $this->_getExternalModel();

        $external = $this->_configData->getConfig('payment/cardinity/external_enabled');

        $this->_log("External on redirect ".$external);
        
        if($external == 1 && $externalModel){
            $this->_log('redirecting buyer to external hosted page');
            $this->_forceRedirect('cardinity/payment/external');    
        }elseif ($authModel && $authModel->getSuccess()) {
            $this->_log('redirecting buyer to success page');
            $this->_forceRedirect('cardinity/payment/success');
        } elseif ($authModel && ($authModel->getThreeDSecureV2Needed())   ) {
            $this->_log('redirecting buyer to authv2 page');
            $this->_forceRedirect('cardinity/payment/authv2');
        } elseif ($authModel && ($authModel->getThreeDSecureNeeded())   ) {
            $this->_log('redirecting buyer to authv1 page');
            $this->_forceRedirect('cardinity/payment/auth');
        }  elseif ($authModel && $authModel->getFailure()) {
            $this->_log('redirecting buyer to failure page');
            $this->_forceRedirect('cardinity/payment/failure');
        } else {
            $this->_setMessage(__('Internal error occurred. Please contact support.'), 'error');
            $this->_forceRedirect('checkout');
        }
    }
}
