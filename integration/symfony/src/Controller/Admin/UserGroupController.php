<?php

namespace App\Controller\Admin;

use App\Entity\User\User;
use App\Entity\User\Role;
use App\Entity\User\UserGroup;
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
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Course\Department;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

/**
 * @Route("/admin/user/group", name="admin_user_group_")
 */
class UserGroupController extends Controller {
    /**
     * @Route("/", name="list")
     */
    public function userGroupShowAll() {

        $userGroups = $this->getDoctrine()
            ->getRepository(UserGroup::class)
            ->findAll();

        return $this->render('role/admin/user_group/list.html.twig', array('userGroups' => $userGroups));
    }

    /**
     * @Route("/create", name="create")
     */
    public function userGroupAdd(Request $request) {
        //create a new userGroup object
        $userGroup = new UserGroup();

        //create form to munipulate this usergroup data
        $form = $this->createFormBuilder($userGroup)
            ->add('name', TextType::class, array(
                'label' => 'Group Name',
                'error_bubbling' => true
            ))
            ->add('description', TextareaType::class, array(
                'label' => 'Description'
            ))
            ->add('roles', EntityType::class, array(
                'class' => Role::class,
                'choice_label' => 'name',
                //
                'mapped' => false,
                'placeholder' => '',
                'required' => false
            ))
            ->add('users', EntityType::class, array(
                // looks for choices from this entity
                'class' => User::class,
                //show all the user name
                //show the user first name, last name and netid as option for the multi-selectable table
                'choice_label' => function ($user) {
                    return $user->__toString();
                },
                'multiple' => true,
                //Use this to add additional HTML attributes to each choice.
                //this helped to add the role id information to json_encode
                'choice_attr' => function ($value, $key, $index) {
                    $roles = array();
                    foreach ($value->getRoles() as $role) {
                        $roles[] = $role->getId();
                    }
                    //the form style forces double quotes arround the attributes, so here we want to use single quotes
                    return ['data-roles' => str_replace("\"", "'", json_encode($roles))];
                }
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'Submit'
            ))
            ->getForm();
        // ... perform saving action, the task is a doctrine entity
        $entityManager = $this->getDoctrine()->getManager();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //get data from the form
            $userGroup = $form->getData();

            //set some attribute to this entity
            $userGroup->setLastModifiedOn()->setCreatedOn();
            //add the data to the database
            $entityManager->persist($userGroup);
            $entityManager->flush();

            $userGroups = $this->getDoctrine()
                ->getRepository(UserGroup::class)
                ->findAll();

            return $this->redirectToRoute('admin_user_group_list');
        }

        //when user first time click the the create button from the userShowAll page
        return $this->render('role/admin/user_group/add.html.twig', array(
            'form' => $form->createView(),

        ));
    }

    /**
     * @Route("/edit", name="edit")
     */
    public function userGroupEdit(Request $request) {
        //get the group id from the page
        $id = $request->query->get('id');
        //get the group information by this id
        $entityManager = $this->getDoctrine()->getManager();
        $userGroup = $entityManager->getRepository(UserGroup::class)->find($id);
        //get the group name by this id
        $groupName = $userGroup->__toString();

        //throu error message when no id fetched
        if (!$id) {
            throw $this->createNotFoundException(
                'No role found for id ' . $id
            );
        }
        //create the form for data changing, same structural form as the add function
        $form = $this->createFormBuilder($userGroup)
            ->add('name', TextType::class, array(
                'label' => 'Group Name',
                //add the error_bubbling attribute here to catch the name-unique error
                'error_bubbling' => true
            ))
            ->add('description', TextareaType::class, array(
                'label' => 'Description'
            ))
            ->add('roles', EntityType::class, array(
                'class' => Role::class,
                'choice_label' => 'name',
                'mapped' => false,
                'placeholder' => '',
                'required' => false
            ))
            ->add('users', EntityType::class, array(
                // looks for choices from this entity
                'class' => User::class,

                'choice_label' => function ($user) {
                    return $user->__toString();
                },
                'multiple' => true,
                'choice_attr' => function ($value, $key, $index) {
                    $roles = array();
                    foreach ($value->getRoles() as $role) {
                        $roles[] = $role->getId();
                    }
                    return ['data-roles' => str_replace("\"", "'", json_encode($roles))];
                }
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'Save'
            ))
            ->getForm();
        $form->handleRequest($request);
        //if the save button is clicked, do the following
        if ($form->isSubmitted() && $form->isValid()) {
            $userGroup->setLastModifiedOn()->setCreatedOn();
            $entityManager->flush();

            return $this->redirectToRoute('admin_user_group_list');

        }
        return $this->render('role/admin/user_group/edit.html.twig', array(
            'form' => $form->createView(),
            'deleteId' => $id,
            'groupName' => $groupName

        ));
    }

    /**
     * @Route("/delete", name="delete")
     */
    public function deleteUserGroup(Request $request) {
        $id = $request->query->get('id');
        if (!$id) {
            throw $this->createNotFoundException(
                'No role found for id ' . $id
            );
        }
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager = $this->getDoctrine()->getManager();
        $userGroup = $entityManager->getRepository(UserGroup::class)->find($id);


        //Try-catch block to check if there are any Foreign Key Constraint Violations
        try {
            $entityManager->remove($userGroup);
            $entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            //Displaying error message for foreign key constraint violation
            $this->addFlash('error', "This User Group has associated data and cannot be deleted.");
            return $this->redirectToRoute('admin_user_group_edit', array(
                'userGroup' => $userGroup,
                'id' => $id
            ));
        }

        $userGroups = $this->getDoctrine()
            ->getRepository(UserGroup::class)
            ->findAll();
        return $this->render('role/admin/user_group/list.html.twig', array(
            'userGroups' => $userGroups
        ));
    }
}
