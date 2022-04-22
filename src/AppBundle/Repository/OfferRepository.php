<?php

namespace AppBundle\Repository;
use AppBundle\Entity\Auction;
use AppBundle\Entity\Offer;
use AppBundle\Entity\User;

class OfferRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param Auction $auction
     * @return Offer
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    
    public function findLastOffer(Auction $auction)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        return $qb 
                ->select("o")
                ->from("AppBundle\Entity\Offer","o")
                ->innerJoin("AppBundle\Entity\Auction", "a", "WITH", "a.id = o.auction")
                ->where("o.auction = :a")
                ->setParameter("a", $auction)
                ->orderBy("o.price", "DESC")
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleResult();
    }
}