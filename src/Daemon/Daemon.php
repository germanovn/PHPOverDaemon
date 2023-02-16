<?php

namespace GermanovN\OverDaemon\Daemon;

interface Daemon
{
    /** @var int application ERROR exit code */
    public const EXIT_CODE_ERROR = 1;

    /** @var int application SUCCESS exit code */
    public const EXIT_CODE_SUCCESS = 0;

    /**
     * Запуск демона
     * @param array|null $args
     * @return int application exit code
     */
    public function devour(array $args = null): int;
}