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
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/action/select-payment-method',
        'mage/validation',
        'novalnetCheckout'
    ],
    function (
        $,
        Component,
        quote,
        checkoutData,
        selectPaymentMethodAction,
    ) {
        'use strict';

        var current_date = new Date();
        var max_year = current_date.getFullYear() - 18;
        var min_year = current_date.getFullYear() - 91;
        var years_range = [];
        for(var year = max_year; year >= min_year; year--) {
            years_range.push(String(year));
        }

        return Component.extend({
            defaults: {
                template: {
                    name: 'Novalnet_Payment/payment/novalnetInvoiceGuarantee',
                    afterRender: function (renderedNodesArray, data) {
                        data.displayPayment();
                    }
                },
            },
            currentBillingAddress: quote.billingAddress,

            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'novalnetInvoiceGuarantee_dob': $('#' + this.getCode() + '_dob').val(),
                    }
                };
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
                }
                if (($('input[id="novalnetInvoice"][name="payment[method]"]').length && !$('input[id="novalnetInvoiceGuarantee"][name="payment[method]"]').length)) {
                    $('input[id="novalnetInvoice"][name="payment[method]"]').closest('.payment-method').show();
                }
                if ((!$('input[id="novalnetInvoice"][name="payment[method]"]').length && $('input[id="novalnetInvoiceGuarantee"][name="payment[method]"]').length)) {
                    $('input[id="novalnetInvoiceGuarantee"][name="payment[method]"]').closest('.payment-method').show();
                }
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

            /**
             * get DOB
             */
            getDob: function () {
                if (window.checkoutConfig.customerData.dob) {
                    var date = new Date(window.checkoutConfig.customerData.dob);
                    var newDate = ("0" + date.getDate()).slice(-2)+'.'+("0" + (date.getMonth() + 1)).slice(-2)+'.'+date.getFullYear();
                    return newDate;
                }
            },

            validate: function () {
                var form = 'form[data-role=novalnetInvoiceGuarantee]';
                return $(form).validation() && $(form).validation('isValid');
            },
            
            validateCompany: function (company) {
                if (company != null && window.checkoutConfig.payment[this.getCode()].allow_b2b_customer == 1) {
                    if (!NovalnetUtility.isValidCompanyName(company)) {
                        return false;
                    } else {
                        return true;
                    }
                }
                return false;
            },

            allowOnlyNumber: function (data, event) {
                if(event.charCode < 48 || event.charCode > 57) {
                    return false;
                }
                return true;
            },
        });
    }
);
