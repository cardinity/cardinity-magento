<?php

namespace Cardinity\Payments\Controller\Payment;

class Callback extends \Cardinity\Payments\Controller\Payment
{
    public function execute()
    {
        $this->_log('called ' . __METHOD__);

        if (!$this->getRequest()->isPost() || empty($this->getRequest()->getPost('PaRes')) || empty($this->getRequest()->getPost('MD'))) {
            $this->_log('invalid callback notification received. Wrong request type or missing mandatory parameters.');

            $this->_forceRedirect('checkout');
        }

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
            $this->_log('invalid callback notification received. Order validation failed.');
            $authModel->cleanup();

            $this->_forceRedirect('checkout');
        }

        // finalize payment
        $model = $this->_getPaymentModel();
        $this->_log('attempting to finalize payment');
        $finalize = $model->finalize($authModel->getPaymentId(), $pares);
        if ($finalize) {
            $this->_log('payment finalized successfully');
            $authModel->setSuccess(true);
            $this->_success();

            $this->_forceRedirect('checkout/onepage/success');
        } else {
            $this->_log('payment finalization failed');
            $authModel->setFailure(true);
            $this->_cancel();

            $this->_forceRedirect('checkout/onepage/failure');
        }
    }
}
