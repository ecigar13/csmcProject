<?php

namespace App\Controller\Admin;

use App\Entity\Misc\IpAddress;
use App\Entity\Misc\Room;
use App\Utils\IpChecker;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * @Route("/admin/ip", name="admin_ip_")
 */
class IpController extends Controller {
    /**
     * @Route("/", name="list")
     */
    public function addressShowAll(IpChecker $ipChecker) {
        $csmc = array();
        $utdcs = array();
        $utd = array();
        $others = array();

        $ipaddress = $this->getDoctrine()
            ->getRepository(IpAddress::class)
            ->findAll();

        foreach ($ipaddress as $ip) {
            if ($ip->getRoom() != null) {
                $csmc[] = $ip;
            } elseif ($ipChecker->inRange(IpChecker::$CS_DEPT_MASK, ip2long($ip->getAddress()))) {
                $utdcs[] = $ip;
            } elseif ($ipChecker->inRange(IpChecker::$UTD_MASK, ip2long($ip->getAddress()))) {
                $utd[] = $ip;
            } else {
                $others[] = $ip;
            }
        }

        return $this->render('role/admin/ip/list.html.twig', array(
            'csmc' => $csmc,
            'utdcs' => $utdcs,
            'utd' => $utd,
            'others' => $others
        ));
    }

    /**
     * @Route("/create", name="create")
     */
    public function addAddress(Request $request) {
        $ipaddress = new IpAddress();

        $form = $this->createFormBuilder($ipaddress)
            ->add('address', TextType::class, array(
                'label' => 'IP Address',
                'error_bubbling' => true
            ))
            ->add('room', EntityType::class, array(
                'class' => Room::class,
                'placeholder' => ' ',
                'required' => false
            ))
            ->add('blocked', CheckboxType::class, array(
                'label' => 'Block?',
                'required' => false
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Submit'
            ))
            ->getForm();


        $entityManager = $this->getDoctrine()->getManager();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $ipaddress = $form->getData();

            $entityManager->persist($ipaddress);
            $entityManager->flush();


            return $this->redirectToRoute('admin_ip_list');
        }

        return $this->render('role/admin/ip/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/edit", name="edit")
     */
    public function addressEdit(Request $request) {

        $id = $request->query->get('id');

        $entityManager = $this->getDoctrine()->getManager();
        $ipaddress = $entityManager->getRepository(IpAddress::class)->find($id);

        if (!$id) {
            throw $this->createNotFoundException(
                'No IP Address found for id ' . $id
            );
        }

        $form = $this->createFormBuilder($ipaddress)
            ->add('address', TextType::class, array(
                'label' => 'IP Address',
                'error_bubbling' => true
            ))
            ->add('room', EntityType::class, array(
                'class' => Room::class,
                'placeholder' => ' ',
                'required' => false
            ))
            ->add('blocked', CheckboxType::class, array(
                'label' => 'Block?',
                'required' => false
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Save'
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            return $this->redirectToRoute('admin_ip_list');
        }

        return $this->render('role/admin/ip/edit.html.twig', array(
            'form' => $form->createView(),
            'deleteId' => $id,
        ));
    }

    /**
     * @Route("/delete", name="delete")
     */
    public function deleteAddress(Request $request) {
        $id = $request->query->get('id');

        $entityManager = $this->getDoctrine()->getManager();
        $ipaddress = $entityManager->getRepository(IpAddress::class)->find($id);

        if (!$id) {
            throw $this->createNotFoundException(
                'No Address found for id ' . $id
            );
        }

        $entityManager->remove($ipaddress);

        $entityManager->flush();

        return $this->redirectToRoute('admin_ip_list');
    }
}