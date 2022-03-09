<?php

namespace Pixel\DirectoryBundle\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Pixel\DirectoryBundle\Entity\Card;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryTrait;

class CardRepository extends EntityRepository implements DataProviderRepositoryInterface
{
    use DataProviderRepositoryTrait;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, new ClassMetadata(Card::class));
    }

    public function create(string $locale): Card
    {
        $card = new Card();
        $card->setLocale($locale);
        return $card;
    }

    public function save(Card $card): void
    {
        $this->getEntityManager()->persist($card);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id, string $locale): ?Card
    {
        $card = $this->find($id);
        if (!$card) {
            return null;
        }
        $card->setLocale($locale);
        return $card;
    }

    public function findAllForSitemap(int $page, int $limit): array
    {
        $offset = ($page * $limit) - $limit;
        $criteria = [
            'isActive' => true,
        ];
        return $this->findBy($criteria, [], $limit, $offset);
    }

    public function countForSitemap()
    {
        $query = $this->createQueryBuilder('c')
            ->select('count(c)');
        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function appendJoins(QueryBuilder $queryBuilder, $alias, $locale)
    {
        $queryBuilder->addSelect('category')->leftJoin($alias . '.category', 'category');
        //$queryBuilder->addSelect($alias.'.category');
    }

    public function appendCategoriesRelation(QueryBuilder $queryBuilder, $alias)
    {
        return $alias . '.category';
        //$queryBuilder->addSelect($alias.'.category');
    }

    protected function appendSortByJoins(QueryBuilder $queryBuilder, string $alias, string $locale): void
    {
        $queryBuilder->innerJoin($alias . '.translations', 'translation', Join::WITH, 'translation.locale = :locale');
        $queryBuilder->setParameter('locale', $locale);
    }


}