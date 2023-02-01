<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */

return [
    'default' => [
        'adapter' => \Symfony\Component\Cache\Adapter\FilesystemAdapter::class,
        'params' => [
            'namespace' => '',
            'lifetime' => 3600,
        ],
    ],
];
