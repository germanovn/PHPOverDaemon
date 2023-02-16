<?php

namespace GermanovN\OverDaemon\Tests\Unit\DaemonGate;

use GermanovN\OverDaemon\DaemonGate\InferiorDaemon;
use GermanovN\OverDaemon\DaemonGate\InferiorDaemonGate;
use GermanovN\OverDaemon\DaemonGate\InferiorDaemonRepository;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;

class InferiorDaemonGateTest extends TestCase
{
    /**
     * @dataProvider data_getNextDaemon
     * @param array $launchedCollection
     * @param int $repositoryCount
     * @param array $repositoryDaemons
     * @param InvokedCount $getAllInvokedCount
     * @param InferiorDaemon|null $expected
     * @return void
     */
    public function test_getNextDaemon(
        array $launchedCollection,
        int $repositoryCount,
        array $repositoryDaemons,
        InvokedCount $getAllInvokedCount,
        ?InferiorDaemon $expected
    ): void {
        $repository = $this->createMock(InferiorDaemonRepository::class);

        $repository->expects($getAllInvokedCount)
            ->method('getAll')
            ->willReturn($repositoryDaemons);

        $repository->expects($this->once())
            ->method('count')
            ->willReturn($repositoryCount);

        $gate = new InferiorDaemonGate($repository);
        self::assertEquals($expected, $gate->getNextDaemon($launchedCollection));
    }

    public function data_getNextDaemon(): iterable
    {
        $daemon = $this->createMock(InferiorDaemon::class);
        $daemon->method('name')
            ->willReturn('some_daemon_name');

        yield 'No daemons launched, but one daemon must be launched' => [
            'launchedCollection' => [],
            'repositoryCount' => 1,
            'repositoryDaemons' => [$daemon],
            'getAllInvokedCount' => $this->once(),
            'expected' => $daemon,
        ];

        yield 'No daemons launched and the repository is also empty' => [
            'launchedCollection' => [],
            'repositoryCount' => 0,
            'repositoryDaemons' => [],
            'getAllInvokedCount' => $this->never(),
            'expected' => null,
        ];

        $secondDaemon = $this->createMock(InferiorDaemon::class);
        $secondDaemon->method('name')
            ->willReturn('some_second_daemon_name');

        yield 'One demon is launched, but two demons must be launched' => [
            'launchedCollection' => [
                $daemon->name()
            ],
            'repositoryCount' => 2,
            'repositoryDaemons' => [$daemon, $secondDaemon],
            'getAllInvokedCount' => $this->once(),
            'expected' => $secondDaemon,
        ];

        yield 'One daemon is launched and it must be launched' => [
            'launchedCollection' => [
                $daemon->name()
            ],
            'repositoryCount' => 1,
            'repositoryDaemons' => [$daemon],
            'getAllInvokedCount' => $this->never(),
            'expected' => null,
        ];
    }
}