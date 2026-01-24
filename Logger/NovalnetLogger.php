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

namespace Novalnet\Payment\Logger;

use Magento\Framework\Stdlib\DateTime\DateTime as MagentoDateTime;
use Novalnet\Payment\Logger\Handler\NovalnetError;
use Novalnet\Payment\Logger\Handler\NovalnetNotice;

class NovalnetLogger
{
    /**
     * @var NovalnetError
     */
    protected $novalnetError;

    /**
     * @var NovalnetNotice
     */
    protected $novalnetNotice;

    /**
     * @var MagentoDateTime
     */
    protected $dateTime;
    /**
     * @param NovalnetError $novalnetError
     * @param NovalnetNotice $novalnetNotice
     * @param MagentoDateTime $dateTime
     */
    public function __construct(
        NovalnetError $novalnetError,
        NovalnetNotice $novalnetNotice,
        MagentoDateTime $dateTime
    ) {
        $this->novalnetError = $novalnetError;
        $this->novalnetNotice = $novalnetNotice;
        $this->dateTime = $dateTime;
    }

    /**
     * To log novalnet notice message
     *
     * @param string $message
     */
    public function notice($message)
    {
        $message = " \r\n " . $this->dateTime->gmtDate('Y-m-d H:i:s') . ' -- ' . $message;
        $this->novalnetNotice->writeLog($message);
    }

    /**
     * To log novalnet error message
     *
     * @param string $message
     */
    public function error($message)
    {
        $message = " \r\n " . $this->dateTime->gmtDate('Y-m-d H:i:s') . ' -- ' . $message;
        $this->novalnetError->writeLog($message);
    }
}
