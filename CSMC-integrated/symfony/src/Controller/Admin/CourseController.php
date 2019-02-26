<?php

namespace App\Controller\Admin;

use App\Entity\Course\Course;
use App\Entity\Course\Department;

use App\Entity\Course\Section;
use App\Entity\Misc\Semester;
use App\Entity\User\Role;
use App\Entity\User\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Form\Extension\Core\Type\FileType;
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
use Doctrine\ORM\EntityRepository;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

/**
 * @Route("/admin/course", name="admin_course_")
 */
class CourseController extends Controller {

    /**
     * @Route("/", name="list")
     */
    public function listCourses() {

        $courses = $this->getDoctrine()
            ->getRepository(Course::class)
            ->findAll();

        $unsupportedCourses = $this->getDoctrine()->getRepository(Course::class)
            ->findBy(array('supported' => false,));
        $num = sizeof($unsupportedCourses);

        return $this->render('role/admin/course/list.html.twig', array(
            'courses' => $courses,
            'numUnsupported' => $num,
        ));

    }


    /**
     * @Route("/create", name="create")
     */
    public function addNewCourse(Request $request) {

        // just setup a fresh $course object (remove the dummy data)
        // $course = new Course();

        $form = $this->createFormBuilder()
            ->add('department', EntityType::class, array(
                // looks for choices from this entity
                'class' => Department::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.name', 'ASC');
                },
                // uses the Department.name property as the visible option sting
                'choice_label' => 'name',
            ))
            ->add('name', TextType::class, array(
                'label' => 'Course Name',
                'error_bubbling' => true,
            ))
            ->add('number', TextType::class, array(
                'label' => 'Course Number',
                'error_bubbling' => true,
            ))
            ->add('supported', CheckboxType::class, array(
                'label' => 'Is this Course supported',
                'error_bubbling' => true,
                'required' => false
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
            // but, the original `$course` variable has also been updated
            $data = $form->getData();

            $course = new Course($data['department'], $data['number'], $data['name'], $data['supported']);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($course);
            $entityManager->flush();

            // If all the actions are successful, show course list page;
            return $this->redirectToRoute('admin_course_list');

        }

        return $this->render('role/admin/course/add.html.twig', array(
            'form' => $form->createView(),
        ));


    }


    /**
     * @Route("/edit/{id}", name="edit")
     */
    public function editCourse(Request $request, Course $course) {

        // Here, uses 'GET' METHOD !!
        // $id = $request->query->get('id');
        //
        $entityManager = $this->getDoctrine()->getManager();
        // $course = $entityManager->getRepository(Course::class)->find($id);

        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        $courseName = $course->getName();

        $form = $this->createFormBuilder($course)
            ->add('department', EntityType::class, array(
                // looks for choices from this entity
                'class' => Department::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.name', 'ASC');
                },
                // uses the Department.name property as the visible option sting
                'choice_label' => 'name',
            ))
            ->add('number', TextType::class, array(
                'label' => 'Course Number',
                'error_bubbling' => true,
            ))
            ->add('name', TextType::class, array(
                'label' => 'Course Name',
                'error_bubbling' => true,
            ))
            ->add('supported', CheckboxType::class, array(
                'label' => 'Is this Course supported',
                'required' => false
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

            // We just need to UPDATE the Course data, not Department Data.
            // UPDATED Value !!
            // $form->getData() holds the submitted values !!
            // but, the original `$course` variable has also been updated !!
            // $course = $form->getData();
            // JUST Flush Course Data !
            $entityManager->flush();


            // If all the actions are successful, show course list page;
            return $this->redirectToRoute('admin_course_list');

        }

        return $this->render('role/admin/course/edit.html.twig', array(
            'form' => $form->createView(),
            'deleteId' => $course->getId(),
            'courseName' => $courseName,
        ));

    }


    /**
     * @Route("/delete", name="delete")
     */
    public function deleteCourse(Request $request) {

        // Here, uses 'GET' protocol !!
        $id = $request->query->get('id');

        $entityManager = $this->getDoctrine()->getManager();
        $course = $entityManager->getRepository(Course::class)->find($id);

        if (!$id) {
            throw $this->createNotFoundException(
                'No course found for id ' . $id
            );
        }

        //Try-catch block to check if there are any Foreign Key Constraint Violations
        try {
            // Delete Course
            $entityManager->remove($course);
            $entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            //Displaying error message for foreign key constraint violation
            $this->addFlash('error', "This Course has associated data(course number) with Section and cannot be deleted.");
            return $this->redirectToRoute('admin_course_edit',
                array('id' => $id));
        }

        // If all the actions are successful, show course list page;
        return $this->redirectToRoute('admin_course_list');

    }

    /**
     * @Route("/roster/upload", name="roster_upload")
     */
    public function courseRosterUploadAction(Request $request) {
        $form = $this->createFormBuilder()
            ->add('roster', FileType::class)
            ->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $roster_file = $form->getData()['roster'];
            $this->parseBulkRoster($roster_file);
        }

        return $this->render('role/admin/course/roster_upload.html.twig', array(
            'form' => $form->createView()
        ));
    }

    private function parseBulkRoster($file) {
        $handle = fopen($file, 'r');
        $em = $this->getDoctrine()->getManager();

        fgetcsv($handle);
        /* Check header names */
        $header = fgetcsv($handle);

        $error = false;
        if ($header[6] != 'Catalog Nbr') {
            $this->addFlash('warning', 'Missing "Catalog" column, found: "' . $header[6] . '"');
            $error = true;
        }

        if ($header[7] != 'Section') {
            $this->addFlash('warning', 'Missing "Section" column, found: "' . $header[7] . '"');
            $error = true;
        }

        // if ($header[4] != 'Last') {
        //     $this->addFlash('warning', 'Missing "Last" column, found: "' . $header[4] . '"');
        //     $error = true;
        // }
        //
        // if ($header[5] != 'First Name') {
        //     $this->addFlash('warning', 'Missing "First Name" column, found: "' . $header[5] . '"');
        //     $error = true;
        // }
        if ($header[2] != 'Name') {
            $this->addFlash('warning', 'Missing "Name" column, found: "' . $header[2] . '"');
            $error = true;
        }

        if ($header[3] != 'Email') {
            $this->addFlash('warning', 'Missing "Email" column, found: "' . $header[3] . '"');
            $error = true;
        }

        if ($error) {
            return false;
        }

        /* Read students */
        $rn = 2; // row number, for reporting errors
        $section_count = 0;
        $new_section_count = 0;
        $student_count = 0;
        $courses = array();
        $students = array();
        $semester = $this->getDoctrine()
            ->getRepository(Semester::class)
            ->findActive();
        while (($row = fgetcsv($handle)) !== false) {
            $course_number = trim($row[6], '"');
            $section_number = trim($row[7], '"');
            // $last_name = trim($row[2], '"');
            // $first_name = trim($row[3], '"');
            $name = trim($row[2], '"');
            $last_name = trim(preg_split('/,/',$name)[0]);
            $first_name = trim(preg_split('/,/',$name)[1]);
            $netid = preg_replace("/@utdallas.edu/i", "", trim($row[3], '"'));

            /* look for course, if not found error */
            if (!array_key_exists($course_number, $courses)) {
                $course = $this->getDoctrine()
                    ->getRepository(Course::class)
                    ->findOneByNumber($course_number);

                if (!$course) {
                    $this->addFlash('warning', 'Course number ' . $course_number . ' not found, skipping row ' . $rn);
                    $rn++;
                    continue;
                }

                $courses[$course_number] = array(
                    'course' => $course,
                    'sections' => array()
                );
            }
            $course = $courses[$course_number]['course'];
            /* look for section, if not found create it */
            if (!array_key_exists($section_number, $courses[$course_number]['sections'])) {
                $section = $this->getDoctrine()
                    ->getRepository(Section::class)
                    ->findOneBy(array(
                        'number' => $section_number,
                        'course' => $course,
                        'semester' => $semester
                    ));

                if (!$section) {
                    $section = new Section($course, $section_number, $semester, null);
                    $em->persist($section);
                    $new_section_count++;
                }

                $courses[$course_number]['sections'][$section_number] = array(
                    'section' => $section,
                    'students' => array()
                );
                $section_count++;
            }
            $section = $courses[$course_number]['sections'][$section_number]['section'];

            /* look for student, if not found create, if not in section add */
            if (!array_key_exists($netid, $students)) {
                $student = $this->getDoctrine()
                    ->getRepository(User::class)
                    ->findOneByUsername($netid);

                // TODO maybe fill in first/last name if not found

                if (!$student) {
                    $student = new User($first_name, $last_name, $netid);
                    $role = $this->getDoctrine()->getRepository(Role::class)->findOneByName("student");
                    $student->addRole($role);
                    $em->persist($student);
                }

                $students[$netid] = $student;
            }

            $student = $students[$netid];

            if (!$section->hasStudent($student)) {
                $section->enroll($student);
                $student_count++;
            }

            $rn++;
        }
        $em->flush();
        $this->addFlash('notice', 'Rosters uploaded for ' . $section_count . ' sections (' . $new_section_count . ' new) in ' . count($courses) . ' courses. '
                                  . $student_count . ' students added.');

        return true;
    }
}