<?php
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
namespace Novalnet\Payment\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

class ResponseCodeValidator extends AbstractValidator
{
    /**
     * Performs validation of result/response code
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $response = new \Magento\Framework\DataObject();
        $response->setData(SubjectReader::readResponse($validationSubject));
        $isValid = true;
        $msg = [];

        if (empty($response->getData('action')) && $response->getData('result/status') != 'SUCCESS') {
            $isValid = false;
            $msg = [__($response->getData('result/status_text'))];
        }

        return $this->createResult($isValid, $msg);
    }
}
