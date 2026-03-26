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

namespace Novalnet\Payment\Logger\Handler;

use Magento\Framework\Logger\Handler\Base as BaseHandler;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord;
use Monolog\Level;

/**
 * Class NovalnetInfo
 */
class NovalnetError extends BaseHandler
{
    /**
     * @var int
     */
    protected $loggerType = MonologLogger::ERROR;

    /**
     * @var string
     */
    protected $fileName = '/var/log/novalnet/error.log';

    /**
     * Write Log messages
     *
     * @param string $message
     */
    public function writeLog($message)
    {
        if (class_exists(Level::class)) {
            $record = new LogRecord(
                new \DateTimeImmutable(),
                'NovalnetLogger',
                Level::Error,
                $message,
                [],
            );

            $record['formatted'] = $this->getFormatter()->format($record);

           return $this->write($record);
        }


        $this->write(['formatted' => $message]);
    }
}
