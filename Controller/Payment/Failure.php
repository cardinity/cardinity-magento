<?php

namespace Cardinity\Payments\Controller\Payment;

class Failure extends \Cardinity\Payments\Controller\Payment
{
    public function execute()
    {
        $this->_log('called ' . __METHOD__);

        $authModel = $this->_getAuthModel();

        if ($authModel->getFailure()) {
            $this->_cancel();
            $this->_log('order cancelled');
            $authModel->cleanup();

            $this->_forceRedirect('checkout/onepage/failure');
        } else {
            $this->_setMessage(__('Invalid failure request.'), 'error');
            $authModel->cleanup();

            $this->_forceRedirect('checkout');
        }
    }
}
