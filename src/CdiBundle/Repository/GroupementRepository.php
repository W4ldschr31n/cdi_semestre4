<?php

namespace CdiBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * GroupementRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GroupementRepository extends EntityRepository
{
    /**
     * Méthode pour rechercher un groupe à partir de son nom
     */
    public function recherche($nom){
        $queryBuilder = $this->createQueryBuilder('g');

        if(!is_null($nom)){
            $queryBuilder->andWhere('g.nom LIKE :nom');
            $queryBuilder->setParameter('nom', "%".$nom."%");
        }

        $res = $queryBuilder->getQuery()->getResult();
        return $res;
    }
}
