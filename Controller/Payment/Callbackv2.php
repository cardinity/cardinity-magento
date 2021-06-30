<?php

namespace Cardinity\Magento\Controller\Payment;

class Callbackv2 extends \Cardinity\Magento\Controller\Payment
{
    public function execute()
    {
        $this->_log("Executing callback v2");
        
        if (!$this->getRequest()->isPost() || empty($this->getRequest()->getPost('cres')) || empty($this->getRequest()->getPost('threeDSSessionData'))) {
            $this->_log('Invalid callback notification received. Wrong request type or missing mandatory parameters.');            

            $this->_log('redirecting buyer to authv1 page');
            $this->_forceRedirect('cardinity/payment/auth');

            return $this->_forceRedirect('checkout');
        }else{

            $this->_log("Cres : ".$this->getRequest()->getPost('cres'));
            $this->_log("TDSData : ".$this->getRequest()->getPost('threeDSSessionData'));            
        }

        $authModel = $this->_getAuthModel();
        $orderModel = $this->_getOrderModel();

        
        $cres = $this->getRequest()->getPost('cres');
        $threedsdata = explode("_", $this->getRequest()->getPost('threeDSSessionData'));
        $orderId = $threedsdata[0];
        $paymentId = $threedsdata[1];
        $order = $orderModel->load($orderId);

        if(!$authModel->getOrderId()){ //if session lost, repopulate from callback data
            $authModel->setOrderId($orderId);
            $authModel->setPaymentId($paymentId);
        }

        if(!$order->getId()){
            $this->_log('Invalid callback data received. Order validation failed.');
            return $this->_forceRedirect('checkout');
        }


        $this->_log('Finalizing payment', $order->getRealOrderId());

        $model = $this->_getPaymentModel();
        $finalize = $model->finalize($paymentId, $cres, true);

 
        if ($finalize) {

            $status = $finalize->getStatus(); 
            if($status == "approved"){
                $this->_log('Payment finalized successfully', $order->getRealOrderId());

                $authModel->setThreeDSecureVHistory('3D Secure version 2');                
                $authModel->setSuccess(true);
                $this->_success();
    
                $this->_forceRedirect('checkout/onepage/success');                
            }else if($status == "pending"){         
                $this->_log('Payment finalization pending, redirecting to authv1 page', $order->getRealOrderId());

                $authModel->setThreeDSecureV2Needed(false);

                $authData = $finalize->getAuthorizationInformation();
                $authModel->setThreeDSecureNeeded(true);
                $authModel->setUrl($authData->getUrl());
                $authModel->setData($authData->getData());   

                $this->_forceRedirect('cardinity/payment/auth');
            }else{                
                $this->_log('Payment finalization failed', $order->getRealOrderId());

                $authModel->setFailure(true);
                $this->_cancel();

                $this->_forceRedirect('checkout/cart');
            }  
          
        } else {
            $this->_log('Unable to finalize payment, error occured', $order->getRealOrderId());

            $authModel->setFailure(true);
            $this->_cancel();

            $this->_forceRedirect('checkout/cart');
        }
    }
}
