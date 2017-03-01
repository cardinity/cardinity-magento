<?php

namespace Cardinity\Payments\Controller\Payment;

class Success extends \Cardinity\Payments\Controller\Payment
{
    public function execute()
    {
        $this->_log('called ' . __METHOD__);

        $authModel = $this->_getAuthModel();

        if ($authModel->getSuccess() && $this->_success()) {
            $this->_log('order marked as paid');
            $authModel->cleanup();

            $this->_forceRedirect('checkout/onepage/success');
        } else {
            $this->_setMessage(__('Invalid success request.'), 'error');
            $authModel->cleanup();

            $this->_forceRedirect('checkout');
        }
    }
}
