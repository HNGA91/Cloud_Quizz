<?php

namespace App\Entity;

use App\Repository\QuestionQuizzRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=QuestionQuizzRepository::class)
 */
class QuestionQuizz
{
    public const NB_CHOIX_MIN = 2;
    public const NB_CHOIX_MAX = 4;

    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity=JeuxQuizz::class)
     *
     * @ORM\JoinColumn(nullable=false)
     */
    private ?JeuxQuizz $jeux_quizz = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $question = null;

    /**
     * Image facultative affichée avec la question
     * (ex. « À quel pays appartient ce drapeau ? » + le drapeau).
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Assert\Length(max: 255)]
    #[Assert\Regex(
        pattern: '#^images/[a-z0-9/_-]+\.(svg|webp|png|jpe?g)$#',
        message: 'Chemin attendu : images/… en minuscules, sans accents ni espaces, terminé par .svg, .webp, .png ou .jpg.'
    )]
    private ?string $image = null;

    /**
     * cascade persist/remove + orphanRemoval : les choix vivent et meurent avec
     * leur question. Les contraintes de chaque Choix rempli sont vérifiées dans
     * valider() (les lignes vides du formulaire sont ignorées).
     *
     * @ORM\OneToMany(targetEntity=Choix::class, mappedBy="question", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @ORM\OrderBy({"position": "ASC"})
     *
     * @var Collection<int, Choix>
     */
    private Collection $choix;

    public function __construct()
    {
        $this->choix = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJeuxQuizz(): ?JeuxQuizz
    {
        return $this->jeux_quizz;
    }

    public function setJeuxQuizz(?JeuxQuizz $jeux_quizz): self
    {
        $this->jeux_quizz = $jeux_quizz;

        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(?string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $image = null === $image ? null : ltrim(trim($image), '/');
        $this->image = '' === $image ? null : $image;

        return $this;
    }

    /**
     * @return Collection<int, Choix>
     */
    public function getChoix(): Collection
    {
        return $this->choix;
    }

    public function addChoix(Choix $choix): self
    {
        if (!$this->choix->contains($choix)) {
            $this->choix[] = $choix;
            $choix->setQuestion($this);
        }

        return $this;
    }

    public function removeChoix(Choix $choix): self
    {
        $this->choix->removeElement($choix);

        return $this;
    }

    public function getBonChoix(): ?Choix
    {
        foreach ($this->choix as $choix) {
            if ($choix->isEstCorrect()) {
                return $choix;
            }
        }

        return null;
    }

    /**
     * Jouable = 2 à 4 choix dont une seule bonne réponse.
     * Une question incomplète (ex. reprise de l'ancien modèle) n'est jamais proposée au joueur.
     */
    public function estJouable(): bool
    {
        $nombre = $this->choix->count();
        $bonnes = $this->choix->filter(static fn (Choix $c): bool => $c->isEstCorrect())->count();

        return $nombre >= self::NB_CHOIX_MIN && $nombre <= self::NB_CHOIX_MAX && 1 === $bonnes;
    }

    /**
     * Complète avec des lignes vides jusqu'à 4, pour le formulaire d'administration.
     * Les lignes laissées vides sont retirées par retirerChoixVides().
     */
    public function completerChoixVides(): self
    {
        for ($i = $this->choix->count(); $i < self::NB_CHOIX_MAX; ++$i) {
            $this->addChoix(new Choix());
        }

        return $this;
    }

    /**
     * Retire les lignes vides et renumérote les positions 1, 2, 3…
     * À appeler après une validation réussie, juste avant l'enregistrement.
     */
    public function retirerChoixVides(): self
    {
        foreach ($this->choix->toArray() as $choix) {
            if ($choix->estVide()) {
                $this->removeChoix($choix);
            }
        }

        $position = 1;
        foreach ($this->choix as $choix) {
            $choix->setPosition($position++);
        }

        return $this;
    }

    /**
     * Règles métier d'une question :
     * - 2, 3 ou 4 choix (les lignes vides ne comptent pas) ;
     * - exactement une bonne réponse ;
     * - pas deux fois le même libellé.
     */
    #[Assert\Callback]
    public function valider(ExecutionContextInterface $context): void
    {
        $remplis = $this->choix->filter(static fn (Choix $c): bool => !$c->estVide());
        $nombre = $remplis->count();

        // Contraintes propres à chaque choix rempli (libellé, format du chemin d'image),
        // rattachées à la bonne ligne du formulaire : choix[0], choix[1]…
        foreach ($remplis as $index => $choix) {
            $context->getValidator()
                ->inContext($context)
                ->atPath(sprintf('choix[%d]', $index))
                ->validate($choix);
        }

        if ($nombre < self::NB_CHOIX_MIN || $nombre > self::NB_CHOIX_MAX) {
            $context->buildViolation('Une question doit avoir 2, 3 ou 4 choix (actuellement : {{ n }}).')
                ->setParameter('{{ n }}', (string) $nombre)
                ->atPath('choix')
                ->addViolation();
        }

        $bonnes = $remplis->filter(static fn (Choix $c): bool => $c->isEstCorrect())->count();

        if (1 !== $bonnes) {
            $context->buildViolation('Cochez exactement une bonne réponse (actuellement : {{ n }}).')
                ->setParameter('{{ n }}', (string) $bonnes)
                ->atPath('choix')
                ->addViolation();
        }

        $libelles = array_map(
            static fn (Choix $c): string => mb_strtolower((string) $c->getLibelle()),
            $remplis->toArray()
        );

        if (count($libelles) !== count(array_unique($libelles))) {
            $context->buildViolation('Deux choix ont le même libellé.')
                ->atPath('choix')
                ->addViolation();
        }
    }
}
