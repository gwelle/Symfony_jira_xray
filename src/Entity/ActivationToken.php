<?php

namespace App\Entity;

use App\Repository\ActivationTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: ActivationTokenRepository::class)]
class ActivationToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $plainToken = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'activationTokens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $account = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $expiredAt = null;

    #[ORM\Column(length: 64)]
    private ?string $hashedToken = null;

    ##[ORM\Column(length: 255, nullable: true)]
    private ?string $previousHashedToken = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlainToken(): ?string
    {
        return $this->plainToken;
    }

    public function setPlainToken(?string $plainToken): static
    {
        $this->plainToken = $plainToken;

        return $this;
    }

    public function getAccount(): ?User
    {
        return $this->account;
    }

    public function setAccount(?User $account): self
    {
        $this->account = $account;

        if ($account !== null && !$account->getActivationTokens()->contains($this)) {
            $account->addActivationToken($this);
        }

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

    public function getExpiredAt(): ?DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(?DateTimeImmutable $expiredAt): static
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiredAt !== null && $this->expiredAt < new DateTimeImmutable();
    }

    public function isValid(): bool
    {
        return !$this->isExpired();
    }


    public function getHashedToken(): ?string
    {
        return $this->hashedToken;
    }

    public function setHashedToken(string $hashedToken): static
    {
        $this->hashedToken = $hashedToken;

        return $this;
    }

    /** 
     * Regenerate the activation token for the user concerning to unit tests.
     * @return void
     */
    public function regenerateToken(): void
    {
        // Conserver l'ancien hashé dans previousHashedToken
        $this->previousHashedToken = $this->hashedToken;

        // Expirer le token courant
        $this->expiredAt = new DateTimeImmutable();

        // Générer un nouveau token brut et son hash
        $plainToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainToken);

        // Mettre à jour les propriétés
        $this->plainToken = $plainToken;
        $this->hashedToken = $hashedToken;

        $this->createdAt = new DateTimeImmutable();
        $this->expiredAt = null;
    }

    public function getPreviousHashedToken(): ?string
    {
        return $this->previousHashedToken;
    }

    public function setPreviousHashedToken(?string $previousHashedToken): static
    {
        $this->previousHashedToken = $previousHashedToken;

        return $this;
    }
}
