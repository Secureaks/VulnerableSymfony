<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * #VULNERABILITY: Controller in place to be called through SSRF vulnerabilities
 */
#[Route('/local', name: 'app_local_')]
class LocalController extends AbstractController
{
    private function isLocalRequest(Request $request): bool
    {
        return in_array($request->getClientIp(), ['127.0.0.1', '::1']);
    }

    #[Route('/', name: 'list')]
    public function list(Request $request): Response
    {
        if (!$this->isLocalRequest($request)) {
            return new Response('Access denied', Response::HTTP_FORBIDDEN);
        }

        $list = "List of local endpoints:\n\n";
        $list .= "/local: List of endpoints\n";
        $list .= "/local/users: List of users\n";
        $list .= "/local/promote/{user}: Promote a user to admin\n";
        $list .= "/local/posts: List of posts\n";

        return new Response($list);
    }

    #[Route('/users', name: 'users')]
    public function users(
        Request        $request,
        UserRepository $userRepository
    ): Response
    {
        if (!$this->isLocalRequest($request)) {
            return new Response('Access denied', Response::HTTP_FORBIDDEN);
        }

        $users = $userRepository->findAll();

        $list = "List of users:\n\n";
        foreach ($users as $user) {
            $rights = in_array('ROLE_ADMIN', $user->getRoles()) ? 'Administrator' : 'User';
            $list .=
                $user->getId(). ' | ' .
                $rights . ' | ' .
                $user->getUsername() . ' | ' .
                $user->getEmail() . "\n";
        }

        return new Response($list);
    }

    #[Route('/promote/{user}', name: 'promote')]
    public function promote(
        Request                $request,
        User                   $user,
        EntityManagerInterface $entityManager
    ): Response
    {
        if (!$this->isLocalRequest($request)) {
            return new Response('Access denied', Response::HTTP_FORBIDDEN);
        }

        $user->setAdmin(true);
        $entityManager->persist($user);
        $entityManager->flush();

        return new Response('User promoted');
    }

    #[Route('/posts', name: 'posts')]
    public function posts(
        Request        $request,
        PostRepository $postRepository
    ): Response
    {
        if (!$this->isLocalRequest($request)) {
            return new Response('Access denied', Response::HTTP_FORBIDDEN);
        }

        $posts = $postRepository->findAll();

        $list = "List of posts:\n\n";
        foreach ($posts as $post) {
            $list .= $post->getId() . ' | ' . $post->getTitle() . ' | ' . $post->getDate()->format('Y-m-d H:i:s') . ' | ' . $post->getAuthor()->getUsername() . "\n";
        }

        return new Response($list);
    }

}