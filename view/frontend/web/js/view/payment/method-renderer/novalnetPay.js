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
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
        'mage/url',
        'mage/storage',
        'mage/translate',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/cart/totals-processor/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/modal/alert',
        'Magento_Checkout/js/model/totals',
        'Magento_Ui/js/model/messageList',
        'novalnetUtilityJs',
        'novalnetPaymentFormJs',
        'novalnetPaymentJs'
    ],
    function (
        $,
        selectPaymentMethodAction,
        checkoutData,
        url,
        storage,
        $t,
        Component,
        customerData,
        totalsDefaultProvider,
        quote,
        alert,
        totals,
        globalMessageList
    ) {
        'use strict';
        var novalnetPay = {
            defaults: {
                template: {
                    name: 'Novalnet_Payment/payment/novalnetPay',
                    afterRender: function (renderedNodesArray, data) {
                        $('#novalnetPay_payment-div').trigger('processStart');
                    }
                },
                paymentFormInstance: new NovalnetPaymentForm(),
                nnPaymentData: "",
                nnDoRedirect: "0",
                nnPostData: ""
            },

            initObservable: function () {
                const self = this;
                this._super()
                    .observe([
                        'nnPaymentData',
                        'nnDoRedirect',
                        'nnPostData'
                    ]);

                let cart = customerData.get('cart');
                cart.subscribe(this.refreshCheckout, this);
                window.addEventListener("hashchange", function () {
                    if (window.location.hash == '#payment') {
                        self.refreshCheckout();
                    }
                });
                return this;
            },

            refreshCheckout: function () {
                const self = this,
                    cart = customerData.get('cart');

                if (cart().summary_count && cart().summary_count > 0) {
                    totalsDefaultProvider.estimateTotals(quote.shippingAddress());
                }

                setTimeout(() => {
                    self.updatePaymentForm();
                }, 1000);
            },

            getGrandTotal: () => {
                if (quote.totals()) {
                    return Math.round(parseFloat(quote.totals()['base_grand_total']) * 100);
                }
            },

            /**
             * After place order callback
             */
            afterPlaceOrder: function () {
                if ($('#' + this.getCode() + '_do_redirect').val() == 1) {
                    $('#novalnetPay_payment-div').trigger('processStart');
                    const self = this;
                    const serviceUrl = url.build("/rest/V1/novalnet/payment/getRedirectURL", {}),
                        payLoad = {quoteId: quote.getQuoteId()};

                    self.redirectAfterPlaceOrder = false;
                    storage.post(serviceUrl, JSON.stringify(payLoad)).done( (response) => {
                        window.location.replace(response);
                    }).fail( (xhr, textStatus, errorThrown) => {
                        $('#novalnetPay_payment-div').trigger('processStop');
                        const errMsg = (xhr.responseJSON.message) ? xhr.responseJSON.message : errorThrown;
                        self.throwError(errMsg);
                    });
                }
            },

            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'novalnetPay_payment_data': $('#' + this.getCode() + '_payment_data').val(),
                    }
                };
            },

            /**
             * Throw's alert on payment error
             */
            throwError: (message) => {
                alert({
                    title: $t('Error'),
                    content: message
                });
            },

            isJson: (data) => {
                try {
                    JSON.parse(data);
                    return true;
                } catch (e) {
                    return false;
                }
            },

            validate: function () {
                const self = this;
                let details = $('#novalnetPay_post_data').val();
                if (details == '' || details == null || details == undefined) {
                    self.paymentFormInstance.getMPaymentRequest();
                    return false;
                } else {
                    let response = self.isJson(details) ? JSON.parse(details) : details;
                        $('#novalnetPay_post_data').val("");
                    if (response.result.status == "SUCCESS") {
                        $("#novalnetPay_do_redirect").val(response.booking_details.do_redirect ? response.booking_details.do_redirect : '0');
                        if (response.payment_details.process_mode && response.payment_details.process_mode == 'redirect') {
                            $("#novalnetPay_do_redirect").val('1');
                        }
                        $('#novalnetPay_payment_data').val(JSON.stringify(response));
                        return true;
                    } else {
                        $('#novalnetPay_payment_data').val("");
                        globalMessageList.addErrorMessage({
                            message: $t(response.result.message)
                        });
                        window.scrollTo({top: 0, behavior: 'smooth'});
                        return false;
                    }
                }
            },

            setIframeSrc: function () {
                const self = this,
                    serviceUrl = url.build('/rest/V1/novalnet/payment/getPayByLink', {}),
                    payload = {quoteId: quote.getQuoteId()};

                storage.post( serviceUrl, JSON.stringify(payload)).done( (response) => {
                    response = self.isJson(response) ? JSON.parse(response) : response;
                    if (response.result.status == "SUCCESS") {
                        document.getElementById('novalnetPaymentIFrame').src = response.result.redirect_url;
                        $("#novalnetPay_payment-div").css({"display" : "block"});
                    } else {
                        $("#novalnetPay_payment-div").css({"display" : "none"});
                        $('#novalnetPay_payment-div').trigger('processStop');
                    }
                }).fail( (xhr, textStatus, errorThrown) => {
                    $("#novalnetPay_payment-div").css({"display" : "none"});
                    $('#novalnetPay_payment-div').trigger('processStop');
                    const errMsg = (xhr.responseJSON.message) ? xhr.responseJSON.message : errorThrown;
                    self.throwError(errMsg);
                });
            },

            getLineItems: () => {
                let items = totals.totals().items,
                    currencyRate = window.checkoutConfig.quoteData.base_to_quote_rate,
                    lineItem = [],
                    i;

                if (items.length) {
                    for( i = 0; i < items.length; i++ ) {
                        lineItem[i] = {
                                label: items[i].name + " (" + Math.round(parseFloat(items[i].qty)) + " x " + Number.parseFloat(items[i].base_price).toFixed(2) + ")",
                                type: "SUBTOTAL",
                                amount: Math.round((parseFloat(items[i].base_row_total)) * 100) + ""
                            };
                    }
                }

                if (totals.totals().hasOwnProperty("tax_amount")) {
                    let tax = Math.round( (parseFloat(totals.totals().tax_amount) / parseFloat(currencyRate)) * 100);

                    if(parseInt(tax) > 0) {
                        lineItem.push({label: "Tax", type: "SUBTOTAL", amount: tax + ""});
                    }
                }

                if (totals.totals().hasOwnProperty("discount_amount")) {
                    let discountTotal = (Math.round( (parseFloat(totals.totals().discount_amount) / parseFloat(currencyRate)) * 100)).toString(),
                        discount = ((Math.sign(discountTotal)) == -1 ) ? discountTotal.substr(1) : discountTotal;

                    if(parseInt(discount) > 0) {
                        lineItem.push({label: "Discount", type: "SUBTOTAL", amount: "-" + discount});
                    }
                }

                if (totals.totals().hasOwnProperty("shipping_amount") && !quote.isVirtual()) {
                    let shippingTitle = "Shipping";
                    if (totals.totals().hasOwnProperty("total_segments")) {
                        let segments = totals.totals().total_segments;
                        $.each(segments, (index, value) => {
                            if (value.code == "shipping") {
                                shippingTitle = value.title
                                shippingTitle = shippingTitle.replace("Shipping & Handling", "")
                            }
                        });
                    }

                    if (shippingTitle == "") {
                        shippingTitle = "Shipping";
                    }

                    let Shipping = Math.round( (parseFloat(totals.totals().shipping_amount) / parseFloat(currencyRate)) * 100),
                        ShippingItem = {label: shippingTitle, type: "SUBTOTAL", amount: Shipping + ''};
                        lineItem.push(ShippingItem);
                }

                return lineItem;
            },

            initPaymentForm : function() {
                const self = this,
                    paymentFormRequestObj = {
                    iframe : '#novalnetPaymentIFrame',
                    initForm: {
                        creditCard: {
                            text : {
                                error: "Your credit card details are invalid",
                                card_holder: {
                                    label: "Card holder name",
                                    place_holder: "Name on CreditCard from post",
                                    error: "Please enter the valid card holder name"
                                },
                                card_number: {
                                    label: "Card number",
                                    place_holder: "XXXX XXXX XXXX XXXX",
                                    error: "Please enter the valid card number"
                                },
                                expiry_date: {
                                    label: "Expiry date",
                                    error: "Please enter the valid expiry month / year in the given format"
                                },
                                cvc: {
                                    label: "CVC/CVV/CID",
                                    place_holder: "XXX",
                                    error: "Please enter the valid CVC/CVV/CID"
                                }
                            }
                        },
                        orderInformation : {
                            lineItems: self.getLineItems(),
                            billing: {
                                requiredFields: ["postalAddress", "phone", "email"]
                            }
                        },
                        uncheckPayments: false,
                        showButton: false,
                    }
                };

                self.paymentFormInstance.validationResponse( (data) => {
                    self.paymentFormInstance.initiate(paymentFormRequestObj);
                    $('#novalnetPay_payment-div').trigger('processStop');

                    if (window.checkoutConfig.payment.novalnetPay.selectedMethod) {
                        self.paymentFormInstance.checkPayment(window.checkoutConfig.payment.novalnetPay.selectedMethod);
                    }
                });

                self.initPaymentFormCallbacks();
            },

            getStreet: function(streetArray) {
                var i, street = '';
                if (streetArray) {
                    for(i=0; i<streetArray.length; i++) {
                        if(streetArray[i] != '') {
                            street += streetArray[i] + ' ';
                        }
                    }

                    return street.trim();
                }

                return street;
            },

            updatePaymentForm: function () {
                const self = this;
                let billingAddress = quote.billingAddress(),
                    shippingAddress = quote.shippingAddress(),
                    shippingSameAsBilling = false;

                if (quote.isVirtual()) {
                    shippingSameAsBilling = true;
                } else if (billingAddress && shippingAddress) {
                    shippingSameAsBilling = (
                        billingAddress.city == shippingAddress.city &&
                        self.getStreet(billingAddress.street) == self.getStreet(shippingAddress.street) &&
                        billingAddress.postcode == shippingAddress.postcode &&
                        billingAddress.countryId == shippingAddress.countryId
                    );
                } else {
                    return false;
                }

                let updatedData = {
                    amount: self.getGrandTotal(),
                    billing_address: {
                        street: self.getStreet(billingAddress.street),
                        city: billingAddress.city,
                        zip: billingAddress.postcode,
                        country_code: billingAddress.countryId
                    }
                };

                if (shippingSameAsBilling) {
                    updatedData.same_as_billing = 1;
                } else {
                    updatedData.shipping_address = {
                        street: self.getStreet(shippingAddress.street),
                        city: shippingAddress.city,
                        zip: shippingAddress.postcode,
                        country_code: shippingAddress.countryId
                    }
                }

                self.paymentFormInstance.updateForm(updatedData, (data) => {});
            },

            initPaymentFormCallbacks: function () {
                const self = this;

                self.paymentFormInstance.selectedPayment( (data) => {
                    data = self.isJson(data) ? JSON.parse(data) : data;

                    if (data.payment_details.type) {
                        window.checkoutConfig.payment.novalnetPay.selectedMethod = data.payment_details.type;
                    }

                    selectPaymentMethodAction({'method': 'novalnetPay',
                        'additional_data': {}
                    });

                    checkoutData.setSelectedPaymentMethod('novalnetPay');

                    if (data.payment_details.type == "GOOGLEPAY" || data.payment_details.type == "APPLEPAY") {
                        $("#novalnetPay_billing-address").css({"display" : "none"});
                        $("#novalnetPay_actions-toolbar").css({"display" : "none"});
                    } else {
                        $("#novalnetPay_billing-address").css({"display" : "block"});
                        $("#novalnetPay_actions-toolbar").css({"display" : "block"});
                    }
                });

                $(document).on('click', '#co-payment-form input[type="radio"]', function (event) {
                    if (this.checked) {
                        self.paymentFormInstance.uncheckPayment();
                    }
                });

                $('form #discount-form button').on('click', function () {
                    let data = $('form #discount-form input').val();
                    if (data || $(this).hasClass('action-cancel')) {
                        self.refreshCheckout();
                    }
                });

                self.paymentFormInstance.getMPaymentResponse( (result) => {
                    $('#novalnetPay_post_data').val(JSON.stringify(result));
                    $('#novalnetPay_submit').trigger('click');
                });

                $('.action-update, #billing-address-same-as-shipping-novalnetPay').click(function () {
                    if ($('.payment-method-title input[type="radio"]:checked').val() == "novalnetPay") {
                        setTimeout(function () {
                            self.updatePaymentForm();
                        }, 500);
                    }
                });

                self.paymentFormInstance.walletResponse({
                    onProcessCompletion: async (response) => {
                        $('body').trigger('processStart');
                        response = self.isJson(response) ? JSON.parse(response) : response;

                        if (response.result.status == "SUCCESS") {
                            let serviceUrl = url.build("/rest/V1/novalnet/payment/placeOrder", {}),
                                bookingStatus = '',
                                payload = {data: response, paymentPage: true};

                            await storage.post( serviceUrl, JSON.stringify(payload)).done( (response) => {
                                if (self.isJson(response)) {
                                    response = JSON.parse(response);
                                }
                                bookingStatus = 'SUCCESS';
                                window.location.replace(response.redirectUrl);
                            }).fail( (xhr, textStatus, errorThrown) => {
                                bookingStatus = 'FAILURE';
                                $('body').trigger('processStop');
                                const errMsg = (xhr.responseJSON.message) ? xhr.responseJSON.message : errorThrown;
                                self.throwError(errMsg);
                            });

                            return {status : bookingStatus, statusText : ''};

                        } else {
                            $('body').trigger('processStop');
                            return {status : 'FAILURE', statusText : ''};
                        }
                    }
                });
            }
        };

        $(document).ready( () => {
            if (quote.paymentMethod() && quote.paymentMethod().method == "novalnetPay") {
                selectPaymentMethodAction({'method': 'novalnetPay',
                    'additional_data': {}
                });

                checkoutData.setSelectedPaymentMethod('novalnetPay');
            }
        });

        return Component.extend(novalnetPay);
    }
);
