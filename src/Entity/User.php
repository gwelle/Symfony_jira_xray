<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\State\UserStateProcessor;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ApiResource(
    operations: [
        new Post(
            denormalizationContext: ['groups' => [self::GROUP_WRITE]],
            processor: UserStateProcessor::class
        )
    ]
)]
#[UniqueEntity(
    fields: ['email'],
    message: 'L\'adresse mail "{{ value }}" Existe déjà.'
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
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
        pattern: '/^[\p{L}]+(?:[ \'-][\p{L}]+)*$/u',
        message: "Le prénom doit contenir uniquement des lettres, espaces, tirets ou apostrophes correctement placés."
    )]
    #[Groups([self::GROUP_WRITE,self::GROUP_READ])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\NotNull(message: 'Le prénom ne peut pas être null.')]
    #[Assert\Regex(
        pattern: '/^[\p{L}]+(?:[ \'-][\p{L}]+)*$/u',
            message: "Le nom ou prénom doit contenir uniquement des lettres, espaces, tirets ou apostrophes correctement placés."
    )]
    #[Groups([self::GROUP_WRITE,self::GROUP_READ])]
    private ?string $lastName = null;

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
    #[Assert\NotNull(message: 'Le mot de passe neut pas être null.')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Votre mot de passe doit comporter au moins {{ limit }} caractères.',
        max: 15,
        maxMessage: 'Votre mot de passe de confirmation ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$/',
        message: 'Le mot de passe doit contenir au moins une lettre majuscule, une minuscule, un chiffre et un caractère spécial.'
    )]
    #[Groups([self::GROUP_WRITE])]
    private ?string $plainPassword = null;

    #[ORM\Column(nullable:true,type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Groups([self::GROUP_READ])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private ?bool $isActivated = null;

    /**
     * @var Collection<int, ActivationToken>
     */
    #[ORM\OneToMany(targetEntity: ActivationToken::class, mappedBy: 'account')]
    #[Groups([self::GROUP_READ])]
    private Collection $activationTokens;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private ?bool $isResend = false;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
    private ?int $resendCount = 0;

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

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (!$this->createdAt) {
            $this->createdAt = new \DateTimeImmutable();
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
     */
    public function getActivationTokens(): Collection
    {
        return $this->activationTokens;
    }

    public function addActivationToken(ActivationToken $activationToken): static
    {
        if (!$this->activationTokens->contains($activationToken)) {
            $this->activationTokens->add($activationToken);
            $activationToken->setAccount($this);
        }

        return $this;
    }

    public function removeActivationToken(ActivationToken $activationToken): static
    {
        if ($this->activationTokens->removeElement($activationToken) && $activationToken->getAccount() === $this) {
            // set the owning side to null (unless already changed)
            $activationToken->setAccount(null);
        }

        return $this;
    }

    public function isResend(): ?bool
    {
        return $this->isResend;
    }

    public function setIsResend(?bool $isResend): static
    {
        $this->isResend = $isResend;

        return $this;
    }

    public function getResendCount(): ?int
    {
        return $this->resendCount;
    }

    public function setResendCount(?int $resendCount): static
    {
        $this->resendCount = $resendCount;

        return $this;
    }

}

