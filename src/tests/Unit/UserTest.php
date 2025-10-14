<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Faker\Factory;
use \Faker\Generator;
use Symfony\Component\Validator\Validation;
use App\Entity\User;
use \PHPUnit\Framework\Attributes\DataProvider;

class UserTest extends TestCase
{
    private ?Generator $faker;
    private ?User $user;
    private ?object $validator;
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

        $violations = $this->validator->validateProperty($this->user, $property);
        if ($valid) {
            $this->assertCount(0, $violations, "Expected no violations for $property with value $value");
        } else {
            $this->assertGreaterThan(0, $violations->count(), "Expected at least one violation for $property with value $value");
        }
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

    public function test_passwords_match_callback(): void
    {

        // ✅ Mot de passe "valide"
        $this->user->setPlainPassword($this->password);
        $this->user->setConfirmationPassword($this->password);

        $violations = $this->validator->validate($this->user, null, groups: ['passwords_check']);
        $this->assertCount(0, $violations, 'Expected no violations when passwords match');

        // ❌ Mot de passe "invalide"
        $this->user->setConfirmationPassword($this->fakePassword);

        $violations = $this->validator->validate($this->user, null, groups: ['passwords_check']);
        $this->assertGreaterThan(0, $violations->count(), 'Expected at least one violation when passwords do not match');
    }
}