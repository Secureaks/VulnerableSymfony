<?php

namespace App\Command;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'blog:init',
    description: 'Initialize the blog',
)]
class BlogInitCommand extends Command
{
    private string $appEnv;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, string $name = null)
    {
        parent::__construct($name);
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('count', InputArgument::OPTIONAL, 'Number of posts to create', 10)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $input->getArgument('count');

        $adminUser = new User();
        $adminUser->setUsername("admin");
        $adminUser->setEmail("admin@admin.com");
        $adminUser->setPassword(md5("P@ssw0rd07"));
        $adminUser->setRoles(["ROLE_ADMIN", "ROLE_USER"]);
        $this->entityManager->persist($adminUser);

        $normalUser = new User();
        $normalUser->setUsername("user");
        $normalUser->setEmail("user@user.com");
        $normalUser->setPassword(md5("user"));
        $normalUser->setRoles(["ROLE_USER"]);
        $this->entityManager->persist($normalUser);

        $this->entityManager->flush();

        $postContents = [
            file_get_contents(__DIR__ . "/../../resources/posts/post01.txt"),
            file_get_contents(__DIR__ . "/../../resources/posts/post02.txt"),
            file_get_contents(__DIR__ . "/../../resources/posts/post03.txt"),
            file_get_contents(__DIR__ . "/../../resources/posts/post04.txt"),
        ];

        $postComments = [
            file_get_contents(__DIR__ . "/../../resources/comments/comment01.txt"),
            file_get_contents(__DIR__ . "/../../resources/comments/comment02.txt"),
            file_get_contents(__DIR__ . "/../../resources/comments/comment03.txt"),
            file_get_contents(__DIR__ . "/../../resources/comments/comment04.txt"),
            file_get_contents(__DIR__ . "/../../resources/comments/comment05.txt"),
        ];

        $imgs = ['placeholder.png'];

        $postTitles = [
            "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
            "Nullam euismod, nisl eget aliquam ultricies, nunc nunc aliquet nunc, vitae al iquam.",
            "Sed euismod, nisl eget aliquam ultricies, nunc nunc aliquet nunc, vitae al iquam.",
            "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
        ];

        // Create blog posts
        for ($i = 0; $i < $count; $i++) {
            // Pickup the admin or the user randomly
            $user = rand(0, 1) ? $adminUser : $normalUser;

            $content = $postContents[rand(0, count($postContents) - 1)];
            $title = $postTitles[rand(0, count($postTitles) - 1)];
            $img = $imgs[rand(0, count($imgs) - 1)];

            // Random datetime between now and 1 year ago
            $date = new \DateTime();
            $date->modify('-'.rand(0, 365).' days');

            // Create the post
            $post = new Post();
            $post->setTitle($title);
            $post->setContent($content);
            $post->setAuthor($user);
            $post->setDate($date);
            $post->setImg($img);
            $this->entityManager->persist($post);

            for ($j = 0; $j < rand(0, 5); $j++) {
                $date = new \DateTime();
                $date->modify('-'.rand(0, 365).' days');

                // Create the comment
                $comment = new Comment();
                $comment->setAuthor(rand(0, 1) ? $adminUser : $normalUser);
                $comment->setPost($post);
                $comment->setContent($postComments[rand(0, count($postComments) - 1)]);
                $comment->setDate($date);
                $this->entityManager->persist($comment);
            }
        }

        $this->entityManager->flush();

        $io->success('Application has been initialized');

        return Command::SUCCESS;
    }
}
