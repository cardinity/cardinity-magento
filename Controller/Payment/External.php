<?php

namespace Cardinity\Payment\Controller\Payment;

class External extends \Cardinity\Payment\Controller\Payment
{
    public function execute()
    {

        return $this->_pageFactory->create();
/*
        $externalModel = $this->_getExternalModel();

        if ($externalModel) {
            return $this->_pageFactory->create();
        }else {
            $this->_setMessage(__('Invalid auth request.'), 'error');
            $externalModel->cleanup();

            $this->_forceRedirect('checkout');
        }*/
    }
}
