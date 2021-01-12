<?php

namespace Cardinity\Payment\Controller\Payment;

class Callbackv2 extends \Cardinity\Payment\Controller\Payment
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

        $order = $orderModel->load($authModel->getOrderId());
        $cres = $this->getRequest()->getPost('cres');
        $orderId = $this->getRequest()->getPost('threeDSSessionData');

        if (!$order->getId()
            || $order->getId() !== $authModel->getOrderId()
            || $order->getRealOrderId() !== $orderId
            || $order->getState() !== $orderModel::STATE_PENDING_PAYMENT
        ) {
            
            $this->_log('Invalid callback data received. Order validation failed.');
           
            $authModel->cleanup();

            return $this->_forceRedirect('checkout');
        }

        $this->_log('Finalizing payment', $order->getRealOrderId());

        $model = $this->_getPaymentModel();
        $finalize = $model->finalize($authModel->getPaymentId(), $cres, true);


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
