<?php

namespace GermanovN\OverDaemon\Daemon;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Обработка сигналов для корректного завершения
 */
abstract class StoppableDaemon
{
    /** @var bool Флаг для корректной остановки демона */
    private bool $runDaemon = true;

    /** @var LoggerInterface|NullLogger  */
    protected $logger;

    /** @var SigHandler|null */
    protected ?SigHandler $sigHandler;

    /**
     * @var array По-умолчанию обрабатывает сигналы:
     * SIGINT - Сигнал прерывания (Ctrl-C) с терминала
     * SIGTERM - Сигнал завершения (сигнал по умолчанию для утилиты kill)
     */
    protected array $stopSigs = [
        SIGINT,
        SIGTERM,
    ];

    public function __construct(SigHandler $sigHandler, LoggerInterface $logger = null)
    {
        foreach ($this->stopSigs as $sig) {
            $sigHandler->subscribe($sig, [$this, 'stopDaemon']);
        }

        $this->logger = $logger ?? new NullLogger();
        $this->sigHandler = $sigHandler;
    }

    public function stopDaemon(int $sig): void
    {
        $this->logger->debug("Sig: '$sig' pid: ".getmypid());
        $this->runDaemon = false;
    }

    public function setStopSigs(array $stopSigs): self
    {
        $this->stopSigs = $stopSigs;
        return $this;
    }

    /**
     * Метод рекомендуется использовать в цикле while в качестве условия выхода
     * @return bool
     */
    protected function isRunningDaemon(): bool
    {
        return $this->runDaemon;
    }
}