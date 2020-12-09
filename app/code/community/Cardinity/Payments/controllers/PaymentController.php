<?php

class Cardinity_Payments_PaymentController extends Mage_Core_Controller_Front_Action
{

    public function authAction()
    {
        $this->_log('called ' . __METHOD__);

        $authModel = $this->_getAuthModel();

        if ($authModel && $authModel->getThreeDSecureNeeded()) {
            // If payment requires 3D Secure Authentication display redirect form
            $this->loadLayout();

            $block = $this->getLayout()->createBlock('Cardinity_Payments_Block_Auth');
            $this->getLayout()->getBlock('content')->append($block);

            $this->renderLayout();
        } else {
            $this->_log('authentication not required. Invalid request.', Zend_Log::ERR);

            $authModel->cleanup();

            return $this->_forceRedirect('checkout/onepage');
        }
    }

    public function callbackAction()
    {

        $this->_log('called ' . __METHOD__);

        if (!$this->getRequest()->isPost() || empty($_POST['PaRes']) || empty($_POST['MD'])) {
            $this->_log('invalid callback notification received. Wrong request type or missing mandatory parameters.', Zend_Log::ERR);
            return $this->_forceRedirect('checkout/onepage');
        }

        $pares = $_POST['PaRes'];
        $orderId = $_POST['MD'];

        $authModel = $this->_getAuthModel();
        $order = Mage::getModel('sales/order')->load($authModel->getOrderId());
        if (!$order->getId()
            || $order->getId() !== $authModel->getOrderId()
            || $order->getRealOrderId() !== $orderId
            || $order->getState() !== Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
        ) {
            $this->_log('invalid callback notification received. Order validation failed.');
            $authModel->cleanup();
            return $this->_forceRedirect('checkout/onepage');
        }

        // finalize payment
        $model = Mage::getModel('cardinity/payment');
        $this->_log('attempting to finalize payment');
        $finalize = $model->finalize($authModel->getPaymentId(), $pares);
        if ($finalize) {

            $this->_log('payment finalized successfully');
            $authModel->setSuccess(true);
            $this->_success();

            return $this->_forceRedirect('checkout/onepage/success');
        } else {

            $this->_log('payment finalization failed');
            $authModel->setFailure(true);
            $this->_cancel();

            return $this->_forceRedirect('checkout/onepage/failure');
        }
    }

    public function  successAction()
    {
        $this->_log('called ' . __METHOD__);

        $authModel = $this->_getAuthModel();

        if ($authModel->getSuccess() && $this->_success()) {
            $this->_log('order marked as paid');
            $authModel->cleanup();
            return $this->_forceRedirect('checkout/onepage/success');
        }
        $this->_log('invalid success request', Zend_Log::ERR);
        $authModel->cleanup();
        return $this->_forceRedirect('checkout/onepage');

    }

    public function failureAction()
    {
        $this->_log('called ' . __METHOD__);

        $authModel = $this->_getAuthModel();

        if ($authModel->getFailure()) {
            $this->_cancel();
            $this->_log('order cancelled');
            $authModel->cleanup();
            return $this->_forceRedirect('checkout/onepage/failure');
        }
        else {
            $this->_log('invalid failure request', Zend_Log::ERR);
            $authModel->cleanup();
            return $this->_forceRedirect('checkout/onepage');
        }

    }

    private function _success()
    {

        $authModel = $this->_getAuthModel();

        $order = Mage::getModel('sales/order')->load($authModel->getOrderId());
        $state = $order->getState();

        if ($state == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
            $this->_createInvoice($order);
            $msg = 'Payment completed via Cardinity. ID: ' . $authModel->getPaymentId();
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $msg, false);
            $order->sendNewOrderEmail();
            $order->setEmailSent(true);
            $order->save();

            return true;

        }

        return false;
    }

    /**
     * When a customer cancel payment from api
     */
    protected function _cancel()
    {
        $authModel = $this->_getAuthModel();
        $order = Mage::getModel('sales/order')->load($authModel->getOrderId());

        if ($order->getId()) {
            $state = $order->getState();
            if ($state == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
                $order->cancel()->save();
                Mage::getSingleton('core/session')->addNotice(Mage::helper('payment')->__('Your order has been canceled.'));
            }
        }
    }

    /**
     * Builds invoice for order.
     * @param $orderObj
     * @return bool
     */
    private function _createInvoice($orderObj)
    {
        if (!$orderObj->canInvoice()) {
            return false;
        }
        $invoice = $orderObj->prepareInvoice();
        $invoice->register();
        if ($invoice->canCapture()) {
            $invoice->capture();
        }
        $invoice->save();
        $orderObj->addRelatedObject($invoice);

        return $invoice;
    }

    private function _log($message, $level = Zend_Log::DEBUG)
    {
        Mage::log('Cardinity Gateway: ' . $message, $level);
    }

    private function _forceRedirect($url)
    {
        return $this->getResponse()->setRedirect(Mage::getUrl($url, array('_secure' => true)));
    }

    private function _getAuthModel()
    {
        return Mage::getModel('cardinity/auth');
    }

}
