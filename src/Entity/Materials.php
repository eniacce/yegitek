<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use App\Repository\MaterialRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MaterialRepository::class)]
#[ORM\Table(name: 'materials')]
#[ORM\HasLifecycleCallbacks]
class Materials
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'İsim boş olamaz.')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'En az 2 karakter olmalı.')]
    private string $name = '';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
