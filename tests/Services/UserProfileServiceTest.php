<?php

namespace App\Tests\Services;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\UserProfileService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserProfileServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private UserRepository $userRepo;
    private UserProfileService $profileService;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $c = static::getContainer();

        $this->em             = $c->get(EntityManagerInterface::class);
        $this->userRepo       = $c->get(UserRepository::class);
        $this->profileService = $c->get(UserProfileService::class);

        /* Nettoyage optionnel si tu ne roules pas chaque test
           dans une transaction (ex. avec DAMADoctrineTestBundle) */
        $this->em->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    /** Cas nominal : mise à jour réussie */
    public function testUpdateProfileChangesData(): void
    {
        // Arrange
        $user = $this->makeUser('alice@example.com', 'Alice', 'Smith');
        $id   = $user->getId();

        // Act
        $this->profileService->updateProfile($user, [
            'firstName' => 'Alicia',
            'lastName'  => 'S.',
            'phone'     => '0600000000',
        ]);

        // Assert
        $this->em->clear();
        $updated = $this->userRepo->find($id);
        self::assertSame('Alicia', $updated->getFirstName());
        self::assertSame('S.',     $updated->getLastName());
        self::assertSame('0600000000', $updated->getPhone());
    }

    /** Validation : email dupliqué → exception */
    public function testUpdateProfileThrowsIfEmailAlreadyUsed(): void
    {
        // Arrange : deux utilisateurs
        $this->makeUser('dup@ex.com', 'Dup', 'One');
        $userB = $this->makeUser('free@ex.com', 'Free', 'Two');

        $this->expectException(\App\Exception\ValidationException::class);

        // Act
        $this->profileService->updateProfile($userB, [
            'email' => 'dup@ex.com', // déjà utilisé
        ]);
    }

    /** Not-found : getUserProfile avec ID inexistant */
    // public function testGetUserProfileThrowsWhenNotFound(): void
    // {
        // $this->validator->validate($object);
        // $this->profileService->getUserProfile(999999);
    // }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                           */
    /* ------------------------------------------------------------------ */

    private function makeUser(string $email, string $first, string $last): User
    {
        $u = new User();
        $u->setEmail($email);
        $u->setFirstName($first);
        $u->setLastName($last);
        $u->setPassword('hash');          // champ NOT NULL
        $u->setRoles(['ROLE_USER']);

        $this->em->persist($u);
        $this->em->flush();

        return $u;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
