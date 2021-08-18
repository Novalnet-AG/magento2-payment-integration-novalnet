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
        'uiComponent',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        $,
        Component,
        quote,
        selectPaymentMethodAction,
        checkoutData,
        rendererList
    ) {
        'use strict';

        rendererList.push(
            {
                type: 'novalnetCc',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetCc'
            },
            {
                type: 'novalnetSepa',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetSepa'
            },
            {
                type: 'novalnetInvoice',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetInvoice'
            },
            {
                type: 'novalnetPrepayment',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetPrepayment'
            },
            {
                type: 'novalnetInvoiceGuarantee',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetInvoiceGuarantee'
            },
            {
                type: 'novalnetSepaGuarantee',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetSepaGuarantee'
            },
            {
                type: 'novalnetCashpayment',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetCashpayment'
            },
            {
                type: 'novalnetMultibanco',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetMultibanco'
            },
            {
                type: 'novalnetPaypal',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetPaypal'
            },
            {
                type: 'novalnetBanktransfer',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetBanktransfer'
            },
            {
                type: 'novalnetIdeal',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetIdeal'
            },
            {
                type: 'novalnetApplepay',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetApplepay'
            },
            {
                type: 'novalnetBancontact',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetBancontact'
            },
            {
                type: 'novalnetEps',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetEps'
            },
            {
                type: 'novalnetGiropay',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetGiropay'
            },
            {
                type: 'novalnetPrzelewy',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetPrzelewy'
            },
            {
                type: 'novalnetPostFinance',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetPostFinance'
            },
            {
                type: 'novalnetPostFinanceCard',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetPostFinanceCard'
            },
            {
                type: 'novalnetInvoiceInstalment',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetInvoiceInstalment'
            },
            {
                type: 'novalnetSepaInstalment',
                component: 'Novalnet_Payment/js/view/payment/method-renderer/novalnetSepaInstalment'
            }
        );

        return Component.extend({
            initObservable: function () {
                quote.billingAddress.subscribe(this.onBillingAddressChange, this);
                return this;
            },

            onBillingAddressChange: function (selectedAddress) {
                if (selectedAddress == null) {
                    return;
                }

                $(document).ready(function(){
                    $.validator.addMethod(
                        "validate-novalnet-date", 
                        function (val, elm) {
                            if(!NovalnetUtility.validateDateFormat(val)) {
                                return false;
                            }
                            return true;
                         },
                        $.mage.__("Please Enter valid date of Birth!")
                    );
                    var saveInAddressBook = selectedAddress["saveInAddressBook"];
                    delete selectedAddress["saveInAddressBook"];
                    // check billingAddress and shippingAddress are same and toggle payments
                    setTimeout(function(){
                        if (($('input[id="novalnetSepa"][name="payment[method]"]').length && $('input[id="novalnetSepaGuarantee"][name="payment[method]"]').length)) {
                            if (JSON.stringify(quote.billingAddress()) == JSON.stringify(quote.shippingAddress()) || quote.isVirtual()) {
                                if ($('input[id="novalnetSepa"][name="payment[method]"]:visible').length) {
                                    $('input[id="novalnetSepa"][name="payment[method]"]').closest('.payment-method').hide();
                                }
                                if ($('input[id="novalnetSepaGuarantee"][name="payment[method]"]:hidden').length) {
                                    $('input[id="novalnetSepaGuarantee"][name="payment[method]"]').closest('.payment-method').show();
                                    if (checkoutData.getSelectedPaymentMethod() == 'novalnetSepa') {
                                        var methodData = {
                                            'method': 'novalnetSepaGuarantee',
                                            'additional_data': {}
                                        };
                                        selectPaymentMethodAction(methodData);
                                        checkoutData.setSelectedPaymentMethod(methodData.method);
                                    }
                                }
                            } else {
                                if ($('input[id="novalnetSepaGuarantee"][name="payment[method]"]:visible').length) {
                                    $('input[id="novalnetSepaGuarantee"][name="payment[method]"]').closest('.payment-method').hide();
                                }
                                if ($('input[id="novalnetSepa"][name="payment[method]"]:hidden').length) {
                                    $('input[id="novalnetSepa"][name="payment[method]"]').closest('.payment-method').show();
                                    if (checkoutData.getSelectedPaymentMethod() == 'novalnetSepaGuarantee') {
                                        var methodData = {
                                            'method': 'novalnetSepa',
                                            'additional_data': {}
                                        };
                                        selectPaymentMethodAction(methodData);
                                        checkoutData.setSelectedPaymentMethod(methodData.method);
                                    }
                                }
                            }
                        }
                        if (($('input[id="novalnetSepa"][name="payment[method]"]').length && !$('input[id="novalnetSepaGuarantee"][name="payment[method]"]').length)) {
                            $('input[id="novalnetSepa"][name="payment[method]"]').closest('.payment-method').show();
                        }
                        if ((!$('input[id="novalnetSepa"][name="payment[method]"]').length && $('input[id="novalnetSepaGuarantee"][name="payment[method]"]').length)) {
                            $('input[id="novalnetSepaGuarantee"][name="payment[method]"]').closest('.payment-method').show();
                        }
                        
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

                        selectedAddress["saveInAddressBook"] = saveInAddressBook;
                    }, 300);
                });
            }
        });
    }
);
