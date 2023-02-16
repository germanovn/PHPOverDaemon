<?php

namespace MyApp\Daemon;

use Exception;
use GermanovN\OverDaemon\Daemon\Daemon;
use GermanovN\OverDaemon\Daemon\StoppableDaemon;
use GermanovN\OverDaemon\DaemonGate\InferiorDaemon;

class MyDaemon extends StoppableDaemon implements InferiorDaemon
{
    private int $pid;
    /**
     * @throws Exception
     */
    public function devour(array $args = null): int
    {
        $this->pid = $args['pid'];
        try {
            $this->logger->debug("Start '" . $this->name() ."' PID: {$this->pid}");

            // бесконечный цикл выполнения
            while ($this->isRunningDaemon()) {
                // получение и выполнение задач
                $this->doTask($this->getSomeTask());
            }
            $this->logger->debug("End '" . $this->name() ."' PID: {$this->pid}");

            return Daemon::EXIT_CODE_SUCCESS;
        }
        catch (Exception $e) {
            $this->logger->error(
                sprintf('Code: %d. Message: %s', $e->getCode(), $e->getMessage()),
                $e->getTrace()
            );
            return Daemon::EXIT_CODE_ERROR;
        }
    }

    /** @throws Exception */
    private function getSomeTask(): int
    {
        return random_int(1, 6);
    }

    private function doTask(int $task): void
    {
        for ($i = 0; $i < ($task * 10); $i++) {
            $this->logger->debug($this->name() . " PID: {$this->pid} " . $i);
            usleep(100000);
        }
    }

    public function name(): string
    {
        return self::class;
    }
}
