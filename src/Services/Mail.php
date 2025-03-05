<?php

namespace App\Services;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Mail
{
    public function __construct(
        private string $sender,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $router,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function sendReset(array $emails, string $token): void
    {
        foreach ($emails as $emailAddress) {
            $url = $this->router->generate('app_reset_password', [
                'email' => $emailAddress,
                'token' => $token
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $email = (new Email())
                ->from($this->sender)
                ->to($emailAddress)
                ->subject('Reset your password')
                ->html('<p>Click <a href="'.$url.'">here</a> to reset your password</p>');

            $this->mailer->send($email);

            $user = $this->userRepository->findOneBy(['email' => $emailAddress]);
            $user->setReset($token);
            $this->entityManager->flush();
        }

    }
}