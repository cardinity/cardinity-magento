<?php

namespace Cardinity\Payment\Controller\Payment;

class Auth extends \Cardinity\Payment\Controller\Payment
{
    public function execute()
    {
        $authModel = $this->_getAuthModel();

        if ($authModel && ($authModel->getThreeDSecureNeeded() || $authModel->getThreeDSecureV2Needed())) {
            return $this->_pageFactory->create();
        }else {
            $this->_setMessage(__('Invalid auth request.'), 'error');
            $authModel->cleanup();

            $this->_forceRedirect('checkout');
        }
    }
}
