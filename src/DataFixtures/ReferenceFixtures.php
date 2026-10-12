<?php

namespace App\DataFixtures;

use App\Entity\Categorie;
use App\Entity\LinkTypeCategorie;
use App\Entity\StatutProjet;
use App\Entity\TypeFrais;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Donnees de reference: types de frais, categories, liens autorises et statuts projet.
 */
class ReferenceFixtures extends Fixture
{
    /**
     * @var array<int, array{0: string, 1: string, 2: bool}>
     */
    private const TYPES = [
        [TypeFrais::DIRECT, 'Frais directs', true],
        [TypeFrais::INDIRECT, 'Frais indirects', true],
        [TypeFrais::FRAIS_CHANTIER, 'Frais de chantier', false],
        [TypeFrais::FRAIS_GENERAL, 'Frais generaux', false],
    ];

    /**
     * @var array<int, array{0: string, 1: string}>
     */
    private const CATEGORIES = [
        [Categorie::MATERIELS, 'Materiels'],
        [Categorie::MATERIAUX, 'Materiaux'],
        [Categorie::MAIN_OEUVRE, 'Main d\'oeuvre'],
        [Categorie::SOUS_TRAITANT, 'Sous-traitants'],
    ];

    /**
     * @var array<int, array{0: string, 1: string}>
     */
    private const STATUTS = [
        ['EN_PREPARATION', 'En preparation'],
        ['EN_COURS', 'En cours'],
        ['SUSPENDU', 'Suspendu'],
        ['TERMINE', 'Termine'],
        ['RECEPTIONNE', 'Receptionne'],
        ['CLOTURE', 'Cloture'],
        ['ANNULE', 'Annule'],
    ];

    public function load(ObjectManager $manager): void
    {
        $categories = [];
        foreach (self::CATEGORIES as $ordre => [$code, $libelle]) {
            $categorie = new Categorie($code, $libelle, $ordre + 1);
            $manager->persist($categorie);
            $categories[$code] = $categorie;
            $this->addReference('categorie_'.$code, $categorie);
        }

        foreach (self::TYPES as $ordre => [$code, $libelle, $requiertCategorie]) {
            $type = new TypeFrais($code, $libelle, $requiertCategorie, $ordre + 1);
            $manager->persist($type);
            $this->addReference('type_'.$code, $type);

            if (!$requiertCategorie) {
                continue;
            }

            foreach ($categories as $categorie) {
                $manager->persist(new LinkTypeCategorie($type, $categorie));
            }
        }

        foreach (self::STATUTS as $ordre => [$code, $libelle]) {
            $statut = new StatutProjet($code, $libelle, $ordre + 1);
            $manager->persist($statut);
            $this->addReference('statut_'.$code, $statut);
        }

        $manager->flush();
    }
}
