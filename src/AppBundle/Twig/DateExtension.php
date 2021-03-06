<?php

namespace AppBundle\Twig;

class DateExtension extends \Twig_Extension
{
    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter("expireDate", [$this, "expireDate"])
        ];
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction("auctionStyle", [$this, "auctionStyle"])
        ];
    }

    /**
     * @param \DateTime $expiresAt
     *
     * @return string
     */
    public function expireDate(\DateTime $expiresAt)
    {
//        if ($expiresAt < new \DateTime("-7 days")) {
//            return $expiresAt->format("Y-m-d H:i");
//        }

        if($expiresAt > new \DateTime("+2 days")){
            return $expiresAt->diff(new \DateTime())->days . " dni";
        }
        if ($expiresAt > new \DateTime("+1 day")) {
            return $expiresAt->diff(new \DateTime())->days . " dzień";
        }
        else
        {
            if($expiresAt > new \DateTime("+1 hour"))
                return $expiresAt->diff(new \DateTime())->h . " godz. " . $expiresAt->diff(new \DateTime())->i . " min.";
            
            if($expiresAt < new \DateTime("+1 hour") && $expiresAt > new \DateTime("+60 seconds"))
                return $expiresAt->diff(new \DateTime())->i . " min.";
 
                return "poniżej minuty";
        }
  
        //return "za " . $expiresAt->diff(new \DateTime())->h . " godz. " . $expiresAt->diff(new \DateTime())->i . " min.";
        //return "za " . $expiresAt->format("H") . " godz. " . $expiresAt->format("i") . " min.";
    }

    /**
     * @param \DateTime $expiresAt
     *
     * @return string
     */
    public function auctionStyle(\DateTime $expiresAt)
    {
        if ($expiresAt < new \DateTime("+1 day")) {
            return "panel-danger";
        }

        return "panel-default";
    }
}
