<?php

namespace App\Services;

use Psr\Log\LoggerInterface;

class Analytics
{
    public function __construct(
        private readonly LoggerInterface $logger
    )
    {
    }

    public function track(): void {
        // Get the referer header
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        if (!$referer || !$this->validate($referer)) {
            return;
        }

        // Call the url with curl to get only the http status code
        $command = 'curl -k -s -o /dev/null -w "%{http_code}" ' . $referer;
        $statusCode = shell_exec($command);

        // Log the response status
        $this->logger->info('Referer URL response status: ' . $statusCode);
    }

    public function validate(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}