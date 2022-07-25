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
        'mage/translate',
        'mage/storage',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'mage/validation',
        'novalnetCheckout'
    ],
    function (
        $,
        $t,
        storage,
        urlBuilder,
        Component,
        quote,
        priceUtils
    ) {
        'use strict';

        $(document).on('change','#novalnetInvoiceInstalment_cycle',function(){
            var orderTotal = quote.totals()['base_grand_total'];
            var cycle = $(this).val();
            if (cycle == null) {
                return;
            }
            var cycleAmount;
            var lastCycleAmount;
            storage.get(
                urlBuilder.createUrl('/novalnet/payment/instalment_cycle/' + orderTotal + '/' + cycle, {})
            ).success(function (response) {
                response = $.parseJSON(response);
                cycleAmount = response.cycle_amount;
                lastCycleAmount = response.last_cycle;
                var html = '<table class="instalment-details-table"><thead><tr><th>' + $.mage.__("Instalment cycles") + '</th><th>' + $.mage.__("Instalment Amount") + '</th></thead><tbody>';
                var j = 0;
                var number_text = '';
                for (var i = 1; i <= cycle; i++) {
                    if (i != cycle) {
                        html += '<tr><td>'+ i + '</td><td>' + priceUtils.formatPrice(cycleAmount, quote.getPriceFormat()) + '</td></tr>';
                    } else if (i == cycle) {
                        html += '<tr><td>'+ i + '</td><td>' + priceUtils.formatPrice(lastCycleAmount, quote.getPriceFormat()) + '</td></tr>';
                    }
                    j++;
                }
                $('.novalnetInvoiceInstalment-details').html(html);
            });
        });

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
                    name: 'Novalnet_Payment/payment/novalnetInvoiceInstalment',
                    afterRender: function (renderedNodesArray, data) {
                    }
                },
            },
            currentBillingAddress: quote.billingAddress,
            totals: quote.getTotals(),

            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'novalnetInvoiceInstalment_cycle': $('#' + this.getCode() + '_cycle').val(),
                        'novalnetInvoiceInstalment_dob': $('#' + this.getCode() + '_dob').val(),
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
                var form = 'form[data-role=novalnetInvoiceInstalment]';
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

            /**
             * @return {*|String}
             */
            getValue: function () {
                var price = 0;

                if (this.totals()) {
                    price = quote.totals()['base_grand_total'];
                }

                return priceUtils.formatPrice(price, quote.getPriceFormat());
            },

            getInstalmentOptions: function () {
                var orderTotal = quote.totals()['base_grand_total'];
                storage.get(
                    urlBuilder.createUrl('/novalnet/payment/instalment_options/'+ this.getCode() + '/' + orderTotal, {})
                ).success(function (response) {
                    var allCycles = $.parseJSON(response);
                    var tariffField = $('#novalnetInvoiceInstalment_cycle');
                    tariffField.find('option').remove();
                    $.each(allCycles, function(id, value) {
                        if (0 == $('#novalnetInvoiceInstalment_cycle[value='+value.instalment_value+']').length) {
                            tariffField.append(
                                $('<option></option>').attr('value', value.instalment_value).text(value.instalment_key)
                            );
                        }
                    });
                    $('#novalnetInvoiceInstalment_cycle').trigger('change');
                });
            },

            allowOnlyNumber: function (data, event) {
                if(event.charCode < 48 || event.charCode > 57) {
                    return false;
                }
                return true;
            }
        });
    }
);
