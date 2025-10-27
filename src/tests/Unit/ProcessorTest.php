<?php

namespace App\tests\Unit\Mock;

use PHPUnit\Framework\TestCase;
use App\State\UserCreationProcessor;
use App\State\UserEmailProcessor;
use App\Entity\User;
use App\Entity\ActivationToken;
use App\Service\UserService;
use App\Service\ActivationService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;
use ApiPlatform\Metadata\Operation; 
use ApiPlatform\State\ProcessorInterface;
use \Faker\Generator;
use Faker\Factory; 
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use \Symfony\Component\Messenger\Envelope;
use App\Message\SendConfirmationEmail;


class ProcessorTest extends TestCase
{
    private ?Generator $faker;   
    private ?User $user;
    private ?ActivationToken $activationToken;
    private MockObject|UserPasswordHasherInterface $userPasswordHasher;
    private MockObject|ActivationService $activationService;
    private MockObject|LoggerInterface $logger;
    
    /** @var MockObject|ProcessorInterface<object|null, object|null> */
    private MockObject|ProcessorInterface $processor;
    private MockObject|Operation $operation;
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|UserService $userService;
    private MockObject|MessageBusInterface $bus;

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
        unset($this->userPasswordHasher);
        unset($this->userService);
        unset($this->activationService);
        unset($this->logger);
        unset($this->processor);
        unset($this->operation);
        unset($this->entityManager);
        unset($this->bus);
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
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userService = $this->createMock(UserService::class);
        $this->bus = $this->createMock(MessageBusInterface::class);
    }

    /**
     * Helper method to process user creation.
     * @param mixed $data The data to process.
     * @param mixed $instance The instance of the processor.
     * @return mixed The result of the user creation processing.
     */
    public function userProcessorResult(mixed $data, mixed $instance): mixed
    {
        if (is_a($instance, UserCreationProcessor::class, true)) {
            $userCreationProcessor = new UserCreationProcessor(
                $this->processor,
                $this->userPasswordHasher,
                $this->userService,
                $this->activationService,
                $this->logger
            );
            return $userCreationProcessor->process($data, $this->operation);
        }
        else if (is_a($instance, UserEmailProcessor::class, true)) {
            $userEmailProcessor = new UserEmailProcessor(
                $this->processor,
                $this->bus,
                $this->entityManager,
                $this->logger
            );
            return $userEmailProcessor->process($data, $this->operation);
        }

        return null;
    }

    /**
     * Summary of assertLogger
     * @param string $level
     * @param string $message
     * @return void
     */
    public function expectsLogger(string $level, string $message): void
    {
        $this->logger
            ->expects($this->once())
            ->method($level)
            ->with($this->stringContains($message));
    }

    /**
     * Summary of expectsProcessorNeverCalled
     * @return void
     */
    public function expectsProcessorNeverCalled(): void
    {
        $this->processor->expects($this->never())->method('process');
    }   

    /**
     * Summary of expectsProcessOnce
     * @return void
     */
    public function expectsProcessOnce():void{
        $this->processor
            ->expects($this->once())
            ->method('process')
            ->willReturn($this->user);
    }

    /**
     * Summary of addTokenToUser
     * @return void
     */
    public function addTokenToUser():void{
        $this->user->addActivationToken($this->activationToken);
    }

    /**
     * Summary of clearPlainToken
     * @return void
     */
    public function clearPlainToken(): void
    {
        $this->activationToken->setPlainToken("");
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

        $this->expectsProcessOnce();

        $this->expectsLogger("info","User created successfully");

        $this->addTokenToUser();

        $result = $this->userProcessorResult($this->user,UserCreationProcessor::class);

        $this->assertInstanceOf(User::class,$result);

        $this->assertSame( $this->user->getEmail(), $result->getEmail());
        $this->assertSame('hashed_password', $result->getPassword());
        $this->assertSame('fake_new_token', $this->user
            ->getActivationTokens()
            ->first()
            ->getPlainToken());
        $this->assertGreaterThanOrEqual(0, $result->getActivationTokens()->count());
    }

    /**
     * Summary of test_process_with_non_user_data_returns_null
     * @return void
     */
    public function test_process_with_non_user_data_returns_null():void{

        $this->expectsProcessorNeverCalled();

        $this->expectsLogger("error", 'Processor reçu une donnée non conforme');

        $result = $this->userProcessorResult(new \stdClass(), UserCreationProcessor::class);

        $this->assertNull($result);
    }

    /**
     * Summary of test_process_user_without_plain_password_returns_null
     * @return void
     */
    public function test_process_user_without_plain_password_returns_null(): void
    {
        $this->user->setPlainPassword('');

        $this->expectsProcessorNeverCalled();

        $this->expectsLogger("error", 'Mot de passe manquant, utilisateur non créé.');

        $result = $this->userProcessorResult($this->user, UserCreationProcessor::class);
        
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

        $this->expectsProcessorNeverCalled();

        $this->expectsLogger("error","Erreur lors du traitement de création utilisateur");

        $this->clearPlainToken();

        $result = $this->userProcessorResult($this->user, UserCreationProcessor::class);
        
        $this->assertNull($result);
    }

    /**
     * Summary of test_process_user_email_sending_flush_and_token_cleared
     * @return void
     */
    public function test_process_user_email_sending_flush_and_token_cleared(): void{

        $this->expectsProcessOnce();
        
        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) {
                return $message instanceof SendConfirmationEmail;
            }))
            ->willReturn(new Envelope(new SendConfirmationEmail(
                $this->user->getEmail(),
                $this->activationToken->getPlainToken(),
                $this->user->getFullName(),
                false
            )));
        
        $this->expectsLogger("info","Email de confirmation envoyé à l'utilisateur");

        $this->addTokenToUser();

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->userProcessorResult($this->user, UserEmailProcessor::class);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($this->user, $result);
        $this->assertNull($this->activationToken->getPlainToken());
    }

    /**
     * Summary of test_process_user_email_sending_failure
     * @return void
     */
    public function test_process_user_email_sending_failure(): void{

        $this->expectsProcessOnce();

        $this->expectsLogger("error","Erreur lors de l’envoi de l’email de confirmation");

        $this->addTokenToUser();

        $result = $this->userProcessorResult($this->user, UserEmailProcessor::class);
        
        $this->assertNull($result);
    }

    /**
     * Summary of test_process_no_token_or_email_for_user_email_sending
     * @return void
     */
    public function test_process_no_token_or_email_for_user_email_sending(): void{

        $this->expectsProcessOnce();

        $this->expectsLogger("warning","Utilisateur sans email ou token : email non envoyé");

        $this->clearPlainToken(); //ou $this->user->setEmail("");

        $result = $this->userProcessorResult($this->user, UserEmailProcessor::class);
        
        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($this->user, $result);
    }

    /**
     * Summary of test_process_user_email_with_no_activation_token
     * @return void
     */
    public function test_process_user_email_with_no_activation_token(): void
    {
        // Arrange
        $this->expectsProcessOnce();

        // On s’assure que le bus et l’entity manager ne sont PAS appelés
        $this->bus->expects($this->never())->method('dispatch');
        $this->entityManager->expects($this->never())->method('flush');

        // Le logger doit signaler l’absence de token
        $this->expectsLogger('warning', 'Utilisateur sans email ou token : email non envoyé');

        // L’utilisateur n’a volontairement aucun token (collection vide)
        $this->user->getActivationTokens()->clear();

        // Act
        $result = $this->userProcessorResult($this->user, UserEmailProcessor::class);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($this->user, $result);
        $this->assertTrue($this->user->getActivationTokens()->isEmpty());
    }

}
