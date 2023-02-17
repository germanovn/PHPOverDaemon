<?php

namespace GermanovN\OverDaemon\Tests\Unit\Daemon;

use GermanovN\OverDaemon\Daemon\SigHandler;
use PHPUnit\Framework\TestCase;

class SigHandlerTest extends TestCase
{
    public const SOME_SIG_NUMBER = 99;
    public const ANOTHER_SIG_NUMBER = 90;

    /**
     * @dataProvider data_subscribe_SubscribeAndHandle_success
     * @param int $handleSigNumber
     * @param array $handlerList
     * @param string|null $expected
     * @return void
     */
    public function test_subscribe_SubscribeAndHandle_success(
        int $handleSigNumber,
        array $handlerList,
        ?string $expected
    ): void {
        $handleState = '';

        $sigHandler = SigHandler::instance();
        foreach ($handlerList as $handlerName => $subscribeSigNumber) {
            $sigHandler->subscribe($subscribeSigNumber, function($sigNumber) use (&$handleState, $handlerName) {
                $handleState .= "Signal $sigNumber handled with $handlerName ";
            });
        }
        $sigHandler->handle($handleSigNumber);

        self::assertEquals($expected, $handleState);
    }

    public function data_subscribe_SubscribeAndHandle_success(): iterable
    {
        yield 'Subscribe and handle sig' => [
            self::SOME_SIG_NUMBER,
            [
                'some_handler' => self::SOME_SIG_NUMBER,
            ],
            'Signal 99 handled with some_handler '
        ];

        yield 'Subscribe, but no handle sig (another sig)' => [
            self::ANOTHER_SIG_NUMBER,
            [
                'some_handler' => self::SOME_SIG_NUMBER,
            ],
            ''
        ];

        yield 'Adds multiple handlers for same signal' => [
            self::SOME_SIG_NUMBER,
            [
                'first_handler' => self::SOME_SIG_NUMBER,
                'second_handler' => self::SOME_SIG_NUMBER,
            ],
            'Signal 99 handled with first_handler Signal 99 handled with second_handler '
        ];

        yield 'Adds multiple handlers for different signal' => [
            self::SOME_SIG_NUMBER,
            [
                'first_handler' => self::ANOTHER_SIG_NUMBER,
                'second_handler' => self::SOME_SIG_NUMBER,
            ],
            'Signal 99 handled with second_handler '
        ];
    }

    /**
     * @dataProvider data_unsubscribe_UnsubscribeAndNoHandle_success
     * @param int $handleSigNumber
     * @param array $handlerList
     * @param string $removeHandlerName
     * @param string|null $expected
     * @return void
     */
    public function test_unsubscribe_RemoveHandlerAndCheckHandle_success(
        int $handleSigNumber,
        array $handlerList,
        string $removeHandlerName,
        ?string $expected
    ): void {
        $handleState = '';

        $sigHandler = SigHandler::instance();

        foreach ($handlerList as $key => $handler) {
            $handlerName = $handler['name'];
            $handlerList[$key]['handler'] = function($sigNumber) use (&$handleState, $handlerName) {
                $handleState .= "Signal $sigNumber handled with $handlerName ";
            };
        }

        foreach ($handlerList as $handler) {
            if ($handler['subscribe']) {
                $sigHandler->subscribe($handler['sig'], $handler['handler']);
            }
            if ($handler['name'] === $removeHandlerName) {
                $removeHandler = $handler;
            }
        }

        if (isset($removeHandler)) {
            $sigHandler->unsubscribe($removeHandler['sig'], $removeHandler['handler']);
        }

        $sigHandler->handle($handleSigNumber);

        self::assertEquals($expected, $handleState);
    }

    public function data_unsubscribe_UnsubscribeAndNoHandle_success(): iterable
    {
        yield 'Remove handler one of one' => [
            self::SOME_SIG_NUMBER,
            [
                [
                    'name' => 'some_handler',
                    'sig' => self::SOME_SIG_NUMBER,
                    'subscribe' => true,
                ],
            ],
            'some_handler',
            ''
        ];

        yield 'Remove handler one of two' => [
            self::SOME_SIG_NUMBER,
            [
                [
                    'name' => 'first_handler',
                    'sig' => self::SOME_SIG_NUMBER,
                    'subscribe' => true,
                ],
                [
                    'name' => 'second_handler',
                    'sig' => self::SOME_SIG_NUMBER,
                    'subscribe' => true,
                ],
            ],
            'second_handler',
            'Signal 99 handled with first_handler '
        ];

        yield 'Remove non-existent handler' => [
            self::SOME_SIG_NUMBER,
            [
                [
                    'name' => 'first_handler',
                    'sig' => self::SOME_SIG_NUMBER,
                    'subscribe' => true,
                ],
                [
                    'name' => 'second_handler',
                    'sig' => self::SOME_SIG_NUMBER,
                    'subscribe' => true,
                ],
                [
                    'name' => 'some_handler',
                    'sig' => self::SOME_SIG_NUMBER,
                    'subscribe' => false,
                ],
            ],
            'some_handler',
            'Signal 99 handled with first_handler Signal 99 handled with second_handler '
        ];
    }
}