<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Services\Analytics;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BlogController extends AbstractController
{
    /**
     * #VULNERABILITY: Intended vulnerable request (SSRF + RCE in the referer header through the track method of the Analytics service)
     */
    #[Route('/', name: 'app_blog')]
    public function index(PostRepository $postRepository, Analytics $analytics): Response
    {
        $analytics->track();
        return $this->render('blog/index.html.twig', [
            'posts' => $postRepository->findAllOrdered(),
        ]);
    }

    /**
     * #VULNERABILITY: Intended vulnerable request (SSRF + RCE in the referer header through the track method of the Analytics service)
     */
    #[Route('/post/{post}', name: 'app_blog_post')]
    public function post(Post $post, CommentRepository $commentRepository, Analytics $analytics): Response
    {
        $analytics->track();
        return $this->render('blog/post.html.twig', [
            'post' => $post,
            'comments' => $commentRepository->findByPostOrdered($post->getId()),
        ]);
    }

    /**
     * #VULNERABILITY: Intended vulnerable request (Stored XSS on comment parameter)
     */
    #[Route('/post/{post}/comment', name: 'app_blog_post_comment', methods: ['POST'])]
    public function comment(Post $post, Request $request, CommentRepository $commentRepository): Response
    {
        // If the user is not logged in, redirect to the login page
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $comment = new Comment();
        $comment->setPost($post);
        $comment->setAuthor($this->getUser());
        $comment->setContent($request->get('comment'));
        $comment->setDate(new \DateTime());
        $commentRepository->save($comment, true);

        return $this->redirectToRoute('app_blog_post', ['post' => $post->getId()]);
    }

    /**
     * #VULNERABILITY: Intended vulnerable request (Reflected XSS on search parameter)
     * #VULNERABILITY: Intended vulnerable request (SQL Injection on search parameter)
     */
    #[Route('/search', name: 'app_blog_post_search', methods: ['GET'])]
    public function search(Request $request, PostRepository $postRepository): Response
    {
        $search = $request->get('s');
        $posts = $postRepository->search($search);

        return $this->render('blog/index.html.twig', [
            'search' => $search,
            'posts' => $posts,
        ]);
    }

    // Legal page
    #[Route('/legal', name: 'app_legal')]
    public function legal(): Response
    {
        return $this->render('blog/legal.html.twig');
    }

    // Legal content
    /**
     * #VULNERABILITY: Intended vulnerable request (File Inclusion)
     */
    #[Route('/legal/content', name: 'app_legal_content', methods: ['GET'])]
    public function legalContent(Request $request): Response
    {
        $contentPath = __DIR__ . '/../../templates/legal/' . $request->get('p');
        if (is_dir($contentPath) || !file_exists($contentPath))
            throw $this->createNotFoundException();

        return new Response(file_get_contents($contentPath));
    }
}
