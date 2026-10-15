<?php

namespace App\Repository;

use App\Entity\JeuxQuizz;
use App\Entity\QuestionQuizz;
use App\Entity\ThemeQuizz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JeuxQuizz>
 */
class JeuxQuizzRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JeuxQuizz::class);
    }

    /**
     * Identifiants des quizz jouables (au moins une question avec une bonne réponse),
     * éventuellement limités à un thème.
     *
     * @return int[]
     */
    public function findIdsJouables(?ThemeQuizz $theme = null): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT IDENTITY(q.jeux_quizz) AS id')
            ->from(QuestionQuizz::class, 'q')
            ->join('q.choix', 'c')
            ->where('c.estCorrect = true');

        if (null !== $theme) {
            $qb->join('q.jeux_quizz', 'j')
                ->andWhere('j.themeQuizz = :theme')
                ->setParameter('theme', $theme);
        }

        return array_map('intval', $qb->getQuery()->getSingleColumnResult());
    }
}
