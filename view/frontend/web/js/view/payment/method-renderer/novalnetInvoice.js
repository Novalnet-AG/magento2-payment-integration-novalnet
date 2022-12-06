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
define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/view/payment/default'
    ],
    function (
        $,
        quote,
        checkoutData,
        selectPaymentMethodAction,
        Component
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: {
                    name: 'Novalnet_Payment/payment/novalnetInvoice',
                    afterRender: function (renderedNodesArray, data) {
                        data.displayPayment();
                    },
                },
            },

            /**
             * Returns payment method instructions
             */
            getInstructions: function () {
                return window.checkoutConfig.payment[this.getCode()].instructions;
            },

            /**
             * Returns payment testmode status
             */
            getTestmode: function () {
                return window.checkoutConfig.payment[this.getCode()].testmode;
            },

            /**
             * Returns payment method logo URL
             */
            getLogo: function () {
                return window.checkoutConfig.payment[this.getCode()].logo;
            },
             
            displayPayment: function () {
                if ($('input[id="novalnetInvoice"][name="payment[method]"]').length && $('input[id="novalnetInvoiceGuarantee"][name="payment[method]"]').length) {
                    if (JSON.stringify(quote.billingAddress()) == JSON.stringify(quote.shippingAddress()) || quote.isVirtual()) {
                        if ($('input[id="novalnetInvoice"][name="payment[method]"]:visible').length) {
                            $('input[id="novalnetInvoice"][name="payment[method]"]').closest('.payment-method').hide();
                        }
                        if ($('input[id="novalnetInvoiceGuarantee"][name="payment[method]"]:hidden').length) {
                            $('input[id="novalnetInvoiceGuarantee"][name="payment[method]"]').closest('.payment-method').show();
                            if (checkoutData.getSelectedPaymentMethod() == 'novalnetInvoice') {
                                var methodData = {
                                    'method': 'novalnetInvoiceGuarantee',
                                    'additional_data': {}
                                };
                                selectPaymentMethodAction(methodData);
                                checkoutData.setSelectedPaymentMethod(methodData.method);
                            }
                        }
                    } else {
                        if ($('input[id="novalnetInvoiceGuarantee"][name="payment[method]"]:visible').length) {
                            $('input[id="novalnetInvoiceGuarantee"][name="payment[method]"]').closest('.payment-method').hide();
                        }
                        if ($('input[id="novalnetInvoice"][name="payment[method]"]:hidden').length) {
                            $('input[id="novalnetInvoice"][name="payment[method]"]').closest('.payment-method').show();
                            if (checkoutData.getSelectedPaymentMethod() == 'novalnetInvoiceGuarantee') {
                                var methodData = {
                                    'method': 'novalnetInvoice',
                                    'additional_data': {}
                                };
                                selectPaymentMethodAction(methodData);
                                checkoutData.setSelectedPaymentMethod(methodData.method);
                            }
                        }
                    }
                } else if (($('input[id="novalnetInvoice"][name="payment[method]"]').length && !$('input[id="novalnetInvoiceGuarantee"][name="payment[method]"]').length)) {
                    $('input[id="novalnetInvoice"][name="payment[method]"]').closest('.payment-method').show();
                } else if ((!$('input[id="novalnetInvoice"][name="payment[method]"]').length && $('input[id="novalnetInvoiceGuarantee"][name="payment[method]"]').length)) {
                    $('input[id="novalnetInvoiceGuarantee"][name="payment[method]"]').closest('.payment-method').show();
                }
            }
        });
    }
);
