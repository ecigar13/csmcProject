<?php

namespace App\Controller\Admin;

use App\Entity\Course\Course;
use App\Entity\User\Role;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Course\Department;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

/**
 * @Route("/admin/role", name="admin_role_")
 */
class RoleController extends Controller {

    /**
     * @Route("/", name="list")
     */
    public function roleShowAll() {

        $roles = $this->getDoctrine()
            ->getRepository(Role::class)
            ->findAll();

        return $this->render('role/admin/role/list.html.twig', array('roles' => $roles));
    }

    /**
     * @Route("/create", name="create")
     */
    public function addNewRole(Request $request) {
        //create a new role object
        $role = new Role();
        //create form for munipulate this role data
        $form = $this->createFormBuilder($role)
            ->add('name', TextType::class, array(
                'label' => 'Role Name',
                'error_bubbling' => true
            ))
            ->add('description', TextareaType::class, array(
                'label' => 'Description'
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'Submit'
            ))
            ->getForm();
        // ... perform some database action
        $entityManager = $this->getDoctrine()->getManager();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //get the data from the page and add the role to the database
            $role = $form->getData();
            $role->setLastModifiedOn()->setCreatedOn();
            $entityManager->persist($role);
            $entityManager->flush();

            return $this->redirectToRoute('admin_role_list');
        }
        //when user first time click the the create button from the roleShowAll page
        return $this->render('role/admin/role/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/edit", name="edit")
     */
    public function editRole(Request $request) {
        // Here, uses 'GET' METHOD !!
        $id = $request->query->get('id');
        $entityManager = $this->getDoctrine()->getManager();
        $role = $entityManager->getRepository(Role::class)->find($id);

        $roleName = $role->__toString();

        if (!$id) {
            throw $this->createNotFoundException(
                'No role found for id ' . $id
            );
        }
        $form = $this->createFormBuilder($role)
            ->add('name', TextType::class, array(
                'label' => 'Role Name',
                'error_bubbling' => true
            ))
            ->add('description', TextareaType::class, array(
                'label' => 'Description',
                'required' => false
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'Save'
            ))
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('roleShowAll');
        }
        return $this->render('role/admin/role/edit.html.twig', array(
            'form' => $form->createView(),
            'deleteId' => $id,
            'roleName' => $roleName

        ));
    }

    /**
     * @Route("/delete", name="delete")
     */
    public function deleteRole(Request $request) {
        $id = $request->query->get('id');
        if (!$id) {
            throw $this->createNotFoundException(
                'No role found for id ' . $id
            );
        }
        $entityManager = $this->getDoctrine()->getManager();
        $role = $entityManager->getRepository(Role::class)->find($id);

        //Try-catch block to check if there are any Foreign Key Constraint Violations
        try {
            $entityManager->remove($role);
            $entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            //Displaying error message for foreign key constraint violation
            $this->addFlash('error', "The User has associated data and cannot be deleted.");
            return $this->redirectToRoute('editRole', array(
                'role' => $role,
                'id' => $id
            ));
        }

        return $this->redirectToRoute('admin_role_list');
    }
}
