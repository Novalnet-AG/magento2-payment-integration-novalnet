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

const timestamp = new Date().getTime();
var config = {
    map: {
        '*': {
            'novalnetUtilityJs': 'https://cdn.novalnet.de/js/v2/NovalnetUtility.js?' + timestamp,
            'novalnetPaymentFormJs': 'https://cdn.novalnet.de/js/pv13/checkout.js?' + timestamp,
            'novalnetPaymentJs': 'https://cdn.novalnet.de/js/v3/payment.js?' + timestamp
        }
    }
};
