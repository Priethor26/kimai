<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Coordinacion;
use App\Entity\Division;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Coordinacion>
 */
class CoordinacionRepository extends EntityRepository
{
    /**
     * @return Coordinacion[]
     */
    public function findVisibleByDivision(Division $division): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.division = :division')
            ->andWhere('c.visible = :visible')
            ->setParameter('division', $division)
            ->setParameter('visible', true)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCoordinador(User $coordinador): ?Coordinacion
    {
        return $this->findOneBy(['coordinador' => $coordinador]);
    }

    public function countProjects(Coordinacion $coordinacion): int
    {
        return (int) $this->getEntityManager()
            ->createQuery('SELECT COUNT(p.id) FROM App\Entity\Project p WHERE p.coordinacion = :coordinacion')
            ->setParameter('coordinacion', $coordinacion)
            ->getSingleScalarResult();
    }

    public function saveCoordinacion(Coordinacion $coordinacion): void
    {
        $em = $this->getEntityManager();
        $em->persist($coordinacion);
        $em->flush();
    }

    public function deleteCoordinacion(Coordinacion $coordinacion): void
    {
        $em = $this->getEntityManager();
        $em->remove($coordinacion);
        $em->flush();
    }
}
