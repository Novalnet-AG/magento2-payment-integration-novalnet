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
        'mage/url',
        'mage/storage',
        'mage/translate',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Customer/js/model/customer',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/cart/totals-processor/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/modal/alert',
        'Magento_Ui/js/modal/modal',
        'Novalnet_Payment/js/action/get-redirect-url',
        'novalnetCheckout'
    ],
    function (
        $,
        url,
        storage,
        $t,
        Component,
        urlBuilder,
        customer,
        customerData,
        totalsDefaultProvider,
        quote,
        alert,
        modal,
        redirectURLAction
    ) {
        'use strict';

        var nn_cc = {
            defaults: {
                template: {
                    name: 'Novalnet_Payment/payment/novalnetCc',
                    afterRender: function (renderedNodesArray, data) {
                        data.initIframe();
                    }
                },
                ccHashValue: '',
                ccUniqueid: '',
                ccDoRedirect: '',
                novalnetPaymentToken: ''
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'ccHashValue',
                        'ccUniqueid',
                        'ccDoRedirect',
                        'novalnetPaymentToken'
                    ]);
                this.novalnetPaymentToken.subscribe(this.onAccountChange, this);
                if (window.checkoutConfig.payment[this.getCode()].tokenId != '' &&
                    window.checkoutConfig.payment[this.getCode()].storePayment == '1') {
                        this.novalnetPaymentToken(window.checkoutConfig.payment[this.getCode()].tokenId);
                } else {
                    this.novalnetPaymentToken('new_account');
                }
                var cart = customerData.get('cart');
                cart.subscribe(this.refreshCheckout, this);
                quote.totals.subscribe(this.reLoadIframe, this);
                return this;
            },

            refreshCheckout: function() {
                var cart = customerData.get('cart');
                if (cart().summary_count && cart().summary_count > 0) {
                    totalsDefaultProvider.estimateTotals(quote.shippingAddress());
                }
            },

            reLoadIframe: function() {
                nn_cc.initIframe();
                if (window.checkoutConfig.payment[this.getCode()].tokenId != '' &&
                    window.checkoutConfig.payment[this.getCode()].storePayment == '1') {
                        this.novalnetPaymentToken(window.checkoutConfig.payment[this.getCode()].tokenId);
                        this.onAccountChange(window.checkoutConfig.payment[this.getCode()].tokenId);
                } else {
                    this.novalnetPaymentToken('new_account');
                    this.onAccountChange('new_account');
                }
            },

            onAccountChange: function (selectedAccount) {
                if (selectedAccount == 'new_account') {
                    $("#novalnet_form_cc").show();
                } else {
                    $("#novalnet_form_cc").hide();
                }
            },

            /**
             * After place order callback
             */
            afterPlaceOrder: function () {
                // check is cc3d
                if ($('#' + this.getCode() + '_do_redirect').val() != 0) {
                    this.redirectAfterPlaceOrder = false;

                    redirectURLAction().success(function (response) {
                        if (response) {
                            window.location.replace(response);
                        } else {
                            window.location.replace(url.build('checkout/cart'));
                        }
                    }).error(function (xhr, ajaxOptions, thrownError) {
                        globalMessageList.addErrorMessage({
                            message: $t(thrownError)
                        });
                        window.location.replace(url.build('checkout/cart'));
                    });
                }
            },

            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'novalnetCc_pan_hash': $('#' + this.getCode() + '_hash').val(),
                        'novalnetCc_unique_id': $('#' + this.getCode() + '_uniqueid').val(),
                        'novalnetCc_do_redirect': $('#' + this.getCode() + '_do_redirect').val(),
                        'novalnetCc_create_token': this.canStorePayment(),
                        'novalnetCc_token': this.getPaymentToken()
                    }
                };
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
             * Returns payment method icons
             */
            getIcons: function () {
                return window.checkoutConfig.payment[this.item.method].icon;
            },

            getCardlogo : function (cardName) {
                return window.checkoutConfig.payment[this.getCode()].cardLogoUrl+'/novalnet'+cardName.toLowerCase()+'.png';
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
                                    if ($('form#novalnetCc .novalnet-payment-saved-payments').length <= 0) {
                                        $('form#novalnetCc .novalnet-payment-new_account').remove();
                                    }
                                    $('#novalnet_form_cc').show();
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
                modal(options, $('#remove-nntoken-cc-modal'));
                $("#remove-nntoken-cc-modal").modal("openModal");
            },

            /**
             * Returns payment method signature key
             */
            initIframe: function () {
                var paymentCode = "novalnetCc";
                if ((window.checkoutConfig.payment[paymentCode].storePayment != '1') || (window.checkoutConfig.payment[paymentCode].storedPayments.length == '0')) {
                    this.onAccountChange('new_account');
                } else {
                    this.onAccountChange(window.checkoutConfig.payment[paymentCode].tokenId);
                }

                var iframeParams = window.checkoutConfig.payment[paymentCode].iframeParams;
                NovalnetUtility.setClientKey(iframeParams.client_key);
                var request = {
                    callback: {
                        on_success: function (result) {
                            if (result) {
                                $('#' + paymentCode + '_hash').val(result['hash']);
                                $('#' + paymentCode + '_uniqueid').val(result['unique_id']);
                                $('#' + paymentCode + '_do_redirect').val(result['do_redirect']);
                                $('#' + paymentCode + '_submit').trigger('click');
                                return false;
                            }
                        },
                        on_error: function (result) {
                            alert({
                                content: result['error_message']
                            });
                        },
                        on_show_overlay: function () {
                            $('#novalnet_iframe').addClass("novalnet-challenge-window-overlay");
                        },
                        on_hide_overlay: function () {
                            $('#novalnet_iframe').removeClass("novalnet-challenge-window-overlay");
                        },
                        on_show_captcha: function (result) {
                            $('#' + this.getCode() + '_gethash').val(0);
                        },
                    },
                    custom: {
                        lang: iframeParams.lang
                    },
                    iframe: {
                        id: "novalnet_iframe",
                        inline: window.checkoutConfig.payment[paymentCode].inlineForm,
                        style: {
                            container: window.checkoutConfig.payment[paymentCode].styleText,
                            input: window.checkoutConfig.payment[paymentCode].inputStyle,
                            label: window.checkoutConfig.payment[paymentCode].labelStyle,
                        },
                        text: {
                            cardHolder : {
                                label: $t('Card holder name'),
                                input: $t('Name on card'),
                            },
                            cardNumber : {
                                label: $t('Card number'),
                                input: $t('XXXX XXXX XXXX XXXX')
                            },
                            expiryDate : {
                                label: $t('Expiry date'),
                                input: $t('MM / YY')
                            },
                            cvc : {
                                label: $t('CVC/CVV/CID'),
                                input: $t('XXX')
                            },
                            cvcHint : $t('what is this?'),
                            error : $t('Your credit card details are invalid')
                        }
                    },
                    customer: this.getCustomerObject(),
                    transaction: {
                        amount: this.getGrandTotal(),
                        currency: window.checkoutConfig.payment[paymentCode].currencyCode,
                        test_mode: window.checkoutConfig.payment[paymentCode].testmode,
                        enforce_3d: window.checkoutConfig.payment[paymentCode].enforce_3d
                    }
                };
                if ($('#novalnet_iframe').length) {
                    NovalnetUtility.createCreditCardForm(request);
                }
            },

            getEmail: function () {
                if(quote.guestEmail) return quote.guestEmail;
                else return window.checkoutConfig.customerData.email;
            },

            getCustomerObject : function () {
                if (quote.isVirtual()) {
                    if (quote.billingAddress() != null) {
                        var customer = {
                            first_name: quote.billingAddress().firstname,
                            last_name: quote.billingAddress().lastname,
                            email: this.getEmail(),
                            billing: {
                                street: this.getStreet(quote.billingAddress().street),
                                city: quote.billingAddress().city,
                                zip: quote.billingAddress().postcode,
                                country_code: quote.billingAddress().countryId
                            },
                            shipping: { same_as_billing: 1 }
                        };
                        return customer;
                    } else {
                        return;
                    }
                } else if (quote.billingAddress() != null && quote.shippingAddress() != null) {
                    if (this.getStreet(quote.billingAddress().street) == this.getStreet(quote.shippingAddress().street) &&
                        quote.billingAddress().city == quote.shippingAddress().city &&
                        quote.billingAddress().postcode == quote.shippingAddress().postcode &&
                        quote.billingAddress().countryId == quote.shippingAddress().countryId) {
                            var customer = {
                                first_name: quote.billingAddress().firstname,
                                last_name: quote.billingAddress().lastname,
                                email: this.getEmail(),
                                billing: {
                                    street: this.getStreet(quote.billingAddress().street),
                                    city: quote.billingAddress().city,
                                    zip: quote.billingAddress().postcode,
                                    country_code: quote.billingAddress().countryId
                                },
                                shipping: { same_as_billing: 1 }

                            };
                    } else {
                        var customer = {
                            first_name: quote.billingAddress().firstname,
                            last_name: quote.billingAddress().lastname,
                            email: this.getEmail(),
                            billing: {
                                street: this.getStreet(quote.billingAddress().street),
                                city: quote.billingAddress().city,
                                zip: quote.billingAddress().postcode,
                                country_code: quote.billingAddress().countryId
                            },
                            shipping: {
                                first_name: quote.shippingAddress().firstname,
                                last_name: quote.shippingAddress().lastname,
                                street: this.getStreet(quote.shippingAddress().street),
                                city: quote.shippingAddress().city,
                                zip: quote.shippingAddress().postcode,
                                country_code: quote.shippingAddress().countryId
                            }
                        };
                    }
                    return customer;
                } else if (quote.billingAddress() != null) {
                    var customer = {
                        first_name: quote.billingAddress().firstname,
                        last_name: quote.billingAddress().lastname,
                        email: this.getEmail(),
                        billing: {
                            street: this.getStreet(quote.billingAddress().street),
                            city: quote.billingAddress().city,
                            zip: quote.billingAddress().postcode,
                            country_code: quote.billingAddress().countryId
                        },
                        shipping: { same_as_billing: 1 }
                    };
                    return customer;
                } else {
                    return null;
                }
            },

            getStreet: function(streetArray) {
                var i, street = '';
                for(i=0; i<streetArray.length; i++) {
                    if(streetArray[i] != '') {
                        street += streetArray[i] + ' ';
                    }
                }
                return street.trim();
            },

            getGrandTotal: function() {
                if (quote.totals()) {
                    return Math.round(parseFloat(quote.totals()['base_grand_total']) * 100);
                }
            },

            reSize: function () {
                if ($('#novalnet_iframe').length > 0) {
                    NovalnetUtility.setCreditCardFormHeight();
                }
            },

            validate: function () {
                if (this.getPaymentToken()) {
                    return true;
                } else {
                    if ($('#' + this.getCode() + '_gethash').val() != 0 && $('#' + this.getCode() + '_hash').val() != '') {
                        $('#' + this.getCode() + '_gethash').val(0);
                        return true;
                    } else {
                        $('#' + this.getCode() + '_gethash').val(1);
                        NovalnetUtility.getPanHash();
                    }
                }

                return false;
            },
        };

        $(document).on('click', '#co-payment-form input[type="radio"]', function (event) {
            if (this.value == "novalnetCc") {
                nn_cc.reSize();
            }
        });

        $(document).on('click', '.action-update, #billing-address-same-as-shipping-novalnetCc', function (event) {
            if ($('.payment-method-title input[type="radio"]:checked').val() == "novalnetCc") {
                nn_cc.initIframe();
                if($('#novalnetCc_new_account').is(':checked'))
                {
                    $("#novalnet_form_cc").show();
                }
            }
        });

        $(document).ready(function () {
            $(window).resize(function () {
                nn_cc.reSize();
            });
        });

        return Component.extend(nn_cc);
    }
);
