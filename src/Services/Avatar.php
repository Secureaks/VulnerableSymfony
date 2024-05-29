<?php

namespace App\Services;

class Avatar
{
    public function getFromUrl(string $url): string|false
    {
        return file_get_contents($url);
    }
}