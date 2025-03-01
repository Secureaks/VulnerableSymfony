<?php

namespace App\Command;

use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'blog:clear',
    description: 'Clear the blog',
)]
class BlogClearCommand extends Command
{
    private string $appEnv;

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
            ->addArgument('force', InputArgument::OPTIONAL, 'Do not ask for confirmation', false)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $force = $input->getArgument('force');

        if (!$force) {
            $validation = $io->ask("Are you sure you want to clear the database?", "no", function ($answer) {
                if (!in_array($answer, ["yes", "no"])) {
                    throw new \RuntimeException("You must answer yes or no");
                }
                return $answer;
            });

            if ($validation !== "yes") {
                $io->warning("Operation aborted");
                return Command::FAILURE;
            }
        }

        $io->info("Start to clear the database...");

        $io->info("Clearing the comments...");
        $comments = $this->commentRepository->findAll();
        foreach ($comments as $comment) {
            $this->entityManager->remove($comment);
        }

        $io->info("Clearing the posts...");
        $posts = $this->postRepository->findAll();
        foreach ($posts as $post) {
            $this->entityManager->remove($post);
        }

        $io->info("Clearing the users...");
        $users = $this->userRepository->findAll();
        foreach ($users as $user) {
            $this->entityManager->remove($user);
        }

        $io->info("Flushing the changes...");
        $this->entityManager->flush();

        $io->success('Database has been cleared!');

        return Command::SUCCESS;
    }

}
