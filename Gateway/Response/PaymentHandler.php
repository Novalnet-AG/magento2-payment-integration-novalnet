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
namespace Novalnet\Payment\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Novalnet\Payment\Model\Ui\ConfigProvider;

class PaymentHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $datetime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * @var \Novalnet\Payment\Model\TransactionStatus
     */
    private $transactionStatusModel;

    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    private $novalnetRequestHelper;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    private $serializer;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer
    ) {
        $this->dateTime = $datetime;
        $this->timeZone = $timezone;
        $this->transactionStatusModel = $transactionStatusModel;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->jsonHelper = $jsonHelper;
        $this->serializer = $serializer;
    }

    /**
     * Handles transaction authorize and capture
     *
     * @param array $handlingSubject
     * @param array $paymentResponse
     * @return void
     */
    public function handle(array $handlingSubject, array $paymentResponse)
    {
        $response = new \Magento\Framework\DataObject();
        $response->setData($paymentResponse);
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);
        $order = $paymentDataObject->getOrder();
        $payment = $paymentDataObject->getPayment();
        $paymentMethodCode = $payment->getMethodInstance()->getCode();
        if ($this->novalnetRequestHelper->isSerialized($payment->getAdditionalData())) {
            $additionalData = $this->serializer->unserialize($payment->getAdditionalData());
        } else {
            $additionalData = json_decode($payment->getAdditionalData(), true);
        }
        $transactionStatus = '';
        if (!empty($additionalData['NnStatus'])) {
            $transactionStatus = $this->novalnetRequestHelper->getStatus($additionalData['NnStatus'], $order);
        }

        if (in_array($response->getData('action'), ['NN_Authorize', 'NN_Capture']) &&
            isset($additionalData['NnTid'])
        ) {
            // Authorize or Capture the initial redirect transaction
            $payment->setTransactionId($additionalData['NnTid'])
                ->setAmount($order->getGrandTotalAmount())
                ->setIsTransactionClosed(false)
                ->setShouldCloseParentTransaction(false);

            if ($transactionStatus == 'ON_HOLD') {
                $payment->setTransactionId($additionalData['NnTid'] . '-capture');
            }
        } elseif (!empty($response->getData())) {
            if (!empty($response->getData('result/redirect_url'))) {
                $payment->setAdditionalData(
                    $this->jsonHelper->jsonEncode($this->novalnetRequestHelper->buildRedirectAdditionalData($response))
                );
            } else {
                if ($transactionStatus == 'ON_HOLD') {
                    $additionalData = $this->handleOnHold($additionalData, $response, $paymentMethodCode);
                    // Capture the Authorized transaction
                    $payment->setTransactionId($response->getData('transaction/tid') . '-capture')
                        ->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))
                        ->setIsTransactionClosed(false)
                        ->setShouldCloseParentTransaction(false);
                    // update Paypal Token information if null available
                    if ($paymentMethodCode == ConfigProvider::NOVALNET_PAYPAL) {
                        $this->savePaymentToken($response, $order);
                    }
                } else {
                    // Authorize or Capture the transaction
                    $payment->setTransactionId($response->getData('transaction/tid'))
                        ->setAmount($order->getGrandTotalAmount())
                        ->setAdditionalData(
                            $this->jsonHelper->jsonEncode($this->novalnetRequestHelper->buildAdditionalData($response, $payment))
                        )->setIsTransactionClosed(false)
                        ->setShouldCloseParentTransaction(false);

                    if (in_array(
                        $paymentMethodCode,
                        [
                            ConfigProvider::NOVALNET_SEPA_GUARANTEE,
                            ConfigProvider::NOVALNET_INVOICE_GUARANTEE,
                            ConfigProvider::NOVALNET_SEPA_INSTALMENT,
                            ConfigProvider::NOVALNET_INVOICE_INSTALMENT
                        ]
                    )) {
                        $amount = $this->novalnetRequestHelper->getFormattedAmount($response->getData('transaction/amount'), 'RAW');
                        if (in_array($response->getData('transaction/status'), ['PENDING', 'ON_HOLD'])) {
                            $payment->authorize(true, $amount);
                        } elseif ($response->getData('transaction/status') == 'CONFIRMED') {
                            $payment->setTransactionId($response->getData('transaction/tid'))
                                ->setLastTransId($response->getData('transaction/tid'))
                                ->capture(null)
                                ->setAmount($amount)
                                ->setIsTransactionClosed(false)
                                ->setShouldCloseParentTransaction(false);
                        }
                    }
                }
            }
        }

        // Store payment data if token exist
        if ($response->getData('result/status') == 'SUCCESS' && $response->getData('transaction/payment_data/token')) {
            $this->novalnetRequestHelper->savePaymentToken($order, $paymentMethodCode, $response);
        }
    }

    /**
     * Handles save paypal payment token
     *
     * @param object $response
     * @param object $order
     * @return none
     */
    private function savePaymentToken($response, $order)
    {
        $transactionStatus = $this->transactionStatusModel->getCollection()->setPageSize(1)
            ->addFieldToFilter('order_id', $order->getOrderIncrementId())
            ->getFirstItem();
        if ($transactionStatus->getId()) {
            $tokenInfo = $transactionStatus->getTokenInfo();
            $tokenInfo = json_decode($tokenInfo, true);
            if ($response->getData('transaction/payment_data/paypal_account')) {
                $tokenInfo['NnPaypalAccount'] = $response->getData('transaction/payment_data/paypal_account');
            }
            if ($response->getData('transaction/payment_data/paypal_transaction_id')) {
                $tokenInfo['NnPaypalTransactionId'] = $response->getData('transaction/payment_data/paypal_transaction_id');
            }
            if (!empty($tokenInfo)) {
                $transactionStatus->setTokenInfo($this->jsonHelper->jsonEncode($tokenInfo))->save();
            }
        }
    }

    /**
     * Handles On Hold actions
     *
     * @param array $additionalData
     * @param object $response
     * @param string $paymentMethodCode
     * @return array
     */
    private function handleOnHold($additionalData, $response, $paymentMethodCode)
    {
        $additionalData['ApiProcess'] = 'capture';
        $additionalData['ApiProcessedAt'] = $this->dateTime->date('d-m-Y H:i:s');
        $additionalData['NnStatus'] = $response->getData('transaction/status');

        $this->transactionStatusModel->loadByAttribute($response->getData('transaction/order_no'), 'order_id')
            ->setStatus($response->getData('transaction/status'))->save();

        // Due date update for Invoice payment
        if (in_array(
            $paymentMethodCode,
            [ConfigProvider::NOVALNET_INVOICE, ConfigProvider::NOVALNET_INVOICE_GUARANTEE, ConfigProvider::NOVALNET_INVOICE_INSTALMENT]
        )) {
            $formatDate = $this->timeZone->formatDate(
                $response->getData('transaction/due_date'),
                \IntlDateFormatter::LONG
            );
            $note = explode('|', $additionalData['NnInvoiceComments']);
            $additionalData['NnInvoiceComments'] = implode('|', $note);
            $additionalData['NnDueDate'] = $formatDate;
        }

        if (in_array(
            $paymentMethodCode,
            [
                ConfigProvider::NOVALNET_SEPA_INSTALMENT,
                ConfigProvider::NOVALNET_INVOICE_INSTALMENT
            ]
        )) {
            $instalmentCycleAmount = $this->novalnetRequestHelper->getFormattedAmount(
                $response->getData('instalment/cycle_amount'),
                'RAW'
            );
            $additionalData['InstallPaidAmount'] = $instalmentCycleAmount;
            $additionalData['PaidInstall'] = $response->getData('instalment/cycles_executed');
            $additionalData['DueInstall'] = $response->getData('instalment/pending_cycles');
            $additionalData['NextCycle'] = $response->getData('instalment/next_cycle_date');
            $additionalData['InstallCycleAmount'] = $instalmentCycleAmount;

            if ($futureInstalmentDates = $response->getData('instalment/cycle_dates')) {
                foreach ($futureInstalmentDates as $cycle => $futureInstalmentDate) {
                    $additionalData['InstalmentDetails'][$cycle] = [
                        'amount' => $instalmentCycleAmount,
                        'nextCycle' => $futureInstalmentDate ? date('Y-m-d', strtotime($futureInstalmentDate)) : '',
                        'paidDate' => ($cycle == 1) ? date('Y-m-d') : '',
                        'status' => ($cycle == 1) ? 'Paid' : 'Pending',
                        'reference' => ($cycle == 1) ? $response->getData('transaction/tid') : ''
                    ];
                }
            }

            $additionalData['InstalmentDetails'][1] = [
                'amount' => $instalmentCycleAmount,
                'nextCycle' => $response->getData('instalment/next_cycle_date'),
                'paidDate' => date('Y-m-d'),
                'status' => 'Paid',
                'reference' => $response->getData('transaction/tid')
            ];
        }
        return $additionalData;
    }
}
