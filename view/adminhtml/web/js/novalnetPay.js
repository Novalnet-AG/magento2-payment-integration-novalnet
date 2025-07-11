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
define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'mage/url',
    'mage/storage',
    'jquery/ui',
    'novalnetPaymentFormJs',
    'novalnetUtilityJs'
], function ($, alert, $t, url, storage) {
    'use strict';
    $(document).ready(function () {
        let novalnetPaymentForm = new NovalnetPaymentForm();
        var topSubmitButton = document.getElementById(
            'submit_order_top_button'
        ).onclick;

        $.widget('mage.novalnetPayJs', {
        /**
         * Initialize the payment form
         */
            initPaymentForm: function () {
                const self = this,
                    paymentFormRequestObj = {
                        iframe: '#novalnetPaymentIFrame',
                        initForm: {
                            uncheckPayments: true,
                            showButton: false
                        }
                    };

                novalnetPaymentForm.initiate(paymentFormRequestObj);

                novalnetPaymentForm.validationResponse(() => {
                    novalnetPaymentForm.initiate(paymentFormRequestObj);
                });

                novalnetPaymentForm.selectedPayment(() => {
                    $('#p_method_novalnetPay').prop('checked', true);
                    document.getElementById('submit_order_top_button').onclick = null;
                    const bottomSubmitButton = document.querySelector(
                        '.action-default.scalable.save.primary'
                    );

                    if (bottomSubmitButton) {
                        bottomSubmitButton.onclick = null;
                    }
                    self.setButtonAttr();
                });

                novalnetPaymentForm.getMPaymentResponse((response) => {
                    self.removeDisabled();
                    response = self.isJson(response) ? JSON.parse(response) : response;

                    if (response.result.status === 'SUCCESS') {
                        if (
                            response.booking_details.do_redirect &&
                response.booking_details.do_redirect === 1
                        ) {
                            alert({
                                title: $t('Error'),
                                content: $t(
                                    'Card type not accepted, try using another card type'
                                )
                            });
                            return false;
                        }

                        const nnResponse = JSON.stringify(response);
                        let nnPaymentDataField = document.getElementById(
                            'novalnetPay_payment_data'
                        );

                        if (!nnPaymentDataField) {
                            nnPaymentDataField = document.createElement('input');
                            nnPaymentDataField.type = 'hidden';
                            nnPaymentDataField.id = 'novalnetPay_payment_data';
                            nnPaymentDataField.name = 'payment[novalnetPay_payment_data]';
                            const nnPaymentMethodField = document.getElementById(
                                'p_method_novalnetPay'
                            );

                            nnPaymentMethodField.insertAdjacentElement(
                                'afterend',
                                nnPaymentDataField
                            );
                            nnPaymentDataField.value = nnResponse;
                        }

                        $('#nn_can_submit_form').val(1);
                        if (topSubmitButton) {
                            topSubmitButton.call(
                                document.getElementById('submit_order_top_button')
                            );
                        }
                    } else {
                        $('#nn_can_submit_form').val(0);
                        alert({
                            title: $t('Error'),
                            content: $t(response.result.message)
                        });
                    }
                });
            },

            /**
         * Set button attributes and custom behavior
         */
            setButtonAttr: function () {
                const self = this;

                $('#submit_order_top_button, .order-totals .actions button')
                    .off('onclick')
                    .on('click', function (event) {
                        if (
                            $('input[name="payment[method]"]:checked').val() ===
                'novalnetPay'
                        ) {
                            event.preventDefault();
                            if ($('#nn_can_submit_form').val() !== 1) {
                                novalnetPaymentForm.getMPaymentRequest();
                            }
                        } else if (topSubmitButton) {
                            topSubmitButton.call(
                                document.getElementById('submit_order_top_button')
                            );
                        }
                    });
            },

            /**
         * Remove disabled attributes
         */
            removeDisabled: function () {
                $('#nn_chk_button').prop('disabled', false);
                $('#nn_can_submit_form').prop('disabled', false);
            },

            /**
         * Check if data is valid JSON
         */
            isJson: function (data) {
                try {
                    JSON.parse(data);
                    return true;
                } catch (e) {
                    return false;
                }
            },

            /**
         * Automatically triggered on widget creation
         */
            _create: function () {
                const self = this;

                self.removeDisabled();
                if ($('input[name="payment[method]"]').length) {
                    $('input[name="payment[method]"]').on('click', function () {
                        if (this.id !== 'p_method_novalnetPay') {
                            novalnetPaymentForm.uncheckPayment();
                        }
                        $('#payment_form_novalnetPay').css({ display: 'block' });
                    });

                    $('input[name="payment[method]"]').on('change', function () {
                        if (this.id !== 'p_method_novalnetPay') {
                            novalnetPaymentForm.uncheckPayment();
                        }
                        $('#payment_form_novalnetPay').css({ display: 'block' });
                    });
                }

                $('#p_method_novalnetPay')
                    .closest('.admin__field-option')
                    .css({ display: 'none' });

                self.initPaymentForm();
            }
        });
        return $.mage.novalnetPayJs();
    });
});
