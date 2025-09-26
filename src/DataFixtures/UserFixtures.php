<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const ADMIN_1_REF = 'admin-1';
    public const ADMIN_2_REF = 'admin-2';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Admin 1
        $admin1 = new User();
        $admin1->setEmail('samba@mail.com');
        $admin1->setRoles(['ROLE_ADMIN']);
        $admin1->setFirstname('Admin');
        $admin1->setLastname('One');
        $admin1->setPassword(
            $this->passwordHasher->hashPassword($admin1, 'Password25') // ⚠️ change en prod
        );
        $manager->persist($admin1);
        $this->addReference(self::ADMIN_1_REF, $admin1);

        // Admin 2
        $admin2 = new User();
        $admin2->setEmail('dev@example.com');
        $admin2->setRoles(['ROLE_ADMIN']);
        $admin2->setFirstname('Admin');
        $admin2->setLastname('Two');
        $admin2->setPassword(
            $this->passwordHasher->hashPassword($admin2, 'Password25')
        );
        $manager->persist($admin2);
        $this->addReference(self::ADMIN_2_REF, $admin2);

        $manager->flush();
    }
}
