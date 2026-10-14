<?php

namespace App\Repository;

use App\Entity\JeuxQuizz;
use App\Entity\QuestionQuizz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuestionQuizz>
 */
class QuestionQuizzRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionQuizz::class);
    }

    /**
     * Questions avec leurs choix chargés en une seule requête (JOIN), au lieu
     * d'une requête supplémentaire par question (problème « N+1 »).
     *
     * @return QuestionQuizz[]
     */
    public function findAvecChoix(?JeuxQuizz $quizz = null): array
    {
        $qb = $this->createQueryBuilder('q')
            ->addSelect('c', 'j')
            ->leftJoin('q.choix', 'c')
            ->join('q.jeux_quizz', 'j')
            ->orderBy('q.id', 'ASC')
            ->addOrderBy('c.position', 'ASC');

        if (null !== $quizz) {
            $qb->andWhere('q.jeux_quizz = :quizz')->setParameter('quizz', $quizz);
        }

        return $qb->getQuery()->getResult();
    }
}
