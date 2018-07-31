<?php

namespace Cardinity\Magento\Controller\Payment;

class Callback extends \Cardinity\Magento\Controller\Payment
{
    public function execute()
    {
        if (!$this->getRequest()->isPost() || empty($this->getRequest()->getPost('PaRes')) || empty($this->getRequest()->getPost('MD'))) {
            $this->_log('Invalid callback notification received. Wrong request type or missing mandatory parameters.');

            return $this->_forceRedirect('checkout');
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
            $this->_log('Invalid callback notification received. Order validation failed.');
            $authModel->cleanup();

            return $this->_forceRedirect('checkout');
        }

        $this->_log('Finalizing payment', $order->getRealOrderId());

        $model = $this->_getPaymentModel();
        $finalize = $model->finalize($authModel->getPaymentId(), $pares);
        if ($finalize) {
            $this->_log('Payment finalized successfully', $order->getRealOrderId());

            $authModel->setSuccess(true);
            $this->_success();

            $this->_forceRedirect('checkout/onepage/success');
        } else {
            $this->_log('Payment finalization failed', $order->getRealOrderId());

            $authModel->setFailure(true);
            $this->_cancel();

            $this->_forceRedirect('checkout/cart');
        }
    }
}
