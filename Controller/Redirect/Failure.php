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
     * @var \Novalnet\Payment\Helper\Request
     */
    protected $novalnetRequestHelper;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serializer;

    /**
     * @var \Novalnet\Payment\Logger\NovalnetLogger
     */
    private $novalnetLogger;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Sales\Model\Order $salesModel
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\HTTP\Client\Curl $clientFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     * @param NNConfig $novalnetConfig
     * @param \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\Order $salesModel,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\HTTP\Client\Curl $clientFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer,
        NNConfig $novalnetConfig,
        \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
    ) {
        parent::__construct($context);
        $this->salesModel = $salesModel;
        $this->checkoutSession = $checkoutSession;
        $this->clientFactory = $clientFactory;
        $this->messageManager = $messageManager;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->jsonHelper = $jsonHelper;
        $this->serializer = $serializer;
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetLogger = $novalnetLogger;
    }

    /**
     * Handles Novalnet redirect failure process
     *
     * @param  none
     * @return void
     */
    public function execute()
    {
        $checkSumResponse = $this->getRequest()->getParams();
        
        // Loads order model by loading the Increment Id
        $order = $this->salesModel->loadByIncrementId($checkSumResponse['order_no']);
        $storeId = '';
        if ($order) {
            $storeId = $order->getStoreId();
        }

        $this->novalnetLogger->notice('Customer return from Novalnet to shop (Novalnet redirect failure controller). Novalnet transaction ID: ' . $checkSumResponse['tid']);

        $this->clientFactory->setHeaders($this->novalnetRequestHelper->getRequestHeaders(false, $storeId));
        $this->clientFactory->post(
            NNConfig::NOVALNET_TRANSACTION_DETAIL_URL,
            $this->jsonHelper->jsonEncode(
                [
                    'transaction' => ['tid'  => $checkSumResponse['tid']],
                    'custom'      => ['lang' => $this->novalnetRequestHelper->getDefaultLanguage()]
                ]
            )
        );

        $response = new \Magento\Framework\DataObject();
        $response->setData(json_decode($this->clientFactory->getBody(), true));

        // Loads order model by loading the Increment Id
        $orderId = !empty($checkSumResponse['order_no']) ? $checkSumResponse['order_no'] : $response->getData('transaction/order_no');
        $this->novalnetLogger->notice('Get order no' . $orderId);
        $order = $this->salesModel->loadByIncrementId($orderId);

        $this->novalnetLogger->notice('Order loaded successfully ' . $order->getIncrementId());
        $this->novalnetRequestHelper->saveCanceledOrder($response, $order);
        $this->messageManager->addErrorMessage(__($response->getData('result/status_text')));
        $resultRedirect = $this->resultRedirectFactory->create();

        // Restore the cart items
        if ($this->novalnetConfig->getGlobalConfig('restore_cart')) {
            $this->novalnetRequestHelper->restoreQuote($orderId);
            $this->novalnetLogger->notice('Successfully restored the cart items' . $order->getIncrementId());
            $resultRedirect->setPath('checkout/cart');
        } else {
            $resultRedirect->setPath('checkout/onepage/failure');
        }

        return $resultRedirect;
    }
}
