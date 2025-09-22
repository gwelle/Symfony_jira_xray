<?php

namespace App\Entity;

use App\Repository\LastActivationTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LastActivationTokenRepository::class)]
class LastActivationToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $Account = null;

    #[ORM\Column(length: 255)]
    private ?string $hashedToken = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiredAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccount(): ?User
    {
        return $this->Account;
    }

    public function setAccount(?User $Account): static
    {
        $this->Account = $Account;

        return $this;
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
}
