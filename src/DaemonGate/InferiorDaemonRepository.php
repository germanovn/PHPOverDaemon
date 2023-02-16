<?php

namespace GermanovN\OverDaemon\DaemonGate;

interface InferiorDaemonRepository
{
    /**
     * @return array<int, InferiorDaemon>
     * @throws InferiorDaemonRepositoryException
     */
    public function getAll(): array;

    /**
     * @param InferiorDaemon $daemon
     * @return void
     * @throws InferiorDaemonRepositoryException
     */
    public function add(InferiorDaemon $daemon): void;

    /**
     * @return int
     * @throws InferiorDaemonRepositoryException
     */
    public function count(): int;
}