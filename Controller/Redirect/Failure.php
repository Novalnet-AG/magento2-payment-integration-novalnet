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

class Failure extends \Magento\Framework\App\Action\Action
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
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    protected $novalnetHelper;

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
        NNConfig $novalnetConfig,
        \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
    ) {
        parent::__construct($context);
        $this->salesModel = $salesModel;
        $this->checkoutSession = $checkoutSession;
        $this->clientFactory = $clientFactory;
        $this->messageManager = $messageManager;
        $this->novalnetHelper = $novalnetHelper;
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetLogger = $novalnetLogger;
    }

    /**
     * Handles Novalnet redirect failure process
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
        $payment = $order->getPayment();
        $lastTransId = $payment->getLastTransId();
        if (!empty($lastTransId)) {
            $this->novalnetLogger->notice('Callback already executed for ' . $order->getIncrementId());
            $resultRedirect->setPath('checkout/onepage/failure');
            return $resultRedirect;
        }

        if (isset($checkSumResponse['tid'])) {
            $this->novalnetLogger->notice('Customer return from Novalnet to shop (Novalnet redirect failure controller). Novalnet transaction ID: ' . $checkSumResponse['tid']);
            $this->clientFactory->setHeaders($this->novalnetHelper->getRequestHeaders(false, $storeId));
            $this->clientFactory->post(
                NNConfig::NOVALNET_TRANSACTION_DETAIL_URL,
                $this->novalnetHelper->jsonEncode(
                    [
                        'transaction' => ['tid'  => $checkSumResponse['tid']],
                        'custom'      => ['lang' => $this->novalnetHelper->getDefaultLanguage()]
                    ]
                )
            );
        }

        $response = new \Magento\Framework\DataObject();
        $responseBody = ($this->clientFactory->getBody()) ? $this->novalnetHelper->jsonDecode($this->clientFactory->getBody()) : [];
        $response->setData($responseBody);

        // Loads order model by loading the Increment Id
        $orderId = !empty($checkSumResponse['order_no']) ? $checkSumResponse['order_no'] : $response->getData('transaction/order_no');
        $this->novalnetLogger->notice('Get order no' . $orderId);
        $order = $this->salesModel->loadByIncrementId($orderId);

        $this->novalnetLogger->notice('Order loaded successfully ' . $order->getIncrementId());
        $this->novalnetHelper->saveCanceledOrder($response, $order);
        $statusText = !empty($checkSumResponse['status_text']) ? $checkSumResponse['status_text'] : $response->getData('result/status_text');
        $this->messageManager->addErrorMessage(__($statusText));

        // Restore the cart items
        if ($this->novalnetConfig->getGlobalConfig('restore_cart')) {
            $this->novalnetHelper->restoreQuote($orderId);
            $this->novalnetLogger->notice('Successfully restored the cart items' . $order->getIncrementId());
            $resultRedirect->setPath('checkout/cart');
        } else {
            $resultRedirect->setPath('checkout/onepage/failure');
        }

        return $resultRedirect;
    }
}
