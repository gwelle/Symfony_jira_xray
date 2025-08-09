<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\State\UserStateProcessor;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;


#[ApiResource(
    operations:[
        new Post(denormalizationContext: ['groups' => ['user:write']],
                 processor: UserStateProcessor::class,
            ),
        new GetCollection(normalizationContext: ['groups' => ['user:read']]),
    ]
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, nullable: false, unique: true)]
    #[Assert\NotBlank(message: 'Please enter your email address.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    #[Assert\NotNull(message: 'Email cannot be null.')]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        message: 'The email address "{{ value }}" is not a valid email address.'
    )]
    #[Groups(['user:write','user:read'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(type: 'array', nullable: false)]
    #[Groups(['user:read'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(nullable: false)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'Please enter your first name.')]
    #[Assert\NotNull(message: 'First name cannot be null.')]
    #[Groups(['user:write','user:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'Please enter your last name.')]
    #[Assert\NotNull(message: 'Last name cannot be null.')]
    #[Groups(['user:write','user:read'])]
    private ?string $lastName = null;

    #[Assert\NotBlank(message: 'Please confirm your password.')]
    #[Assert\NotNull(message: 'Confirmation password cannot be null.')]
    #[Assert\Expression(
        'this.getPlainPassword() === this.getConfirmationPassword()',
        message: 'The plain password and confirmation password do not match.'
    )]
    #[Assert\Length(
        min: 8,
        minMessage: 'Your confirmation password should be at least {{ limit }} characters long.',
        max: 15,
        maxMessage: 'Your confirmation password cannot be longer than {{ limit }} characters.'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$/',
        message: 'Confirmation password must be 8-15 characters long, contain at least one uppercase letter, one lowercase letter, and one number.'
    )]
    #[Groups(['user:write'])]
    private ?string $confirmationPassword = null;

    #[Assert\NotBlank(message: 'Please enter a password.')]
    #[Assert\NotNull(message: 'Password cannot be null.')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Your plain password should be at least {{ limit }} characters long.',
        max: 15,
        maxMessage: 'Your plain password cannot be longer than {{ limit }} characters.'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$/',
        message: 'Password must be 8-15 characters long, contain at least one uppercase letter, one lowercase letter, and one number.'
    )]
    #[Groups(['user:write'])]
    private ?string $plainPassword = null;

    #[ORM\Column(nullable:true,type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getConfirmationPassword(): ?string
    {
        return $this->confirmationPassword;
    }

    public function setConfirmationPassword(string $confirmationPassword): static
    {
        $this->confirmationPassword = $confirmationPassword;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

}
