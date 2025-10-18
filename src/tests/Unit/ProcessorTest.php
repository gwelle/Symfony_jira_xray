<?php

namespace App\tests\Unit\Mock;

use PHPUnit\Framework\TestCase;
use App\State\UserCreationProcessor;
use App\Entity\User;
use App\Entity\ActivationToken;
use App\Service\ActivationService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;
use ApiPlatform\Metadata\Operation; 
use ApiPlatform\State\ProcessorInterface;
use \Faker\Generator;
use Faker\Factory; 
use PHPUnit\Framework\MockObject\MockObject;

class ProcessorTest extends TestCase
{
    private ?Generator $faker;   
    private ?User $user;
    private ?ActivationToken $activationToken;
    private MockObject|UserPasswordHasherInterface $userPasswordHasher;
    private MockObject|ActivationService $activationService;
    private MockObject|LoggerInterface $logger;
    private MockObject|ProcessorInterface $processor;
    private MockObject|Operation $operation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();

        $this->user = new User();
        $this->user->setEmail($this->faker->email());
        $this->user->setPlainPassword('plain_password');
        $this->user->setPassword('hashed_password');

        $this->activationToken = new ActivationToken();
        $this->activationToken->setPlainToken("fake_new_token");

        $this->initStubAndMocks();
    }

    /**
     * Cleans up after each test.
     * @return void
     */
    public function tearDown(): void
    {
        $this->faker = null;
        $this->user = null;
        $this->activationToken = null;
        parent::tearDown();
    }  

    /**
     * Initializes stubs and mocks for the test.
     * @return void
     */
    public function initStubAndMocks(): void
    {
        $this->userPasswordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $this->activationService = $this->createStub(ActivationService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->processor = $this->createMock(ProcessorInterface::class);
        $this->operation = $this->createStub(Operation::class);
    }

    /**
     * Helper method to process user creation.
     * @param mixed $data The data to process.
     * @return mixed The result of the user creation processing.
     */
    public function userCreationProcessorResult(mixed $data): mixed
    {
        $userCreationProcessor = new UserCreationProcessor(
            $this->processor,
            $this->userPasswordHasher,
            $this->activationService,
            $this->logger
        );
        return $userCreationProcessor->process($data, $this->operation);
    }

    /**
     * Summary of test_process_user_creation
     * @return void
     */
    public function test_process_user_creation(): void
    {
        $this->userPasswordHasher->method('hashPassword')->willReturn('hashed_password');
        $this->activationService->method('generateToken')->willReturn('fake_new_token');
        
        $this->logger->expects($this->never())->method('error');

        $this->processor->expects($this->once())->method('process')
            ->willReturnCallback(function ($data, $operation, $uriVariables, $context) {
                // Simulate the behavior of the next processor in the chain
                return $data;
        });

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('User created successfully'));

        $this->user->addActivationToken($this->activationToken);

        $result = $this->userCreationProcessorResult($this->user);

        $this->assertInstanceOf(User::class,$result);

        $this->assertSame( $this->user->getEmail(), $result->getEmail());
        $this->assertSame('hashed_password', $result->getPassword());
        $this->assertSame('fake_new_token', $this->user
            ->getActivationTokens()
            ->first()
            ->getPlainToken());
        $this->assertTrue($result->getActivationTokens()->count() >= 0);
    }

    /**
     * Summary of test_process_with_non_user_data_returns_null
     * @return void
     */
    public function test_process_with_non_user_data_returns_null():void{

        $this->processor->expects($this->never())->method('process');

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Processor reçu une donnée non conforme'));

        $result = $this->userCreationProcessorResult(new \stdClass());

        $this->assertNull($result);
    }

    /**
     * Summary of test_process_user_without_plain_password_returns_null
     * @return void
     */
    public function test_process_user_without_plain_password_returns_null(): void
    {
        $this->user->setPlainPassword('');

        $this->processor->expects($this->never())->method('process');

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Mot de passe manquant, utilisateur non créé.'));

        $result = $this->userCreationProcessorResult($this->user);
        
        $this->assertNull($result);
    }

    /**
     * Summary of test_process_activation_service_throws_exception
     * @return void
     */
    public function test_process_generation_token_throws_exception(): void
    {
        $this->activationService
            ->method('generateToken')
            ->willThrowException(new \Exception('Error generating token'));

        $this->processor->expects($this->never())->method('process');

       $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Erreur lors du traitement de création utilisateur'));

        $this->activationToken->setPlainToken("");

        $result = $this->userCreationProcessorResult($this->user);

        $this->assertNull($result);
        
    }
}
