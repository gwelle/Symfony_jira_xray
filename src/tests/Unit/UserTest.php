<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Faker\Factory;
use \Faker\Generator;
use Symfony\Component\Validator\Validation;
use App\Entity\User;
use \PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Doctrine\Common\Collections\Collection;

class UserTest extends TestCase
{
    private ?Generator $faker;
    private ?User $user;
    private ?object $validator;
    private ?object $violations;
    private ?string $password;
    private ?string $fakePassword;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
        $this->user = new User();
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        $this->password = $_ENV['PASSWORD'];
        $this->fakePassword = $_ENV['PASSWORD_INVALID'];
    }

    public function tearDown(): void
    {
        $this->faker = null;
        $this->user = null;
        $this->validator = null;
        $this->password = null;
        $this->fakePassword = null;
    }

    /**
     * Summary of providePropertyCases
     * @return array<bool|string>[]
     */
    public static function providePropertyCases(): array
    {
        return [
            ['email', true],
            ['email', false],
            ['firstName', true],
            ['firstName', false],
            ['lastName', true],
            ['lastName', false],
            ['plainPassword', true],
            ['plainPassword', false],
            ['confirmationPassword', true],
            ['confirmationPassword', false],
        ];
    }

    /**
     * Summary of validateUserPasswords
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    private function validateUserPasswords(): ConstraintViolationListInterface
    {
        return $this->validator->validate($this->user, null, groups: ['passwords_check']);
    }

    /**
     * Summary of assertPropertyViolations
     * @param mixed $violations
     * @param bool $isValid
     * @param string $propertyName
     * @return void
     */
    private function assertPropertyViolations($violations, bool $isValid, string $propertyName): void
    {
        if ($isValid) {
            $this->assertCount(0, $violations, "Expected no violations for {$propertyName}");
        } 
        else {
            $this->assertGreaterThan(0, count($violations), "Expected at least one violation for {$propertyName}");
        }
    } 

    /**
     * Summary of assertPasswordsScenario
     * @param string $plain
     * @param string $confirmation
     * @param bool $isValid
     * @return void
     */
    private function assertPasswordsScenario(string $plain, string $confirmation, bool $isValid): void
    {
        $this->user->setPlainPassword($plain);
        $this->user->setConfirmationPassword($confirmation);
        $violations = $this->validateUserPasswords();
        $this->assertPropertyViolations($violations, $isValid, 'plainPassword / confirmationPassword');
    }

    /**
     * Summary of test_validator_property
     * @param string $property
     * @param bool $valid
     * @return void
     */
    #[DataProvider('providePropertyCases')]
    public function test_validator_property(string $property, bool $valid): void
    {
        $confirmationPassword = $this->password;
        $fakeConfirmationPassword = $this->fakePassword;

        $value = match ($property) {
            'email' => $valid ? $this->faker->safeEmail() : $this->faker->email() . $this->faker->randomNumber(),
            'firstName' => $valid ? $this->faker->firstName() : $this->faker->firstName() . $this->faker->randomNumber(),
            'lastName' => $valid ? $this->faker->lastName() : $this->faker->lastName() . $this->faker->randomNumber(),
            'plainPassword' => $valid ? $this->password : $this->fakePassword,
            'confirmationPassword' => $valid ? $confirmationPassword : $fakeConfirmationPassword,
            default => throw new \InvalidArgumentException("Unknown property $property")
        };

        $setter = 'set' . ucfirst($property);
        $this->user->$setter($value);

        $this->assertContains('ROLE_USER', $this->user->getRoles(), 'User should have ROLE_USER by default');
        $this->assertFalse($this->user->isActivated(), 'User should not be activated by default');

        $this->violations = $this->validator->validateProperty($this->user, $property);
        $this->assertPropertyViolations($this->violations, $valid, $property);
    }

    /**
     * Summary of test_passwords_match_callback
     * @return void
     */
    public function test_passwords_match_callback(): void
    {
        // ✅ Cas valide : mots de passe identiques
        $this->assertPasswordsScenario($this->password, $this->password, true);

        // ❌ Cas invalide : mots de passe différents
        $this->assertPasswordsScenario($this->password, $this->fakePassword, false);
    }

    /**
     * Summary of test_lifecycle_callback_on_user
     * @return void
     */
    public function test_lifecycle_callback_on_user(): void
    {
        // ✅ La création automatique de createdAt via onPrePersist().
        $this->assertNull($this->user->getCreatedAt());
        $this->user->onPrePersist();
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->user->getCreatedAt());
        $this->assertNotNull($this->user->getCreatedAt());
    }

    /**
     * Summary of test_user_property_activation_token
     * @return void
     */
    public function test_user_property_activation_token(): void
    {
        $this->assertInstanceOf(Collection::class, $this->user->getActivationTokens());
        $this->assertCount(0, $this->user->getActivationTokens());
    }
}
