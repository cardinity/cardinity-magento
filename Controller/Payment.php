<?php

namespace Cardinity\Payments\Controller;

abstract class Payment extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        parent::__construct(
            $context
        );

        $this->_objectManager = $context->getObjectManager();
        $this->_messageManager = $context->getMessageManager();
        $this->_logger = $logger;
        $this->_pageFactory = $pageFactory;
    }

    protected function _forceRedirect($url)
    {
        $this->_redirect($url, ['_secure' => true]);
    }

    protected function _getOrderModel()
    {
        return $this->_objectManager->create('Magento\Sales\Model\Order');
    }

    protected function _getAuthModel()
    {
        return $this->_objectManager->create('Cardinity\Payments\Model\AuthModel');
    }

    protected function _getPaymentModel()
    {
        return $this->_objectManager->create('Cardinity\Payments\Model\PaymentModel');
    }

    protected function _setMessage($message, $type)
    {
        switch ($type) {
            case 'notice':
                $this->_messageManager->addNoticeMessage($message);
                break;
            case 'success':
                $this->_messageManager->addSuccessMessage($message);
                break;
            case 'warning':
                $this->_messageManager->addWarningMessage($message);
                break;
            case 'error':
                $this->_messageManager->addErrorMessage($message);
                break;
        }
    }

    protected function _log($message)
    {
        $this->_logger->info('Cardinity Gateway: ' . $message);
    }

    protected function _cancel()
    {
        $this->_log('called ' . __METHOD__);

        $authModel = $this->_getAuthModel();
        $orderModel = $this->_getOrderModel();

        $order = $orderModel->load($authModel->getOrderId());
        $state = $order->getState();

        if ($order->getId() && $state == $orderModel::STATE_PENDING_PAYMENT) {
            $order->cancel()->save();
            $this->_setMessage(__('Your order has been canceled.'), 'notice');
        } else {
            $this->_setMessage(__('Unexpected error occurred. Please contact support.'), 'error');
        }
    }

    protected function _success()
    {
        $this->_log('called ' . __METHOD__);

        $authModel = $this->_getAuthModel();
        $orderModel = $this->_getOrderModel();

        $order = $orderModel->load($authModel->getOrderId());
        $state = $order->getState();

        if ($order->getId() && $state == $orderModel::STATE_PENDING_PAYMENT) {
            $order->setState($orderModel::STATE_PROCESSING);
            $order->setStatus($orderModel::STATE_PROCESSING);
            $order->setEmailSent(true);
            $order->save();

            $this->_createInvoice($order);

            return true;
        }

        return false;
    }

    protected function _createInvoice($orderObj)
    {
        $this->_log('called ' . __METHOD__);

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
}
