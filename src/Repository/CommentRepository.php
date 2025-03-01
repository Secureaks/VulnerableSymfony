<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 *
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function save(Comment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Comment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    public function total(): int
    {
        return $this->createQueryBuilder("c")
            ->select("COUNT(c.id)")
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByPostOrdered(int $postId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.post = :postId')
            ->setParameter('postId', $postId)
            ->orderBy('c.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('c')
            ->select('count(c.id)')
            ->where('c.author = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
