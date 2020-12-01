<?php

namespace Cardinity\Payment\Controller\Payment;

class Redirect extends \Cardinity\Payment\Controller\Payment
{
    public function execute()
    {
        $authModel = $this->_getAuthModel();

        if ($authModel && $authModel->getSuccess()) {
            $this->_log('redirecting buyer to success page');
            $this->_forceRedirect('cardinity/payment/success');
        } elseif ($authModel && ($authModel->getThreeDSecureNeeded() || $authModel->getThreeDSecureV2Needed())   ) {
            $this->_log('redirecting buyer to auth page');
            $this->_forceRedirect('cardinity/payment/auth');
        } elseif ($authModel && $authModel->getFailure()) {
            $this->_log('redirecting buyer to failure page');
            $this->_forceRedirect('cardinity/payment/failure');
        } else {
            $this->_setMessage(__('Internal error occurred. Please contact support.'), 'error');
            $this->_forceRedirect('checkout');
        }
    }
}
