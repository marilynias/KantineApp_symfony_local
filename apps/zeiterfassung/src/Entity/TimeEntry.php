<?php

namespace Zeiterfassung\Entity;

use Doctrine\ORM\Mapping as ORM;
use Shared\Entity\Costumer as User;
use Zeiterfassung\Repository\TimeEntryRepository;

#[ORM\Entity(repositoryClass: TimeEntryRepository::class)]
class TimeEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete:"CASCADE")]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $checkinTime = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $checkoutTime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    // -------------------------------
    // USER RELATION
    // -------------------------------
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    // -------------------------------
    // CHECK-IN / CHECK-OUT
    // -------------------------------
    public function getCheckinTime(): ?\DateTimeInterface
    {
        return $this->checkinTime;
    }

    public function setCheckinTime(\DateTimeInterface $checkinTime): static
    {
        $this->checkinTime = $checkinTime;
        return $this;
    }

    public function getCheckoutTime(): ?\DateTimeInterface
    {
        return $this->checkoutTime;
    }

    public function setCheckoutTime(?\DateTimeInterface $checkoutTime): static
    {
        $this->checkoutTime = $checkoutTime;
        return $this;
    }

    public function getStatus(): string
    {
        return ($this->checkinTime && $this->checkoutTime) ? 'complete' : 'missing';
    }

    // -------------------------------
    // VIRTUAL / DERIVED FIELD
    // -------------------------------
    // public function getUserDepartment(): ?string
    // {
    //     return $this->user?->getDepartment();
    // }

    public function __toString(): string
    {
        return $this->user ? $this->user->getFullName() : 'Unknown User';
    }
}
