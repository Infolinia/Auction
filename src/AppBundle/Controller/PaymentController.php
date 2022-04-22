<?php
/**
 * Created by PhpStorm.
 * User: Squidy
 * Date: 20.05.2018
 * Time: 13:48
 */

namespace AppBundle\Controller;


use AppBundle\Entity\Auction;
use AppBundle\Entity\Offer;
use AppBundle\Entity\User;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use SoapClient;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;

class PaymentController extends Controller
{

    /**
     * @Route("/payment/test", name="payment_test")
     * @param Request $request
     * @return Response
     */
    public function testAction(Request $request)
    {

        $soap = new SoapClient("https://sandbox.przelewy24.pl/external/73622.wsdl");
        $test = $soap->TestAccess("73622", "1a05b517a34b4e5e3ac5218f15d16ad0");

        if ($test)
            $variable = 'Połączenie zostało wykonane pomyślnie.';
        else
            $variable = 'Brak dostępu.';
        return $this->render('Buyed/variable.html.twig',  ["variable"=>$variable]);

    }

    /**
     * @Route("/payment/register/{id}", name="payment_register")
     * @param Request $request
     * @param Auction $auction
     * @return Response
     */
    public function registerAction(Request $request, Auction $auction)
    {
        $price = $auction->getPrice() * 100;
        $price = $auction->getLastOffer()->getPrice() * 100;
        $sessionId = $auction->getId() .",". md5(uniqid(session_id(), true));
        $crc = md5($sessionId."|73622|". $price ."|PLN|04cd438b9c16b1c8");

        $details = array(
            array('name'=>'p24_session_id','value'=>$sessionId),
            array('name'=>'p24_merchant_id','value'=>'73622'),
            array('name'=>'p24_pos_id','value'=>'73622'),
            array('name'=>'p24_amount','value'=>$price),
            array('name'=>'p24_currency','value'=>'PLN'),
            array('name'=>'p24_description','value'=>$auction->getDescription()),
            array('name'=>'p24_client','value'=>'Jakub Testerek'),
            array('name'=>'p24_address','value'=>'Kwiatowa'),
            array('name'=>'p24_zip','value'=>'77-990'),
            array('name'=>'p24_city','value'=>'Gdańsk'),
            array('name'=>'p24_country','value'=>'PL'),
            array('name'=>'p24_email','value'=>'email@host.pl'),
            array('name'=>'p24_language','value'=>'pl'),
            array('name'=>'p24_url_status','value'=>'http://77.55.211.46/payment/status'),
            array('name'=>'p24_url_return','value'=>'http://77.55.211.46/auction/buyed'),
            array('name'=>'p24_api_version','value'=>'3.2'),
            array('name'=>'p24_sign','value'=>$crc)
        );

        $soap = new SoapClient("https://sandbox.przelewy24.pl/external/73622.wsdl");
        $res = $soap->RegisterTransaction("73622", "1a05b517a34b4e5e3ac5218f15d16ad0", $details);
        return $this->redirect('https://sandbox.przelewy24.pl/trnRequest/'.$res->result);
    }

    /**
     * @Route("/payment/status", name="payment_status")
     */
    public function statusAction()
    {
        $request = Request::createFromGlobals();
        $p24_pos_id = $request->request->get('p24_pos_id', 'inne');
        $p24_session_id = $request->request->get('p24_session_id', 'inne');
        $p24_amount = $request->request->get('p24_amount', 'inne');
        $p24_currency = $request->request->get('p24_currency', 'inne');
        $p24_order_id = $request->request->get('p24_order_id', 'inne');
        $p24_method = $request->request->get('p24_method', 'inne');
        $p24_statement  = $request->request->get('p24_statement', 'inne');
        $p24_sign  = $request->request->get('p24_sign', 'inne');

        $parts = explode(',', $p24_session_id);
        $soap = new SoapClient("https://sandbox.przelewy24.pl/external/73622.wsdl");
        $res = $soap-> VerifyTransaction('73622', '1a05b517a34b4e5e3ac5218f15d16ad0', $p24_order_id, $p24_session_id, $p24_amount);
        if ($res->error->errorCode) {
            echo 'Something went wrong: ' . $res->error->errorMessage;
        } else {
            echo 'Transaction OK';
            $entityManager = $this->getDoctrine()->getManager();
            $auction = $entityManager->getRepository(Auction::class)->findOneBy(['id' => intval($parts[0]), 'status' => 'finished']);
            $auction->setStatus(Auction::STATUS_PAYMENT);
            $entityManager->persist($auction);
            $entityManager->flush();
            $this->addFlash("success", "Procedura płatności za: {$auction->getTitle()} została zakończona.");
        }

    }
}