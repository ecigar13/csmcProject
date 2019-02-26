<?php

namespace App\Controller\Entity\Course;

use App\Entity\Course\Section;
use App\Entity\User\Role;
use App\Entity\User\User;
use App\Form\Course\SectionType;
use App\Serializer\Converter\UserNameConverter;
use App\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class SectionController extends Controller {
    /**
     * @Route("/course/section", name="section")
     */
    public function sectionAction() {
        if ($this->isGranted('instructor') || $this->isGranted('teaching_assistant')) {
            $sections = $this->getDoctrine()
                ->getRepository(Section::class)
                ->findByInstructor($this->getUser());

            return $this->render('role/instructor/course/section.html.twig', array(
                'sections' => $sections
            ));
        } elseif ($this->isGranted('student')) {
            $sections = $this->getDoctrine()
                ->getRepository(Section::class)
                ->findAllByStudent($this->getUser());

            return $this->render('role/student/course/courses.html.twig', array(
                'sections' => $sections
            ));
        } else {
            throw $this->createAccessDeniedException();
        }
    }

    /**
     * @Route("/course/section/view/{id}", name="section_roster")
     */
    public function sectionRosterAction(Request $request, Section $section) {
        $this->denyAccessUnlessGranted(['instructor']);

        if (!$section) {
            $this->createNotFoundException('Section cannot be found.');
        } else {
            $form = $this->createFormBuilder()
                ->add('file', FileType::class)
                ->add('submit', SubmitType::class)
                ->getForm();

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();

                $roster = $this->parseRoster($data['file']);
                if ($roster) {
                    $new = 0;
                    foreach ($roster as $student) {
                        if ($section->getRoster()->contains($student)) {
                            continue;
                        }
                        $section->enroll($student);
                        $new++;
                    }
                    $this->addFlash('notice', 'Successfully added ' . $new . ' students');
                }

            }
            $this->getDoctrine()->getManager()->flush();

            return $this->render('role/instructor/course/section_roster.html.twig', array(
                'section' => $section,
                'roster' => Utilities::sortCollection($section->getRoster(), function ($first, $second) {
                    switch ($first->getLastName() <=> $second->getLastName()) {
                        case -1:
                            return -1;
                        case 1:
                            return 1;
                        case 0:
                            return $first->getFirstName() <=> $second->getFirstName();
                        default:
                            return 0;
                    }
                }),
                'form' => $form->createView()
            ));
        }
    }

    /**
     * @Route("/course/section/view/{id}/roster", name="section_roster_download")
     */
    public function sectionRosterDownloadAction(Request $request, Section $section) {
        $this->denyAccessUnlessGranted(['instructor']);

        if (!$section) {
            $this->createNotFoundException('Section cannot be found.');
        } else {
            $roster = $section->getRoster()->toArray();

            $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
            $normalizer = new ObjectNormalizer($classMetadataFactory, new UserNameConverter());
            $encoder = new CsvEncoder();
            $serializer = new Serializer(array($normalizer), array($encoder));
            $roster = $serializer->normalize($roster, null, array('groups' => array('roster')));
            $data = $serializer->serialize($roster, 'csv');

            $response = new Response();
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $section->getCourse()->getDepartment()->getAbbreviation() . $section->getCourse()->getNumber() . '.' .
                $section->getNumber() . '_' . 'roster.csv'
            );
            $response->headers->set('Content-disposition', $disposition);
            $response->headers->set('Content-type', 'text/csv');
            $response->setContent($data);

            return $response;
        }
    }

    // TODO replace with CsvSerialize->desersialize()

    /**
     * @param $file
     *
     * @return array
     */
    private function parseRoster($file) {
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle);
        $roster = array();
        foreach ($header as $index => $column) {
            $column = preg_replace("/([^a-z\s]+)/i", "", $column);
            $name = trim(strtolower($column));
            if ($name == 'first name' || $name == 'firstname' || $name == 'first_name') {
                $fn_index = $index;
            } elseif ($name == 'last name' || $name == 'lastname' || $name == 'last_name') {
                $ln_index = $index;
            } else {
                if ($name == 'netid' || $name == 'username') {
                    $un_index = $index;
                }
            }
        }

        if (!isset($fn_index) || !isset($ln_index) || !isset($un_index)) {
            //error
            $this->addFlash('warning', 'Error: expected columns "First Name", "Last Name", "Username"');
            return false;
        } else {
            $i = 1; // for counting rows, start at 1 to get correct row count
            $new = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $first_name = trim($row[$fn_index], '"');
                $last_name = trim($row[$ln_index], '"');
                $username = trim($row[$un_index], '"');

                //skip empty lines
                if (empty($first_name) && empty($last_name) && empty($username)) {
                    continue;
                }

                if (empty($first_name) || empty($last_name) || empty($username)) {
                    //error
                    $this->addFlash('warning', 'Error in row ' . $i . ', student not added');
                } else {
                    $user = $this->getDoctrine()
                        ->getRepository(User::class)
                        ->findOneByUsername($username);

                    if (!$user) {
                        $user = new User($first_name, $last_name, $username);
                        $user->addRole($this->getDoctrine()
                            ->getRepository(Role::class)
                            ->findOneByName('student'));
                        $this->getDoctrine()->getManager()->persist($user);

                    }
                    $roster[] = $user;
                    $new++;
                }
                $i++;
            }
            $this->getDoctrine()->getManager()->flush();
        }

        fclose($handle);
        return $roster;
    }
}