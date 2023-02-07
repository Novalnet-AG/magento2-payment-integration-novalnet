<?php
/**
 * Novalnet payment extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Novalnet End User License Agreement
 * that is bundled with this package in the file LICENSE.txt
 *
 * DISCLAIMER
 *
 * If you wish to customize Novalnet payment extension for your needs,
 * please contact technic@novalnet.de for more information.
 *
 * @category   Novalnet
 * @package    Novalnet_Payment
 * @copyright  Copyright (c) Novalnet AG
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */
namespace Novalnet\Payment\Controller\Redirect;

use Novalnet\Payment\Model\NNConfig;
use Novalnet\Payment\Model\Ui\ConfigProvider;

class Success extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $salesModel;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $clientFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    protected $novalnetHelper;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var NNConfig
     */
    protected $novalnetConfig;

    /**
     * @var \Novalnet\Payment\Logger\NovalnetLogger
     */
    private $novalnetLogger;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Sales\Model\Order $salesModel
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\HTTP\Client\Curl $clientFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param NNConfig $novalnetConfig
     * @param \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\Order $salesModel,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\HTTP\Client\Curl $clientFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        NNConfig $novalnetConfig,
        \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
    ) {
        parent::__construct($context);
        $this->salesModel = $salesModel;
        $this->clientFactory = $clientFactory;
        $this->checkoutSession = $checkoutSession;
        $this->messageManager = $messageManager;
        $this->novalnetHelper = $novalnetHelper;
        $this->jsonHelper = $jsonHelper;
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetLogger = $novalnetLogger;
    }

    /**
     * Handles Novalnet redirect success process
     *
     * @return mixed
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $checkSumResponse = $this->getRequest()->getParams();
        // Loads order model by loading the Increment Id
        $order = $this->salesModel->loadByIncrementId($checkSumResponse['order_no']);
        $storeId = '';
        if ($order) {
            $storeId = $order->getStoreId();
        }
        $this->novalnetLogger->notice('Customer return from Novalnet to shop (Novalnet redirect success controller). Novalnet transaction ID: ' . $checkSumResponse['tid']);
        $payment = $order->getPayment();
        $lastTransId = $payment->getLastTransId();
        if (!empty($lastTransId)) {
            $this->novalnetLogger->notice('Callback already executed for ' . $order->getIncrementId());
            $resultRedirect->setPath('checkout/onepage/success');
            return $resultRedirect;
        }

        $this->clientFactory->setHeaders($this->novalnetHelper->getRequestHeaders(false, $storeId));
        $this->clientFactory->post(
            NNConfig::NOVALNET_TRANSACTION_DETAIL_URL,
            $this->jsonHelper->jsonEncode(
                [
                    'transaction' => ['tid'  => $checkSumResponse['tid']],
                    'custom'      => ['lang' => $this->novalnetHelper->getDefaultLanguage()]
                ]
            )
        );

        $response = new \Magento\Framework\DataObject();
        $responseBody = ($this->clientFactory->getBody()) ? json_decode($this->clientFactory->getBody(), true) : [];
        $response->setData($responseBody);

        // Loads order model by loading the Increment Id
        $order = $this->salesModel->loadByIncrementId($response->getData('transaction/order_no'));

        $this->novalnetLogger->notice('Order loaded successfully ' . $order->getIncrementId());

        $payment = $order->getPayment();
        $paymentMethodCode = $payment->getMethodInstance()->getCode();
        $additionalData = (!empty($payment->getAdditionalData())) ? json_decode($payment->getAdditionalData(), true) : [];

        // Checks payment hash on return
        if (!$this->novalnetHelper->checkPaymentHash($checkSumResponse, $additionalData)) {
            if ($this->novalnetConfig->getGlobalConfig('restore_cart')) {
                $this->novalnetHelper->restoreQuote($order->getIncrementId());
                $this->novalnetLogger->notice('Successfully restored the cart items' . $order->getIncrementId());
                $resultRedirect->setPath('checkout/cart');
            } else {
                $resultRedirect->setPath('checkout/onepage/failure');
            }
            $errorMessage = 'While redirecting some data has been changed. The hash check failed.';
            $this->messageManager->addErrorMessage(__($errorMessage));
            $this->novalnetLogger->notice($errorMessage);

            return $resultRedirect;
        }

        // Process in handling on redirect payment return success
        if (!$this->novalnetHelper->checkReturnedData($response, $order, $payment)) {
            if ($this->novalnetConfig->getGlobalConfig('restore_cart')) {
                $this->novalnetHelper->restoreQuote($order->getIncrementId());
                $this->novalnetLogger->notice('Successfully restored the cart items' . $order->getIncrementId());
                $resultRedirect->setPath('checkout/cart');
            } else {
                $resultRedirect->setPath('checkout/onepage/failure');
            }
            $this->messageManager->addErrorMessage(__($response->getData('result/status_text')));
            $this->novalnetLogger->notice('Failure for check returned data. The status text is: ' . $response->getData('result/status_text'));

            return $resultRedirect;
        }

        if (!empty($order) && empty($this->checkoutSession->getLastQuoteId())) {
            $this->checkoutSession->setLastQuoteId($order->getQuoteId());
        }

        if (!empty($order) && empty($this->checkoutSession->getLastSuccessQuoteId())) {
            $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
        }

        if (!empty($order) && empty($this->checkoutSession->getLastOrderId())) {
            $this->checkoutSession->setLastOrderId($order->getId());
        }

        // Store payment data if token exist
        if ($response->getData('transaction/payment_data/token')) {
            $this->novalnetHelper->savePaymentToken($order, $paymentMethodCode, $response);
            $this->novalnetLogger->notice('Stored payment data');
        }

        $resultRedirect->setPath('checkout/onepage/success');

        return $resultRedirect;
    }
}
