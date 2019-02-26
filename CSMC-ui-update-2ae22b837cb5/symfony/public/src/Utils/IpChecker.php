<?php

namespace App\Utils;


use App\Entity\Misc\IpAddress;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;

class IpChecker {
    public static $UTD_MASK = '10.0.0.0/8';
    public static $CS_DEPT_MASK = '10.176.0.0/16';

    private $requestStack;
    private $entityManager;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager) {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    public function inRange($range, $ip = null) {
        if (!$ip) {
            $ip = $this->getIp();
        }
        $ip_decimal = $ip;
        if (!$ip_decimal) {
            return false;
        }

        if (strpos($range, '/') == false) {
            $range .= '/32';
        }
        list($range, $netmask) = explode('/', $range, 2);
        $range_decimal = ip2long($range);

        $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
        $netmask_decimal = ~$wildcard_decimal;

        return ($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal);
    }

    public function isKnown($ip = null) {
        if (!$ip) {
            $ip = $this->getIp();
        }
        $address = $this->entityManager
            ->getRepository(IpAddress::class)
            ->findOneByAddress($ip);
        if (!$address) {
            return false;
        } elseif ($address->getRoom()) {
            return true;
        } else {
            return false;
        }
    }

    public function getIp() {
        return ip2long($this->requestStack->getCurrentRequest()->getClientIp()); // ?: in_array(['dev', 'test']) ? ip2long('127.0.0.1') : false;
    }
}