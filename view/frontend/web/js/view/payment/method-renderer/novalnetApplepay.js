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
        'Magento_Ui/js/modal/alert',
        'Magento_Checkout/js/model/quote',
        'mage/translate',
        'Magento_Checkout/js/model/totals',
        'mage/url',
        'mage/storage',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/cart/totals-processor/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'novalnetUtilityJs',
        'novalnetPaymentJs'
    ],
    function(
        $,
        Component,
        alert,
        quote,
        $t,
        totals,
        urlBuilder,
        storage,
        customerData,
        totalsDefaultProvider,
        additionalValidators
    ) {
        "use strict";
        let nn_applepay = {
            /**
             * Default's
             */
            defaults: {
                template: {
                    name: "Novalnet_Payment/payment/novalnetApplepay"
                },
                novalnetApplepay: "APPLEPAY",
                canShowPaymentMethod: "",
                applePayInstance: NovalnetPayment().createPaymentObject()
            },

            /**
             * Initialize observables
             */
            initObservable: function () {
                this._super().observe(["canShowPaymentMethod"]);
                let cart = customerData.get("cart");
                cart.subscribe(this.refreshCheckout, this);
                return this;
            },

            /**
             * Refresh quote grand total on cart change
             */
            refreshCheckout: function() {
                let cart = customerData.get("cart");
                if (cart().summary_count && cart().summary_count > 0) {
                    totalsDefaultProvider.estimateTotals(quote.shippingAddress());
                }
            },

            /**
             * Returns payment method instructions
             */
            getInstructions: function() {
                return window.checkoutConfig.payment[this.item.method].instructions;
            },

            /**
             * Returns payment method logo
             */
            getLogo: function() {
                return window.checkoutConfig.payment[this.item.method].logo;
            },

            /**
             * Check payment availability
             *
             * @return object Promise
             */
            checkAvailability: async function() {
                const self = this,
                    response = new Promise(function(resolve, reject) {
                        self.applePayInstance.setPaymentIntent(self.paymentRequest());
                        self.applePayInstance.isPaymentMethodAvailable(function(canShowButton) {
                            resolve(canShowButton);
                        });
                    }),
                    isAllowed = await response.then(function(result) {
                        return result;
                    });

                return (isAllowed == true) ? self.canShowPaymentMethod(1) : self.canShowPaymentMethod(0);
            },

            /**
             * Can show apple pay payment
             *
             * @return bool
             */
            isApplePayAllowed: function() {
                var self = this;
                self.checkAvailability();
                return self.canShowPaymentMethod();
            },

            /**
             * Returns payment testmode status
             */
            getTestmode: function() {
                return window.checkoutConfig.payment[this.item.method].testmode;
            },

            /**
             * Returns Grand Total
             */
            getGrandTotal: function() {
                if (quote.totals()) {
                    return Math.round(parseFloat(quote.totals().base_grand_total) * 100);
                }
            },

            /**
             * Throw's alert on payment error
             */
            throwError: function(message) {
                alert({
                    title: $t("Error"),
                    content: message
                });
            },

            /**
             * To check the data is JSON
             *
             * @return bool
             */
            isJson: function(data) {
                try {
                    JSON.parse(data);
                    return true;
                } catch (e) {
                    return false;
                }
            },

            /**
             * Returns display items for payment sheet
             *
             * @return array
             */
            getLineItems: function() {
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
                        $.each(segments, function (index, value) {
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

            /**
             * Init checkout page applepay button on DOM load
             */
            initApplePay: function() {
                const self = this;

                $("#novalnet_guest_checkoutdiv").css({"display" : "none"});
                self.applePayInstance.setPaymentIntent(self.paymentRequest());
                self.applePayInstance.isPaymentMethodAvailable(function(canShowApplePay) {
                    if (canShowApplePay) {
                        $("#novalnet_applepay_checkoutdiv").empty();
                        self.applePayInstance.addPaymentButton("#novalnet_applepay_checkoutdiv");
                        $("#novalnet_applepay_checkoutdiv").find("apple-pay-button").css({'width': "100%"});
                    }
                });
            },

            /**
             * Applepay Payment Request
             *
             * @return object
             */
            paymentRequest: function() {
                const self = this,
                    requestData = window.checkoutConfig.payment.novalnetApplepay;

                return {
                    clientKey: requestData.clientKey,
                    paymentIntent: {
                        merchant: {
                            countryCode: requestData.countryCode,
                            paymentDataPresent: false
                        },
                        transaction: {
                            amount: self.getGrandTotal(),
                            currency: requestData.currencyCode,
                            paymentMethod: self.novalnetApplepay,
                            environment: (requestData.testmode == 1) ? "SANDBOX" : "PRODUCTION",
                            setPendingPayment: quote.isVirtual() ? true : requestData.is_pending
                        },
                        order: {
                            paymentDataPresent: false,
                            merchantName: requestData.sellerName,
                            lineItems: self.getLineItems(),
                            billing: {
                                requiredFields: ["postalAddress"]
                            },
                            shipping: {
                                requiredFields: ["phone", "email"],
                            }
                        },
                        button: {
                            style: requestData.btnTheme,
                            locale: requestData.langCode,
                            type: requestData.btnType,
                            boxSizing: "border-box",
                            dimensions: {
                                width: 240,
                                cornerRadius: parseInt(requestData.btnRadius),
                                height: parseInt(requestData.btnHeight)
                            }
                        },
                        callbacks: {
                            onProcessCompletion: function (response, bookingResult) {
                                if (response.result.status == "SUCCESS") {
                                    let serviceUrl = urlBuilder.build("/rest/V1/novalnet/payment/placeOrder", {}),
                                        payload = {data: response, paymentType: "novalnetApplepay", paymentPage: true};

                                    storage.post( serviceUrl, JSON.stringify(payload)).done(function(response) {
                                        if (self.isJson(response)) {
                                            response = JSON.parse(response);
                                        }
                                        $('body').trigger('processStart');
                                        window.location.replace(response.redirectUrl);
                                        bookingResult({status: "SUCCESS", statusText: ""});
                                    }).fail(function(xhr, textStatus, errorThrown) {
                                        $('body').trigger('processStop');
                                        const errMsg = (xhr.responseJSON.message) ? xhr.responseJSON.message : errorThrown;
                                        self.throwError(errMsg);
                                        bookingResult({status: "FAILURE", statusText: errMsg});
                                    });
                                } else {
                                    $('body').trigger('processStop');
                                    bookingResult({status: 'FAILURE', statusText: ""});
                                }
                            },
                            onPaymentButtonClicked: function(clickResult) {
                                clickResult({status: "SUCCESS"});
                            }
                        }
                    }
                };
            }
        };

        $(document).on("click", '#co-payment-form input[type="radio"]', function() {
            if(this.value == "novalnetApplepay") {
                const applePayInstance = NovalnetPayment().createPaymentObject();
                applePayInstance.setPaymentIntent(nn_applepay.paymentRequest());
                applePayInstance.isPaymentMethodAvailable(function(canShowApplePay) {
                    if (canShowApplePay) {
                        $("#novalnet_applepay_checkoutdiv").empty();
                        applePayInstance.addPaymentButton("#novalnet_applepay_checkoutdiv");
                        $("#novalnet_applepay_checkoutdiv").find("apple-pay-button").css({'width': "100%"});
                    }
                });
            }
        });

        return Component.extend(nn_applepay);
    }
);
