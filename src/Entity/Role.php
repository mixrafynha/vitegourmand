<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'role')]
#[ORM\UniqueConstraint(name: 'uniq_role_code', columns: ['code'])]
class Role
{
    public const USER     = 'ROLE_USER';
    public const EMPLOYEE = 'ROLE_EMPLOYEE';
    public const ADMIN    = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ✅ Sempre guardar no formato ROLE_*
    #[ORM\Column(length: 50, unique: true)]
    private string $code = self::USER;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $label = null;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'roleEntities')]
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * ✅ Aceita: "admin", "ADMIN", "ROLE_ADMIN", "employee", etc.
     * e guarda sempre: "ROLE_ADMIN" / "ROLE_EMPLOYEE" / "ROLE_USER"
     */
    public function setCode(string $code): self
    {
        $this->code = self::normalizeCode($code);
        return $this;
    }

    public static function normalizeCode(string $code): string
    {
        $code = strtoupper(trim($code));
        $code = str_replace(' ', '_', $code);

        if (!str_starts_with($code, 'ROLE_')) {
            $code = 'ROLE_' . $code;
        }

        // correção comum FR
        if ($code === 'ROLE_EMPLOYE') {
            $code = self::EMPLOYEE;
        }

        // fallback seguro
        if (!in_array($code, [self::USER, self::EMPLOYEE, self::ADMIN], true)) {
            $code = self::USER;
        }

        return $code;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label ? trim($label) : null;
        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->code === self::ADMIN;
    }

    public function isEmployee(): bool
    {
        return $this->code === self::EMPLOYEE;
    }

    public function isUser(): bool
    {
        return $this->code === self::USER;
    }

    public function __toString(): string
    {
        return $this->code;
    }

    /** @return Collection<int, User> */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addRoleEntity($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeRoleEntity($this);
        }

        return $this;
    }
}
