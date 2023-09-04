<?php

// src/Security/Hasher/Md5Hasher.php
namespace App\Security\Hasher;

use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class Md5Hasher implements PasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    public function hash(string $plainPassword): string
    {
        return md5($plainPassword);
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        if ('' === $plainPassword) {
            return false;
        }

        return $hashedPassword === md5($plainPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        // Check if a password hash would benefit from rehashing
        return false;
    }
}