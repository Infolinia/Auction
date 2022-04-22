<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Auction;
use AppBundle\Entity\Offer;
use AppBundle\Form\BidType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class OfferController extends Controller
{

    /**
     * @Route("/auction/buyed", name="offer_index")
     *
     * @return Response
     */
    public function indexAction()
    {
        $entityManager = $this->getDoctrine()->getManager();

        $auctions = $entityManager
                ->getRepository(Auction::class)
                ->findMyFinishedBids($this->getUser());
        
        $actualDate = new \DateTime();
        foreach($auctions as $a){
            if($a->getExpiresAt() < $actualDate && $a->getStatus() === Auction::STATUS_ACTIVE)
            {
                $a->setStatus(Auction::STATUS_FINISHED);
                $entityManager->persist($a);
            }
        }
        $entityManager->flush();

        return $this->render("Buyed/index.html.twig", ["auctions" => $auctions]);
    }

    /**
     * @Route("/auction/buy/{id}", name="offer_buy", methods={"POST"})
     *
     * @param Auction $auction
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function buyAction(Auction $auction)
    {
        $this->denyAccessUnlessGranted("ROLE_USER");
        if ($this->getUser()->getId() === $auction->getOwner()->getId()) {
            throw new AccessDeniedException();
        }
        $offer = new Offer();
        $offer
            ->setAuction($auction)
            ->setType(Offer::TYPE_BUY)
            ->setPrice($auction->getPrice())
        ->setOwner($this->getUser());

        $auction
            ->setStatus(Auction::STATUS_FINISHED)
            ->setExpiresAt(new \DateTime());

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($auction);
        $entityManager->persist($offer);
        $entityManager->flush();

        $this->addFlash("success", "Kupiłeś przedmiot {$auction->getTitle()} za kwotę {$offer->getPrice()} zł");

        return $this->redirectToRoute("auction_details", ["id" => $auction->getId()]);
    }

    /**
     * @Route("/auction/bid/{id}", name="offer_bid", methods={"POST"})
     *
     * @param Request $request
     * @param Auction $auction
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function bidAction(Request $request, Auction $auction)
    {
        $this->denyAccessUnlessGranted("ROLE_USER");

        $offer = new Offer();
        $bidForm = $this->createForm(BidType::class, $offer);

        $bidForm->handleRequest($request);

        if ($bidForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $lastOffer = $entityManager
                ->getRepository(Offer::class)
                ->findOneBy(["auction" => $auction], ["createdAt" => "DESC"]);

            if (isset($lastOffer)) {
                if($lastOffer->getOwner() === $this->getUser()){
                    $this->addFlash("error", "Nie możesz przelicytować swojej oferty");
                    
                    return $this->redirectToRoute("auction_details", ["id" => $auction->getId()]);
                }
                if ($offer->getPrice() <= $lastOffer->getPrice()) {
                    $this->addFlash("error", "Twoja oferta nie może być niższa niż {$lastOffer->getPrice()} zł");

                    return $this->redirectToRoute("auction_details", ["id" => $auction->getId()]);
                }
            } else {
                if ($offer->getPrice() < $auction->getStartingPrice()) {
                    $this->addFlash("error", "Twoja oferta nie może być niższa od ceny wywoławczej");

                    return $this->redirectToRoute("auction_details", ["id" => $auction->getId()]);
                }
            }

            $offer
                ->setType(Offer::TYPE_BID)
                ->setAuction($auction)
                ->setOwner($this->getUser());

            $entityManager->persist($offer);
            $entityManager->flush();

            $this->addFlash(
                "success",
                "Złożyłeś ofertę na przedmiot {$auction->getTitle()} za kwotę {$offer->getPrice()} zł"
            );
        } else {
            $this->addFlash("error", "Nie udało się zalicytować przedmiotu {$auction->getTitle()}");
        }

        return $this->redirectToRoute("auction_details", ["id" => $auction->getId()]);
    }
    
   /**
     * @Route("/offers/my/bidding", name="offer_my_bidding")
     *
     * @return Response
     */
    public function biddingAction()
    {
        $em = $this->getDoctrine()->getManager();
        
        $auctions = $em
                    ->getRepository(Auction::class)
                    ->findMyActiveBids($this->getUser());
        
        $actualDate = new \DateTime();
        foreach($auctions as $a){
            if($a->getExpiresAt() < $actualDate && $a->getStatus() === Auction::STATUS_ACTIVE)
            {
                $a->setStatus(Auction::STATUS_FINISHED);
                $em->persist($a);
            }
        }
        $em->flush();
        
        return $this->render("Offer/bid.html.twig", ['auctions' => $auctions]);
    }
    
}
