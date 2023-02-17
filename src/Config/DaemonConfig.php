<?php

namespace GermanovN\OverDaemon\Config;

class DaemonConfig
{
    private const DEFAULT_MAX_CHILD_PROCESSES = 10;

    private const DEFAULT_DELAY_TIME_SEC = 120;

    /** @var int 67108864 bytes = 64 * 1024 * 1024 */
    private const DEFAULT_MEMORY_LIMIT = 67108864;

    /**
     * @var int Максимальное количество дочерних процессов
     * @see \GermanovN\OverDaemon\DaemonGate\InferiorDaemonGate
     */
    protected $max_child_processes = self::DEFAULT_MAX_CHILD_PROCESSES;

    /**
     * @var int Время прерывания в секундах между проверками освобождения стека дочерних процессов
     * @see sleep()
     */
    protected $delay_time_sec = self::DEFAULT_DELAY_TIME_SEC;

    /**
     * @var int Ограничение количества используемой памяти, чтобы демон корректно завершил работу до исключения
     * @see memory_get_usage()
     */
    protected $memory_limit = self::DEFAULT_MEMORY_LIMIT;

    /**
     * @var bool Требуется ли перехват STDIN, STDOUT, STDERR. В случае 'true' вывод будет отправлен в '/dev/null'
     * @see STDIN
     * @see STDOUT
     * @see STDERR
     */
    protected $silent = true;

    public function getMaxChildProcesses(): int
    {
        return $this->max_child_processes;
    }

    public function setMaxChildProcesses(int $max_child_processes): self
    {
        $this->max_child_processes = $max_child_processes;
        return $this;
    }

    public function getDelayTimeSec(): int
    {
        return $this->delay_time_sec;
    }

    public function setDelayTimeSec(int $delay_time_sec): self
    {
        $this->delay_time_sec = $delay_time_sec;
        return $this;
    }

    public function getMemoryLimit(): int
    {
        return $this->memory_limit;
    }

    public function setMemoryLimit(int $memory_limit): self
    {
        $this->memory_limit = $memory_limit;
        return $this;
    }

    public function isSilent(): bool
    {
        return $this->silent;
    }

    public function setSilent(bool $silent): self
    {
        $this->silent = $silent;
        return $this;
    }
}