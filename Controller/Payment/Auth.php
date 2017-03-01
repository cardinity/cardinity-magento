<?php

namespace Cardinity\Payments\Controller\Payment;

class Auth extends \Cardinity\Payments\Controller\Payment
{
    public function execute()
    {
        $this->_log('called ' . __METHOD__);

        $authModel = $this->_getAuthModel();

        if ($authModel && $authModel->getThreeDSecureNeeded()) {
            return $this->_pageFactory->create();
        } else {
            $this->_setMessage(__('Invalid auth request.'), 'error');
            $authModel->cleanup();

            $this->_forceRedirect('checkout');
        }
    }
}
