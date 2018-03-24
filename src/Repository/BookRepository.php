<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 * @method Book[]    findAll()
 * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * @param integer $page
     * @param integer $perPage
     * @param string $searchText
     * @return Paginator
     */
    public function getPage($page, $perPage, $searchText = '')
    {
        $queryBuilder = $this->createQueryBuilder('b');

        $like = '%' . addcslashes($searchText, '%_') . '%';

        if (strlen($searchText)) {
            $queryBuilder
                ->leftJoin('b.author', 'author')
                ->leftJoin('b.genre', 'genre')
                ->where('b.title LIKE :title')
                ->orWhere('b.isbn = :query')
                ->orWhere('author.name LIKE :title')
                ->orWhere('genre.name LIKE :title')
                ->setParameter('query', $searchText)
                ->setParameter('title', $like);
        }

        $queryBuilder
            ->orderBy('b.title', 'ASC')
            ->setFirstResult($perPage * ($page - 1))
            ->setMaxResults($perPage);

        $query = $queryBuilder->getQuery();

        return (new Paginator($query));
    }
}
