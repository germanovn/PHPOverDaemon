<?php

namespace GermanovN\OverDaemon\DaemonGate;

use GermanovN\OverDaemon\Daemon\Daemon;

interface InferiorDaemon extends Daemon
{
    /**
     * Имя демона используется для определения того запущен ли демон
     * @see \GermanovN\OverDaemon\DaemonGate\InferiorDaemonGate
     * @return string
     */
    public function name(): string;

    public function beforeDevour(): bool;

    public function afterDevour(): void;
}