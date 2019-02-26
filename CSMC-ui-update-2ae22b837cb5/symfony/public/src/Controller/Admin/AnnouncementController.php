<?php

namespace App\Controller\Admin;


use App\Entity\Misc\Announcement;
use App\Entity\Misc\Semester;
use App\Entity\Misc\SemesterSeason;

use App\Entity\Course\Course;
use App\Entity\Course\Department;
use App\Entity\Course\Section;

use App\Entity\User\User;
use App\Entity\User\Role;
use App\Entity\User\UserGroup;
use App\Form\AnnouncementType;
use Doctrine\ORM\EntityRepository;


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
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;


use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\Common\Persistence\ObjectManager;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

/**
 * @Route("/admin/announcement", name="admin_announcement_")
 */
class AnnouncementController extends Controller {
    /**
     * @Route("/", name="list")
     */
    public function listAnnouncements() {

        $announcements = $this->getDoctrine()
            ->getRepository(Announcement::class)
            ->findAll();

        /* Inactive is NOT REQUIRED in this project
        $inactiveAnnons = $this->getDoctrine()->getRepository(Announcement::class)
            ->findBy(array('active' => false,));
        $num = sizeof($inactiveAnnons);
        */

        return $this->render('role/admin/announcement/list.html.twig', array(
            'announcements' => $announcements,
            // 'numInactive' => $num,
        ));
    }

    /**
     * @Route("/create", name="create")
     */
    public function addNewAnnouncement(Request $request) {
        $form = $this->createForm(AnnouncementType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $announcement = new Announcement($data['subject'], $data['message'], $data['startDate'], $data['endDate']);
            $announcement->setActive($data['active']);
            foreach($data['roles'] as $role) {
                $announcement->addRole($role);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($announcement);
            $entityManager->flush();


            // If all the actions are successful, show Announcement list page;
            return $this->redirectToRoute('admin_announcement_list');
        }

        return $this->render('role/admin/announcement/add.html.twig', array(
            'form' => $form->createView(),
        ));


    }


    /**
     * @Route("/edit", name="edit")
     */
    public function editAnnouncement(Request $request) {

        // Here, uses 'GET' METHOD !!
        $id = $request->query->get('id');

        $entityManager = $this->getDoctrine()->getManager();
        $announcement = $entityManager->getRepository(Announcement::class)->find($id);

        if (!$id) {
            throw $this->createNotFoundException(
                'No announcement found for id ' . $id
            );
        }

        $subject = $announcement->getSubject();

        $form = $this->createFormBuilder($announcement)
            ->add('subject', TextType::class, array(
                'label' => 'Subject',
                'error_bubbling' => true,
            ))
            ->add('message', TextareaType::class, array(
                'label' => 'Message',
                'error_bubbling' => true,
                // 'data' => 'Default message data',
            ))
            /*
            ->add('message', TextareaType::class, array(
                'label' => 'Message'))
            */
            /* Active Field is NOT USED in this project
            ->add('active', CheckboxType::class, array(
                'label' => 'Active',
                'required' => false,
            ))
            */
            ->add('roles', EntityType::class, array(
                // looks for choices from this entity
                'class' => Role::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.name', 'ASC');
                },
                'choice_label' => 'name',
                'multiple' => true,
                'label' => 'Role',
                'required' => false,
            ))
            ->add('userGroups', EntityType::class, array(
                // looks for choices from this entity
                'class' => UserGroup::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('ug')
                        ->orderBy('ug.name', 'ASC');
                },
                'choice_label' => 'name',
                'multiple' => true,
                'label' => 'User Group',
                'required' => false,
            ))
            ->add('startDate', DateType::class, array(
                'label' => 'Start Date',
                'html5' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => 'yyyy/mm/dd'
            ))
            ->add('endDate', DateType::class, array(
                'label' => 'End Date',
                'html5' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => 'yyyy/mm/dd'
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'Save'
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            // EXCLUSIVE selection on Roles when both Roles and UserGroups are selected
            $formRoles = $announcement->getRoles();
            $formUGs = $announcement->getUserGroups();

            $numRoles = count($formRoles);
            $numUGs = count($formUGs);

            if ($numRoles > 0 && $numUGs > 0) {
                $announcement->setUserGroupsNull();
            }


            $entityManager->flush();

            // If all the actions are successful, show Announcement list page;
            return $this->redirectToRoute('listAnnouncements');

        }

        return $this->render('role/admin/announcement/edit.html.twig', array(
            'form' => $form->createView(),
            'deleteId' => $id,
            'subject' => $subject,
        ));

    }


    /**
     * @Route("/delete", name="delete")
     */
    public function deleteAnnouncement(Request $request) {

        // Here, uses 'GET' protocol !!
        $id = $request->query->get('id');

        $entityManager = $this->getDoctrine()->getManager();
        $announcement = $entityManager->getRepository(Announcement::class)->find($id);

        if (!$id) {
            throw $this->createNotFoundException(
                'No announcement found for id ' . $id
            );
        }


        //Try-catch block to check if there are any Foreign Key Constraint Violations
        try {
            // Delete Announcement
            $entityManager->remove($announcement);
            $entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            //Displaying error message for foreign key constraint violation
            $this->addFlash('error', "This Announcement has associated data and cannot be deleted.");
            return $this->redirectToRoute('editAnnouncement',
                array('id' => $id));
        }


        // If all the actions are successful, show Section list page;
        return $this->redirectToRoute('listAnnouncements');

    }


}
