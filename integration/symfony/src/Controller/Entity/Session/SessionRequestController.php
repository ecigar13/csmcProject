<?php

namespace App\Controller\Entity\Session;

use App\DataTransferObject\FileData;
use App\Entity\Misc\File;
use App\Entity\Session\RequestStatus;
use App\Form\RequestType;
use App\Entity\Course\Section;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
// use App\Entity\File\File;
use Doctrine\ORM\EntityRepository;
use App\Entity\File\Directory;
use App\Entity\File\Link;
use App\Entity\User\User;
use App\Entity\User\Role;
use App\Entity\Course\Course;
use App\Entity\Course\Department;

class SessionRequestController extends Controller {
    /**
     * @Route("/session/request", name="session_request")
     */
    public function sessionRequestAction() {
        if ($this->isGranted('instructor')) {
            $requests = $this->getDoctrine()
                ->getRepository(\App\Entity\Session\Request::class)
                ->findByUser($this->getUser());
        } else {
            throw $this->createAccessDeniedException();
        }

        return $this->render('role/instructor/session/request/requests.html.twig', array(
            'requests' => $requests
        ));
    }

    /**
     * @Route("/session/request/view/{id}", name="session_request_view")
     */
    public function sessionRequestViewAction(Request $request, $id) {
        $this->denyAccessUnlessGranted([
            'instructor'
        ]);

        $session_request = $this->getDoctrine()->getRepository('App\Entity\Session\Request')->find($id);
        if (!$session_request) {
            throw new $this->createNotFoundException('Request not found!');
        } else {
            if ($session_request->getUser() != $this->getUser()) {
                throw $this->createAccessDeniedException();
            }

            return $this->render('role/instructor/session/request/request.html.twig', array(
                'request' => $session_request
            ));
        }
    }

    /**
     * @Route("/session/request/create", name="session_request_create")
     */
    public function sessionRequestCreateAction(Request $request) {
        $this->denyAccessUnlessGranted([
            'instructor'
        ]);

        $form = $this->createForm(RequestType::class, array(), array('user' => $this->getUser()));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $session_request_data = $form->getData();

            $session_request = new \App\Entity\Session\Request($session_request_data['type'],
                $this->getUser(),
                $session_request_data['topic'],
                $session_request_data['startDate'],
                $session_request_data['endDate'],
                $session_request_data['studentInstructions'],
                $session_request_data['sections']->toArray());
            
            
            $sessionFolder = $this->createDirectory($session_request_data['topic'],$session_request_data['sections']->toArray()[0],$this->getUser());
            foreach($session_request_data['files'] as $file) {
                $file_data = new FileData($file, $this->getUser(),$sessionFolder->getPath() . '/' . $file->getClientOriginalName());
                $session_request->attachFile($file_data, $em, $metadata=[]);
            }

            foreach($session_request->getFiles() as $file){
                    $file->setParent($sessionFolder);
                    $em->persist($file);

            }
            $session_request->setDirectory($sessionFolder);


            $em->persist($session_request);

            $em->flush();


            $this->addFlash('notice', 'Successfully requested session!');

            return $this->redirectToRoute('session_request');
        }

        return $this->render('role/instructor/session/request/form.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * Automatic Folder Creation.
     *
     *
     * @param $session
     * @param $sections
     * @param $user
     *
     * @return Directory
     */
    public function createDirectory(string $session,Section $section ,User $user){
        $user=$this->getUser();
        $netId = $user->getUsername();
        $firstName = $user->getFirstName();
        $lastName = $user->getLastName();
        $entityManager = $this->getDoctrine()->getManager();
        $userClass = $this->getDoctrine()->getRepository(User::class);
        $roleClass = $this->getDoctrine()->getRepository(Role::class);
        $UserName = $this->getParameter('file_manager')['superUser'];
        $admin = $userClass->findOneBy(array('username' => $UserName));
        $Instructor = $roleClass->findOneByName('instructor');
        $Mentor = $roleClass->findOneByName('mentor');
        $Admin = $roleClass->findOneByName('admin');
        $Student = $roleClass->findOneByName('student');
        $Developer = $roleClass->findOneByName('developer');
        $directoryClass = $this->getDoctrine()->getRepository(Directory::class);
        try{
            // Create root folder it's Not there
            $root=$directoryClass->findOneBy(array('path' => '/root'));
            if(!$root){
                $root  = new Directory('root',$admin,'/root',);        
                $entityManager->persist($root);
                $entityManager->flush();
            }
            
            $instructorFolderPath = '/root/Instructors';
            $instructorFolder=$directoryClass->findOneBy(array('path' => $instructorFolderPath ));
            if(!$instructorFolder){
                $instructorFolder  = new Directory('Instructors',$admin,$instructorFolderPath);
                $instructorFolder->setParent($root);
                $instructorFolder->addRole($Instructor);
                $instructorFolder->addRole($Admin);
                $instructorFolder->addRole($Mentor);
                $instructorFolder->addRole($Developer);
                $entityManager->persist($instructorFolder);
                $entityManager->flush();
            }
            $instructorName = $netId. '_' .$lastName;
            $nameFolderPath = $instructorFolderPath . '/' . $instructorName;
            $nameFolder = $directoryClass->findOneBy(array('path' => $nameFolderPath));
            if(!$nameFolder){
                $nameFolder = new Directory($instructorName,$user,$nameFolderPath);
                $nameFolder->setParent($instructorFolder);
                $nameFolder->addUser($user);
                $nameFolder->addRole($Mentor);
                $nameFolder->addRole($Developer);
                $nameFolder->addRole($Admin);
                $entityManager->persist($nameFolder);
                $entityManager->flush();
            }

            $seasonName=$section->getSemester()->getSeason(). '_' . $section->getSemester()->getYear();
            $seasonPath= $nameFolderPath . '/' .  $seasonName;
            $season = $directoryClass->findOneBy(array('path' => $seasonPath));
            if(!$season){
                $season = new Directory($seasonName,$admin,$seasonPath);
                $season->setParent($nameFolder);
                $season->addUser($user);
                $season->addRole($Mentor);
                $season->addRole($Developer);
                $season->addRole($Admin);
                $entityManager->persist($season);
                $entityManager->flush();
            }
            $sectionName = $section->getCourse()->getDepartment()->getAbbreviation(). '_' . $section->getCourse()->getNumber();
            $sectionPath = $seasonPath . '/' .  $sectionName;
            $sectionFolder = $directoryClass->findOneBy(array('path' => $sectionPath));
            if(!$sectionFolder){
                $sectionFolder = new Directory( $sectionName,$admin,$sectionPath);
                $sectionFolder->setParent($season);
                $sectionFolder->addUser($user);
                $sectionFolder->addRole($Mentor);
                $sectionFolder->addRole($Developer);
                $sectionFolder->addRole($Admin);
                $entityManager->persist($sectionFolder);
                $entityManager->flush();
            }

            $sessionName=$session;
            $sessionPath=$sectionPath . '/' . $sessionName;
            $sessionFolder=$directoryClass->findOneBy(array('path' => $sessionPath));
            $i = 1;
            while($sessionFolder) {
                $sessionName = "{$sessionName}({$i})";
                $sessionPath= $sectionPath  . '/' .  $sessionName;
                $sessionFolder=$directoryClass->findOneBy(array('path' => $sessionPath));
                ++$i;
            } 
            $sessionFolder = new Directory($sessionName,$user,$sessionPath);
            $sessionFolder->setParent($sectionFolder);
            $sessionFolder->addRole($Mentor);
            $sessionFolder->addRole($Developer);
            $sessionFolder->addRole($Admin);
            $sessionFolder->addUser($user);
            $entityManager->persist($sessionFolder);
            $entityManager->flush();

        }
        catch (IOExceptionInterface $e) {
            return null;
        }

            
        return $sessionFolder;
    }

    /**
     * @Route("/session/request/edit/{id}", name="session_request_edit")
     */
    public function sessionRequestEdit(Request $request, \App\Entity\Session\Request $session_request) {
        $this->denyAccessUnlessGranted([
            'instructor'
        ]);

        $data = array(
            'topic' => $session_request->getTopic(),
            'startDate' => $session_request->getStartDate(),
            'endDate' => $session_request->getEndDate(),
            'studentInstructions' => $session_request->getStudentInstructions(),
            'sections' => $session_request->getSections(),
            'uploadedFiles' => $session_request->getFiles()
        );

        $form = $this->createForm(RequestType::class, $data, array('user' => $session_request->getUser()));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $session_request_data = $form->getData();
            $em = $this->getDoctrine()->getManager();

            $session_request->update($session_request_data['type'],
                $session_request_data['topic'],
                $session_request_data['startDate'],
                $session_request_data['endDate'],
                $session_request_data['studentInstructions'],
                $session_request_data['sections']->toArray());

            $sessionFolder = $session_request->getDirectory();
            foreach ($session_request_data['files'] as $file) {
                $file_data = new FileData($file, $this->getUser(),$sessionFolder->getPath() . '/' . $file->getClientOriginalName());
                $session_request->attachFile($file_data, $em,$metadata=[]);
            }

            foreach($session_request->getFiles() as $file){
                    $file->setParent($sessionFolder);
                    $em->persist($file);

            }
            $session_request->setDirectory($sessionFolder);


            $em->persist($session_request);

            $em->flush();

            $this->addFlash('notice', 'Successfully requested session!');


            return $this->redirectToRoute('session_request');
        }

        return $this->render('role/instructor/session/request/form.html.twig', array(
            'form' => $form->createView()
        ));
    }
}
