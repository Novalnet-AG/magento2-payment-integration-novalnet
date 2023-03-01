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

class PaymentHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timeZone;

    /**
     * @var \Novalnet\Payment\Model\TransactionStatus
     */
    private $transactionStatusModel;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    private $novalnetHelper;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
     * @param \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel,
        \Novalnet\Payment\Helper\Data $novalnetHelper
    ) {
        $this->dateTime = $dateTime;
        $this->timeZone = $timeZone;
        $this->transactionStatusModel = $transactionStatusModel;
        $this->novalnetHelper = $novalnetHelper;
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
        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($payment->getAdditionalData());

        $transactionStatus = '';
        if (!empty($additionalData['NnStatus']) && !empty($additionalData['NnPaymentType'])) {
            $transactionStatus = $this->novalnetHelper->getStatus($additionalData['NnStatus'], $order, $additionalData['NnPaymentType']);
        }

        if ($response->getData('action') == 'NN_ZeroCapture') {
            $payment->setIsTransactionClosed(false)
                ->setShouldCloseParentTransaction(false);
        } elseif (in_array($response->getData('action'), ['NN_Authorize', 'NN_Capture']) &&
            isset($additionalData['NnTid'])
        ) {
            // Authorize or Capture the initial redirect transaction
            $payment->setTransactionId($additionalData['NnTid'])
                ->setAmount($order->getGrandTotalAmount())
                ->setIsTransactionClosed(false)
                ->setShouldCloseParentTransaction(false);

            if ($transactionStatus == 'ON_HOLD') {
                $payment->setTransactionId($additionalData['NnTid']);
            }

            if ($response->getData('Async') == 'callback') { // Capture from Novalnet admin portal through callback
                $payment->setTransactionId($additionalData['NnTid'] . '-capture');
            }
        } elseif (!empty($response->getData())) {
            if (!empty($response->getData('result/redirect_url'))) {
                $payment->setAdditionalData(
                    $this->novalnetHelper->jsonEncode($this->novalnetHelper->buildRedirectAdditionalData($response))
                );
            } else {
                if ($transactionStatus == 'ON_HOLD') {
                    $additionalData = $this->handleOnHold($additionalData, $response);
                    // Capture the Authorized transaction
                    $payment->setTransactionId($response->getData('transaction/tid') . '-capture')
                        ->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))
                        ->setIsTransactionClosed(false)
                        ->setShouldCloseParentTransaction(false);
                } else {
                    // Authorize or Capture the transaction
                    $payment->setTransactionId($response->getData('transaction/tid'))
                        ->setAmount($order->getGrandTotalAmount())
                        ->setAdditionalData(
                            $this->novalnetHelper->jsonEncode($this->novalnetHelper->buildAdditionalData($response, $payment))
                        )->setIsTransactionClosed(false)
                        ->setShouldCloseParentTransaction(false);

                    if (in_array(
                        $response->getData('transaction/payment_type'),
                        [
                            'GUARANTEED_DIRECT_DEBIT_SEPA',
                            'GUARANTEED_INVOICE',
                            'INSTALMENT_DIRECT_DEBIT_SEPA',
                            'INSTALMENT_INVOICE'
                        ]
                    )) {
                        $amount = $this->novalnetHelper->getFormattedAmount($response->getData('transaction/amount'), 'RAW');
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
            $this->novalnetHelper->savePaymentToken($order, $paymentMethodCode, $response);
        }
    }

    /**
     * Handles On Hold actions
     *
     * @param array $additionalData
     * @param object $response
     * @return array
     */
    private function handleOnHold($additionalData, $response)
    {
        $additionalData['ApiProcess'] = 'capture';
        $additionalData['ApiProcessedAt'] = $this->dateTime->date('d-m-Y H:i:s');
        $additionalData['NnStatus'] = $response->getData('transaction/status');

        $this->transactionStatusModel->loadByAttribute($response->getData('transaction/order_no'), 'order_id')
            ->setStatus($response->getData('transaction/status'))->save();

        $paymentType = !empty($additionalData['NnPaymentType']) ? $additionalData['NnPaymentType'] : '';

        // Due date update for Invoice payment
        if (in_array(
            $paymentType,
            ['INVOICE', 'GUARANTEED_INVOICE', 'INSTALMENT_INVOICE']
        )) {
            $formatDate = $this->timeZone->formatDate(
                $response->getData('transaction/due_date'),
                \IntlDateFormatter::LONG
            );
            $note = (!empty($additionalData['NnInvoiceComments'])) ? explode('|', $additionalData['NnInvoiceComments']) : [];
            $additionalData['NnInvoiceComments'] = (!empty($note)) ? implode('|', $note) : '';
            $additionalData['NnDueDate'] = $formatDate;
        }

        if (in_array(
            $paymentType,
            [
                'INSTALMENT_DIRECT_DEBIT_SEPA',
                'INSTALMENT_INVOICE'
            ]
        )) {
            $instalmentCycleAmount = $this->novalnetHelper->getFormattedAmount(
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
