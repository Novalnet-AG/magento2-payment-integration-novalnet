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
namespace Novalnet\Payment\Model;

use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;
use Novalnet\Payment\Model\Ui\ConfigProvider;

class CallbackRepository implements \Novalnet\Payment\Api\CallbackRepositoryInterface
{
    /**
     * @var array
     */
    private $mandatoryParams = [
        'event' => [
            'type',
            'checksum',
            'tid'
        ],
        'merchant' => [
            'vendor',
            'project'
        ],
        'transaction' => [
            'tid',
            'payment_type',
            'status',
        ],
        'result' => [
            'status'
        ],
    ];

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $requestInterface;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    private $novalnetConfig;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    private $novalnetHelper;

    /**
     * @var Order
     */
    private $salesOrderModel;

    /**
     * @var \Novalnet\Payment\Logger\NovalnetLogger
     */
    private $novalnetLogger;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction
     */
    private $transactionModel;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    private $pricingHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timeZone;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    private $currencyModel;

    /**
     * @var \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader
     */
    private $creditmemoLoader;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var \Novalnet\Payment\Model\TransactionStatus
     */
    private $transactionStatusModel;

    /**
     * @var \Novalnet\Payment\Model\Callback
     */
    private $callbackModel;

    /**
     * @var \Magento\Sales\Model\Service\CreditmemoService
     */
    private $creditmemoService;

    /**
     * @var \Magento\Sales\Model\Order\CreditmemoFactory
     */
    private $creditMemoFactory;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    private $paymentHelper;

    /**
     * @var \Magento\Sales\Model\Order\Address\Renderer
     */
    private $salesOrderAddressRenderer;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    private $orderEmailSender;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    private $inlineTranslation;

    /**
     * @var mixed
     */
    private $additionalMessage;

    /**
     * @var mixed
     */
    private $callbackMessage;

    /**
     * @var mixed
     */
    private $response;

    /**
     * @var mixed
     */
    private $order;

    /**
     * @var array
     */
    private $eventData;

    /**
     * @var string
     */
    private $eventType;

    /**
     * @var int
     */
    private $eventTid;

    /**
     * @var int
     */
    private $parentTid;

    /**
     * @var string
     */
    private $storeFrontendName;

    /**
     * @var string
     */
    private $emailSubject;

    /**
     * @var string
     */
    private $currentTime;

    /**
     * @var mixed
     */
    private $orderNo;

    /**
     * @var mixed
     */
    private $testMode;

    /**
     * @var mixed
     */
    private $emailBody;

    /**
     * @var mixed
     */
    private $storeId;

    /**
     * @var string
     */
    private $paymentAccessKey;

    /**
     * @var mixed
     */
    private $payment;

    /**
     * @var string
     */
    private $paymentCode;

    /**
     * @var string
     */
    private $paymentType;

    /**
     * @var mixed
     */
    private $paymentTxnId;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $emailFromAddr;

    /**
     * @var string
     */
    private $emailFromName;

    /**
     * @var string
     */
    private $emailToName;

    /**
     * @var string
     */
    private $emailToAddr;

    /**
     * Constructor
     *
     * @param Order $salesOrderModel
     * @param \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transactionModel
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Directory\Model\Currency $currencyModel
     * @param \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader $creditmemoLoader
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
     * @param \Novalnet\Payment\Model\Callback $callbackModel
     * @param \Magento\Sales\Model\Service\CreditmemoService $creditmemoService
     * @param \Magento\Sales\Model\Order\CreditmemoFactory $creditMemoFactory
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Sales\Model\Order\Address\Renderer $salesOrderAddressRenderer
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderEmailSender
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\RequestInterface $requestInterface
     */
    public function __construct(
        Order $salesOrderModel,
        \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger,
        \Magento\Sales\Model\Order\Payment\Transaction $transactionModel,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Directory\Model\Currency $currencyModel,
        \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader $creditmemoLoader,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel,
        \Novalnet\Payment\Model\Callback $callbackModel,
        \Magento\Sales\Model\Service\CreditmemoService $creditmemoService,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditMemoFactory,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\Order\Address\Renderer $salesOrderAddressRenderer,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderEmailSender,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\RequestInterface $requestInterface
    ) {
        $this->salesOrderModel = $salesOrderModel;
        $this->novalnetLogger = $novalnetLogger;
        $this->transactionModel = $transactionModel;
        $this->pricingHelper = $pricingHelper;
        $this->dateTime = $dateTime;
        $this->timeZone = $timeZone;
        $this->transportBuilder = $transportBuilder;
        $this->currencyModel = $currencyModel;
        $this->creditmemoLoader = $creditmemoLoader;
        $this->invoiceSender = $invoiceSender;
        $this->transactionStatusModel = $transactionStatusModel;
        $this->callbackModel = $callbackModel;
        $this->creditmemoService = $creditmemoService;
        $this->creditMemoFactory = $creditMemoFactory;
        $this->paymentHelper = $paymentHelper;
        $this->salesOrderAddressRenderer = $salesOrderAddressRenderer;
        $this->orderEmailSender = $orderEmailSender;
        $this->inlineTranslation = $inlineTranslation;
        $this->novalnetHelper = $novalnetHelper;
        $this->novalnetConfig = $novalnetConfig;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->requestInterface = $requestInterface;
    }

    /**
     * Novalnet payment callback
     *
     * @api
     * @return string
     */
    public function callback()
    {
        if ($this->assignGlobalParams()) {
            if ($this->eventType == 'PAYMENT') {
                if (empty($this->paymentTxnId)) {
                    $this->handleCommunicationFailure();
                } else {
                    $this->displayMessage('Novalnet Callback executed. The Transaction ID already existed');
                }
            } elseif ($this->eventType == 'TRANSACTION_CAPTURE') {
                $this->transactionCapture();
            } elseif ($this->eventType == 'TRANSACTION_CANCEL') {
                $this->transactionCancellation();
            } elseif ($this->eventType == 'TRANSACTION_REFUND') {
                $this->refundProcess();
            } elseif ($this->eventType == 'TRANSACTION_UPDATE') {
                $this->transactionUpdate();
            } elseif ($this->eventType == 'CREDIT') {
                $this->creditProcess();
            } elseif ($this->eventType == 'INSTALMENT') {
                $this->instalmentProcess();
            } elseif ($this->eventType == 'INSTALMENT_CANCEL') {
                $this->instalmentCancelProcess();
            } elseif (in_array($this->eventType, ['CHARGEBACK', 'RETURN_DEBIT', 'REVERSAL'])) {
                $this->chargebackProcess();
            } elseif (in_array($this->eventType, ['PAYMENT_REMINDER_1', 'PAYMENT_REMINDER_2'])) {
                $this->paymentReminderProcess();
            } elseif ($this->eventType == 'SUBMISSION_TO_COLLECTION_AGENCY') {
                $this->collectionProcess();
            } else {
                $this->displayMessage("The webhook notification has been received for the unhandled EVENT type($this->eventType)");
            }
        }

        return $this->additionalMessage . $this->callbackMessage;
    }

    /**
     * Assign Global params for callback process
     *
     * @return boolean
     */
    private function assignGlobalParams()
    {
        try {
            $this->eventData = (!empty($this->requestInterface->getContent())) ? $this->novalnetHelper->jsonDecode($this->requestInterface->getContent()) : [];
        } catch (\Exception $e) {
            $this->novalnetLogger->error("Received data is not in the JSON format $e");
            $this->displayMessage("Received data is not in the JSON format $e");
        }

        // Get callback setting params (from shop admin)
        $this->testMode = $this->novalnetConfig->getMerchantScriptConfig('test_mode');
        $this->emailBody = '';

        // Check whether the IP address is authorized
        if (!$this->checkIP()) {
            return false;
        }

        if (empty($this->eventData)) {
            $this->displayMessage('No params passed over!');
            return false;
        }

        $this->response = new DataObject();
        $this->response->setData($this->eventData); // Assign response params to object data
        
        if (! empty($this->response->getData('custom/shop_invoked'))) {
            $this->displayMessage('Process already handled in the shop.');
            return false;
        }

        // Set Event data
        $this->eventType = $this->response->getData('event/type');
        $this->parentTid = !empty($this->response->getData('event/parent_tid'))
            ? $this->response->getData('event/parent_tid') : $this->response->getData('event/tid');
        $additionalData['NnparentTid'] = $this->parentTid;
        $this->eventTid  = $this->response->getData('event/tid');
        $this->orderNo = $this->response->getData('transaction/order_no');
        $this->order = $this->getOrder();
        if ($this->order === false) {
            return false;
        }
        $this->currentTime = $this->dateTime->date('d-m-Y H:i:s');
        $this->storeId = $this->order->getStoreId(); // Get order store id
        $this->paymentAccessKey = $this->novalnetConfig->getGlobalConfig('payment_access_key', $this->storeId);
        $this->payment = $this->order->getPayment(); // Get payment object
        $this->paymentCode = $this->payment->getMethodInstance()->getCode(); // Get payment method code
        $this->paymentTxnId = $this->payment->getLastTransId(); // Get payment last transaction id
        $this->currency = $this->order->getOrderCurrencyCode(); // Get order currency
        $this->paymentType = $this->getPaymentTypeForOrder();
        if (!$this->validateEventData()) {
            return false;
        }

        return true;
    }

    /**
     * To get payment type of the order
     *
     * @return string
     */
    private function getPaymentTypeForOrder()
    {
        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($this->payment->getAdditionalData());

        if (!empty($this->paymentCode) && $this->paymentCode != ConfigProvider::NOVALNET_PAY && preg_match('/novalnet/i', $this->paymentCode)) {
            $canUpdate = false;

            if (empty($additionalData['NnPaymentTitle'])) {
                $additionalData['NnPaymentTitle'] = $this->novalnetConfig->getPaymentTitleByCode($this->paymentCode);
                $canUpdate = true;
            }

            if (empty($additionalData['NnPaymentType'])) {
                $additionalData['NnPaymentType'] = $this->novalnetConfig->getPaymentTypeByCode($this->paymentCode);
                $canUpdate = true;
            }

            if (empty($additionalData['NnPaymentProcessMode'])) {
                $processMode = ($this->novalnetConfig->isRedirectPayment($this->paymentCode)) ? 'redirect' : 'direct';
                $additionalData['NnPaymentProcessMode'] = $processMode;
                $canUpdate = true;
            }

            if ($canUpdate) {
                $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
            }
        }

        return (!empty($additionalData['NnPaymentType'])) ? $additionalData['NnPaymentType'] : '';
    }

    /**
     * Complete the order in-case response failure from Novalnet server
     *
     * @return mixed
     */
    private function handleCommunicationFailure()
    {
        if ($this->paymentType != $this->response->getData('transaction/payment_type')) {
            $this->displayMessage(
                'Novalnet callback received. Payment type ( ' . $this->response->getData('transaction/payment_type')
                . ' ) is not matched with ' . $this->paymentCode . '!'
            );
            return false;
        }
        // Unhold order if it is being held
        if ($this->order->canUnhold()) {
            $this->order->unhold()->save();
        }

        // update and save the payment additional data
        $additionalData = $this->novalnetHelper->buildAdditionalData($this->response, $this->payment);
        $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();

        $amount = $this->novalnetHelper->getFormattedAmount(
            $this->response->getData('transaction/amount'),
            'RAW'
        );

        // Set order status based on Novalnet transaction status
        if ($this->response->getData('result/status') == 'SUCCESS' &&
            in_array($this->response->getData('transaction/status'), ['PENDING', 'ON_HOLD', 'CONFIRMED'])
        ) {
            if ($this->order->canInvoice() && $this->response->getData('transaction/status') == 'CONFIRMED') {
                // capture transaction
                $this->payment->setTransactionId($additionalData['NnTid'])
                    ->setLastTransId($additionalData['NnTid'])
                    ->capture(null)
                    ->setAmount($amount)
                    ->setIsTransactionClosed(false)
                    ->setShouldCloseParentTransaction(false)
                    ->save();
            } else {
                // authorize transaction
                $this->payment->authorize(true, $amount)->save();
            }

            $orderStatus = $this->getOrderStatus();
            if(isset($additionalData['NnZeroAmountBooking']) && !empty($additionalData['NnZeroAmountBooking'])){
                $orderStatus = $this->novalnetConfig->getOnholdStatus($this->storeId);
            }
            if ($this->response->getData('transaction/status') == 'PENDING') {
                $orderStatus = 'pending';
            }
            $this->order->setState(Order::STATE_PROCESSING)
                ->addStatusToHistory($orderStatus, __('Customer successfully returned from Novalnet'))
                ->save();

            if (!empty($this->response->getData('transaction/payment_data/token'))) {
                $this->novalnetHelper->savePaymentToken($this->order, $this->paymentCode, $this->response);
            }

            // Order email
            if ($this->order->getCanSendNewEmailFlag()) {
                try {
                    $this->orderEmailSender->send($this->order);
                    $invoice = current($this->order->getInvoiceCollection()->getItems());
                    if ($invoice) {
                        $this->invoiceSender->send($invoice);
                    }
                } catch (\Exception $e) {
                    $this->novalnetLogger->error($e);
                }
            }
        } else {
            // Cancel the order based on Novalnet transaction status
            $this->novalnetHelper->saveCanceledOrder($this->response, $this->order);
            $this->displayMessage('Payment cancelled for the transaction ' . $this->eventTid);
        }

        $this->displayMessage('Novalnet Callback Script executed successfully on ' . $this->currentTime);
    }

    /**
     * Capture transaction
     *
     * @return void
     */
    private function transactionCapture()
    {
        $invoiceDuedate = $this->response->getData('transaction/due_date');
        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($this->payment->getAdditionalData());

        $transactionStatus = !empty($additionalData['NnStatus'])
            ? $this->novalnetHelper->getStatus($additionalData['NnStatus'], $this->order, $this->paymentType) : '';

        if ($this->order->canInvoice() &&
            in_array($transactionStatus, ['ON_HOLD', 'PENDING'])
        ) {
            if ($invoiceDuedate && $this->response->getData('result/status') == 'SUCCESS') {
                $formatDate = $this->timeZone->formatDate($invoiceDuedate, \IntlDateFormatter::LONG);
                if (isset($additionalData['NnInvoiceComments'])) {
                    $note = (!empty($additionalData['NnInvoiceComments'])) ? explode('|', $additionalData['NnInvoiceComments']) : [];
                    $additionalData['NnInvoiceComments'] = (!empty($note)) ? implode('|', $note) : '';
                    $additionalData['NnDueDate'] = $formatDate;
                }
                if ($this->paymentType == 'CASHPAYMENT') {
                    $additionalData['CpDueDate'] = $formatDate;
                }

                $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
            }

            $this->emailBody = $message = __(
                'The transaction has been confirmed on %1',
                $this->currentTime
            );
            $additionalData['NnStatus'] = $this->response->getData('transaction/status');
            $additionalData['NnComments'] = empty($additionalData['NnComments']) ?
                '<br>'. $message . '<br>' : $additionalData['NnComments'] . '<br>' . $message . '<br>';

            if (in_array(
                $this->paymentType,
                ['INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA']
            )) {
                $additionalData = $this->getInstalmentAdditionalData($additionalData);
            }

            $transactionStatus = $this->transactionStatusModel->loadByAttribute($this->parentTid, 'tid');
            $transactionStatus->setStatus($additionalData['NnStatus'])->save();
            // Capture the Authorized transaction
            $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
            $this->payment->capture(null)->save();

            $orderStatus = $this->getOrderStatus();
            $this->order->setState(Order::STATE_PROCESSING)
                ->addStatusToHistory($orderStatus, 'The transaction has been confirmed')
                ->save();
            $invoice = current($this->order->getInvoiceCollection()->getItems());
            if ($invoice) {
                $this->invoiceSender->send($invoice);
            }
        } else {
            // transaction already captured or transaction not been authorized.
            $message = 'Order already captured.';
        }

        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Check transaction cancellation
     *
     * @return void
     */
    private function transactionCancellation()
    {
        $transactionStatus = $this->transactionStatusModel->loadByAttribute($this->parentTid, 'tid');

        $this->novalnetHelper->saveCanceledOrder($this->response, $this->order, $this->response->getData('transaction/status'));
        $this->emailBody = $message = __(
            'The transaction has been canceled on %1',
            $this->currentTime
        );

        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($this->payment->getAdditionalData());
        $additionalData['NnComments'] = empty($additionalData['NnComments'])
            ? '<br>' . $message : $additionalData['NnComments'] . '<br><br>' . $message;
        $additionalData['NnStatus'] = $this->response->getData('transaction/status');
        $transactionStatus->setStatus($additionalData['NnStatus'])->save();
        $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Handle payment refund/bookback process
     *
     * @return void
     */
    private function refundProcess()
    {
        $refundTid = empty($this->response->getData('transaction/refund/tid'))
            ? $this->response->getData('transaction/tid') : $this->response->getData('transaction/refund/tid');
        $refundAmount = empty($this->response->getData('transaction/refund/amount'))
            ? $this->response->getData('transaction/amount') : $this->response->getData('transaction/refund/amount');
        $this->emailBody = $message = __(
            'Refund has been initiated for the TID: %1 with the amount %2. New TID:%3',
            $this->parentTid,
            $this->novalnetHelper->getFormattedAmount($refundAmount, 'RAW') . ' ' . $this->currency,
            $refundTid
        );


        $message = __(
            'Refund has been initiated for the TID: %1 with the amount %2.',
            $this->parentTid,
            $this->novalnetHelper->getFormattedAmount($refundAmount, 'RAW') . ' ' . $this->currency,
        );
        
        if ($this->parentTid != $refundTid) {
            $message .= ' ' . __('New TID: %1.', $refundTid);
        }

        $shopInvoked = !empty($this->response->getData('custom/shop_invoked'))
            ? $this->response->getData('custom/shop_invoked') : 0;
        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($this->payment->getAdditionalData());
        if (!$shopInvoked && !isset($additionalData['CANCEL_ALL_CYCLES'])) {
            $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message :
                $additionalData['NnComments'] . '<br><br>' . $message;
            $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
        }

        if ($this->order->getState() != Order::STATE_CLOSED && $this->order->canCreditmemo() && !$shopInvoked) {
            $refundData = [];
            $grandTotal = $this->novalnetHelper->getFormattedAmount($this->order->getGrandTotal());
            $totalRefunded = $this->novalnetHelper->getFormattedAmount($this->order->getTotalOnlineRefunded());
            $totalRefundNow = $totalRefunded + $refundAmount;
            if ($totalRefundNow >= $grandTotal || $refundAmount >= $grandTotal) {
                $adjustmentNegative = $grandTotal - $refundAmount;
                $refundData['adjustment_negative'] = $this->novalnetHelper->getFormattedAmount($adjustmentNegative, 'RAW');
                $creditmemo = $this->creditMemoFactory->createByOrder($this->order, $refundData);
                $this->creditmemoService->refund($creditmemo);
            } elseif ($totalRefunded < $grandTotal && $totalRefundNow <= $grandTotal) {
                $refundData['adjustment_positive'] = ($this->novalnetHelper->getFormattedAmount($refundAmount, 'RAW'));
                $itemToCredit = [];
                foreach ($this->order->getAllItems() as $item) {
                    $itemToCredit[$item->getId()] = ['qty'=> 0];
                }
                $refundData['adjustment_negative'] = 0;
                $refundData['shipping_amount'] = 0;
                $refundData['items'] = $itemToCredit;
                if ($this->paymentType == 'INSTALMENT_INVOICE' || $this->paymentType == 'INSTALMENT_DIRECT_DEBIT_SEPA') {
                    $additionalData['Nnrefundexc'] = 1;
                    $additionalData['NnrefAmount'] = $totalRefundNow;
                    $additionalData['NnrefundedTid'] = $this->parentTid;
                    $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
                }
                $this->creditmemoLoader->setOrderId($this->order->getId()); //pass order id
                $this->creditmemoLoader->setCreditmemo($refundData);
                $creditmemo = $this->creditmemoLoader->load();
                $this->creditmemoService->refund($creditmemo);
            }
        }

        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Handle transaction update
     *
     * @return void
     */
    private function transactionUpdate()
    {
        $invoiceDuedate = $this->response->getData('transaction/due_date');
        $transactionPaymentType = $this->response->getData('transaction/payment_type');
        $transaction = $this->transactionStatusModel->loadByAttribute($this->parentTid, 'tid');
        $message = "Novalnet callback received for the unhandled transaction type($transactionPaymentType) for $this->eventType EVENT";
        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($this->payment->getAdditionalData());
        $transactionStatus = $this->novalnetHelper->getStatus($transaction->getStatus(), $this->order, $this->paymentType);

        if ($invoiceDuedate && $this->response->getData('result/status') == 'SUCCESS') {
            $formatDate = $this->timeZone->formatDate($invoiceDuedate, \IntlDateFormatter::LONG);
            $nnAmount = $this->pricingHelper->currency(
                $this->novalnetHelper->getFormattedAmount(($this->response->getData('instalment/cycle_amount') ? $this->response->getData('instalment/cycle_amount')
                : $this->response->getData('transaction/amount')), 'RAW'),
                true,
                false
            );
            $additionalData['NnAmount'] = $nnAmount;
            $this->emailBody = $message =  __(
                'The transaction has been updated with amount %1 and due date with %2',
                $additionalData['NnAmount'],
                $formatDate
            );
            if (isset($additionalData['NnInvoiceComments'])) {
                $note = (!empty($additionalData['NnInvoiceComments'])) ? explode('|', $additionalData['NnInvoiceComments']) : [];
                $additionalData['NnInvoiceComments'] = (!empty($note)) ? implode('|', $note) : '';
                $additionalData['NnDueDate'] = $formatDate;
            }
            if ($this->paymentType == 'CASHPAYMENT') {
                $additionalData['CpDueDate'] = $formatDate;
                $this->emailBody = $message =  __(
                    'The transaction has been updated with amount %1 and slip expiry date with %2',
                    $additionalData['NnAmount'],
                    $formatDate
                );
            }
            $additionalData['dueDateUpdateAt'] = $this->dateTime->date('d-m-Y H:i:s');
            $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
        }

        if ($transactionStatus == 'PENDING') {
            if ($this->response->getData('transaction/status') == 'ON_HOLD') {
                $orderStatus = $this->novalnetConfig->getOnholdStatus($this->storeId);
                $this->emailBody = $message = __(
                    'The transaction status has been changed from pending to on hold for the TID: %1 on %2.',
                    $this->parentTid,
                    $this->currentTime
                );
                $additionalData['NnStatus'] = $this->response->getData('transaction/status');
                $additionalData['NnComments'] = empty($additionalData['NnComments']) ?
                    '<br>' . $message . '<br>' : $additionalData['NnComments'] . '<br>' . $message . '<br>';
                $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
            } elseif ($this->response->getData('transaction/status') == 'CONFIRMED') {
                if (in_array(
                    $this->paymentType,
                    ['INSTALMENT_DIRECT_DEBIT_SEPA', 'INSTALMENT_INVOICE']
                )) {
                    $additionalData = $this->getInstalmentAdditionalData($additionalData);
                }
                $this->emailBody = $message = __(
                    'Transaction updated successfully for the TID: %1 with the amount %2 on %3',
                    $this->eventTid,
                    $amount = $this->novalnetHelper->getFormattedAmount(
                        $this->response->getData('transaction/amount'),
                        'RAW'
                    ).' '.$this->currency,
                    $this->currentTime
                );

                $additionalData['NnStatus'] = $this->response->getData('transaction/status');
                $additionalData['NnComments'] = empty($additionalData['NnComments']) ?
                    '<br>' . $message . '<br>' : $additionalData['NnComments'] . '<br>' . $message . '<br>';
                $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
                $this->payment->setTransactionId($this->parentTid . '-capture')
                    ->setIsTransactionClosed(true)
                    ->capture(null)->save();

                if ($this->paymentType == 'INVOICE') {
                    $orderStatus = Order::STATE_PROCESSING;
                } elseif (in_array($this->paymentType, ['PREPAYMENT', 'CASHPAYMENT', 'MULTIBANCO'])) {
                    $orderStatus = 'pending';
                } else {
                    $orderStatus = $this->novalnetConfig->getOrderCompletionStatus($this->storeId);
                }
            }

            $transaction->setStatus($this->response->getData('transaction/status'))->save();
            if (!empty($orderStatus)) {
                $this->order->setState(Order::STATE_PROCESSING)
                    ->setStatus($orderStatus)
                    ->save();
            }
            $invoice = current($this->order->getInvoiceCollection()->getItems());
            if ($invoice && $this->response->getData('transaction/status') == 'CONFIRMED') {
                $this->invoiceSender->send($invoice);
            }
        } elseif ($transactionStatus == 'ON_HOLD' && $this->response->getData('transaction/status') == 'ON_HOLD' &&
            in_array($this->paymentType, ['DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'GUARANTEED_INVOICE', 'PREPAYMENT'])) {
                $this->emailBody = $message = __(
                    'Transaction updated successfully for the TID: %1 with the amount %2 on %3',
                    $this->eventTid,
                    $amount = $this->novalnetHelper->getFormattedAmount(
                        $this->response->getData('transaction/amount'),
                        'RAW'
                    ).' '.$this->currency,
                    $this->currentTime
                );
                $additionalData['NnComments'] = empty($additionalData['NnComments']) ?
                        '<br>' . $message . '<br>' : $additionalData['NnComments'] . '<br>' . $message . '<br>';
                $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
        }

        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Handle payment credit process
     *
     * @return void
     */
    private function creditProcess()
    {
        $transactionPaymentType = $this->response->getData('transaction/payment_type');
        $message = "Novalnet callback received for the unhandled transaction type($transactionPaymentType) for $this->eventType EVENT";
        $amount = $this->novalnetHelper->getFormattedAmount(
            $this->response->getData('transaction/amount'),
            'RAW'
        );

        if (in_array(
            $transactionPaymentType,
            ['INVOICE_CREDIT', 'ONLINE_TRANSFER_CREDIT', 'CASHPAYMENT_CREDIT', 'MULTIBANCO_CREDIT']
        )) {
            $additionalData = $this->novalnetHelper->getPaymentAdditionalData($this->payment->getAdditionalData());
            $updatedAmount = $this->novalnetHelper->getFormattedAmount($this->order->getGrandTotal());
            // Loads callback model using the Increment ID
            $callbackInfo = $this->callbackModel->loadLogByOrderId($this->orderNo);
            $transactionStatus = $this->transactionStatusModel->loadByAttribute($this->parentTid, 'tid');
            $totalAmount = $this->response->getData('transaction/amount') + $callbackInfo->getCallbackAmount();
            // Get original order amount
            $grandTotal = $this->novalnetHelper->getFormattedAmount($this->order->getGrandTotal());
            $totalAmountRefunded = $this->novalnetHelper->getFormattedAmount($this->order->getTotalRefunded());
            // Log callback data for reference
            $this->logCallbackInfo($callbackInfo, $totalAmount, $this->orderNo);
            $message = __(
                'Credit has been successfully received for the TID: %1 with amount %2 on %3. Please refer PAID order details in our Novalnet Admin Portal for the TID: %4',
                $this->parentTid,
                $amount . ' ' . $this->currency,
                $this->currentTime,
                $this->eventTid
            );

            $additionalData['NnStatus'] = ($this->response->getData('transaction/status'))
                ? $this->response->getData('transaction/status') : '';
            $transactionStatus->setStatus($this->response->getData('transaction/status'))->save();
            $currentGrandTotal = 0;
            if ($totalAmountRefunded) {
                $currentGrandTotal = $grandTotal - $totalAmountRefunded;
                if ($totalAmount >= $currentGrandTotal) {
                    $additionalData['NnPaid'] = 1;
                }
            } elseif ($totalAmount >= $grandTotal) {
                $additionalData['NnPaid'] = 1;
            }
            if (($totalAmount < $grandTotal) ||
                $transactionPaymentType == 'ONLINE_TRANSFER_CREDIT'
            ) {
                if ($transactionPaymentType == 'ONLINE_TRANSFER_CREDIT' && ($totalAmount > $grandTotal)) {
                    $message = $message  . '<br>' . __(
                        'Credit has been successfully received for the TID: %1 with amount %2 on %3. Please refer PAID order details in our Novalnet Admin Portal for the TID: %4',
                        $this->parentTid,
                        $amount . ' ' . $this->currency,
                        $this->currentTime,
                        $this->eventTid
                    );
                }

                $this->emailBody = $message;
                $additionalData['NnComments'] = empty($additionalData['NnComments'])
                    ? '<br>' . $message
                    : $additionalData['NnComments'] . '<br><br>' . $message;
                $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
                if ($totalAmountRefunded) {
                    if (($totalAmount >= $currentGrandTotal)) {
                        $this->getOrderStatusforCreditEvent(); // Set order status
                    }
                } elseif ($updatedAmount) {
                    if (($totalAmount >= $updatedAmount)) {
                        $this->getOrderStatusforCreditEvent(); // Set order status
                    }
                }
            } elseif ($this->order->canInvoice()) {
                $this->emailBody = $message;
                $additionalData['NnComments'] = empty($additionalData['NnComments'])
                    ? '<br>' . $message
                    : $additionalData['NnComments'] . '<br><br>' . $message;
                $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
                // Save payment information with invoice for Novalnet successful transaction
                $this->payment->setTransactionId($this->parentTid . '-capture')
                    ->setIsTransactionClosed(true)
                    ->capture(null)->save();
                $this->order->save();
                $this->order->setPayment($this->payment)->save();
                $invoice = $this->order->getInvoiceCollection()->getFirstItem();
                if ($invoice) {
                    $this->invoiceSender->send($invoice);
                }
                $this->getOrderStatusforCreditEvent(); // Set order status
            } elseif ($this->payment->getAdditionalInformation($this->paymentCode . '_callbackSuccess') != 1) {
                $this->emailBody = $message;
                $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message
                : $additionalData['NnComments'] . '<br><br>' . $message;
                $this->payment->setAdditionalInformation($this->paymentCode
                . '_callbackSuccess', 1);
                $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
                $this->getOrderStatusforCreditEvent(); // Set order status
                $this->order->setPayment($this->payment)->save();

                $invoice = $this->order->getInvoiceCollection()->getFirstItem();
                $invoice->setState(\Magento\Sales\Model\Order\Invoice::STATE_PAID);
                $invoice->save();
            } else {
                $message = 'Callback Script executed already. Refer Order :' . $this->orderNo;
            }
        } else {
            $message = __(
                'Credit has been successfully received for the TID: %1 with amount %2 on %3. Please refer PAID order details in our Novalnet Admin Portal for the TID: %4',
                $this->parentTid,
                $amount . ' ' . $this->currency,
                $this->currentTime,
                $this->eventTid
            );
            $additionalData = $this->novalnetHelper->getPaymentAdditionalData($this->payment->getAdditionalData());
            $this->emailBody = $message;
            $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message
                : $additionalData['NnComments'] . '<br><br>' . $message;
            $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
        }

        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Handle payment INSTALMENT process
     *
     * @return void
     */
    private function instalmentProcess()
    {
        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($this->payment->getAdditionalData());
        $additionalData['NnTid'] = $this->response->getData('transaction/tid');
        $instalmentTransactionAmount = $this->novalnetHelper->getFormattedAmount(
            $this->response->getData('instalment/cycle_amount'),
            'RAW'
        );
        $paidAmount = $additionalData['InstallPaidAmount'] + $instalmentTransactionAmount;
        $additionalData['InstallPaidAmount'] = $paidAmount;
        $additionalData['PaidInstall'] = $this->response->getData('instalment/cycles_executed');
        $additionalData['DueInstall'] = $this->response->getData('instalment/pending_cycles');
        $additionalData['NextCycle'] = $this->response->getData('instalment/next_cycle_date');
        if ($this->response->getData('instalment/prepaid')) {
            $additionalData['prepaid'] = $this->response->getData('instalment/prepaid');
        } else {
            if (isset($additionalData['prepaid'])) {
                 unset($additionalData['prepaid']);
            }
        }
        $additionalData['InstallCycleAmount'] = $instalmentTransactionAmount;
        $additionalData['NnAmount'] = $this->pricingHelper->currencyByStore($instalmentTransactionAmount, $this->order->getStore()->getStoreId(), true, false);

        if ($this->paymentType == 'INSTALMENT_INVOICE') {
            $note = (!empty($additionalData['NnInvoiceComments'])) ? explode('|', $additionalData['NnInvoiceComments']) : [];
            if ($this->response->getData('transaction/due_date')) {
                $formatDate = $this->timeZone->formatDate(
                    $this->response->getData('transaction/due_date'),
                    \IntlDateFormatter::LONG
                );
                $additionalData['NnDueDate'] = $formatDate;
            }

            $note[5] = 'InvoiceAmount:' . $this->pricingHelper->currencyByStore(
                $instalmentTransactionAmount,
                $this->order->getStore()->getStoreId(),
                true,
                false
            );

            $note[6] = 'Payment Reference:' . $this->response->getData('transaction/tid');
            if (isset($note[7])) {
                unset($note[7]);
            }
            $additionalData['NnInvoiceComments'] = (!empty($note)) ? implode('|', $note) : '';
        }

        $additionalData['InstalmentDetails'][$this->response->getData('instalment/cycles_executed')] = [
            'amount' => $instalmentTransactionAmount,
            'nextCycle' => $this->response->getData('instalment/next_cycle_date'),
            'paidDate' => date('Y-m-d'),
            'status' => 'Paid',
            'reference' => $this->response->getData('transaction/tid')
        ];
        $additionalData['Nninstalmetexc'] = 1;
        $Nninstalmetexc = $additionalData['Nninstalmetexc'];
        $additionalData['Nninstalmentnew'][$this->eventTid]['Nninstalmentnewtid'] = $this->eventTid;
        $additionalData['Nninstalmentnew'][$this->eventTid]['Nninstalmentnewtime'] = $this->currentTime;
        $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();

        // Send instalment email to end customer
        if (!$this->novalnetConfig->getGlobalConfig('instalment_mail', $this->storeId)) {
            $this->sendInstalmentmail();
        }

        $this->displayMessage('Novalnet Callbackscript received. Instalment payment executed properly');
    }

    /**
     * Handle payment INSTALMENT process
     *
     * @return void
     */
    private function instalmentCancelProcess()
    {

        $cancelType  = $this->response->getData('instalment/cancel_type');
        $message = '';
        if ($cancelType == 'ALL_CYCLES') {
            $message = __(
                'Instalment has been cancelled for the TID: %1 & Refund has been initiated %2 .',
                $this->parentTid,
                $this->novalnetHelper->getFormattedAmount(
                    $this->response->getData('transaction/refund/amount'),
                    'RAW'
                ) . ' ' . $this->currency 
            );
        }
        if ($cancelType == 'REMAINING_CYCLES') {
            $message = __(
                'Instalment has been stopped for the TID: %1',
                $this->parentTid
            );
        }
        $this->emailBody = $message;

        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($this->payment->getAdditionalData());
        $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message :
            $additionalData['NnComments'] . '<br><br>' . $message;
        $additionalData['InstalmentCancel'] = 1;
        $additionalData[$cancelType] = 1;
        $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
        if ($cancelType =='ALL_CYCLES') {
            $this->order->setState(Order::STATE_CLOSED)->setStatus(Order::STATE_CLOSED)->save();
        }
        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Handle payment CHARGEBACK/RETURN_DEBIT/REVERSAL process
     *
     * @return void
     */
    private function chargebackProcess()
    {
        // Update callback comments for Chargebacks
        $this->emailBody = $message = __(
            'Chargeback executed successfully for the TID: %1 amount: %2 on %3. The subsequent TID: %4',
            $this->parentTid,
            $this->novalnetHelper->getFormattedAmount(
                $this->response->getData('transaction/amount'),
                'RAW'
            ) . ' ' . $this->currency,
            $this->currentTime,
            $this->response->getData('transaction/tid')
        );

        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($this->payment->getAdditionalData());
        $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message :
            $additionalData['NnComments'] . '<br><br>' . $message;
        $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();

        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Handle payment reminder process
     *
     * @return void
     */
    private function paymentReminderProcess()
    {
        $reminderCount = explode('_', $this->response->getData('event/type'));
        $reminderCount = end($reminderCount);
        $this->emailBody = $message = __(
            'Payment Reminder %1 has been sent to the customer.',
            $reminderCount
        );

        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($this->payment->getAdditionalData());
        $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message :
            $additionalData['NnComments'] . '<br><br>' . $message;
        $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();

        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Handle collection process
     *
     * @return void
     */
    private function collectionProcess()
    {
        $this->emailBody = $message = __(
            'The transaction has been submitted to the collection agency. Collection Reference: %1',
            $this->response->getData('collection/reference')
        );

        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($this->payment->getAdditionalData());
        $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message :
            $additionalData['NnComments'] . '<br><br>' . $message;
        $this->payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();

        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Send callback notification E-mail
     *
     * @return bool
     */
    private function sendCallbackMail()
    {
        // Get email configuration settings
        $this->getEmailConfig();

        if ($this->emailBody && $this->emailFromAddr && $this->emailToAddr) {
            if (!$this->sendEmailMagento()) {
                $this->displayMessage('Mailing failed!' . '<br>', false);
                return false;
            }
        }

        return true;
    }

    /**
     * Get email config
     *
     * @return void
     */
    private function getEmailConfig()
    {
        $this->storeFrontendName = $this->storeManager->getStore()->getFrontendName();
        $this->emailFromAddr = $this->getConfigValue('trans_email/ident_general/email');
        $this->emailFromName = $this->getConfigValue('trans_email/ident_general/name');
        $this->emailToAddr = $this->novalnetConfig->getMerchantScriptConfig('mail_to_addr');
        $this->emailToName = 'store admin'; // Adapt for your need
        $this->emailSubject = 'Novalnet Callback Script Access Report - ' . $this->storeFrontendName;
    }

    /**
     * Retrieves Novalnet configuration values
     *
     * @param  string $path
     * @return string
     */
    private function getConfigValue($path)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Send callback notification E-mail (with callback template)
     *
     * @return boolean
     */
    private function sendEmailMagento()
    {
        try {
            $emailToAddrs = str_replace(' ', '', $this->emailToAddr);
            $emailToAddrs = (!empty($emailToAddrs)) ? explode(',', $emailToAddrs) : [];
            $templateVars = [
                'fromName' => $this->emailFromName,
                'fromEmail' => $this->emailFromAddr,
                'toName' => $this->emailToName,
                'toEmail' => $this->emailToAddr,
                'subject' => $this->emailSubject
            ];

            if (!empty($this->emailBody) &&
                is_object($this->emailBody) &&
                $this->emailBody instanceof \Magento\Framework\Phrase
            ) {
                $templateVars['body'] = $this->emailBody->render();
            } else {
                $templateVars['body'] = $this->emailBody;
            }

            $from = ['email' => $this->emailFromAddr, 'name' => $this->emailFromName];
            $this->inlineTranslation->suspend();

            $storeId = $this->storeManager->getStore()->getId();
            $templateOptions = [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId
            ];
            $transport = $this->transportBuilder->setTemplateIdentifier(
                'novalnet_callback_email_template',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->setFrom($from)
                ->addTo($emailToAddrs)
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();
            $this->displayMessage(__FUNCTION__ . ': Sending Email succeeded! <br>', false);
        } catch (\Exception $e) {
            $this->novalnetLogger->error("Email sending failed: $e");
            $this->displayMessage('Email sending failed: ', false);
            return false;
        }

        return true;
    }

    /**
     * Get payment order status
     *
     * @return string
     */
    private function getOrderStatus()
    {
        $orderStatus = 'pending';
        if ($this->response->getData('transaction/status') == 'ON_HOLD') {
            $orderStatus = $this->novalnetConfig->getOnholdStatus($this->storeId);
        } elseif ($this->response->getData('transaction/status') == 'CONFIRMED' ||
             $this->response->getData('transaction/status') == 'PENDING' &&
            !in_array(
                $this->paymentType,
                [
                    'GUARANTEED_DIRECT_DEBIT_SEPA',
                    'GUARANTEED_INVOICE',
                    'INSTALMENT_DIRECT_DEBIT_SEPA',
                    'INSTALMENT_INVOICE'
                ]
            )
        ) {
            $orderStatus = $this->novalnetConfig->getOrderCompletionStatus($this->storeId);
        }

        return !empty($orderStatus) ? $orderStatus : Order::STATE_PROCESSING;
    }

    /**
     * Get Instalment payment Additional data
     *
     * @param  array $additionalData
     * @return array
     */
    private function getInstalmentAdditionalData($additionalData)
    {
        $additionalData['PaidInstall'] = $this->response->getData('instalment/cycles_executed');
        $additionalData['DueInstall'] = $this->response->getData('instalment/pending_cycles');
        $additionalData['NextCycle'] = $this->response->getData('instalment/next_cycle_date');
        if ($futureInstalmentDates = $this->response->getData('instalment/cycle_dates')) {
            foreach (array_keys($futureInstalmentDates) as $cycle) {
                $additionalData['InstalmentDetails'][$cycle] = ['amount' => $additionalData['InstallPaidAmount'],
                    'nextCycle' => !empty($futureInstalmentDates[$cycle + 1]) ? date('Y-m-d', strtotime($futureInstalmentDates[$cycle + 1])) : '',
                    'paidDate' => ($cycle == 1) ? date('Y-m-d') : '',
                    'status' => ($cycle == 1) ? 'Paid' : 'Pending',
                    'reference' => ($cycle == 1) ? $this->response->getData('transaction/tid') : ''];
            }
        }
        return $additionalData;
    }

    /**
     * Check whether the ip address is authorised
     *
     * @return boolean
     */
    private function checkIP()
    {
        $requestReceivedIp = $this->novalnetHelper->getRequestIp();
        $novalnetHostIp = gethostbyname('pay-nn.de');

        if (!empty($novalnetHostIp)) {
            if (!$this->validateRequestIp($novalnetHostIp) && !$this->testMode) {
                $this->displayMessage(
                    __('Unauthorised access from the IP [ %1 ]', $requestReceivedIp)
                );

                return false;
            }
        } else {
            $this->displayMessage('Unauthorised access from the IP');
            return false;
        }

        return true;
    }

    /**
     * Validate the request ip with Novalnet host Ip
     *
     * @param string $novalnetHostIp
     * @return boolean
     */
    private function validateRequestIp($novalnetHostIp)
    {
        $serverVariables = $this->requestInterface->getServer();
        $remoteAddrHeaders = [
            'HTTP_X_FORWARDED_HOST',
            'HTTP_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($remoteAddrHeaders as $header) {
            if (property_exists($serverVariables, $header) === true) {
                if (in_array($header, ['HTTP_X_FORWARDED_HOST', 'HTTP_X_FORWARDED_FOR'])) {
                    $forwardedIps = (!empty($serverVariables[$header])) ? explode(",", $serverVariables[$header]) : [];
                    if (in_array($novalnetHostIp, $forwardedIps)) {
                        return true;
                    }
                }

                if ($serverVariables[$header] == $novalnetHostIp) {
                    return true;
                }
            }
        }

        return false;
    }
    /**
     * Validate required parameter from the server request
     *
     * @return bool
     */
    private function validateEventData()
    {
        foreach ($this->mandatoryParams as $category => $parameters) {
            if (empty($this->response->getData($category))) {
                // Could be a possible manipulation in the notification data
                $this->displayMessage('Required parameter category(' . $category . ') not received');

                return false;
            } else {
                foreach ($parameters as $parameter) {
                    if (empty($this->response->getData($category .'/'. $parameter))) {
                        // Could be a possible manipulation in the notification data
                        $this->displayMessage(
                            'Required parameter(' . $parameter . ') in the category(' . $category . ') not received'
                        );

                        return false;
                    }
                }
            }
        }

        // Validate the received checksum.
        if (!$this->validateChecksum()) {
            return false;
        }

        // Validate TID's from the event data
        if (!empty($this->parentTid) && !preg_match('/^\d{17}$/', $this->parentTid)
        ) {
            $this->displayMessage(
                'Invalid TID[' . $this->parentTid
                . '] for Order :' . $this->response->getData('transaction/order_no')
            );

            return false;
        } elseif (!empty($this->eventTid) && !preg_match('/^\d{17}$/', $this->eventTid)) {
            $this->displayMessage(
                'Invalid TID[' . $this->eventTid
                . '] for Order :' . $this->response->getData('transaction/order_no')
            );

            return false;
        }

        return true;
    }

    /**
     * Validate checksum in response
     *
     * @return bool
     */
    private function validateChecksum()
    {
        $checksumString  = $this->response->getData('event/tid') . $this->response->getData('event/type')
            . $this->response->getData('result/status');

        if (isset($this->response->getData('transaction')['amount'])) {
            $checksumString .= $this->response->getData('transaction/amount');
        }

        if ($this->response->getData('transaction/currency')) {
            $checksumString .= $this->response->getData('transaction/currency');
        }

        $accessKey = trim($this->paymentAccessKey);
        if (!empty($accessKey)) {
            $checksumString .= strrev($accessKey);
        }

        $generatedChecksum = hash('sha256', $checksumString);
        if ($generatedChecksum !== $this->response->getData('event/checksum')) {
            $this->displayMessage('While notifying some data has been changed. The hash check failed');

            return false;
        }

        return true;
    }

    /**
     * Show callback process transaction comments
     *
     * @param  string  $text
     * @param  boolean $pullOut
     * @return void
     */
    private function displayMessage($text, $pullOut = true)
    {
        if ($pullOut === false) {
            $this->additionalMessage = $text;
        } else {
            $this->callbackMessage = $text;
        }

        $this->novalnetLogger->notice($text);
    }

    /**
     * Log callback transaction information
     *
     * @param  \Novalnet\Payment\Model\Callback $callbackModel
     * @param  float $amount
     * @param  string $orderNo
     * @return void
     */
    private function logCallbackInfo($callbackModel, $amount, $orderNo)
    {
        // Get the original/parent transaction id
        $callbackModel->setOrderId($orderNo)
            ->setCallbackAmount($amount)
            ->setReferenceTid($this->eventTid)
            ->setCallbackTid($this->parentTid)
            ->setCallbackDatetime($this->currentTime)
            ->save();
    }

    /**
     * Get payment order status for CREDIT Event
     *
     * @return void
     */
    private function getOrderStatusforCreditEvent()
    {
        $orderState = $orderStatus = Order::STATE_PROCESSING;
        if ($this->response->getData('transaction/status') == 'CONFIRMED' && $this->paymentType == 'INVOICE') {
            $orderStatus = Order::STATE_COMPLETE;
        } else {
            $orderStatus = $this->novalnetConfig->getOrderCompletionStatus($this->storeId);
        }

        $this->order->setState(
            $orderState,
            true,
            __('Novalnet webhook set status (%1) for Order ID = %2', $orderState, $this->orderNo)
        );
        $this->order->addStatusToHistory(
            $orderStatus,
            __('Novalnet webhook added order status %1', $orderStatus)
        );
        $this->order->save();
    }

    /**
     * Send Instalment mail
     *
     * @return mixed
     */
    private function sendInstalmentmail()
    {
        $this->getEmailConfig();
        $from = ['email' => $this->emailFromAddr, 'name' => $this->emailFromName];
        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($this->order->getPayment()->getAdditionalData());
        $templateVars = [
            'order'      => $this->order,
            'orderNo'    => $this->orderNo,
            'storeName' => $this->storeFrontendName,
            'order_id'   => $this->order->getId(),
            'customer_name' => $this->order->getCustomerName(),
            'cycleAmount' => $this->novalnetHelper->getFormattedAmount($this->response->getData('instalment/cycle_amount'), 'RAW'),
            'currency' => $this->currency,
            'formattedShippingAddress' => !empty($this->order->getShippingAddress()) ? $this->salesOrderAddressRenderer->format($this->order->getShippingAddress(), 'html') : '',
            'formattedBillingAddress' => $this->salesOrderAddressRenderer->format($this->order->getBillingAddress(), 'html'),
            'store' => $this->order->getStore(),
            'payment_html' => $this->paymentHelper->getInfoBlockHtml(
                $this->order->getPayment(),
                $this->order->getStore()->getStoreId()
            ),
            'sepaPayment' => ((!isset($additionalData['prepaid']) || empty($additionalData['prepaid'])) && ($this->paymentType == 'INSTALMENT_DIRECT_DEBIT_SEPA')) ? 1 : ""
        ];

        try {
            $this->inlineTranslation->suspend();
            $templateOptions = [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $this->order->getStore()->getStoreId()
            ];

            $transport = $this->transportBuilder->setTemplateIdentifier(
                'novalnet_callback_instalment_email_template',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->setFrom($from)
                ->addTo($this->order->getCustomerEmail())
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();
            $this->displayMessage(__FUNCTION__ . ': Sending Email succeeded!'.'<br>', false);
        } catch (\Exception $e) {
            $this->novalnetLogger->error("Email sending failed: $e");
            $this->displayMessage('Email sending failed: ', false);
            return false;
        }
    }

    /**
     * Get order reference.
     *
     * @return mixed
     */
    private function getOrder()
    {
        if ($this->orderNo) {
            $order = $this->salesOrderModel->loadByIncrementId($this->orderNo);
            $baseGrandTotal = (float) $order->getBaseGrandTotal();
            $order->setBaseGrandTotal($baseGrandTotal);
            $order->save();
        }

        if (!isset($order) || empty($order->getIncrementId())) {
            $orderCollection = $this->transactionModel->getCollection()->addFieldToFilter('txn_id', $this->parentTid);
            if (!empty($orderCollection)) {
                $order = $orderCollection->getFirstItem()->getOrder();
            }
        }

        if (empty($order) || empty($order->getIncrementId())) {
            $this->displayMessage('Required (Transaction ID) not Found!');
            return false;
        }

        return $order;
    }
}
