<?php 
namespace App\Tests; 
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase; 
use Doctrine\ORM\EntityManagerInterface; 
use Doctrine\Common\DataFixtures\Purger\ORMPurger; 
use App\DataFixtures\TestFixtures;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; 
use Symfony\Component\HttpFoundation\Response; 

abstract class AbstractApiTestCase extends WebTestCase { 
    protected \Symfony\Bundle\FrameworkBundle\KernelBrowser $client; 
    protected EntityManagerInterface $em; 
    
    protected function getCredentials(): array { 
        return ['email' => 'test@example.com', 'password' => 'test']; 
    } 
    
    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->em = $container->get(\Doctrine\ORM\EntityManagerInterface::class);

        // --- PURGE propre pour MySQL/MariaDB
        $conn = $this->em->getConnection();
        try {
            // désactive les FK (MySQL/MariaDB)
            try { $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

            $purger = new ORMPurger($this->em);
            $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE); // évite l'ordre de delete
            $purger->purge();
        } finally {
            try { $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}
        }

        // --- charge tes fixtures
        $hasher = $container->get(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class);
        (new \App\DataFixtures\TestFixtures($hasher))->load($this->em);
    }
    
    protected function authenticate(): void
    {
        $email = $this->getCredentials()['username'] ?? 'test@example.com';

        $user = $this->em->getRepository(\App\Entity\User::class)
            ->findOneBy(['email' => $email]);

        $this->assertNotNull($user, 'User de test introuvable: '.$email.'. Assure-toi que la fixture crée bien cet utilisateur.');

        $this->client->loginUser($user);
}
 
    
    protected function jsonRequest(string $method, string $uri, array $payload = [], array $server = []): void { 
        $this->client->request( $method, $uri, 
            [], 
            [], 
            array_merge(['CONTENT_TYPE' => 'application/json'], $server), 
            json_encode($payload, JSON_THROW_ON_ERROR) 
        );
    } 
    
    protected function decodeJson(): array {
        $content = $this->client->getResponse()->getContent(); 
        return is_string($content) ? (json_decode($content, true) ?? []) : []; 
    } 
}