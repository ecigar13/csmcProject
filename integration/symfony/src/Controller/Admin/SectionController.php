<?php

namespace App\Controller\Admin;


use App\Entity\Misc\Semester;
use App\Entity\Misc\SemesterSeason;

use App\Entity\Course\Course;
use App\Entity\Course\Department;
use App\Entity\Course\Section;


use Doctrine\ORM\EntityRepository;
use App\Entity\User\User;


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

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

/**
 * @Route("/admin/section", name="admin_section_")
 */
class SectionController extends Controller {
    /**
     * @Route("/", name="list")
     */
    public function listSections() {

        $sections = $this->getDoctrine()
            ->getRepository(Section::class)
            ->findAll();

        return $this->render('role/admin/section/list.html.twig', array(
            'sections' => $sections,
        ));
    }

    /**
     * @Route("/create", name="create")
     */
    public function addNewSection(Request $request) {
        //
        // For only supported Course, Admin can create Section
        // TODO: order by Department Abbreviation (first) and Course Number (second)
        $form = $this->createFormBuilder()
            ->add('course', EntityType::class, array(
                // looks for choices from this entity
                'class' => Course::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.supported = :name')
                        ->setParameter('name', '1')
                        ->orderBy('c.number', 'ASC');
                },
                // uses the Course.name property as the visible option sting
                'choice_label' => function ($course) {
                    return $label = $course->getDepartment()->getAbbreviation() . ' ' . $course->getNumber();
                },
                'label' => 'Course Number',
            ))
            ->add('number', TextType::class, array(
                'label' => 'Section Number',
                'error_bubbling' => true,
            ))
            ->add('semester', EntityType::class, array(
                // looks for choices from this entity
                'class' => Semester::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->orderBy('s.year', 'DESC');
                },
                // uses the Semester.year property as the visible option sting
                'choice_label' => function ($semester) {
                    return $semester->getYear() . ' ' . $semester->getSeason();
                },
                'label' => 'Semester',
                'required' => false,
            ))
            ->add('instructor', EntityType::class, array(
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->join('u.roles', 'r')
                        ->where('r.name = :name')
                        ->setParameter('name', 'instructor')
                        ->orderBy('u.firstName', 'ASC');
                },
                'choice_label' => function ($instructor) {
                    return $instructor->getFirstName() . ' ' . $instructor->getLastName();
                },
                'label' => 'Instructor',
                'required' => false,
            ))
            ->add('teaching_assistants', EntityType::class, array(
                // looks for choices from this entity
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->join('u.roles', 'r')
                        ->where('r.name = :name')
                        ->setParameter('name', 'TA')
                        ->orderBy('u.firstName', 'ASC');
                },
                'choice_label' => function ($teaching_assistants) {
                    return $teaching_assistants->getFirstName() . ' ' . $teaching_assistants->getLastName();
                },
                'multiple' => true,
                'label' => 'Teaching Assistants',
                'required' => false,
            ))
            ->add('description', TextareaType::class, array(
                'label' => 'Admin Notes',
                'required' => false,
                'error_bubbling' => true,
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'Submit'
            ))
            ->getForm();


        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // $form->getData() holds the submitted values
            // but, the original `$Section` variable has also been updated
            $data = $form->getData();
            $section = new Section($data['course'], $data['number'], $data['semester'], $data['instructor']);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($section);
            $entityManager->flush();

            // If all the actions are successful, show Section list page;
            return $this->redirectToRoute('admin_section_list');
        }

        return $this->render('role/admin/section/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/edit/{id}", name="edit")
     */
    public function editSection(Request $request, Section $section) {

        // Here, uses 'GET' METHOD !!
        // $id = $request->query->get('id');

        $entityManager = $this->getDoctrine()->getManager();
        // $section = $entityManager->getRepository(section::class)->find($id);

        if (!$section) {
            throw $this->createNotFoundException(
                'Section not found'
            );
        }

        $sectionNumber = $section->getNumber();

        $form = $this->createFormBuilder($section)
            ->add('course', EntityType::class, array(
                // looks for choices from this entity
                'class' => Course::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.supported = :name')
                        ->setParameter('name', '1')
                        ->orderBy('c.number', 'ASC');
                },
                // uses the Course.name property as the visible option sting
                'choice_label' => function ($course) {
                    return $course->getDepartment()->getAbbreviation() . ' ' . $course->getNumber();
                },
                'label' => 'CourseNumber',
            ))
            ->add('number', TextType::class, array(
                'label' => 'section Number',
                'error_bubbling' => true,
            ))
            ->add('semester', EntityType::class, array(
                // looks for choices from this entity
                'class' => Semester::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->orderBy('s.year', 'DESC');
                },
                // uses the Semester.year property as the visible option sting
                'choice_label' => function ($semester) {
                    return $semester->getYear() . ' ' . $semester->getSeason();
                },
                'label' => 'Semester',
                'required' => false,
            ))
            ->add('instructors', EntityType::class, array(
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->join('u.roles', 'r')
                        ->where('r.name = :name')
                        ->setParameter('name', 'instructor')
                        ->orderBy('u.firstName', 'ASC');
                },
                'choice_label' => function ($instructor) {
                    return $instructor->getFirstName() . ' ' . $instructor->getLastName();
                },
                'label' => 'Instructor',
                'required' => false,
                'multiple' => true
            ))
            ->add('teaching_assistants', EntityType::class, array(
                // looks for choices from this entity
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->join('u.roles', 'r')
                        ->where('r.name = :name')
                        ->setParameter('name', 'TA')
                        ->orderBy('u.firstName', 'ASC');
                },
                'choice_label' => function ($teaching_assistants) {
                    return $teaching_assistants->getFirstName() . ' ' . $teaching_assistants->getLastName();
                    // 'choice_label' => 'name',
                },
                'multiple' => true,
                'label' => 'Teaching Assistants',
                'required' => false,
            ))
            ->add('description', TextareaType::class, array(
                'label' => 'Admin Notes',
                'required' => false,
                'error_bubbling' => true,
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'Save'
            ))
            ->getForm();


        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // We just need to UPDATE the section data, not Department Data.
            // UPDATED Value !!
            // $form->getData() holds the submitted values !!
            // but, the original `$section` variable has also been updated !!
            // $section = $form->getData();
            // JUST Flush section Data !
            $entityManager->flush();

            // If all the actions are successful, show section list page;
            return $this->redirectToRoute('admin_section_list');
        }

        return $this->render('role/admin/section/edit.html.twig', array(
            'form' => $form->createView(),
            'deleteId' => $section->getId(),
            'sectionNumber' => $sectionNumber,
        ));
    }

    /**
     * @Route("/delete", name="delete")
     */
    public function deleteSection(Request $request) {

        // Here, uses 'GET' protocol !!
        $id = $request->query->get('id');

        $entityManager = $this->getDoctrine()->getManager();
        $Section = $entityManager->getRepository(Section::class)->find($id);

        if (!$id) {
            throw $this->createNotFoundException(
                'No Section found for id ' . $id
            );
        }

        //Try-catch block to check if there are any Foreign Key Constraint Violations
        try {
            // Delete Section
            $entityManager->remove($Section);
            $entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            //Displaying error message for foreign key constraint violation
            $this->addFlash('error', "This Section has associated data and cannot be deleted.");
            return $this->redirectToRoute('admin_section_edit',
                array('id' => $id));
        }

        // If all the actions are successful, show Section list page;
        return $this->redirectToRoute('admin_section_list');
    }

    /**
     * @Route("/roster/{id}", name="roster")
     */
    public function rosterAction(Section $section) {
        return $this->render('role/admin/section/roster.html.twig', array(
            'section' => $section
        ));
    }
}
