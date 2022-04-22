<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Twig;

/**
 * Description of PriceExtension
 *
 * @author artur
 */
class PriceExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter("winOffer", [$this, "winOffer"])
        ];
    }
    
    public function winOffer($offers)
    {
        $offersArray = new \ArrayCollection($offers);
        usort($offersArray, function($a, $b){
            return $a['price'] - $b['price'];
        });
        
        echo $offersArray[0]->getPrice();
        return $offersArray[0]->getPrice();
    }
    
}
