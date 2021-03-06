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
        'mage/storage',
        'mage/translate',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Ui/js/modal/alert',
        'Magento_Ui/js/modal/modal',
        'mage/validation',
        'novalnetCheckout'
    ],
    function (
        $,
        storage,
        $t,
        Component,
        urlBuilder,
        customer,
        quote,
        checkoutData,
        selectPaymentMethodAction,
        alert,
        modal
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
                    name: 'Novalnet_Payment/payment/novalnetSepaGuarantee',
                    afterRender: function (renderedNodesArray, data) {
                        data.displayPayment();
                    }
                },
                sepaAccountNumber: '',
                novalnetPaymentToken: ''
            },
            currentBillingAddress: quote.billingAddress,

            initObservable: function () {
                this._super()
                    .observe([
                        'sepaAccountNumber',
                        'novalnetPaymentToken'
                    ]);
                this.novalnetPaymentToken.subscribe(this.onAccountChange, this);
                this.novalnetPaymentToken(window.checkoutConfig.payment[this.getCode()].tokenId);

                return this;
            },

            onAccountChange: function (selectedAccount) {
                if (selectedAccount == 'new_account') {
                    $("#novalnet_form_sepa_guarantee").show();
                } else {
                    $("#novalnet_form_sepa_guarantee").hide();
                }
            },

            getData: function () {
                var CustomerDOB = $('#' + this.getCode() + '_dob').val();

                return {
                    'method': this.item.method,
                    'additional_data': {
                        'novalnetSepaGuarantee_iban': this.getIBAN(),
                        'novalnetSepaGuarantee_dob': CustomerDOB,
                        'novalnetSepaGuarantee_create_token': this.canStorePayment(),
                        'novalnetSepaGuarantee_token': this.getPaymentToken()
                    }
                };
            },

            displayPayment: function () {
                if (window.checkoutConfig.payment[this.getCode()].storePayment != '1' || window.checkoutConfig.payment[this.getCode()].storedPayments.length == 0) {
                    this.onAccountChange('new_account');
                } else {
                    this.onAccountChange(window.checkoutConfig.payment[this.getCode()].tokenId);
                }
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
                } else if (($('input[id="novalnetSepa"][name="payment[method]"]').length && !$('input[id="novalnetSepaGuarantee"][name="payment[method]"]').length)) {
                    $('input[id="novalnetSepa"][name="payment[method]"]').closest('.payment-method').show();
                } else if ((!$('input[id="novalnetSepa"][name="payment[method]"]').length && $('input[id="novalnetSepaGuarantee"][name="payment[method]"]').length)) {
                    $('input[id="novalnetSepaGuarantee"][name="payment[method]"]').closest('.payment-method').show();
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
             * check can store payment reference
             */
            canStorePayment: function () {
                if (!this.getPaymentToken()) {
                    return ($('#' + this.getCode() + '_store_payment').prop("checked") == true);
                } else {
                    return false;
                }
            },

            /**
             * check can show store payment
             */
            showStorePayment: function () {
                if (window.checkoutConfig.payment[this.getCode()].storePayment == '1') {
                    return customer.isLoggedIn();
                } else {
                    return false;
                }
            },

            /**
             * get stored payments
             */
            getStoredPayments: function () {
                if (window.checkoutConfig.payment[this.getCode()].storePayment == '1') {
                    return window.checkoutConfig.payment[this.getCode()].storedPayments;
                } else {
                    return false;
                }
            },

            /**
             * get payment token
             */
            getPaymentToken: function () {
                if (this.novalnetPaymentToken() && this.novalnetPaymentToken() != 'new_account') {
                    return this.novalnetPaymentToken();
                } else {
                    return false;
                }
            },

            /**
             * remove payment token
             */
            removeToken: function (tokenId) {
                var parent = this;

                var options = {
                    type: 'popup',
                    modalClass: 'nntoken-remove-popup-modal',
                    responsive: true,
                    innerScroll: false,
                    buttons: [{
                        text: $t('No'),
                        class: 'nntoken-cancel-remove-modal action tocart primary',
                        click: function () {
                            this.closeModal();
                        }
                    },
                    {
                        text: $t('Yes'),
                        class: 'nntoken-confirm-remove-modal action tocart primary',
                        click: function () {
                            var button = this;
                            storage.get(
                                urlBuilder.createUrl('/novalnet/payment/remove_token/' + tokenId, {})
                            ).success(function (response) {
                                button.closeModal();
                                if (response) {
                                    $('a[nntoken-id=' + tokenId + ']').closest('.novalnet-payment-saved-payments').remove();
                                    if ($('form#novalnetSepaGuarantee .novalnet-payment-saved-payments').length <= 0) {
                                        $('form#novalnetSepaGuarantee .novalnet-payment-new_account').remove();
                                    }
                                    $('#novalnet_form_sepa_guarantee').show();
                                    parent.novalnetPaymentToken('new_account');
                                    window.location = window.location.hash ;
                                    location.reload();
                                } else {
                                    alert({
                                        content: $t('Novalnet Payment Token does not found')
                                    });
                                }
                            }).error(function (xhr, ajaxOptions, thrownError) {
                                button.closeModal();
                                alert({
                                    content: $t(thrownError)
                                });
                            });
                        }
                    }]
                };

                // Initialize and Open popup
                modal(options, $('#remove-nntoken-modal'));
                $("#remove-nntoken-modal").modal("openModal");
            },

            /**
             * get IBAN
             */
            getIBAN: function () {
                if (!this.getPaymentToken()) {
                    return $('#' + this.getCode() + '_account_number').val();
                }
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
                var form = 'form[data-role=novalnetSepaGuarantee]';
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

            sepaGuaranteeMandateToggle: function () {
                $('#sepa_guarantee_mandate_details').toggle('slow');
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
