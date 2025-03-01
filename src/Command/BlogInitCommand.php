<?php

namespace App\Command;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'blog:init',
    description: 'Initialize the blog',
)]
class BlogInitCommand extends Command
{
    private string $appEnv;

    private array $imageList = [
        'https://fastly.picsum.photos/id/1/5000/3333.jpg?hmac=Asv2DU3rA_5D1xSe22xZK47WEAN0wjWeFOhzd13ujW4',
        'https://fastly.picsum.photos/id/20/3670/2462.jpg?hmac=CmQ0ln-k5ZqkdtLvVO23LjVAEabZQx2wOaT4pyeG10I',
        'https://fastly.picsum.photos/id/26/4209/2769.jpg?hmac=vcInmowFvPCyKGtV7Vfh7zWcA_Z0kStrPDW3ppP0iGI',
        'https://fastly.picsum.photos/id/43/1280/831.jpg?hmac=glK-rQ0ppFClW-lvjk9FqEWKog07XkOxJf6Xg_cU9LI',
        'https://fastly.picsum.photos/id/58/1280/853.jpg?hmac=YO3QnOm9TpyM5DqsJjoM4CHg8oIq4cMWLpd9ALoP908',
        'https://fastly.picsum.photos/id/54/3264/2176.jpg?hmac=blh020fMeJ5Ru0p-fmXUaOAeYnxpOPHnhJojpzPLN3g',
        'https://fastly.picsum.photos/id/60/1920/1200.jpg?hmac=fAMNjl4E_sG_WNUjdU39Kald5QAHQMh-_-TsIbbeDNI',
        'https://fastly.picsum.photos/id/76/4912/3264.jpg?hmac=VkFcSa2Rbv0R0ndYnz_FAmw02ON1pPVjuF_iVKbiiV8',
        'https://fastly.picsum.photos/id/84/1280/848.jpg?hmac=YFRYDI4UsfbeTzI8ZakNOR98wVU7a-9a2tGF542539s',
        'https://fastly.picsum.photos/id/122/4147/2756.jpg?hmac=-B_1uAvYufznhjeA9xSSAJjqt07XrVzDWCf5VDNX0pQ',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CommentRepository $commentRepository,
        private PostRepository $postRepository,
        private UserRepository $userRepository,
        string $name = null
    ) {
        parent::__construct($name);
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

        $io->info('Initializing the blog...');

        if (!$this->isDatabaseEmpty()) {
            $io->warning('Database is not empty.');
            $validation = $io->ask("Do you want to clear the database?", "no", function ($answer) {;
                if (!in_array($answer, ["yes", "no"])) {
                    throw new \RuntimeException("You must answer yes or no");
                }
                return $answer;
            });

            if ($validation !== "yes") {
                $io->warning("Operation aborted.");
                return Command::FAILURE;
            }

            $io->info("Clearing the database...");
            // Call the clear command
            $command = $this->getApplication()->find('blog:clear');
            $command->run(new ArrayInput(['force' => true]), $output);
        }

        $count = $input->getArgument('count');

        $adminUser = new User();
        $adminUser->setUsername("admin");
        $adminUser->setFirstname("Admin");
        $adminUser->setLastname("Admin");
        $adminUser->setEmail("admin@admin.com");
        $adminUser->setPassword(md5("P@ssw0rd07"));
        $adminUser->setRoles(["ROLE_ADMIN", "ROLE_USER"]);
        $adminUser->setAdmin(true);
        $this->entityManager->persist($adminUser);

        $normalUser = new User();
        $normalUser->setUsername("user");
        $normalUser->setFirstname("User");
        $normalUser->setLastname("User");
        $normalUser->setEmail("user@user.com");
        $normalUser->setPassword(md5("user"));
        $normalUser->setRoles(["ROLE_USER"]);
        $normalUser->setAdmin(false);
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

        $postTitles = [
            "Harnessing the Cloud: Innovations in Remote Work Technology",
            "Green Thumbs Up: The Rise of Urban Gardening in 2024",
            "Echoes of the Past: How Historical Fiction Influences Modern Culture",
            "Silicon Valley Skirmishes: Inside the Competitive Tech Startup Scene",
        ];

        // Create blog posts
        for ($i = 0; $i < $count; $i++) {
            // Pickup the admin or the user randomly
            $user = rand(0, 1) ? $adminUser : $normalUser;

            $content = $postContents[rand(0, count($postContents) - 1)];
            $title = $postTitles[rand(0, count($postTitles) - 1)];
            $img = $this->getNextImage($i);

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

    private function getNextImage(int $index): string {
        return $this->imageList[$index % count($this->imageList)];
    }

    private function isDatabaseEmpty(): bool {
        $count = $this->userRepository->total();
        $count += $this->postRepository->total();
        $count += $this->commentRepository->total();

        return $count === 0;
    }
}
