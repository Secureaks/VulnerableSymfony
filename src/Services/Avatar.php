<?php

namespace App\Services;

use Psr\Log\LoggerInterface;

class Avatar
{

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function getFromUrl(string $url): string|false
    {
        try {
            $content = file_get_contents($url);
        } catch (\Exception $e) {
            $this->logger->error('Error getting avatar from URL: ' . $e->getMessage());
            return false;
        }

        return $content;
    }
}