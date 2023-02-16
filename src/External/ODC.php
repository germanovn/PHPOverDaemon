<?php

use GermanovN\OverDaemon\Config\DaemonConfig;
use GermanovN\OverDaemon\Daemon\OverDaemon;
use GermanovN\OverDaemon\Daemon\SigHandler;

class OverDaemonCommand
{
    private const DEV_NULL = '/dev/null';

    public function run($args): int
    {
        $db = \Yii::app()->db;

        $repository = \Yii::app()->r;

        $sigHandler = SigHandler::instance();
        pcntl_signal(SIGINT, [$sigHandler, 'handle']);
        pcntl_signal(SIGTERM, [$sigHandler, 'handle']);
        pcntl_signal(SIGHUP, [$sigHandler, 'handle']);

        $daemon = new OverDaemon(
            new DaemonConfig(),
            $repository,
            $sigHandler,
            function () use ($db) {
                if ($db instanceof \CDbConnection) {
                    $db->setActive(false);
                    $db->setActive(true);
                }
            }
        );

        return $daemon->devour();
    }
//    /**
//     * Установленные в родительском процессе соединения перестают работать в дочерних процессах необходимо их переоткрыть
//     * @throws \Exception
//     */
//    private function renewConnections(): void
//    {
//        if (\Yii::app()->db instanceof CDbConnection) {
//            \Yii::app()->db->setActive(false);
//            \Yii::app()->db->setActive(true);
//        }
//    }
}

class Yii {
    public object $db;
    public \GermanovN\OverDaemon\DaemonGate\InferiorDaemonRepository $r;
    public static function app(): self
    {
        return new Yii();
    }
}
class CDbConnection {
    public function setActive(bool $active)
    {
    }
}