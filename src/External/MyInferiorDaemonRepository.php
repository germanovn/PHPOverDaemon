<?php

namespace MyApp\Daemon;

use GermanovN\OverDaemon\DaemonGate\InferiorDaemon;
use GermanovN\OverDaemon\DaemonGate\InferiorDaemonRepository;

class MyInferiorDaemonRepository implements InferiorDaemonRepository
{
    private array $daemonCollection;

    public function getAll(): array
    {
        return $this->daemonCollection;
    }

    public function add(InferiorDaemon $daemon): void
    {
        $this->daemonCollection[] = $daemon;
    }

    public function count(): int
    {
        return count($this->daemonCollection);
    }
}