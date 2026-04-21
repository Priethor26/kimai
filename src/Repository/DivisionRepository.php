<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Division;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Division>
 */
class DivisionRepository extends EntityRepository
{
    /**
     * @return Division[]
     */
    public function findVisible(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.visible = :visible')
            ->setParameter('visible', true)
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByLider(User $lider): ?Division
    {
        return $this->findOneBy(['lider' => $lider]);
    }

    public function countActiveCoordinaciones(Division $division): int
    {
        return (int) $this->getEntityManager()
            ->createQuery('SELECT COUNT(c.id) FROM App\Entity\Coordinacion c WHERE c.division = :division AND c.visible = true')
            ->setParameter('division', $division)
            ->getSingleScalarResult();
    }

    public function saveDivision(Division $division): void
    {
        $em = $this->getEntityManager();
        $em->persist($division);
        $em->flush();
    }

    public function deleteDivision(Division $division): void
    {
        $em = $this->getEntityManager();
        $em->remove($division);
        $em->flush();
    }
}
