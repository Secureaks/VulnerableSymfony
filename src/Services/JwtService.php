<?php

namespace App\Services;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * #VULNERABILITY: Intentionally vulnerable JWT service for security training
 *
 * Vulnerabilities implemented:
 * - Weak secret (easily crackable)
 * - Algorithm "none" accepted (signature bypass)
 * - No expiration validation (token replay)
 * - SQL injection via "kid" header
 */
class JwtService
{
    /**
     * #VULNERABILITY: Weak and predictable secret
     * Can be cracked with tools like hashcat, jwt_tool, or jwt-cracker
     */
    private string $secret = 'secret123';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Connection $connection
    )
    {
    }

    /**
     * Generate a JWT token for a user
     */
    public function generateToken(User $user): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT'
        ]));

        $payload = $this->base64UrlEncode(json_encode([
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'roles' => $user->getRoles(),
            'iat' => time(),
            'exp' => time() + 3600 // 1 hour
        ]));

        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $this->secret, true)
        );

        return "$header.$payload.$signature";
    }

    /**
     * #VULNERABILITY: Multiple security flaws in token validation
     */
    public function validateToken(string $token): array|false
    {
        $parts = explode('.', $token);

        if (count($parts) < 2) {
            return false;
        }

        $header = json_decode($this->base64UrlDecode($parts[0]), true);
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);

        if (!$header || !$payload) {
            return false;
        }

        /**
         * #VULNERABILITY 1: Algorithm "none" attack
         * Accepts tokens without signature verification when alg=none
         *
         * Exploit: Create a token with {"alg":"none"} header and any payload
         * The signature can be empty or omitted entirely
         */
        if (strtolower($header['alg'] ?? '') === 'none') {
            $this->logger->info('JWT: Accepted token with algorithm none');
            return $payload;
        }

        /**
         * #VULNERABILITY 2: SQL Injection via "kid" header
         * The Key ID is used directly in a SQL query without sanitization
         *
         * Exploit: Set kid to: ' UNION SELECT 'secret123' --
         * This will make the query return 'secret123' as the key
         */
        if (isset($header['kid'])) {
            $secret = $this->getKeyFromDatabase($header['kid']);
            if ($secret) {
                $this->secret = $secret;
            }
        }

        /**
         * #VULNERABILITY 3: No expiration validation
         * The 'exp' claim is completely ignored
         * Expired tokens remain valid indefinitely
         *
         * Exploit: Use a captured token even after it should have expired
         */
        // Note: Intentionally NOT checking $payload['exp']

        // Verify signature for HS256
        if (($header['alg'] ?? '') === 'HS256') {
            $signature = $parts[2] ?? '';
            $expectedSignature = $this->base64UrlEncode(
                hash_hmac('sha256', "{$parts[0]}.{$parts[1]}", $this->secret, true)
            );

            if ($signature === $expectedSignature) {
                return $payload;
            }
        }

        return false;
    }

    /**
     * #VULNERABILITY: SQL Injection in key lookup
     * The kid parameter is concatenated directly into the SQL query
     */
    private function getKeyFromDatabase(string $kid): ?string
    {
        try {
            // Vulnerable SQL query - kid is not sanitized
            $sql = "SELECT secret_key FROM jwt_keys WHERE kid = '$kid' LIMIT 1";
            $result = $this->connection->executeQuery($sql)->fetchOne();

            return $result ?: null;
        } catch (\Exception $e) {
            $this->logger->error('JWT key lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Decode payload from token without validation
     * Useful for debugging or extracting claims
     */
    public function decodePayload(string $token): array|false
    {
        $parts = explode('.', $token);
        if (count($parts) < 2) {
            return false;
        }

        return json_decode($this->base64UrlDecode($parts[1]), true) ?: false;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
