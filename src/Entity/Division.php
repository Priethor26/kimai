<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\DivisionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_divisions')]
#[ORM\UniqueConstraint(name: 'UNIQ_division_name', columns: ['name'])]
#[ORM\UniqueConstraint(name: 'UNIQ_division_lider', columns: ['lider_id'])]
#[ORM\Index(name: 'IDX_division_visible', columns: ['visible'])]
#[ORM\Entity(repositoryClass: DivisionRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity(fields: ['name'], message: 'Esta División ya existe.')]
#[UniqueEntity(fields: ['lider'], message: 'Este usuario ya es Líder de otra División.')]
class Division
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 100, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $name = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $lider = null;

    #[ORM\Column(name: 'visible', type: Types::BOOLEAN, nullable: false)]
    #[Assert\NotNull]
    private bool $visible = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return $this->id === null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getLider(): ?User
    {
        return $this->lider;
    }

    public function setLider(?User $lider): void
    {
        $this->lider = $lider;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): void
    {
        $this->visible = $visible;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
