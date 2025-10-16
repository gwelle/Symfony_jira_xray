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
     * Summary of setUp
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

    /**
     * Summary of tearDownAfterClass
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::$faker = null;
        self::$user = null;
        self::$activationToken = null;
    }

    /**
     * Summary of createUser
     * @return void
     */
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
     * Summary of test_initial_state
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
     * Summary of test_creation_token
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

    /**
     * Summary of test_set_createdAt
     * @return void
     */
    public function test_set_createdAt(): void
    {
        $now = new \DateTimeImmutable();
        self::$activationToken->setCreatedAt($now);
        $this->assertInstanceOf(\DateTimeImmutable::class, self::$activationToken->getCreatedAt());
        $this->assertSame($now, self::$activationToken->getCreatedAt());
    }

    /**
     * Summary of test_token_expiration_in_different_cases
     * @return void
     */
    public function test_token_expiration_in_different_cases(): void
    {
        // Test de la validité initiale
        $this->assertFalse(self::$activationToken->isExpired());
        
        // Simuler la création du token il y a 25 heures
        $past = new \DateTimeImmutable('-25 hours');
        self::$activationToken->setExpiredAt($past);

        // Le token devrait maintenant être expiré
        $this->assertTrue(self::$activationToken->isExpired());

        $past = new \DateTimeImmutable('+1 hour');
        self::$activationToken->setExpiredAt($past);

        // Le token devrait toujours être valide
        $this->assertFalse(self::$activationToken->isExpired());
        $this->assertTrue(self::$activationToken->isValid());

    }

    /**
     * Summary of test_token_validity_consistency
     * @return void
     */
    public function test_token_validity_consistency(): void
    {
        $expired = new \DateTimeImmutable('-26 hours');
        self::$activationToken->setExpiredAt($expired);

        $this->assertTrue(self::$activationToken->isExpired());
        $this->assertFalse(self::$activationToken->isValid());
    }

    /**
     * Summary of test_token_regeneration_resets_values
     * @return void
     */
    public function test_token_regeneration_resets_values(): void
    {
        $oldPlain = self::$activationToken->getPlainToken();
        $oldHashed = self::$activationToken->getHashedToken();
        $oldCreated = self::$activationToken->getCreatedAt();

        // Simuler une expiration
        self::$activationToken->setExpiredAt(new \DateTimeImmutable('-2 days'));
        $this->assertTrue(self::$activationToken->isExpired());

        // Régénérer le token
        self::$activationToken->regenerateToken();

        // Assertions : les valeurs doivent avoir changé
        $this->assertNotEquals($oldPlain, self::$activationToken->getPlainToken());
        $this->assertNotEquals($oldHashed, self::$activationToken->getHashedToken());
        $this->assertNotEquals($oldCreated, self::$activationToken->getCreatedAt());

        // Et le nouveau token ne doit pas être expiré
        $this->assertFalse(self::$activationToken->isExpired());
        $this->assertTrue(self::$activationToken->isValid());
    }

    /**
     * Summary of test_token_regeneration_preserves_previous_hash
     * @return void
     */
    public function test_token_regeneration_preserves_previous_hash(): void
    {
        $oldHashed = self::$activationToken->getHashedToken();
        self::$activationToken->regenerateToken();

        $this->assertNotEquals($oldHashed, self::$activationToken->getHashedToken());
        $this->assertEquals($oldHashed, self::$activationToken->getPreviousHashedToken());
        $this->assertTrue(self::$activationToken->isValid());
    }

    /**
     * Summary of test_bidirectional_relationship_with_user
     * @return void
     */
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
