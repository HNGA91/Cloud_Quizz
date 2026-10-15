<?php

namespace App\Command;

use App\Entity\Choix;
use App\Entity\JeuxQuizz;
use App\Entity\QuestionQuizz;
use App\Entity\ThemeQuizz;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Crée (ou recrée) les quizz de démonstration du thème Géographie :
 * « Capitales » et « Drapeaux ».
 *
 *   php bin/console app:demo:geographie
 *
 * Idempotente : relancée, elle remplace les questions de ces deux quizz
 * sans créer de doublons (thème et quizz retrouvés par leur nom).
 */
#[AsCommand(
    name: 'app:demo:geographie',
    description: 'Crée les quizz de démonstration Géographie (Capitales, Drapeaux)',
)]
class DemoGeographieCommand extends Command
{
    private const THEME = ['titre' => 'Géographie', 'image' => 'images/carte/geographie/geography.jpg', 'alt' => 'Globe terrestre'];

    private const DOSSIER_DRAPEAUX = 'images/jeux/geographie/drapeaux/';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
        #[Autowire('%kernel.project_dir%/public')]
        private readonly string $dossierPublic,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $quizz = $this->donnees();

        // 1. Toutes les images doivent exister avant d'écrire quoi que ce soit
        $manquantes = [];
        foreach ($quizz as $def) {
            foreach ($def['questions'] as $q) {
                foreach (array_merge([$q['image']], array_column($q['choix'], 1)) as $image) {
                    if (null !== $image && !is_file($this->dossierPublic.'/'.$image)) {
                        $manquantes[$image] = true;
                    }
                }
            }
        }
        if ([] !== $manquantes) {
            $io->error(['Images introuvables dans public/ :', ...array_keys($manquantes)]);

            return Command::FAILURE;
        }

        // 2. Thème et quizz : retrouvés par leur nom, créés s'ils n'existent pas
        $theme = $this->trouver(ThemeQuizz::class, 'titre', self::THEME['titre']);
        if (!$theme instanceof ThemeQuizz) {
            $theme = (new ThemeQuizz())
                ->setTitre(self::THEME['titre'])
                ->setImage(self::THEME['image'])
                ->setImageAlt(self::THEME['alt']);
            $this->em->persist($theme);
            $io->note('Thème « Géographie » créé.');
        }

        $aRemplacer = 0;
        $jeux = [];
        foreach ($quizz as $nom => $def) {
            $jeu = $this->trouver(JeuxQuizz::class, 'jeux', $nom, $theme);
            if (!$jeu instanceof JeuxQuizz) {
                $jeu = (new JeuxQuizz())
                    ->setThemeQuizz($theme)
                    ->setJeux($nom)
                    ->setImage($def['image'])
                    ->setImageAlt($def['alt']);
                $this->em->persist($jeu);
                $io->note(sprintf('Quizz « %s » créé.', $nom));
            } else {
                $aRemplacer += $this->em->getRepository(QuestionQuizz::class)->count(['jeux_quizz' => $jeu]);
            }
            $jeux[$nom] = $jeu;
        }

        if ($aRemplacer > 0 && !$io->confirm(sprintf('%d question(s) existante(s) de ces quizz seront remplacées. Continuer ?', $aRemplacer))) {
            return Command::SUCCESS;
        }

        // 3. Remplacement des questions, en une transaction : tout ou rien
        $this->em->wrapInTransaction(function () use ($quizz, $jeux, $io): void {
            foreach ($quizz as $nom => $def) {
                foreach ($this->em->getRepository(QuestionQuizz::class)->findBy(['jeux_quizz' => $jeux[$nom]]) as $ancienne) {
                    $this->em->remove($ancienne); // ses choix suivent (cascade)
                }

                foreach ($def['questions'] as $q) {
                    $question = (new QuestionQuizz())
                        ->setJeuxQuizz($jeux[$nom])
                        ->setQuestion($q['texte'])
                        ->setImage($q['image']);

                    // Ordre d'insertion mélangé : la bonne réponse n'a pas toujours le plus petit id
                    $choix = $q['choix'];
                    shuffle($choix);

                    foreach ($choix as $position => [$libelle, $image, $correct]) {
                        $question->addChoix(
                            (new Choix())
                                ->setLibelle($libelle)
                                ->setImage($image)
                                ->setEstCorrect($correct)
                                ->setPosition($position + 1)
                        );
                    }

                    // Mêmes règles que le formulaire d'administration
                    $erreurs = $this->validator->validate($question);
                    if (count($erreurs) > 0) {
                        throw new \RuntimeException(sprintf('« %s » : %s', $q['texte'], (string) $erreurs));
                    }

                    $this->em->persist($question);
                }

                $io->writeln(sprintf(' ✔ %s : %d questions', $nom, count($def['questions'])));
            }
        });

        $io->success('Quizz de démonstration Géographie prêts.');

        return Command::SUCCESS;
    }

    /**
     * Recherche insensible à la casse (« géographie » = « Géographie »).
     */
    private function trouver(string $classe, string $champ, string $valeur, ?ThemeQuizz $theme = null): ?object
    {
        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from($classe, 'e')
            ->where(sprintf('LOWER(e.%s) = LOWER(:valeur)', $champ))
            ->setParameter('valeur', $valeur)
            ->setMaxResults(1);

        if (null !== $theme && null !== $theme->getId()) {
            $qb->andWhere('e.themeQuizz = :theme')->setParameter('theme', $theme);
        } elseif (null !== $theme) {
            return null; // thème tout juste créé : il n'a encore aucun quizz
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Le premier choix de chaque question est la bonne réponse
     * (l'ordre est mélangé à l'affichage).
     *
     * @return array<string, array{image: string, alt: string, questions: list<array{texte: string, image: ?string, choix: list<array{0: string, 1: ?string, 2: bool}>}>}>
     */
    private function donnees(): array
    {
        $capitale = static fn (string $pays, string $bonne, string ...$autres): array => [
            'texte' => sprintf('Quelle est la capitale %s ?', $pays),
            'image' => null,
            'choix' => array_merge([[$bonne, null, true]], array_map(static fn (string $a): array => [$a, null, false], $autres)),
        ];

        // « Quel est le drapeau de … ? » : les choix sont des images
        $drapeauDe = fn (string $pays, string $bonne, string ...$autres): array => [
            'texte' => sprintf('Quel est le drapeau %s ?', $pays),
            'image' => null,
            'choix' => array_merge(
                [[$this->nomPays($bonne), self::DOSSIER_DRAPEAUX.$bonne.'.svg', true]],
                array_map(fn (string $a): array => [$this->nomPays($a), self::DOSSIER_DRAPEAUX.$a.'.svg', false], $autres)
            ),
        ];

        // « À quel pays appartient ce drapeau ? » : l'image est sur la question
        $paysDuDrapeau = static fn (string $fichier, string $bonne, string ...$autres): array => [
            'texte' => 'À quel pays appartient ce drapeau ?',
            'image' => self::DOSSIER_DRAPEAUX.$fichier.'.svg',
            'choix' => array_merge([[$bonne, null, true]], array_map(static fn (string $a): array => [$a, null, false], $autres)),
        ];

        return [
            'Capitales' => [
                'image' => 'images/carte/geographie/eiffel-tower.jpg',
                'alt' => 'Tour Eiffel',
                'questions' => [
                    $capitale('de la France', 'Paris', 'Lyon', 'Marseille', 'Bruxelles'),
                    $capitale("de l'Italie", 'Rome', 'Milan', 'Naples', 'Turin'),
                    $capitale("de l'Espagne", 'Madrid', 'Barcelone', 'Séville', 'Lisbonne'),
                    $capitale('du Portugal', 'Lisbonne', 'Porto', 'Madrid'),
                    $capitale("de l'Allemagne", 'Berlin', 'Munich', 'Francfort', 'Vienne'),
                    $capitale('du Canada', 'Ottawa', 'Toronto', 'Montréal', 'Vancouver'),
                    $capitale("de l'Australie", 'Canberra', 'Sydney', 'Melbourne', 'Perth'),
                    $capitale('du Brésil', 'Brasília', 'Rio de Janeiro', 'São Paulo', 'Buenos Aires'),
                    $capitale('du Japon', 'Tokyo', 'Kyoto', 'Osaka'),
                    $capitale('du Maroc', 'Rabat', 'Casablanca', 'Marrakech', 'Alger'),
                    $capitale('du Sénégal', 'Dakar', 'Abidjan', 'Bamako', 'Saint-Louis'),
                    [
                        'texte' => 'Vrai ou faux : Berne est la capitale de la Suisse.',
                        'image' => null,
                        'choix' => [['Vrai', null, true], ['Faux', null, false]],
                    ],
                ],
            ],
            'Drapeaux' => [
                'image' => 'images/carte/geographie/flags.jpg',
                'alt' => 'Drapeaux du monde',
                'questions' => [
                    $drapeauDe("de l'Irlande", 'irlande', 'cote-divoire', 'italie', 'nigeria'),
                    $drapeauDe('de la Suède', 'suede', 'norvege', 'finlande', 'danemark'),
                    $drapeauDe('de la Belgique', 'belgique', 'allemagne', 'roumanie', 'tchad'),
                    $drapeauDe('de Monaco', 'monaco', 'indonesie', 'pologne'),
                    $drapeauDe('des Pays-Bas', 'pays-bas', 'luxembourg', 'france', 'russie'),
                    $drapeauDe('du Japon', 'japon', 'bangladesh', 'coree-du-sud'),
                    $paysDuDrapeau('bresil', 'Brésil', 'Portugal', 'Argentine', 'Colombie'),
                    $paysDuDrapeau('canada', 'Canada', 'Pérou', 'Liban', 'Suisse'),
                    $paysDuDrapeau('royaume-uni', 'Royaume-Uni', 'Australie', 'Nouvelle-Zélande', 'États-Unis'),
                    $paysDuDrapeau('grece', 'Grèce', 'Uruguay', 'Finlande'),
                    $paysDuDrapeau('afrique-du-sud', 'Afrique du Sud', 'Jamaïque', 'Kenya', 'Zimbabwe'),
                    [
                        'texte' => 'Ce drapeau est-il celui du Portugal ?',
                        'image' => self::DOSSIER_DRAPEAUX.'portugal.svg',
                        'choix' => [['Oui', null, true], ['Non', null, false]],
                    ],
                ],
            ],
        ];
    }

    private function nomPays(string $fichier): string
    {
        return [
            'irlande' => 'Irlande', 'cote-divoire' => "Côte d'Ivoire", 'italie' => 'Italie', 'nigeria' => 'Nigeria',
            'suede' => 'Suède', 'norvege' => 'Norvège', 'finlande' => 'Finlande', 'danemark' => 'Danemark',
            'belgique' => 'Belgique', 'allemagne' => 'Allemagne', 'roumanie' => 'Roumanie', 'tchad' => 'Tchad',
            'monaco' => 'Monaco', 'indonesie' => 'Indonésie', 'pologne' => 'Pologne',
            'pays-bas' => 'Pays-Bas', 'luxembourg' => 'Luxembourg', 'france' => 'France', 'russie' => 'Russie',
            'japon' => 'Japon', 'bangladesh' => 'Bangladesh', 'coree-du-sud' => 'Corée du Sud',
        ][$fichier] ?? ucfirst($fichier);
    }
}
