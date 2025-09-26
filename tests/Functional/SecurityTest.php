<?php
// tests/Functional/SecurityTest.php
namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityTest extends WebTestCase
{
    public function testGetProfileAsAuthenticatedUser(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        // ─── Purge de la table User pour éviter les doublons ───
        $em->createQuery('DELETE FROM App\Entity\User')->execute();
        $em->flush();

        // ─── Création du user frais ───
        $user = (new User())
            ->setEmail('bob@example.com')
            ->setFirstName('Bob')
            ->setLastName('Doe')
            ->setPassword(password_hash('Password1!', PASSWORD_BCRYPT))
            ->setRoles(['ROLE_USER']);

        $em->persist($user);
        $em->flush();

        // ─── Simule la connexion sans passer par /login ───
        $client->loginUser($user);

        // ─── Appel de /mon-compte en JSON ───
        $client->request('GET', '/mon-compte', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $data    = json_decode($content, true);
        $this->assertSame('bob@example.com', $data['email']);
    }
}
