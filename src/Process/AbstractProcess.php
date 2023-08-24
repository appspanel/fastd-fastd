<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2017
 *
 * @see      https://www.github.com/janhuang
 * @see      http://www.fast-d.cn/
 */

namespace FastD\Process;

use FastD\Swoole\Process;

/**
 * Class AbstractProcess.
 */
abstract class AbstractProcess extends Process
{
    /**
     * @var array<string,mixed>
     */
    protected array $options = [];

    /**
     * @param array<string,mixed> $options
     * @return $this
     */
    public function configure(array $options = []): static
    {
        $this->options = $options;

        return $this;
    }
}
