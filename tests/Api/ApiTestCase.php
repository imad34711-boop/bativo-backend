<?php

namespace App\Tests\Api;

use App\DataFixtures\ReferenceFixtures;
use App\DataFixtures\SecurityFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Socle des tests de bout en bout: base recreee, fixtures chargees, client authentifie.
 */
abstract class ApiTestCase extends WebTestCase
{
    protected const ADMIN_EMAIL = 'admin@bativo.local';
    protected const ADMIN_PASSWORD = 'test-admin-password';

    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->reinitialiserSchema();
        $this->chargerFixtures();
        $this->authentifier();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function reinitialiserSchema(): void
    {
        $metadonnees = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $outil = new SchemaTool($this->entityManager);
        $outil->dropSchema($metadonnees);
        $outil->createSchema($metadonnees);
    }

    private function chargerFixtures(): void
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $loader = new Loader();
        $loader->addFixture(new ReferenceFixtures());
        $loader->addFixture(new SecurityFixtures($hasher));

        (new ORMExecutor($this->entityManager))->execute($loader->getFixtures(), true);
    }

    private function authentifier(): void
    {
        $this->authentifierComme(self::ADMIN_EMAIL, self::ADMIN_PASSWORD);
    }

    protected function authentifierComme(string $email, string $motDePasse): void
    {
        $reponse = $this->requete('POST', '/api/login', [
            'email' => $email,
            'password' => $motDePasse,
        ], Response::HTTP_OK);

        self::assertArrayHasKey('token', $reponse, 'Le login doit retourner un jeton JWT.');

        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$reponse['token']);
    }

    /**
     * Envoie une requete JSON et verifie le code de statut attendu.
     *
     * @param array<string, mixed>|null $payload
     *
     * @return array<string, mixed>
     */
    protected function requete(string $methode, string $url, ?array $payload = null, int $statutAttendu = Response::HTTP_OK): array
    {
        $this->client->jsonRequest($methode, $url, $payload ?? []);

        $reponse = $this->client->getResponse();
        $contenu = (string) $reponse->getContent();

        self::assertSame(
            $statutAttendu,
            $reponse->getStatusCode(),
            sprintf('%s %s a retourne %d. Corps: %s', $methode, $url, $reponse->getStatusCode(), $contenu)
        );

        if (Response::HTTP_NO_CONTENT === $reponse->getStatusCode() || '' === $contenu) {
            return [];
        }

        return (array) json_decode($contenu, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Construit le marche de reference utilise par les tests de bout en bout.
     *
     * Marche de 10 000 000 MAD HT (12 000 000 TTC), plafond de retenue de garantie a 7% soit 840 000.
     *
     * @return array{projet: array<string, mixed>, depenses: list<array<string, mixed>>, revenues: list<array<string, mixed>>}
     */
    protected function creerScenarioMarche(): array
    {
        $projet = $this->requete('POST', '/api/projets', [
            'numeroMarche' => 'AO-2026-014',
            'objet' => 'Construction d un groupe scolaire a Casablanca',
            'ville' => 'Casablanca',
            'montantMarcheHt' => 10000000.0,
            'tauxTva' => 20.0,
            'tauxRetenueGarantie' => 10.0,
            'tauxPlafondRetenueGarantie' => 7.0,
            'maitreOuvrage' => 'Ministere de l Education Nationale',
            'dureeMois' => 18,
            'dateDebut' => '2026-01-05',
            'dateFin' => '2027-07-05',
            'statutCode' => 'EN_COURS',
        ], Response::HTTP_CREATED);

        $projetId = $projet['id'];

        $depenses = [];
        foreach ([
            ['typeCode' => 'DIRECT', 'categorieCode' => 'MATERIAUX', 'sousCategorie' => 'Gros oeuvre', 'elementDepense' => 'Beton et acier', 'montantHt' => 2500000.0, 'fournisseur' => 'Cimenterie Atlas'],
            ['typeCode' => 'DIRECT', 'categorieCode' => 'MAIN_OEUVRE', 'sousCategorie' => 'Equipes chantier', 'elementDepense' => 'Salaires ouvriers', 'montantHt' => 1500000.0, 'fournisseur' => null],
            ['typeCode' => 'DIRECT', 'categorieCode' => 'SOUS_TRAITANT', 'sousCategorie' => 'Lots techniques', 'elementDepense' => 'Electricite et plomberie', 'montantHt' => 2000000.0, 'fournisseur' => 'Techno Bat SARL'],
            ['typeCode' => 'FRAIS_CHANTIER', 'categorieCode' => null, 'sousCategorie' => 'Installation', 'elementDepense' => 'Base vie et cloture', 'montantHt' => 500000.0, 'fournisseur' => 'Modul Loc'],
            ['typeCode' => 'FRAIS_GENERAL', 'categorieCode' => null, 'sousCategorie' => 'Siege', 'elementDepense' => 'Quote-part frais de siege', 'montantHt' => 300000.0, 'fournisseur' => null],
        ] as $index => $ligne) {
            $depenses[] = $this->requete(
                'POST',
                sprintf('/api/projets/%d/depenses', $projetId),
                $ligne + ['date' => sprintf('2026-%02d-15', $index + 2), 'tauxTva' => 20.0],
                Response::HTTP_CREATED
            );
        }

        $revenues = [];
        foreach ([
            ['numeroDecompte' => 1, 'montantHt' => 3000000.0, 'date' => '2026-04-30'],
            ['numeroDecompte' => 2, 'montantHt' => 3000000.0, 'date' => '2026-08-31'],
            ['numeroDecompte' => 3, 'montantHt' => 4000000.0, 'date' => '2026-12-31'],
        ] as $ligne) {
            $revenues[] = $this->requete(
                'POST',
                sprintf('/api/projets/%d/revenues', $projetId),
                $ligne + ['tauxTva' => 20.0, 'tauxRetenueGarantie' => 10.0],
                Response::HTTP_CREATED
            );
        }

        return ['projet' => $projet, 'depenses' => $depenses, 'revenues' => $revenues];
    }

    /**
     * @param array<string, mixed> $lignes
     */
    protected function afficherTableau(string $titre, array $lignes): void
    {
        $sortie = \PHP_EOL.'  '.$titre.\PHP_EOL;
        $largeur = max(array_map('strlen', array_keys($lignes)));

        foreach ($lignes as $libelle => $valeur) {
            $sortie .= sprintf("    %-{$largeur}s : %s%s", $libelle, $valeur, \PHP_EOL);
        }

        fwrite(\STDOUT, $sortie);
    }

    protected static function formaterMontant(float $montant): string
    {
        return number_format($montant, 2, ',', ' ').' MAD';
    }
}
