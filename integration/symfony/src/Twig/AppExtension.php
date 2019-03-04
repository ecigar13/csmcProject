<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return array(
            new TwigFilter('phone', array($this, 'phoneFilter'))
        );
    }

    public function phoneFilter($phoneNumber)
    {
        if (strlen($phoneNumber) == 0) return "";
        $formatted = "(" . substr($phoneNumber, 0, 3) . ") ";
        $formatted .= substr($phoneNumber, 3, 3) . "-" . substr($phoneNumber, 6);
        return $formatted;
    }

}