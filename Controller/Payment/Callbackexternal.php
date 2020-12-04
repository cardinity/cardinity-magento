<?php

namespace Cardinity\Payment\Controller\Payment;

class Callbackexternal extends \Cardinity\Payment\Controller\Payment
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
            $order = $orderModel->load($externalModel->getOrderId());

            
            $message = '';

            $postData = $_POST;//$this->getRequest()->getPost();
            
            ksort($postData);

            foreach($postData as $key => $value) {
                if ($key == 'signature') continue;
                $message .= $key.$value;
            }

            $signature = hash_hmac('sha256', $message, $externalModel->getSecret());


            if ($signature == $postData['signature']) {
                $this->_log('Post data valid');

                $this->_log(print_r($postData, true));
                
                if($postData['status'] == "approved"){
                    $this->_log('Payment successful ID:'. $postData['id'], $order->getRealOrderId());
    
                    $externalModel->setPaymentId($postData['id']);
                    $externalModel->setSuccess(true);

                    /*$authModel = $this->_getAuthModel();
                    $authModel->cleanup();

                    $authModel->setOrderId($order->getId());
                    $authModel->setRealOrderId($order->getRealOrderId());
                    $authModel->setPaymentId($postData['id']);
                    $authModel->setSuccess(true);*/


                    $this->_success($external = true);
    
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

        /*
        $authModel = $this->_getAuthModel();
        $orderModel = $this->_getOrderModel();

        $order = $orderModel->load($authModel->getOrderId());
        $pares = $this->getRequest()->getPost('PaRes');
        $orderId = $this->getRequest()->getPost('MD');

        if (!$order->getId()
            || $order->getId() !== $authModel->getOrderId()
            || $order->getRealOrderId() !== $orderId
            || $order->getState() !== $orderModel::STATE_PENDING_PAYMENT
        ) {
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
        }*/
    }
}

