<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\CoordinacionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_coordinaciones')]
#[ORM\UniqueConstraint(name: 'UNIQ_coord_name_per_division', columns: ['name', 'division_id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_coord_coordinador', columns: ['coordinador_id'])]
#[ORM\Index(name: 'IDX_coord_division', columns: ['division_id'])]
#[ORM\Index(name: 'IDX_coord_visible', columns: ['visible'])]
#[ORM\Entity(repositoryClass: CoordinacionRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity(fields: ['name', 'division'], message: 'Ya existe una Coordinación con ese nombre en esta División.')]
#[UniqueEntity(fields: ['coordinador'], message: 'Este usuario ya es Coordinador de otra Coordinación.')]
class Coordinacion
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

    #[ORM\ManyToOne(targetEntity: Division::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Assert\NotNull]
    private ?Division $division = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $coordinador = null;

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

    public function getDivision(): ?Division
    {
        return $this->division;
    }

    public function setDivision(?Division $division): void
    {
        $this->division = $division;
    }

    public function getCoordinador(): ?User
    {
        return $this->coordinador;
    }

    public function setCoordinador(?User $coordinador): void
    {
        $this->coordinador = $coordinador;
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
