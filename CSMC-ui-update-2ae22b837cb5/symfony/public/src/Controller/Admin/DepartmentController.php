<?php
/**
 * Created by IntelliJ IDEA.
 * User: Prince
 * Date: 3/2/2018
 * Time: 10:40 AM
 */

namespace App\Controller\Admin;

use App\Entity\Course\Course;
use App\Entity\Course\Department;
use App\Entity\User\Role;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * @Route("/admin/department", name="admin_department_")
 */
class DepartmentController extends Controller {
    /**
     * @Route("/create", name="create")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function AddDepartmentAction(Request $request) {
        $department = new Department();
        $department->setLastModifiedOn(new DateType());
        $department->setCreatedOn(new DateType());
        $form = $this->createFormBuilder($department)
            ->add('name', null, array('error_bubbling' => true))
            ->add('abbreviation', null, array('error_bubbling' => true))
            ->add("adminNotes", TextareaType::class, array('required' => false))
            ->add('submit', SubmitType::class)
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($department);
            $entityManager->flush();
            return $this->redirectToRoute('admin_department_list');
        }
        return $this->render('role/admin/department/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/", name="list")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function ListDepartmentsFunction(Request $request) {
        $departments = $this->getDoctrine()
            ->getRepository(Department::class)
            ->findAll();
        return $this->render('role/admin/department/list.html.twig', array('departments' => $departments));
    }

    /**
     * @Route("/edit/{id}", name="edit")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function EditDepartmentFunction(Request $request, Department $department) {
        // $delete_id = $request->query->get('id');
        $entityManager = $this->getDoctrine()->getManager();
        // $department = $entityManager->getRepository(Department::class)->find($delete_id);
        //Getting the name for the department being edited
        $departmentName = $department->getName();
        if (!$department) {
            throw $this->createNotFoundException(
                'Department not found!'
            );
        }
        $form = $this->createFormBuilder($department)
            ->add('name', null, array('error_bubbling' => true))
            ->add('abbreviation', null, array('error_bubbling' => true))
            ->add("adminNotes", TextareaType::class, array('required' => false))
            ->add('submit', SubmitType::class, array('label' => 'Save'))
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $department->setLastModifiedOn(new DateType());
            $entityManager->flush();
            $departments = $this->getDoctrine()
                ->getRepository(Department::class)
                ->findAll();
            return $this->redirectToRoute('admin_department_list', array('departments' => $departments));
        }
        return $this->render('role/admin/department/edit.html.twig', array(
            'form' => $form->createView(),
            'deleteId' => $department->getId(),
            'departmentName' => $departmentName
        ));
    }

    /**
     * @Route("/delete", name="delete")
     */
    public function deleteDepartmentFunction(Request $request) {

        $id = $request->query->get('id');
        $entityManager = $this->getDoctrine()->getManager();
        $department = $entityManager->getRepository(Department::class)->find($id);
        if (!$id) {
            throw $this->createNotFoundException(
                'No Department found for id ' . $id
            );
        }

        //Try-catch block to check if there are any Foreign Key Constraint Violations
        try {
            $entityManager->remove($department);
            $entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            //Displaying error message for foreign key constraint violation
            $this->addFlash('error', "The Department has associated data and cannot be deleted.");
            return $this->redirectToRoute('admin_department_edit', array(
                'department' => $department,
                'id' => $id
            ));
        }

        // Get department list
        $departments = $this->getDoctrine()
            ->getRepository(Department::class)
            ->findAll();
        return $this->redirectToRoute('admin_department_list', array('departments' => $departments));
    }
}
