<?php

namespace App\Entity;

use App\Repository\ActivationTokenRepository;
use Doctrine\ORM\Mapping as ORM;

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
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiredAt = null;

    #[ORM\Column(length: 64)]
    private ?string $hashedToken = null;

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

    public function setAccount(?User $account): static
    {
        $this->account = $account;

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

    public function getExpiredAt(): ?\DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(?\DateTimeImmutable $expiredAt): static
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }

    public function isExpired(): bool
    {
            return $this->expiredAt !== null && $this->expiredAt < new \DateTimeImmutable();
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
}
