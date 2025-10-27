<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use App\State\UserEmailProcessor;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use DateTimeImmutable;

#[ApiResource(
    operations: [
        new Post(
            denormalizationContext: ['groups' => [self::GROUP_WRITE]],
            processor: UserEmailProcessor::class,
            validationContext: [
                // Ici on précise les groupes à utiliser pour la validation
                'groups' => ['Default', 'passwords_check']
            ]
        ),
        new Get(normalizationContext: ['groups' => [self::GROUP_READ]])
        
    ]
)]
#[UniqueEntity(
    fields: ['email'],
    message: 'L\'adresse mail "{{ value }}" Existe déjà.'
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`account`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
#[Assert\Callback('validatePasswordsMatch', groups: ['passwords_check'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    public const GROUP_WRITE = 'user:write';
    public const GROUP_READ = 'user:read';
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, nullable: false, unique: true)]
    #[Assert\NotBlank(message: 'L\'adresse email est obligatoire.')]
    #[Assert\NotNull(message: 'L\'adresse email ne peut pas être null.')]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        message: 'L\'adresse email "{{ value }}" est invalide.'
    )]
    #[Groups([self::GROUP_WRITE,self::GROUP_READ])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(type: 'json', nullable: false)]
    #[Groups([self::GROUP_READ])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\NotNull(message: 'Le prénom ne peut pas être null.')]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{M}]+(?:[ \'-][\p{L}\p{M}]+)*$/u',
        message: "Le prénom doit contenir uniquement des lettres, espaces, tirets ou apostrophes correctement placés."
    )]
    #[Groups([self::GROUP_WRITE,self::GROUP_READ])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\NotNull(message: 'Le prénom ne peut pas être null.')]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{M}]+(?:[ \'-][\p{L}\p{M}]+)*$/u',
        message: "Le nom ou prénom doit contenir uniquement des lettres, espaces, tirets ou apostrophes correctement placés."
    )]
    #[Groups([self::GROUP_WRITE,self::GROUP_READ])]
    private ?string $lastName = null;

    #[Assert\NotBlank(message: 'Le mot de passe de confirmation est obligatoire.')]
    #[Assert\NotNull(message: 'Le mot de passe de confirmation ne peut pas être null.')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Votre mot de passe de confirmation doit comporter au moins {{ limit }} caractères.',
        max: 15,
        maxMessage: 'Votre mot de passe de confirmation ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$/',
        message: 'Le mot de passe de confirmation doit contenir au moins une lettre majuscule, une minuscule, un chiffre et un caractère spécial.'
    )]
    #[Groups([self::GROUP_WRITE])]
    private ?string $confirmationPassword = null;

    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.')]
    #[Assert\NotNull(message: 'Le mot de passe ne peut pas être null.')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Votre mot de passe doit comporter au moins {{ limit }} caractères.',
        max: 15,
        maxMessage: 'Votre mot de passe de confirmation ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$/',
        message: 'Le mot de passe doit contenir entre 8 et 15 caractères, avec au moins une majuscule, une minuscule, un chiffre et un caractère spécial.'
    )]
    #[Groups([self::GROUP_WRITE])]
    private ?string $plainPassword = null;

    #[ORM\Column(nullable:true,type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Groups([self::GROUP_READ])]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $isActivated = false;

    /**
     * @var Collection<int, ActivationToken>
     */
    #[ORM\OneToMany(
        targetEntity: ActivationToken::class,
        mappedBy: 'account',
        cascade: ['persist', 'remove'],   //  Permet de persister automatiquement les tokens
        orphanRemoval: true               //  Nettoie les tokens supprimés
)]
    #[Groups([self::GROUP_READ])]
    private Collection $activationTokens;

    /**
     * Constructor for User entity.
     * Initializes the activationTokens collection.
     */
    public function __construct()
    {
        $this->activationTokens = new ArrayCollection();
    }

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

    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
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

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (!$this->createdAt) {
            $this->createdAt = new DateTimeImmutable();
        }
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

    public function isActivated(): ?bool
    {
        return $this->isActivated;
    }

    public function setIsActivated(bool $isActivated): static
    {
        $this->isActivated = $isActivated;

        return $this;
    }

    /**
     * @return Collection<int, ActivationToken>
     * @psalm-return Collection<int, ActivationToken>
     */
    public function getActivationTokens(): Collection
    {
        return $this->activationTokens;
    }

    /**
     * @param \App\Entity\ActivationToken $activationToken
     * @return User|null
     */
    public function addActivationToken(ActivationToken $activationToken): ?User
    {
        if (!$this->activationTokens->contains($activationToken)) {
            $this->activationTokens->add($activationToken);
            $activationToken->setAccount($this);
        }

        return $this;
    }

    /**
     * @param \App\Entity\ActivationToken $activationToken
     * @return User|null
     */
    public function removeActivationToken(ActivationToken $activationToken): ?User
    {
        if ($this->activationTokens->removeElement($activationToken) && $activationToken->getAccount() === $this) {
            // set the owning side to null (unless already changed)
            $activationToken->setAccount(null);
        }

        return $this;
    }

    /**
     * Summary of validatePasswordsMatch
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     * @return void
     */
    public function validatePasswordsMatch(ExecutionContextInterface $context): void
    {
        if ($this->plainPassword !== $this->confirmationPassword) {
            $context->buildViolation('The plain password and confirmation password do not match.')
                ->atPath('confirmationPassword')
                ->addViolation();
        }
    }


    /**
     * Permet d’activer dynamiquement des groupes selon le type d’opération (POST, PUT, etc.)
     * 
     * @param mixed $operation
     * @return string[]
     */
    public static function validationGroups($operation): array
    {
        // Pour une création, on ajoute le groupe password_check
        if ($operation instanceof Post) {
            return ['Default', 'passwords_check'];
        }

        // Sinon, validation normale
        return ['Default'];
    }
}

