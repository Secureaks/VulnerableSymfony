<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\JwtService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * #VULNERABILITY: API Controller with JWT authentication vulnerabilities
 *
 * This controller demonstrates common JWT security flaws:
 * - Algorithm confusion attacks
 * - Token forgery via alg:none
 * - Privilege escalation through role manipulation in tokens
 * - Missing server-side authorization checks
 */
#[Route('/api', name: 'api_')]
class ApiController extends AbstractController
{
    /**
     * Authenticate user and return JWT token
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        JwtService $jwtService,
        UserRepository $userRepository
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'error' => 'Invalid JSON payload'
            ], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json([
                'error' => 'Email and password are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        // Using MD5 to match existing vulnerable password hashing
        if ($user && md5($password) === $user->getPassword()) {
            return $this->json([
                'token' => $jwtService->generateToken($user)
            ]);
        }

        return $this->json([
            'error' => 'Invalid credentials'
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Get current user profile from JWT token
     */
    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(
        Request $request,
        JwtService $jwtService,
        UserRepository $userRepository
    ): JsonResponse
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return $this->json([
                'error' => 'No token provided'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $payload = $jwtService->validateToken($token);

        if (!$payload) {
            return $this->json([
                'error' => 'Invalid or expired token'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = $userRepository->find($payload['sub']);

        if (!$user) {
            return $this->json([
                'error' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'roles' => $user->getRoles(),
                'aboutMe' => $user->getAboutMe()
            ]
        ]);
    }

    /**
     * #VULNERABILITY: List all users - trusts roles from JWT payload
     *
     * The roles are read from the token payload without server-side verification.
     * An attacker can forge a token with alg:none and set roles to ["ROLE_ADMIN"]
     *
     * Exploit:
     * 1. Create token with alg:none header
     * 2. Set payload with roles:["ROLE_ADMIN"]
     * 3. Access this endpoint with the forged token
     */
    #[Route('/users', name: 'users', methods: ['GET'])]
    public function users(
        Request $request,
        JwtService $jwtService,
        UserRepository $userRepository
    ): JsonResponse
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return $this->json([
                'error' => 'No token provided'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $payload = $jwtService->validateToken($token);

        if (!$payload) {
            return $this->json([
                'error' => 'Invalid or expired token'
            ], Response::HTTP_UNAUTHORIZED);
        }

        /**
         * #VULNERABILITY: Trusting roles from JWT payload
         * Should verify roles from database, not from the token
         */
        if (!in_array('ROLE_ADMIN', $payload['roles'] ?? [])) {
            return $this->json([
                'error' => 'Admin privileges required'
            ], Response::HTTP_FORBIDDEN);
        }

        $users = $userRepository->findAll();

        // Detect if token uses alg:none (forged token)
        $tokenParts = explode('.', $this->extractToken($request));
        $header = json_decode(base64_decode(strtr($tokenParts[0], '-_', '+/')), true);
        $isAlgNone = strtolower($header['alg'] ?? '') === 'none';

        return $this->json([
            'flag' => $isAlgNone ? 'FLAG{4lg_n0n3_1s_n0t_s3cur3}' : 'FLAG{w34k_s3cr3t_cr4ck3d}',
            'users' => array_map(fn(User $u) => [
                'id' => $u->getId(),
                'email' => $u->getEmail(),
                'username' => $u->getUsername(),
                'roles' => $u->getRoles()
            ], $users)
        ]);
    }

    /**
     * #VULNERABILITY: Promote user to admin - trusts roles from JWT payload
     *
     * Same vulnerability as /users endpoint - roles are not verified server-side
     */
    #[Route('/admin/promote/{user}', name: 'promote', methods: ['POST'])]
    public function promote(
        Request $request,
        User $user,
        JwtService $jwtService,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return $this->json([
                'error' => 'No token provided'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $payload = $jwtService->validateToken($token);

        if (!$payload) {
            return $this->json([
                'error' => 'Invalid or expired token'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // #VULNERABILITY: Trusting roles from JWT payload
        if (!in_array('ROLE_ADMIN', $payload['roles'] ?? [])) {
            return $this->json([
                'error' => 'Admin privileges required'
            ], Response::HTTP_FORBIDDEN);
        }

        $user->setAdmin(true);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'flag' => 'FLAG{pr1v1l3g3_3sc4l4t10n_v14_jwt}',
            'success' => true,
            'message' => 'User promoted to admin',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ]
        ]);
    }

    /**
     * #VULNERABILITY: Endpoint to test expiration bypass
     * The server does not validate the exp claim
     */
    #[Route('/secret', name: 'secret', methods: ['GET'])]
    public function secret(
        Request $request,
        JwtService $jwtService
    ): JsonResponse
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return $this->json([
                'error' => 'No token provided'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $payload = $jwtService->validateToken($token);

        if (!$payload) {
            return $this->json([
                'error' => 'Invalid token'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check if token should be expired (but server accepted it anyway)
        $exp = $payload['exp'] ?? time() + 9999;
        $isExpired = $exp < time();

        if ($isExpired) {
            return $this->json([
                'flag' => 'FLAG{3xp1r4t10n_byp4ss_n0_v4l1d4t10n}',
                'message' => 'Your token is expired but was accepted anyway!'
            ]);
        }

        return $this->json([
            'message' => 'Token is valid. Try with an expired token to get the flag.',
            'exp' => $exp,
            'current_time' => time()
        ]);
    }

    /**
     * Extract Bearer token from Authorization header
     */
    private function extractToken(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader) {
            return null;
        }

        if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
