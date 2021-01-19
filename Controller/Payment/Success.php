<?php

namespace Cardinity\Magento\Controller\Payment;

class Success extends \Cardinity\Magento\Controller\Payment
{
    public function execute()
    {
        $authModel = $this->_getAuthModel();
        $externalModel = $this->_getExternalModel();

        if ($authModel->getSuccess() && $this->_success()) {
            $authModel->cleanup();

            $this->_forceRedirect('checkout/onepage/success');
        } elseif ($externalModel->getSuccess() && $this->_success()) {
            $externalModel->cleanup();

            $this->_forceRedirect('checkout/onepage/success');
        } else {
            $this->_setMessage(__('Invalid request.'), 'error');
            $authModel->cleanup();

            $this->_forceRedirect('checkout');
        }
    }
}
