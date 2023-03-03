<?php

namespace Cardinity\Magento\Controller\Payment;

class Callbackexternal extends \Cardinity\Magento\Controller\Payment
{

    public function execute()
    {
        $this->_log("Executing callback external");


        if (!$this->getRequest()->isPost() || empty($this->getRequest()->getPost('signature')) ) {
            $this->_log('Invalid callback notification received. Wrong request type or missing mandatory parameters.');

            return $this->_forceRedirect('checkout');
        }else{

            //callback from external

            $externalModel = $this->_getExternalModel();
            $orderModel = $this->_getOrderModel();

            $message = '';

            $postData = $this->getRequest()->getPost()->toArray();

            ksort($postData);

            foreach($postData as $key => $value) {
                if ($key == 'signature') continue;
                $message .= $key.$value;
            }


            $order = $orderModel->load($postData['description']);

            $projectSecret = $this->scopeConfig->getValue('payment/cardinity/cardinity_project_secret', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);


            $signature = hash_hmac('sha256', $message, $projectSecret) ;


            if ($signature == $postData['signature']) {
                $this->_log('Post data valid');

                $externalModel->setOrderId($postData['description']);

                $this->_log(print_r($postData, true));

                if($postData['status'] == "approved"){
                    $this->_log('Payment successful ID:'. $postData['id'], $order->getRealOrderId());

                    $externalModel->setPaymentId($postData['id']);
                    $externalModel->setSuccess(true);


                    //$this->_success($external = true);
                    $this->_success(true);

                    $this->_forceRedirect('checkout/onepage/success');
                }else{
                    $this->_log('Payment failed', $order->getRealOrderId());

                    $externalModel->setFailure(true);
                    $this->_cancel();

                    $this->_forceRedirect('checkout/cart');
                }
            } else {
                $this->_log('Post data invalid');
                $this->_log('Payment failed', $order->getRealOrderId());

                $externalModel->setFailure(true);
                $this->_cancel();

                $this->_forceRedirect('checkout/cart');
            }
        }

    }

}
