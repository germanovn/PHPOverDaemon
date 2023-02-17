<?php

namespace GermanovN\OverDaemon\Daemon;

class SigHandler
{
    /** @var SigHandler|null */
    protected static $instance = null;

    /** @var <int, array>array  */
    private $handlers = [];

    private function __construct()
    {
    }

    public static function instance(): SigHandler
    {
        if (is_null(self::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function handle(int $sigNumber): void
    {
        if (!empty($this->handlers[$sigNumber])) {
            foreach ($this->handlers[$sigNumber] as $signalHandler) {
                $signalHandler($sigNumber);
            }
        }
    }

    public function subscribe(int $sigNumber, callable $handler)
    {
        $this->handlers[$sigNumber][$this->getFunctionHash($handler)] = $handler;
    }

    public function unsubscribe(int $sigNumber, callable $handler)
    {
        unset($this->handlers[$sigNumber][$this->getFunctionHash($handler)]);
    }

    private function getFunctionHash(callable $callable): string
    {
        return spl_object_hash((object) $callable);
    }
}