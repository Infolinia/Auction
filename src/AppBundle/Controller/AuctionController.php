<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Auction;
use AppBundle\Form\BidType;
use AppBundle\Service\DateService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuctionController extends Controller
{
    /**
     * @Route("/", name="auction_index")
     *
     * @return Response
     */
    public function indexAction()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $auctions = $entityManager->getRepository(Auction::class)->findActiveOrdered();
        
        $logger = $this->get("logger");
        $logger->info("uzytkownik wszedł na akcję index");

        $dateService = $this->get(DateService::class);

        $logger->info("aktualny dzień miesiąca to " . $dateService->getDay(new \DateTime()));
 
        return $this->render("Auction/index.html.twig", ["auctions" => $auctions]);
    }

    /**
     * @Route("/auction/details/{id}", name="auction_details")
     *
     * @param Auction $auction
     *
     * @return Response
     */
    public function detailsAction(Auction $auction)
    {
        if ($auction->getStatus() === Auction::STATUS_FINISHED) {
            return $this->render("Auction/finished.html.twig", ["auction" => $auction]);
        }

        $buyForm = $this->createFormBuilder()
            ->setAction($this->generateUrl("offer_buy", ["id" => $auction->getId()]))
            ->add("submit", SubmitType::class, ["label" => "Kup"])
            ->getForm();

        $bidForm = $this->createForm(
            BidType::class,
            null,
            ["action" => $this->generateUrl("offer_bid", ["id" => $auction->getId()])]
        );

        return $this->render(
            "Auction/details.html.twig",
            [
                "auction" => $auction,
                "buyForm" => $buyForm->createView(),
                "bidForm" => $bidForm->createView(),
            ]
        );
    }
    
    /**
     * @Route("/elapsed/{id}", name="auction_elapsed")
     * @param Auction $auction
     */
    public function elapsedAction(Auction $auction)
    {
        $auction->setStatus(Auction::STATUS_FINISHED);
        $em = $this->getDoctrine()->getManager();
        $em->persist($auction);
        $em->flush();
        
        return new Response("OK");
    }
   
}
