<?php

namespace Cardinity\Magento\Controller\Payment;

class Callback extends \Cardinity\Magento\Controller\Payment
{
    public function execute()
    {
        $this->_log("Executing callback v1");
        if (!$this->getRequest()->isPost() || empty($this->getRequest()->getPost('PaRes')) || empty($this->getRequest()->getPost('MD'))) {
            $this->_log('Invalid callback notification received. Wrong request type or missing mandatory parameters.');

            return $this->_forceRedirect('checkout');
        }

        $authModel = $this->_getAuthModel();
        $orderModel = $this->_getOrderModel();

        $pares = $this->getRequest()->getPost('PaRes');
        $md = explode("_",$this->getRequest()->getPost('MD'));
        $orderId = $md[0];
        $paymentId = $md[1];

        $order = $orderModel->load($orderId);

        
        if(!$authModel->getOrderId()){ //if session lost, repopulate from callback data
            $authModel->setOrderId($orderId);
            $authModel->setPaymentId($paymentId);
        }


        if (!$order->getId() || $order->getState() !== $orderModel::STATE_PENDING_PAYMENT ) {
            $this->_log('Invalid callback notification received. Order validation failed.');
            $authModel->cleanup();

            return $this->_forceRedirect('checkout');
        }

        $this->_log('Finalizing payment', $order->getRealOrderId());

        $model = $this->_getPaymentModel();
        $finalize = $model->finalize($authModel->getPaymentId(), $pares);
        
        if ($finalize) {
            $status = $finalize->getStatus(); 
            if($status == "approved"){
                $this->_log('Payment finalized successfully', $order->getRealOrderId());

                $authModel->setThreeDSecureVHistory('3D Secure version 1');
                $authModel->setSuccess(true);
                $this->_success();

                $this->_forceRedirect('checkout/onepage/success');                    
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
