<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    /**
     * #VULNERABILITY: Intended vulnerable request (SQL Injection)
     */
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function index(Request $request, UserRepository $repository, Security $security): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->get("email");
            $plainPassword = $request->get("password");

            try {
                $user = $repository->getUserLogin($email, $plainPassword);

                if (!$user) {
                    $this->addFlash('error', 'Invalid credentials');
                } else {
                    // Create the user session
                    $userForSecurity = $repository->findOneBy(['email' => $user['email'] ?? ""]);
                    if (!$userForSecurity) {
                        $this->addFlash('error', 'Invalid credentials');
                        return $this->render('login/index.html.twig', []);
                    }
                    $security->login($userForSecurity);
                    return $this->redirectToRoute('app_blog');
                }

            } catch (\Exception $e) {
                $this->addFlash('error', 'Invalid credentials');
            }
        }

        return $this->render('login/index.html.twig', []);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout()
    {
        // controller can be blank: it will never be called!
    }

    /**
     * #VULNERABILITY: User enumeration
     */
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserRepository $userRepository): Response
    {
        if ($request->isMethod('POST')) {


            $email = $request->get('email');
            $username = $request->get('username');
            $password = $request->get('password');
            $confirmPassword = $request->get('confirmPassword');

            // Check if email is valid and not already in use
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Email is not valid');
                return $this->redirectToRoute('app_register');
            }

            // Check if email is not already in use
            $user = $userRepository->findOneBy(['email' => $email]);
            if ($user) {
                $this->addFlash('error', 'Email is already in use');
                return $this->redirectToRoute('app_register');
            }

            // Check if username is not already in use
            $user = $userRepository->findOneBy(['username' => $username]);
            if ($user) {
                $this->addFlash('error', 'Username is already in use');
                return $this->redirectToRoute('app_register');
            }

            // Check if password is not empty
            if (empty($password)) {
                $this->addFlash('error', 'Password cannot be empty');
                return $this->redirectToRoute('app_register');
            }

            // Check if password and confirm password are the same
            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Password and confirm password are not the same');
                return $this->redirectToRoute('app_register');
            }

            // Create the new user
            $user = new User();
            $user->setEmail($email);
            $user->setRoles(['ROLE_USER']);
            $user->setUsername($username);
            $user->setPassword(md5($password));

            $userRepository->save($user, true);

            return $this->redirectToRoute('app_login', [
                'message' => 'User created successfully, please log in'
            ]);
        }

        return $this->render('login/register.html.twig', []);
    }

}
