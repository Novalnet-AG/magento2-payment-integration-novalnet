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
        'Magento_Ui/js/modal/alert',
        'Magento_Ui/js/modal/modal',
        'Magento_Catalog/js/price-utils',
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
        alert,
        modal,
        priceUtils
    ) {
        'use strict';

        $(document).on('change','#novalnetSepaInstalment_cycle',function(){
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
                $('.novalnetSepaInstalment-details').html(html);
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
                    name: 'Novalnet_Payment/payment/novalnetSepaInstalment',
                    afterRender: function (renderedNodesArray, data) {
                        data.displayForm();
                    }
                },
                sepaAccountNumber: '',
                novalnetPaymentToken: ''
            },
            currentBillingAddress: quote.billingAddress,
            totals: quote.getTotals(),

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
                    $("#novalnet_form_sepa_instalment").show();
                } else {
                    $("#novalnet_form_sepa_instalment").hide();
                }
            },

            displayForm: function () {
                if (window.checkoutConfig.payment[this.getCode()].storePayment != '1' || window.checkoutConfig.payment[this.getCode()].storedPayments.length == 0) {
                    this.onAccountChange('new_account');
                } else {
                    this.onAccountChange(window.checkoutConfig.payment[this.getCode()].tokenId);
                }
            },

            getData: function () {
                var CustomerDOB = $('#' + this.getCode() + '_dob').val();

                return {
                    'method': this.item.method,
                    'additional_data': {
                        'novalnetSepaInstalment_iban': this.getIBAN(),
                        'novalnetSepaInstalment_dob': CustomerDOB,
                        'novalnetSepaInstalment_cycle': this.getInstalmentCycle(),
                        'novalnetSepaInstalment_create_token': this.canStorePayment(),
                        'novalnetSepaInstalment_token': this.getPaymentToken()
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
                                    if ($('form#novalnetSepaInstalment .novalnet-payment-saved-payments').length <= 0) {
                                        $('form#novalnetSepaInstalment .novalnet-payment-new_account').remove();
                                    }
                                    $('#novalnet_form_sepa_instalment').show();
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

            /**
             * get InstalmentCycle
             */
            getInstalmentCycle: function () {
                return $('#' + this.getCode() + '_cycle').val();
            },

            validate: function () {
                var form = 'form[data-role=novalnetSepaInstalment]';
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

            sepaInstalmentMandateToggle: function () {
                $('#sepa_instalment_mandate_details').toggle('slow');
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
                    var tariffField = $('#novalnetSepaInstalment_cycle');
                    tariffField.find('option').remove();
                    $.each(allCycles, function(id, value) {
                        if (0 == $('#novalnetSepaInstalment_cycle[value='+value.instalment_value+']').length) {
                            tariffField.append(
                                $('<option></option>').attr('value', value.instalment_value).text(value.instalment_key)
                            );
                        }
                    });
                    $('#novalnetSepaInstalment_cycle').trigger('change');
                });
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
