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
                        novalnetPay.initLoader();
                    }
                },
                paymentFormInstance: new NovalnetPaymentForm(),
                PaymentAdditionalData: "",
                canDoRedirect: "0",
                iframePostData: ""
            },

            initObservable: function () {
                const self = this;
                this._super()
                    .observe([
                        'PaymentAdditionalData',
                        'canDoRedirect',
                        'iframePostData'
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

            initLoader: () => {
                $('#novalnetPay_payment-div').trigger('processStart');
            },

            stopLoader: () => {
                $('#novalnetPay_payment-div').trigger('processStop');
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


            getGrandTotal: function () {
                const totals = quote.totals();
                if (!totals) return Promise.resolve(null);
            
                const currentTotal = Math.round(parseFloat(totals.base_grand_total) * 100);
                return new Promise((resolve) => {
                    let resolved = false;
            
                    const newTotal = Math.round(parseFloat(totals.base_grand_total) * 100);
                    if (totals && totals.base_grand_total && newTotal !== currentTotal) {
                        resolved = true;
                        return resolve(newTotal);
                    }
            
                    const subscription = quote.totals.subscribe(function (newTotals) {
                        if (newTotals && newTotals.base_grand_total) {

                            const updatedTotal = Math.round(parseFloat(newTotals.base_grand_total) * 100);

                            if (updatedTotal !== currentTotal) {
                                subscription.dispose();
                                resolved = true;
                                resolve(updatedTotal);
                            }
                        }
                    });
            
                    setTimeout(() => {
                        if (!resolved) {
                            subscription.dispose();
                            resolve(currentTotal);
                        }
                    }, 2000);
                });
            },

            afterPlaceOrder: function () {
                if (this.canDoRedirect() == '1') {
                    const self = this;
                    self.initLoader();
                    const serviceUrl = url.build("/rest/V1/novalnet/payment/getRedirectURL", {}),
                        payLoad = {data: {quote_id: quote.getQuoteId()}};

                    self.redirectAfterPlaceOrder = false;
                    storage.post(serviceUrl, JSON.stringify(payLoad)).done( (response) => {
                        window.location.replace(response);
                    }).fail( (xhr, textStatus, errorThrown) => {
                        self.stopLoader();
                        const errMsg = (xhr.responseJSON.message) ? xhr.responseJSON.message : errorThrown;
                        self.throwError(errMsg);
                    });
                }
            },

            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'novalnetPay_payment_data': this.PaymentAdditionalData(),
                    }
                };
            },

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
                let details = self.iframePostData();
                if (details == '' || details == null || details == undefined) {
                    self.paymentFormInstance.getMPaymentRequest();
                    return false;
                } else {
                    let response = self.isJson(details) ? JSON.parse(details) : details;
                        self.iframePostData("");
                    if (response.result.status == "SUCCESS") {
                        self.canDoRedirect(response.booking_details.do_redirect ? response.booking_details.do_redirect : '0');
                        if (response.payment_details.process_mode && response.payment_details.process_mode == 'redirect') {
                            self.canDoRedirect('1');
                        }
                        self.PaymentAdditionalData(JSON.stringify(response));
                        return true;
                    } else {
                        self.PaymentAdditionalData("");
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
                    payload = {data: {quote_id: quote.getQuoteId()}};

                storage.post( serviceUrl, JSON.stringify(payload)).done( (response) => {
                    response = self.isJson(response) ? JSON.parse(response) : response;
                    if (response.result.status == "SUCCESS") {
                        document.getElementById('novalnetPaymentIFrame').src = response.result.redirect_url;
                        $("#novalnetPay_payment-div").css({"display" : "block"});
                    } else {
                        $("#novalnetPay_payment-div").css({"display" : "none"});
                        self.stopLoader();
                    }
                }).fail( (xhr, textStatus, errorThrown) => {
                    $("#novalnetPay_payment-div").css({"display" : "none"});
                    self.stopLoader();
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
                                error: $t("Your credit card details are invalid"),
                                card_holder: {
                                    label: $t("Card holder name"),
                                    place_holder: $t("Name on Card"),
                                    error: $t("Please enter the valid card holder name")
                                },
                                card_number: {
                                    label: $t("Card number"),
                                    place_holder: $t("XXXX XXXX XXXX XXXX"),
                                    error: $t("Please enter the valid card number")
                                },
                                expiry_date: {
                                    label: $t("Expiry date"),
                                    error: $t("Please enter the valid expiry month / year in the given format")
                                },
                                cvc: {
                                    label: $t("CVC/CVV/CID"),
                                    place_holder: $t("XXX"),
                                    error: $t("Please enter the valid CVC/CVV/CID")
                                }
                            }
                        },
                        orderInformation : {
                            lineItems: self.getLineItems(),
                            billing: {}
                        },
                        uncheckPayments: false,
                        showButton: false,
                    }
                };

                self.paymentFormInstance.initiate(paymentFormRequestObj);

                self.paymentFormInstance.validationResponse( (data) => {
                    self.paymentFormInstance.initiate(paymentFormRequestObj);
                    self.stopLoader();
                    if (window.checkoutConfig.payment.novalnetPay.selectedMethod) {
                        self.paymentFormInstance.checkPayment(window.checkoutConfig.payment.novalnetPay.selectedMethod);
                    }
                });

                self.initPaymentFormCallbacks();
            },

            getStreet: function (streetArray) {
                let i, street = '';
                if (streetArray) {
                    for(i=0; i < streetArray.length; i++) {
                        if(streetArray[i] != '') {
                            street += streetArray[i] + ' ';
                        }
                    }
                    return street.trim();
                }

                return street;
            },

            updatePaymentForm: async function () {
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
	        	var baseGrandTotal = await self.getGrandTotal();
                let updatedData = {
                    amount: baseGrandTotal,
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
                        $("#novalnetPay_actions-toolbar").css({"display" : "none"});
                    } else {
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
                    self.iframePostData(JSON.stringify(result));
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
                        self.initLoader();
                        response = self.isJson(response) ? JSON.parse(response) : response;
                        if (response.result.status == "SUCCESS") {
                            self.iframePostData(JSON.stringify(response));
                            $("#novalnetPay_submit").trigger('click');
                            return {status : 'SUCCESS', statusText : ''};
                        } else {
                            self.stopLoader();
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
