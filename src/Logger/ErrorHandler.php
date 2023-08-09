<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 * @copyright 2016
 *
 */

namespace FastD\Logger;

/**
 * Class ErrorHandler.
 */
class ErrorHandler extends HandlerAbstract
{
    /**
     * {@inheritDoc}
     */
    protected function logContextFormat(): array
    {
        return [
            'ip'    => get_local_ip(),
            'get'   => request()->getQueryParams(),
            'post'  => request()->getParsedBody(),
            'msg'   => exception()->getMessage(),
            'code'  => exception()->getCode(),
            'file'  => exception()->getFile(),
            'line'  => exception()->getLine(),
            'trace' => explode("\n", exception()->getTraceAsString()),
        ];
    }
}
