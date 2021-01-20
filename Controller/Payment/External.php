<?php

namespace Cardinity\Magento\Controller\Payment;

class External extends \Cardinity\Magento\Controller\Payment
{
    public function execute()
    {
        $externalModel = $this->_getExternalModel();

        $externalModel->dump();
        if ($externalModel) {
            return $this->_pageFactory->create();
        }else {
            $this->_setMessage(__('Invalid auth request.'), 'error');
            $externalModel->cleanup();

            $this->_forceRedirect('checkout');
        }
    }
}
