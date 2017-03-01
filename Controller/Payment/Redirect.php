<?php

namespace Cardinity\Payments\Controller\Payment;

class Redirect extends \Cardinity\Payments\Controller\Payment
{
    public function execute()
    {
        $this->_log('called ' . __METHOD__);

        $authModel = $this->_getAuthModel();

        if ($authModel && $authModel->getSuccess()) {
            $this->_log('redirecting buyer to success page');
            $this->_forceRedirect('cardinity/payment/success');
        } elseif ($authModel && $authModel->getThreeDSecureNeeded()) {
            $this->_log('redirecting buyer to auth page');
            $this->_forceRedirect('cardinity/payment/auth');
        } elseif ($authModel->getFailure()) {
            $this->_log('redirecting buyer to failure page');
            $this->_forceRedirect('cardinity/payment/failure');
        } else {
            $this->_setMessage(__('Internal error occurred. Please contact support.'), 'error');
            $this->_forceRedirect('checkout');
        }
    }
}
