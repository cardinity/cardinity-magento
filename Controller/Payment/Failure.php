<?php

namespace Cardinity\Magento\Controller\Payment;

class Failure extends \Cardinity\Magento\Controller\Payment
{
    public function execute()
    {
        $authModel = $this->_getAuthModel();

        if ($authModel->getFailure()) {
            $this->_cancel();
            $authModel->cleanup();

            $this->_forceRedirect('checkout/onepage/failure');
        } else {
            $this->_setMessage(__('Invalid failure request.'), 'error');
            $authModel->cleanup();

            $this->_forceRedirect('checkout');
        }
    }
}
