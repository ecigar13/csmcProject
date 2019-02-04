<?php

namespace App\Controller\Admin;

use App\Entity\Misc\Semester;
use App\Entity\Misc\SemesterSeason;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
 * @Route("/admin/semester", name="admin_semester_")
 */
class SemesterController extends Controller {
    /**
     * @Route("/", name="list")
     */
    public function semesterShowAll() {
        $semesters = $this->getDoctrine()
            ->getRepository(Semester::class)
            ->findAll();

        return $this->render('role/admin/semester/list.html.twig', array(
            'semesters' => $semesters,
        ));
    }

    /**
     * @Route("/create", name="create")
     */
    public function addSemester(Request $request) {
        $semester = new Semester();

        $form = $this->createFormBuilder($semester)
            ->add('season', EntityType::class, array(
                'class' => SemesterSeason::class,
                'choice_label' => 'name',
                'placeholder' => 'Select Season',
                'error_bubbling' => true
            ))
            ->add('year', TextType::class, array(
                'label' => 'Year',
                'error_bubbling' => true
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
            ->add('active', CheckboxType::class, array(
                'label' => 'Active',
                'required' => false
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Submit'
            ))
            ->getForm();

        $entityManager = $this->getDoctrine()->getManager();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $semester = $form->getData();

            $entityManager->persist($semester);
            $entityManager->flush();

            $semester = $this->getDoctrine()
                ->getRepository(Semester::class)
                ->findAll();
            return $this->render('role/admin/semester/list.html.twig', array('semester' => $semester));
        }

        return $this->render('role/admin/semester/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/edit", name="edit")
     */
    public function semesterEdit(Request $request) {
        $id = $request->query->get('id');

        $entityManager = $this->getDoctrine()->getManager();
        $semester = $entityManager->getRepository(Semester::class)->find($id);

        if (!$id) {
            throw $this->createNotFoundException(
                'No semester found for id ' . $id
            );
        }

        $form = $this->createFormBuilder($semester)
            ->add('season', EntityType::class, array(
                'class' => SemesterSeason::class,
                'choice_label' => 'name',
                'placeholder' => 'Select Season'
            ))
            ->add('year', TextType::class, array(
                'label' => 'Year'
            ))
            ->add('startDate', DateType::class, array(
                'label' => 'Start Date',
                'html5' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => 'yyyy/mm/dd'
            ))
            ->add('endDate', DateType::class, array(
                'label' => 'Start Date',
                'html5' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => 'yyyy/mm/dd'
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

            $semester = $this->getDoctrine()
                ->getRepository(Semester::class)
                ->findAll();

            return $this->render('role/admin/semester/list.html.twig');

        }

        return $this->render('role/admin/semester/edit.html.twig', array(
            'form' => $form->createView(),
            'deleteId' => $id,
        ));
    }

    /**
     * @Route("/delete", name="delete")
     */
    public function deleteSemester(Request $request) {
        $id = $request->query->get('id');

        $entityManager = $this->getDoctrine()->getManager();
        $semester = $entityManager->getRepository(Semester::class)->find($id);

        if (!$id) {
            throw $this->createNotFoundException(
                'No Semester found for id ' . $id
            );
        }

        $entityManager->remove($semester);

        $entityManager->flush();

        $semester = $this->getDoctrine()
            ->getRepository(Semester::class)
            ->findAll();

        return $this->render('role/admin/semester/list.html.twig', array('semester' => $semester));
    }
}