<?php

namespace Cardinity\Payment\Controller\Payment;

class Success extends \Cardinity\Payment\Controller\Payment
{
    public function execute()
    {
        $authModel = $this->_getAuthModel();

        if ($authModel->getSuccess() && $this->_success()) {
            $authModel->cleanup();

            $this->_forceRedirect('checkout/onepage/success');
        } else {
            $this->_setMessage(__('Invalid request.'), 'error');
            $authModel->cleanup();

            $this->_forceRedirect('checkout');
        }
    }
}
