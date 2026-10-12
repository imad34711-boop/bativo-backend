<?php

namespace App\Dto;

use App\Entity\Projet;
use App\Entity\StatutProjet;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Charge utile de creation et de mise a jour d'un projet.
 */
final class ProjetInput
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Le numero de marche est obligatoire.')]
        #[Assert\Length(max: 100)]
        public string $numeroMarche = '',

        #[Assert\NotBlank(message: "L'objet du marche est obligatoire.")]
        public string $objet = '',

        #[Assert\Positive]
        public ?int $numero = null,

        #[Assert\Length(max: 150)]
        public ?string $ville = null,

        #[Assert\PositiveOrZero(message: 'Le montant du marche ne peut pas etre negatif.')]
        public float $montantMarcheHt = 0.0,

        #[Assert\Range(min: 0, max: 100)]
        public float $tauxTva = 20.0,

        #[Assert\PositiveOrZero]
        public ?float $cautionProvisoire = null,

        #[Assert\PositiveOrZero]
        public ?float $cautionDefinitive = null,

        #[Assert\Range(min: 0, max: 100)]
        public float $tauxRetenueGarantie = 10.0,

        #[Assert\Range(min: 0, max: 100)]
        public float $tauxPlafondRetenueGarantie = 7.0,

        #[Assert\Length(max: 200)]
        public ?string $maitreOuvrage = null,

        #[Assert\Length(max: 200)]
        public ?string $maitreOuvrageDelegue = null,

        #[Assert\Length(max: 200)]
        public ?string $architecte = null,

        #[Assert\Length(max: 200)]
        public ?string $bet = null,

        #[Assert\Length(max: 200)]
        public ?string $bureauControle = null,

        #[Assert\Length(max: 200)]
        public ?string $laboratoire = null,

        #[Assert\Length(max: 200)]
        public ?string $topographe = null,

        #[Assert\Positive]
        public ?int $dureeMois = null,

        #[Assert\Positive]
        public ?int $dureeJours = null,

        public ?\DateTimeImmutable $dateDebut = null,

        public ?\DateTimeImmutable $dateFin = null,

        public ?string $statutCode = null,

        public array $metadata = [],
    ) {
    }

    #[Assert\Callback]
    public function validerPeriode(\Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        if (null !== $this->dateDebut && null !== $this->dateFin && $this->dateFin < $this->dateDebut) {
            $context->buildViolation('La date de fin ne peut pas preceder la date de debut.')
                ->atPath('dateFin')
                ->addViolation();
        }
    }

    public function applyTo(Projet $projet, ?StatutProjet $statut): void
    {
        $projet
            ->setNumero($this->numero)
            ->setNumeroMarche($this->numeroMarche)
            ->setObjet($this->objet)
            ->setVille($this->ville)
            ->setMontantMarcheHt(self::decimal($this->montantMarcheHt))
            ->setTauxTva(self::decimal($this->tauxTva))
            ->setCautionProvisoire(null === $this->cautionProvisoire ? null : self::decimal($this->cautionProvisoire))
            ->setCautionDefinitive(null === $this->cautionDefinitive ? null : self::decimal($this->cautionDefinitive))
            ->setTauxRetenueGarantie(self::decimal($this->tauxRetenueGarantie))
            ->setTauxPlafondRetenueGarantie(self::decimal($this->tauxPlafondRetenueGarantie))
            ->setMaitreOuvrage($this->maitreOuvrage)
            ->setMaitreOuvrageDelegue($this->maitreOuvrageDelegue)
            ->setArchitecte($this->architecte)
            ->setBet($this->bet)
            ->setBureauControle($this->bureauControle)
            ->setLaboratoire($this->laboratoire)
            ->setTopographe($this->topographe)
            ->setDureeMois($this->dureeMois)
            ->setDureeJours($this->dureeJours)
            ->setDateDebut($this->dateDebut)
            ->setDateFin($this->dateFin)
            ->setStatut($statut)
            ->setMetadata($this->metadata);
    }

    private static function decimal(float $valeur): string
    {
        return number_format($valeur, 2, '.', '');
    }
}
