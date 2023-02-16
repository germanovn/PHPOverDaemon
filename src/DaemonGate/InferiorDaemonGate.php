<?php

namespace GermanovN\OverDaemon\DaemonGate;

use ArrayIterator;

/**
 * Репозиторий со списком всех демонов, которые должны быть запущены
 */
class InferiorDaemonGate
{
    /** @var InferiorDaemonRepository */
    private InferiorDaemonRepository $repository;

    public function __construct(InferiorDaemonRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Метод подбирает из репозитория только тех демонов, которые ещё не запущены
     * @param array<int, string> $launchedCollection - коллекция уже запущенных демонов
     * @return InferiorDaemon|null
     */
    public function getNextDaemon(array $launchedCollection): ?InferiorDaemon
    {
        if (count($launchedCollection) >= $this->repository->count()) {
            return null;
        }

        return $this->getFirstIdleDaemon(
            $this->findIdleDaemons($launchedCollection)
        );
    }

    /**
     * @param ArrayIterator<InferiorDaemon>|null $idleDaemons
     * @return InferiorDaemon|null
     */
    private function getFirstIdleDaemon(ArrayIterator $idleDaemons): ?InferiorDaemon
    {
        if (0 < $idleDaemons->count()) {
            return $idleDaemons->current();
        }

        return null;
    }

    private function findIdleDaemons(array $launchedCollection): ArrayIterator
    {
        $result = [];
        foreach ($this->repository->getAll() as $daemon) {
            if (!$this->isLaunchedDaemon($daemon, $launchedCollection)) {
                $result[] = $daemon;
            }
        }

        return new ArrayIterator($result);
    }

    private function isLaunchedDaemon(InferiorDaemon $daemon, array $launchedCollections): bool
    {
        return in_array($daemon->name(), $launchedCollections, true);
    }
}