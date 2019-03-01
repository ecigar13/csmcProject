<?php

namespace App\Controller\Admin;

use App\Entity\Misc\Room;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Doctrine\Common\Persistence\ObjectManager;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

/**
 * @Route("/admin/room", name="admin_room_")
 */
class RoomsController extends Controller {
    /**
     * @Route("/list", name="list")
     */
    public function roomShowAll() {
        $room = $this->getDoctrine()
            ->getRepository(Room::class)
            ->findAll();

        return $this->render('role/admin/room/list.html.twig', array('room' => $room));
    }

    /**
     * @Route("/create", name="create")
     */
    public function roomAdd(Request $request) {

        $room = new Room();

        $form = $this->createFormBuilder($room)
            ->add('building', TextType::class, array(
                'label' => 'Building',
                'error_bubbling' => true
            ))
            ->add('floor', TextType::class, array(
                'label' => 'Floor'
            ))
            ->add('number', TextType::class, array(
                'label' => 'Number'
            ))
            ->add('description', TextareaType::class, array(
                'label' => 'Description'
            ))
            ->add('capacity', TextType::class, array(
                'label' => 'Capacity'
            ))
            ->add('active', CheckboxType::class, array(
                'label' => 'Active',
                'required' => false
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Submit'
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->getDoctrine()->getManager();

            $room = $form->getData();

            $room->setLastModifiedOn()->setCreatedOn();
            $entityManager->persist($room);
            $entityManager->flush();


            $room = $this->getDoctrine()
                ->getRepository(Room::class)
                ->findAll();
            return $this->render('role/admin/room/list.html.twig', array('room' => $room));
        }

        return $this->render('role/admin/room/add.html.twig', array(
            'form' => $form->createView(),
        ));

    }

    /**
     * @Route("/edit", name="edit")
     */
    public function roomEdit(Request $request) {

        $id = $request->query->get('id');

        $entityManager = $this->getDoctrine()->getManager();
        $room = $entityManager->getRepository(Room::class)->find($id);

        if (!$id) {
            throw $this->createNotFoundException(
                'No room found for id ' . $id
            );
        }
        $form = $this->createFormBuilder($room)
            ->add('building', TextType::class, array(
                'label' => 'Building',
                'error_bubbling' => true
            ))
            ->add('floor', TextType::class, array(
                'label' => 'Floor'
            ))
            ->add('number', TextType::class, array(
                'label' => 'Number'
            ))
            ->add('description', TextareaType::class, array(
                'label' => 'Description'
            ))
            ->add('capacity', TextType::class, array(
                'label' => 'Capacity'
            ))
            ->add('active', CheckboxType::class, array(
                'label' => 'Active',
                'required' => false
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Save'
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            $room = $this->getDoctrine()
                ->getRepository(Room::class)
                ->findAll();
            return $this->render('role/admin/room/list.html.twig', array('room' => $room));

        }
        $validator = $this->get('validator');
        $errors = $validator->validate($room);
        return $this->render('role/admin/room/edit.html.twig', array(
            'form' => $form->createView(),
            'deleteId' => $id,
            'errors' => $errors,
        ));

    }

    /**
     * @Route("/delete", name="delete")
     */
    public function roomDelete(Request $request) {

        $id = $request->query->get('id');

        $entityManager = $this->getDoctrine()->getManager();
        $room = $entityManager->getRepository(Room::class)->find($id);

        if (!$id) {
            throw $this->createNotFoundException(
                'No room found for id ' . $id
            );
        }
        //Try-catch block to check if there are any Foreign Key Constraint Violations
        try {
            $entityManager->remove($room);
            $entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            //Displaying error message for foreign key constraint violation
            $this->addFlash('error', "This Room has associated data and cannot be deleted.");
            return $this->redirectToRoute('admin_room_edit', array(
                'room' => $room,
                'id' => $id
            ));
        }

        return $this->redirectToRoute('admin_room_list');
    }

}

