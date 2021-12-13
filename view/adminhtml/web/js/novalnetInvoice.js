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
    "jquery",
    'Magento_Catalog/js/price-utils',
    'mage/storage',
    'mage/url',
    "jquery/ui"
    ],
    function ($, priceUtils, storage, urlBuilder) {
        'use strict';

        $.widget('mage.novalnetInvoiceFormJs', {
            _create:function () {
                var self = this;

                $(document).on('keypress', '#novalnetInvoiceGuarantee_dd, #novalnetInvoiceInstalment_dd, #novalnetInvoiceGuarantee_yyyy, #novalnetInvoiceInstalment_yyyy', function (e) {
                    if(e.charCode < 48 || e.charCode > 57) return false;
                });

                $(document).on('input', '#novalnetInvoiceGuarantee_dd, #novalnetInvoiceInstalment_dd', function (e) {
                    if (e.keyCode != 8 ) {
                        var min_date = 1;
                        var max_date = 31;
                        var date_val = $(this).val();
                        if (isNaN(date_val) || date_val.length > 1 && date_val < min_date || date_val > max_date) {
                            $(this).val(date_val.substring(0, date_val.length - 1));
                            return false;
                        }
                    }
                });

                $(document).on('focusout', '#novalnetInvoiceGuarantee_dd, #novalnetInvoiceInstalment_dd', function (e) {
                    var date_val = $(this).val();
                    if (date_val && date_val.length < 2 && date_val < 10) {
                        if (date_val == 0) {
                            date_val = 1;
                        }
                        $(this).val('0' + date_val);
                    }
                });
                
                $(document).on('change','#novalnetInvoiceInstalment_cycle',function(){
                    var orderTotal = $('#invoiceinstalment_total').val();
                    var currency = $('#invoiceinstalment_currency').val();
                    var cycle = $(this).val();
                    if (cycle == null) {
                        return;
                    }
                    var cycleAmount;
                    var lastCycleAmount;
                    storage.get(
                        $('#invoiceinstalment_cycle_detail_url').val() + orderTotal + '/' + cycle 
                    ).success(function (response) {
                        response = $.parseJSON(response);
                        cycleAmount = response.cycle_amount;
                        lastCycleAmount = response.last_cycle;
                        var html = '<table class="instalment-details-table"><thead><tr><th>' + $.mage.__("Instalment cycles") + '</th><th>' + $.mage.__("Instalment Amount") + '</th></thead><tbody>';
                        var j = 0;
                        for (var i = 1; i <= cycle; i++) {
                            if (i != cycle) {
                                html += '<tr><td>'+ i + '</td><td>' + currency + priceUtils.formatPrice(cycleAmount ) + '</td></tr>';
                            } else if (i == cycle) {
                                html += '<tr><td>'+ i + '</td><td>' + currency + priceUtils.formatPrice(lastCycleAmount ) + '</td></tr>';
                            }
                            j++;
                        }
                        $('.novalnetInvoiceInstalment-details').html(html);
                    });
                });
                
                $('#p_method_novalnetInvoiceInstalment').click(function(){
                    $( "#novalnetInvoiceInstalment_cycle" ).trigger('change');
                });
                $(document).ready(function(){
                    $( "#novalnetInvoiceInstalment_cycle" ).trigger('change');
                });
                
                function getNumberWithOrdinal(n) {
                    var s=["th","st","nd","rd"],
                    v=n%100;
                    return n+(s[(v-20)%10]||s[v]||s[0]);
                }

                // Year input listener
                var current_date = new Date();
                var max_year = current_date.getFullYear() - 18;
                var min_year = current_date.getFullYear() - 91;
                var years_range = [];
                var yearElement;
                for(var year = max_year; year >= min_year; year--) {
                    years_range.push(String(year));
                }

                $(document).on('input', '#novalnetInvoiceGuarantee_yyyy, #novalnetInvoiceInstalment_yyyy', function (e) {
                    yearElement = document.getElementById(e.target.id);
                    var a, b, i, year_val = this.value;
                    closeAllLists();
                    if (!year_val || isNaN(year_val) || year_val.length < 1) {return false;}
                    if (year_val.length == 1) {
                        if (year_val != String(max_year).charAt(0) && year_val != String(min_year).charAt(0)) {
                            $(this).val(year_val.substring(0, year_val.length - 1));
                        }
                    } else if (year_val.length > 1) {
                        var years_string = years_range.join("|");
                        years_string = years_string.slice(0, -(4 - year_val.length));
                        var dots = '.'.repeat((4 - year_val.length));
                        years_string = years_string.replace(new RegExp(dots + '\\|', 'g'), '|');
                        if (!new RegExp(years_string).test(year_val)) {
                            $(this).val(year_val.substring(0, year_val.length - 1));
                            year_val = $(this).val()
                        }
                    }

                    if (year_val.length > 1) {
                        a = document.createElement("div");
                        a.setAttribute("id", this.id + "autocomplete-list");
                        a.setAttribute("class", "autocomplete-items");
                        this.parentNode.appendChild(a);
                        for (i = 0; i < years_range.length; i++) {
                            if (years_range[i].substr(0, year_val.length).toUpperCase() == year_val.toUpperCase()) {
                                b = document.createElement("div");
                                b.innerHTML = "<strong>" + years_range[i].substr(0, year_val.length) + "</strong>";
                                b.innerHTML += years_range[i].substr(year_val.length);
                                b.innerHTML += "<input type='hidden' value='" + years_range[i] + "'>";
                                b.addEventListener("click", function(e) {
                                    yearElement.value = this.getElementsByTagName("input")[0].value;
                                    closeAllLists();
                                });
                                a.appendChild(b);
                            }
                        }

                        if (!new RegExp(years_string).test(year_val)) {
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            return false;
                        }
                    }
                });

                function closeAllLists(elmnt) {
                    var x = document.getElementsByClassName("autocomplete-items");
                    for (var i = 0; i < x.length; i++) {
                        if (elmnt != x[i] && elmnt != yearElement) {
                            x[i].parentNode.removeChild(x[i]);
                        }
                    }
                }

                $(document).on('click', function (e) {
                    closeAllLists(e.target);
                });

                $('#p_method_novalnetInvoiceGuarantee').ready(function () {
                    if ($('#p_method_novalnetInvoice:visible').length && $('#p_method_novalnetInvoiceGuarantee').length) {
                        $('#p_method_novalnetInvoice').closest('dt').hide();
                        $('#payment_form_novalnetInvoice').hide();
                    }
                });
            }
        });

        return $.mage.novalnetInvoiceFormJs;
    }
);
