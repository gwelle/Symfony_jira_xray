<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Entity\User;
use App\Entity\ActivationToken;
use Faker\Factory;
use \Faker\Generator;

class ActivationTokenTest extends TestCase
{
    private static ?Generator $faker;
    private static ?User $user = null;
    private static ?ActivationToken $activationToken = null;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        self::$faker = Factory::create();
        self::$user = new User();
        self::$activationToken = new ActivationToken();
        $this->createUser();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::$faker = null;
        self::$user = null;
        self::$activationToken = null;
    }

    public function createUser(): void
    {
        $password = $_ENV['PASSWORD'];
        self::$user->setEmail(self::$faker->safeEmail());
        self::$user->setFirstName(self::$faker->firstName());
        self::$user->setLastName(self::$faker->lastName());
        self::$user->setPlainPassword($password);
        self::$user->setConfirmationPassword($password);
        self::$user->setCreatedAt(new \DateTimeImmutable());
    }

    /**
     * @return void
     */
    public function test_initial_state(): void
    {
        $this->assertInstanceOf(User::class, self::$user);
        $this->assertInstanceOf(ActivationToken::class, self::$activationToken);
        $this->assertNull(self::$activationToken->getId());
        $this->assertNull(self::$activationToken->getCreatedAt());
    }

    /**
     * @return void
     */
    public function test_creation_token(): void
    {
        $plainToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', data: $plainToken);

        self::$activationToken->setPlainToken($plainToken);
        self::$activationToken->setHashedToken($hashedToken);

        $this->assertEquals(expected: $plainToken, actual: self::$activationToken->getPlainToken());
        $this->assertEquals(expected: $hashedToken, actual: self::$activationToken->getHashedToken());
        $this->assertNotSame(expected: self::$activationToken->getPlainToken(), actual: self::$activationToken->getHashedToken());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', self::$activationToken->getHashedToken());
    }

    public function test_set_createdAt(): void
    {
        $now = new \DateTimeImmutable();
        self::$activationToken->setCreatedAt($now);
        $this->assertInstanceOf(\DateTimeImmutable::class, self::$activationToken->getCreatedAt());
        $this->assertSame($now, self::$activationToken->getCreatedAt());
    }

    public function test_token_validity_and_expiration(): void
    {
        // Test de la validité initiale
        $this->assertFalse(self::$activationToken->isExpired());
        $this->assertTrue(self::$activationToken->isValid());

        // Simuler la création du token il y a 25 heures
        $past = new \DateTimeImmutable('-25 hours');
        self::$activationToken->setCreatedAt($past);

        // Le token devrait maintenant être expiré
        $this->assertTrue(self::$activationToken->isExpired());
        $this->assertFalse(self::$activationToken->isValid());
    }

    //Relation bidirectionnelle avec User
    public function test_bidirectional_relationship_with_user(): void
    {
        self::$activationToken->setAccount(self::$user);
        $this->assertInstanceOf(User::class, self::$activationToken->getAccount());
        $this->assertSame(self::$user, self::$activationToken->getAccount());

        $this->assertTrue(self::$user->getActivationTokens()->contains(self::$activationToken));

        self::$user->removeActivationToken(self::$activationToken);
        $this->assertFalse(self::$user->getActivationTokens()->contains(self::$activationToken));
    }
}
