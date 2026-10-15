<?php

namespace App\Repository;

use App\Entity\QuestionQuizz;
use App\Entity\ThemeQuizz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ThemeQuizz>
 */
class ThemeQuizzRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ThemeQuizz::class);
    }

    /**
     * Identifiants des thèmes jouables : ceux qui ont au moins un quizz
     * contenant au moins une question avec une bonne réponse.
     * Calculé en une requête, pour tous les thèmes de la page d'accueil.
     *
     * @return int[]
     */
    public function findIdsJouables(): array
    {
        $ids = $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT IDENTITY(j.themeQuizz) AS id')
            ->from(QuestionQuizz::class, 'q')
            ->join('q.jeux_quizz', 'j')
            ->join('q.choix', 'c')
            ->where('c.estCorrect = true')
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $ids);
    }
}
