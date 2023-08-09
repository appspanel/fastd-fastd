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
 * Class AccessHandler.
 */
class AccessHandler extends HandlerAbstract
{
    /**
     * {@inheritDoc}
     */
    protected function logContextFormat(): array
    {
        return [
            'ip'     => get_local_ip(),
            'status' => response()->getStatusCode(),
            'get'    => request()->getQueryParams(),
            'post'   => request()->getParsedBody(),
        ];
    }
}
