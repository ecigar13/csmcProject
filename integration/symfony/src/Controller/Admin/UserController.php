<?php

namespace App\Controller\Admin;

use App\Entity\User\User;
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
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Course\Department;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

/**
 * @Route("/admin/user", name="admin_user_")
 */
class UserController extends Controller {
    /**
     * @Route("/{role}",
     *     name="list",
     *     defaults={"role":"all"},
     *     requirements={"role":"all|admin|instructor|mentor|student"}
     *     )
     */
    public function userShowAll(string $role) {
        if($role == 'all') {
            $users = $this->getDoctrine()
                ->getRepository(User::class)
                ->findAll();
        } else {
            $repo = $this->getDoctrine()
                ->getRepository(User::class);
            $qb = $repo->createQueryBuilder('u')
                ->join('u.roles', 'r')
                ->where('r.name = :name')
                ->setParameter('name', $role);
            $users = $qb->getQuery()->getResult();
        }

        return $this->render('role/admin/user/list.html.twig', array(
            'users' => $users
        ));
    }

    /**
     * @Route("/userShowInstructor", name="userShowInstructor")
     */
    public function userShowInstructor() {
        $repo = $this->getDoctrine()
            ->getRepository(User::class);
        $qb = $repo->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.name = :name')
            ->setParameter('name', 'instructor');
        $instructor = $qb->getQuery()->getResult();
        return $this->render('role/admin/user/userShowInstructor.html.twig', array(
            'users' => $instructor
        ));
    }

    /**
     * @Route("/userShowMentor", name="userShowMentor")
     */
    public function userShowMentor() {
        $repo = $this->getDoctrine()
            ->getRepository(User::class);
        $qb = $repo->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.name = :name')
            ->setParameter('name', 'mentor');
        $mentor = $qb->getQuery()->getResult();
        return $this->render('role/admin/user/userShowMentor.html.twig', array(
            'users' => $mentor
        ));
    }

    /**
     * @Route("/userShowAdmin", name="userShowAdmin")
     */
    public function userShowAdmin() {
        $repo = $this->getDoctrine()
            ->getRepository(User::class);
        $qb = $repo->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.name = :name')
            ->setParameter('name', 'admin');
        $admin = $qb->getQuery()->getResult();
        return $this->render('role/admin/user/userShowAdmin.html.twig', array(
            'users' => $admin
        ));
    }

    /**
     * @Route("/userShowStudent", name="userShowStudent")
     */
    public function userShowStudent() {
        $repo = $this->getDoctrine()
            ->getRepository(User::class);
        $qb = $repo->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.name = :name')
            ->setParameter('name', 'student');
        $student = $qb->getQuery()->getResult();
        return $this->render('role/admin/user/userShowStudent.html.twig', array(
            'users' => $student
        ));
    }

    /**
     * @Route("/create", name="create")
     */
    public function addNewUser(Request $request) {
        //create form for munipulate this user data
        $form = $this->createFormBuilder()
            ->add('username', TextType::class, array(
                'label' => 'NetID',
                'error_bubbling' => true
            ))
            ->add('firstName', TextType::class, array(
                'label' => 'First Name',
                'error_bubbling' => true
            ))
            ->add('lastName', TextType::class, array(
                'label' => 'Last Name',
                'error_bubbling' => true
            ))
            ->add('cardId', TextType::class, array(
                'label' => 'Card Id',
                'error_bubbling' => true,
                'required' => false
            ))
            ->add('scancode', TextType::class, array(
                'label' => 'Scan Code',
                'error_bubbling' => true,
                'required' => false
            ))
            ->add('roles', EntityType::class, array(
                // looks for choices from this entity
                'class' => Role::class,
                // uses the role.name property as the visible option sting
                'choice_label' => 'name',
                'multiple' => true
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'Submit'
            ))
            ->getForm();
        // ... perform saving action, the task is a doctrine entity
        $entityManager = $this->getDoctrine()->getManager();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //get the data from the form and update to the database
            $data = $form->getData();

            $user = new User($data['firstName'], $data['lastName'], $data['username']);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('admin_user_list');

        }
        //when user first time click the the create button from the userShowAll page
        return $this->render('role/admin/user/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/edit/{id}", name="edit")
     */
    public function editUser(Request $request, User $user) {
        $entityManager = $this->getDoctrine()->getManager();
        $userName = $user->__toString();

        $form = $this->createFormBuilder($user)
            ->add('username', TextType::class, array(
                'label' => 'NetID',
                'error_bubbling' => true
            ))
            ->add('firstName', TextType::class, array(
                'label' => 'First Name',
                'error_bubbling' => true
            ))
            ->add('lastName', TextType::class, array(
                'label' => 'Last Name',
                'error_bubbling' => true
            ))
            ->add('cardId', TextType::class, array(
                'label' => 'Card Id',
                'error_bubbling' => true,
                'required' => false
            ))
            ->add('scancode', TextType::class, array(
                'label' => 'Scan Code',
                'error_bubbling' => true,
                'required' => false
            ))
            ->add('roles', EntityType::class, array(
                // looks for choices from this entity
                'class' => Role::class,
                //show role name in the selected table
                'choice_label' => 'name',
                'multiple' => true
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'Save'
            ))
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            return $this->redirectToRoute('admin_user_list');

        }
        return $this->render('role/admin/user/edit.html.twig', array(
            'form' => $form->createView(),
            'deleteId' => $user->getId(),
            'userName' => $userName

        ));
    }

    /**
     * @Route("/delete", name="delete")
     */
    public function deleteUser(Request $request) {
        $id = $request->query->get('id');
        if (!$id) {
            throw $this->createNotFoundException(
                'No role found for id ' . $id
            );
        }
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->find($id);


        //Try-catch block to check if there are any Foreign Key Constraint Violations
        try {
            $entityManager->remove($user);
            $entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            //Displaying error message for foreign key constraint violation
            $this->addFlash('error', "The User has associated data and cannot be deleted.");
            return $this->redirectToRoute('admin_user_edit', array(
                'user' => $user,
                'id' => $id
            ));
        }

        // Get user list
        return $this->redirectToRoute('admin_user_list');
    }
}
