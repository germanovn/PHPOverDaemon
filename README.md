# OverDaemon

Всем привет! В процессе интеграции с брокерами сообщений мне пришлось демонизировать некоторые куски кода на PHP. Это тот репозиторий содержит пакет подключаемый с помощью Composer. Цель пакета - поддержание написанных вами демонов в рабочем состоянии, а так же управление ими и сбор логов.

## Установка

```bash
composer require germanovn/php-overdaemon
```

## Использование

Для использования OverDaemon необходимо:
1. создать класс репозитория демонов реализующий интерфейс библиотеки `GermanovN\OverDaemon\DaemonGate\InferiorDaemonRepository`;
2. иметь демона, над которым вы хотите иметь больший контроль.

## Примеры

Ваш репозиторий демонов может выглядеть так:
```php
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
```

Ваш демон может выглядеть так:
```php
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
            // бесконечный цикл выполнения
            while ($this->isRunningDaemon()) {
                // получение задачи
                $execTime = $this->getSomeLongTask();
                if (null === $execTime) {
                    $this->stopDaemon(SIGSTOP);
                    continue;
                }
                // выполнение задачи
                $this->logger->debug("Start '" . $this->name() ."' PID: {$this->pid}");
                $this->doTask($execTime);
                $this->logger->debug("End '" . $this->name() ."' PID: {$this->pid}");
            }

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
    private function getSomeLongTask(): ?int
    {
        $execTime = random_int(1, 10) * 10;
        return $execTime >= 90 ? null : $execTime;
    }

    private function doTask(int $task): void
    {
        for ($i = 0; $i < $task; $i++) {
            $this->logger->debug($this->name() . " PID: {$this->pid} " . $i);
            usleep(10000);
        }
    }
    
    public function beforeDevour(): bool
    {
        // в это методе можно обновить подключение к вашей БД
        return true;
    }

    public function afterDevour(): void
    {
    }

    public function name(): string
    {
        return self::class;
    }
}
```

Обратите внимание в примере используется, абстрактный класс `\GermanovN\OverDaemon\Daemon\StoppableDaemon`.
Этот класс существует для реализации корректного завершения работы при получении POSIX сигналов.
По-умолчанию обрабатывает сигналы:
* SIGINT - Сигнал прерывания (Ctrl-C) с терминала;
* SIGTERM - Сигнал завершения (сигнал по умолчанию для утилиты kill).

Пример использования OverDaemon:
```php
<?php
// src/Command/OverDaemonCommand.php

// Перехват сигналов
pcntl_async_signals(true);

// Передача обработки сигналов в SigHandler
$sigHandler = SigHandler::instance();
pcntl_signal(SIGINT, [$sigHandler, 'handle']);
pcntl_signal(SIGTERM, [$sigHandler, 'handle']);
pcntl_signal(SIGHUP, [$sigHandler, 'handle']);

$repository = new MyInferiorDaemonRepository();
$repository->add(new MyDaemon());

$daemon = new OverDaemon(
    new DaemonConfig(),
    $repository,
    $sigHandler
);

echo $daemon->devour();
```

## Запуск тестов

```bash
vendor\bin\phpunit tests
```
