<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    /**
     * #VULNERABILITY: Intended vulnerable request (Missing right control)
     */
    #[Route('/user/role/{user}', name: 'app_admin_role', methods: ['POST'])]
    public function changeRole(Request $request, UserRepository $userRepository, User $user): Response
    {
        $availableRoles = ['ROLE_USER', 'ROLE_ADMIN'];
        $requestedRole = $request->get('role');

        if (empty($requestedRole) || !in_array($requestedRole, $availableRoles)) {
            $this->addFlash('error', 'Role is not valid');
            return $this->redirectToRoute('app_admin');
        }

        $role = $requestedRole === 'ROLE_USER' ? ['ROLE_USER'] : ['ROLE_USER', 'ROLE_ADMIN'];

        $user = $userRepository->find($user);
        $user->setRoles($role);
        $userRepository->save($user, true);

        $this->addFlash('success', 'Role changed successfully');
        return $this->redirectToRoute('app_admin');
    }

    #[Route('/admin/delete/{user}', name: 'app_admin_delete')]
    public function deleteUser(
        EntityManagerInterface $entityManager,
        PostRepository         $postRepository,
        CommentRepository      $commentRepository,
        User                   $user
    ): Response
    {
        $postCount = $postRepository->countByUser($user);
        $commentCount = $commentRepository->countByUser($user);

        if ($postCount > 0 || $commentCount > 0) {
            $this->addFlash('error', 'User cannot be deleted because it has posts or comments');
        } else {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'User deleted successfully');
        }

        return $this->redirectToRoute('app_admin');
    }

    // Create a new user account
    #[Route('/admin/create', name: 'app_admin_create')]
    public function createUser(UserRepository $userRepository, Request $request): Response
    {

        $availableRoles = ['ROLE_USER', 'ROLE_ADMIN'];

        $email = $request->get('email');
        $username = $request->get('username');
        $password = $request->get('password');
        $requestedRole = $request->get('role');

        if (empty($requestedRole) || !in_array($requestedRole, $availableRoles)) {
            $this->addFlash('error', 'Role is not valid');
            return $this->redirectToRoute('app_admin');
        }

        // Check if email is valid and not already in use
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Email is not valid');
            return $this->redirectToRoute('app_admin');
        }

        // Check if email is not already in use
        $user = $userRepository->findOneBy(['email' => $email]);
        if ($user) {
            $this->addFlash('error', 'Email is already in use');
            return $this->redirectToRoute('app_admin');
        }

        // Check if username is not already in use
        $user = $userRepository->findOneBy(['username' => $username]);
        if ($user) {
            $this->addFlash('error', 'Username is already in use');
            return $this->redirectToRoute('app_admin');
        }

        // Check if password is not empty
        if (empty($password)) {
            $this->addFlash('error', 'Password cannot be empty');
            return $this->redirectToRoute('app_admin');
        }

        $role = $requestedRole === 'ROLE_USER' ? ['ROLE_USER'] : ['ROLE_USER', 'ROLE_ADMIN'];

        // Create the new user
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($role);
        $user->setUsername($username);
        $user->setPassword(md5($password));

        $userRepository->save($user, true);
        $this->addFlash('success', 'User created successfully');

        return $this->redirectToRoute('app_admin');
    }
}
