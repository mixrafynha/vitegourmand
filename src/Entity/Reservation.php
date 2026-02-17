<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Reservation
{
    #[ORM\Id]                         // <-- ISSO É OBRIGATÓRIO
    #[ORM\GeneratedValue]             // <-- auto increment
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank, Assert\Email]
    private string $email;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    private string $phone;

    #[ORM\Column]
    #[Assert\NotBlank]
    private \DateTimeImmutable $date;

    #[ORM\Column]
    #[Assert\Positive]
    private int $people;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    // 🟢 getters e setters
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getPhone(): string { return $this->phone; }
    public function setPhone(string $phone): self { $this->phone = $phone; return $this; }
    public function getDate(): \DateTimeImmutable { return $this->date; }
    public function setDate(\DateTimeImmutable $date): self { $this->date = $date; return $this; }
    public function getPeople(): int { return $this->people; }
    public function setPeople(int $people): self { $this->people = $people; return $this; }
    public function getMessage(): ?string { return $this->message; }
    public function setMessage(?string $message): self { $this->message = $message; return $this; }
}
