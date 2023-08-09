<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */

namespace FastD\Logger;

use Monolog\DateTimeImmutable;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonoLogger;

/**
 * Class Logger.
 */
class Logger extends MonoLogger
{
    /**
     * @param int $level
     * @param string $message
     * @param array $context
     * @param \Monolog\DateTimeImmutable|null $datetime
     * @return bool
     */
    public function addRecord(int $level, string $message, array $context = [], DateTimeImmutable $datetime = null): bool
    {
        if (empty($this->handlers)) {
            $emptyHandler = new StreamHandler('php://temp');
            $emptyHandler->setFormatter(new LineFormatter());
            $this->pushHandler($emptyHandler);
        }

        return parent::addRecord($level, $message, $context);
    }
}
