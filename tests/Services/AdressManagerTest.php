<?php

namespace App\Tests\Services;

use App\Entity\User;
use App\Entity\Adresse;
use App\Services\AdressManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class AdressManagerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private AdressManager $addressManager;

    protected function setUp(): void
    {
        echo "SETUP DEMARRE\n";

        try {
            self::bootKernel();
            echo "KERNEL BOOTE\n";
        } catch (\Throwable $e) {
            echo "ERREUR AU BOOT : " . $e->getMessage() . "\n";
            throw $e;
        }

        try {
            $container = static::getContainer();
            echo "CONTAINER RECUPERE\n";
        } catch (\Throwable $e) {
            echo "ERREUR RECUP CONTAINER : " . $e->getMessage() . "\n";
            throw $e;
        }

        try {
            $this->entityManager = $container->get(EntityManagerInterface::class);
            echo "ENTITY MANAGER OK\n";
        } catch (\Throwable $e) {
            echo "ERREUR ENTITY MANAGER : " . $e->getMessage() . "\n";
            throw $e;
        }

        try {
            $this->addressManager = $container->get(AdressManager::class);
            echo "ADDRESS MANAGER OK\n";
        } catch (\Throwable $e) {
            echo "ERREUR ADDRESS MANAGER : " . $e->getMessage() . "\n";
            throw $e;
        }

        try {
            $this->entityManager->createQuery('DELETE FROM App\Entity\Adresse')->execute();
            $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
            echo "NETTOYAGE TERMINE\n";
        } catch (\Throwable $e) {
            echo "ERREUR NETTOYAGE : " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function testAddAdresse(): void
    {
        echo "TEST ADD ADRESSE DEMARRE\n";

        $user = new User();
        $user->setEmail('testadd@exemple.com');
        $user->setPassword('dummyhashed');
        $user->setRoles(['ROLE_USER']);
        $user->setFirstName('Douma');      //  ← champs NOT NULL
        $user->setLastName('Faye');       //  ← champs NOT NULL
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $data = [
            'label' => 'Maison',
            'line1' => '123 Rue de Test',
            'city' => 'Testville',
            'postalCode' => '12345',
            'country' => 'Testland',
            'type' => 'shipping',
            'isDefault' => true,
        ];

        $adresse = $this->addressManager->addAdresse($user, $data);

        $this->assertInstanceOf(Adresse::class, $adresse);
        $this->assertEquals('Maison', $adresse->getLabel());
        $this->assertTrue($adresse->isDefault());
    }

    public function testUpdateAdresse(): void
    {
        echo "TEST UPDATE ADRESSE DEMARRE\n";

        $user = new User();
        $user->setEmail('testupdate@exemple.com');
        $user->setPassword('dummyhashed');
        $user->setRoles(['ROLE_USER']);
        $user->setFirstName('Douma');      //  ← champs NOT NULL
        $user->setLastName('Faye');       //  ← champs NOT NULL

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $data = [
            'label' => 'Bureau',
            'line1' => '456 Rue Update',
            'city' => 'MajCity',
            'postalCode' => '67890',
            'country' => 'Majland',
            'type' => 'billing',
            'isDefault' => false,
        ];

        $adresse = $this->addressManager->addAdresse($user, $data);

        $updateData = [
            'label' => 'Bureau Principal',
            'isDefault' => true,
        ];

        $updatedAdresse = $this->addressManager->updateAdresse($user, $adresse, $updateData);

        $this->assertEquals('Bureau Principal', $updatedAdresse->getLabel());
        $this->assertTrue($updatedAdresse->isDefault());
    }

    public function testDeleteAdresse(): void
    {
        echo "TEST DELETE ADRESSE DEMARRE\n";

        $user = new User();
        $user->setEmail('testdelete@exemple.com');
        $user->setPassword('dummyhashed');
        $user->setRoles(['ROLE_USER']);
        $user->setFirstName('Douma');      //  ← champs NOT NULL
        $user->setLastName('Faye');       //  ← champs NOT NULL

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $data = [
            'label' => 'Maison à Supprimer',
            'line1' => '789 Rue Delete',
            'city' => 'DelCity',
            'postalCode' => '54321',
            'country' => 'Delland',
            'type' => 'shipping',
            'isDefault' => false,
        ];

        $adresse = $this->addressManager->addAdresse($user, $data);
        $id = $adresse->getId();

        $this->addressManager->deleteAdresse($user, $adresse);

        $deletedAdresse = $this->entityManager->getRepository(Adresse::class)->find($id);
        $this->assertNull($deletedAdresse);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
