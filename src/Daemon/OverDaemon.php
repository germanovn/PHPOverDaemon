<?php

namespace GermanovN\OverDaemon\Daemon;

use GermanovN\OverDaemon\Config\DaemonConfig;
use GermanovN\OverDaemon\DaemonGate\InferiorDaemonGate;
use GermanovN\OverDaemon\DaemonGate\InferiorDaemonRepository;
use Psr\Log\LoggerInterface;

/**
 * Демон, который "клонирует" (fork) свой процесс для выполнения полезной работы в дочернем процессе.
 * В случае если дочерний процесс по какой-то причине падает, он будет поднят снова.
 * Сам `OverDaemon` не делает ни чего, кроме контроля за тем, чтобы дочерние процессы работали.
 */
class OverDaemon extends StoppableDaemon implements Daemon
{
    private const DEV_NULL = '/dev/null';
    private const ERROR_PID = -1;
    private const WNOHANG_NO_CHILD = 0;

    /**
     * Коллекция запущенных демонов: ключ - PID демона, значение - строка InferiorDaemon::name()
     * @see \GermanovN\OverDaemon\DaemonGate\InferiorDaemon
     * @var array<int, string>
     */
    private array $launchedCollection = [];

    private ?DaemonConfig $config;

    private InferiorDaemonGate $inferiorDaemonGate;

    private $renewConnections;

    public function __construct(
        DaemonConfig $config,
        InferiorDaemonRepository $repository,
        SigHandler $sigHandler,
        callable $renewConnections = null,
        LoggerInterface $logger = null
    ) {
        $this->stdHandle($config);
        $this->checkSystem();
        $this->config = $config;
        $this->inferiorDaemonGate = new InferiorDaemonGate($repository);
        $this->renewConnections = $renewConnections;
        parent::__construct($sigHandler, $logger);
    }

    /**
     * @inheritDoc
     * @param array|null $args
     * @return int application exit code
     */
    public function devour(array $args = null): int
    {
        while ($this->isRunningDaemon()) {

            if ($this->checkOutOfMemory()) {
                $this->logger->error('Out of memory');
                return Daemon::EXIT_CODE_ERROR;
            }

            $inferior = $this->inferiorDaemonGate->getNextDaemon($this->launchedCollection);

            if (null !== $inferior && count($this->launchedCollection) <= $this->config->getMaxChildProcesses()) {

                // создать дочерний процесс, который будет выполнять задачу
                $pid = pcntl_fork();

                // код далее выполняется двумя процессами
                if ($pid === self::ERROR_PID) {

                    // не удалось создать дочерний процесс
                    return Daemon::EXIT_CODE_ERROR;
                }
                elseif ($pid) {

                    // OverDaemon. Зарегистрировать дочерний процесс PID в родительском процессе
                    $this->launchedCollection[$pid] = $inferior->name();
                    $this->logger->debug("new child $pid");
                }
                elseif (0 === $pid) {

                    // InferiorDaemon. Запуск дочернего демона
                    if (is_callable($this->renewConnections)) {
                        call_user_func([$this, 'renewConnections']);
                    }
                    $inferior->devour(['pid' => getmypid()]);
                    $this->logger->debug('Exit');
                    exit();
                }
            }
            else {
                $this->logger->debug('Go to sleep');
                // прерывание между проверками освобождения стека дочерних процессов, чтобы не гонять цикл в холостую
                sleep($this->config->getDelayTimeSec());
                $this->logger->debug('Woke up');
            }

            $this->waitPid();
        }

        $this->logger->debug("\nEnd\n");
        return Daemon::EXIT_CODE_SUCCESS;
    }

    /**
     * Метод проверяет состояние дочерних процессов, убирает из стека завершённые и зомби процессы (waitpid)
     * @return void
     */
    private function waitPid(): void
    {
        $signaled_pid = pcntl_waitpid(self::ERROR_PID, $status, WNOHANG);
        if ($signaled_pid !== self::WNOHANG_NO_CHILD) {
            if ($signaled_pid === self::ERROR_PID) {
                // больше нет дочерних процессов
                $this->launchedCollection = [];
                $this->logger->debug('All done');
            }
            else {
                // дочерний процесс завершён, удалить его из стека
                unset($this->launchedCollection[$signaled_pid]);
                $this->logger->debug("Clear: $signaled_pid");
            }
        }
    }

    private function stdHandle(DaemonConfig $config): void
    {
        if ($config->isSilent()) {
            if (is_resource(STDIN)) {
                fclose(STDIN);
                fopen(self::DEV_NULL, 'r');
            }
            if (is_resource(STDOUT)) {
                fclose(STDOUT);
                fopen(self::DEV_NULL, 'ab');
            }
            if (is_resource(STDERR)) {
                fclose(STDERR);
                fopen(self::DEV_NULL, 'ab');
            }
        }
    }

    private function checkSystem(): void
    {
        if (! function_exists('pcntl_fork')) {
            print_r('PCNTL functions not available on this PHP installation');
            die(Daemon::EXIT_CODE_ERROR);
        }

        if (! function_exists('spl_object_hash')) {
            print_r('SPL functions not available on this PHP installation');
            die(Daemon::EXIT_CODE_ERROR);
        }
    }

    private function checkOutOfMemory(): bool
    {
        return memory_get_usage() > $this->config->getMemoryLimit();
    }
}