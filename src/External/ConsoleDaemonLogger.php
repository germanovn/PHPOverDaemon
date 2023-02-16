<?php

namespace External;

use Psr\Log\AbstractLogger;

class ConsoleDaemonLogger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        $now = new \DateTime();
        $message = $now->format('Y/m/d H:i:s')." [$level] $message";

        $this->displayString($message);
    }

    private function displayString(string $message): void
    {
        print_r("$message\n");
    }
}