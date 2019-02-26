<?php

namespace App\Controller;

use App\DataTransferObject\FileData;
use App\Entity\Course\Department;
use App\Entity\File\File;
use App\Entity\Occurrence\AbsenceOccurrence;
use App\Entity\Occurrence\BehaviorOccurrence;
use App\Entity\Occurrence\CumulativeTardinessOccurrence;
use App\Entity\Occurrence\Occurrence;
use App\Entity\Occurrence\OccurrenceType;
use App\Entity\Penalty\CourseOfAction;
use App\Entity\User\Info\ProfileModificationRequest;
use App\Entity\User\User;
use App\Entity\User\Info\Specialty;
use App\Entity\Misc\Subject;
use App\Twig\OccurrenceExtension;
use App\Utils\AttendancePenaltyPersistenceManager;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

/**
 * @Route("/admin", name="admin_")
 */
class AdminController extends Controller
{
    /**
     * Retrieves associated EntityManager.
     *
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getEntityManager()
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * @Route("/", name="home")
     * @param Request $request
     *
     * @return Response
     */
    public function homeAction()
    {
        $session = $this->get('session');

        $successMessage = null;
        // get message if there is one
        foreach ($session->getFlashBag()->get('success', array()) as $message) {
            $successMessage = $message;
        }

        return $this->render('role/admin/base.html.twig', array(
            'successMessage' => $successMessage
        ));
    }

    /**
     * @Route("/cropper", name="cropper")
     *
     * @return Response
     */
    public function cropperAction() {
        $mentors = $this->getDoctrine()
            ->getRepository(User::class)
            ->findByRole('mentor');

        return $this->render('role/admin/cropper.html.twig', array(
            'mentors' => $mentors
        ));
    }

    /**
     * @Route("/ajax/image_upload", name="image_upload")
     *
     * @param Request $request
     * @return Response
     */
    public function mentorFileUpload(Request $request) {
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedException(array('JSON'));
        }

        $file = new FileData();
        $file->file = $request->files->get('file');
        $crop = $request->request->get('crop');
        $canvas = $request->request->get('canvas');
        $image = $request->request->get('image');

        $this->get('logger')->debug($crop);

        $em = $this->getDoctrine()->getManager();

        $file = File::fromUploadData($file, $em, array(
            'crop' => $crop,
            'canvas' => $canvas,
            'image' => $image
        ));

        $em->persist($file);

        $user_id = $request->request->get('user');
        $user = $this->getDoctrine()->getRepository(User::class)->find($user_id);

        $user->updateProfilePicture($file);

        $em->flush();

        return new Response('success', 200);
    }

    /**
     * @Route("/ajax/count_pending_mod_requests", name="count_pending_mod_requests")
     *
     * @param Request $request
     * @return Response
     */
    public function countPendingModRequests(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedException(array());
        }

        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository(ProfileModificationRequest::class);
        $qb = $repository->createQueryBuilder('r');

        $qb->select($qb->expr()->count('r.id'));

        $numPendingRequests = $qb->getQuery()->getSingleScalarResult();

        return new Response($numPendingRequests, 200);
    }

    /**
     * @Route("/ajax/count_pending_occurrences", name="count_pending_occurrences")
     *
     * @param Request $request
     * @return Response
     */
    public function countPendingOccurrences(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedException(array());
        }

        $numPendingOccurrences = $this->getDoctrine()->getManager()
            ->getRepository(Occurrence::class)->getPendingOccurrencesCount();

        return new Response($numPendingOccurrences, 200);
    }

    /**
     * @Route("/ajax/get_occurrence_details", name="ajax_get_occurrence_details")
     *
     * @param Request $request
     * @return Response
     */
    public function getOccurrenceDetails(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedException(array());
        }

        $jsonData = array();

        $occurrenceId = $request->request->get('occurrenceId');
        $occurrenceExtension = new OccurrenceExtension();
        $occurrence = $this->getDoctrine()->getRepository(Occurrence::class)->findOneBy(array('id' => $occurrenceId));

        $temp = array('id' => $occurrence->getId(),
            'status' => $occurrence->getStatus(),
            'subject' => $occurrence->getSubject()->getPreferredName(),
            'submitter' => $occurrenceExtension->submitter($occurrence),
            'creationDate' => $occurrenceExtension->date($occurrence),
            'points' => $occurrenceExtension->points($occurrence),
            'adminNotes' => $occurrence->getAdminNotes(),
            'description' => $occurrenceExtension->description($occurrence),
            'type' => $occurrenceExtension->type($occurrence),
            'isBehaviorOccurrence' => $occurrence instanceof BehaviorOccurrence ? true : false,
            'isCumulativeOccurrence' => $occurrence instanceof CumulativeTardinessOccurrence ? true : false,
            'isAbsenceOccurrence' => $occurrence instanceof AbsenceOccurrence ? true : false
        );
        $jsonData[0] = $temp;

        $occurrenceTypes = $this->getEntityManager()->getRepository(OccurrenceType::class)->findAll();
        $occurrenceTypes = array_map(function ($occurrenceType) {
            return array(
                'description' => $occurrenceType->getDescription(),
                'defaultPoints' => $occurrenceType->getDefaultPoints()
            );
        }, $occurrenceTypes);
        $jsonData[1] = $occurrenceTypes;

        if ($occurrence instanceof CumulativeTardinessOccurrence) {
            $cumulativeStartDate = $occurrence->getPeriodStart();
            $cumulativeEndDate = $occurrence->getPeriodEnd();
            $accumulatedOccurrences = $occurrence->getAccumulatedOccurrences();
            $occArray = array();
            $index = 0;
            foreach ($accumulatedOccurrences as $occ) {
                $tempOcc = array(
                    'tardinessDate' => $occ->getCreationDate(),
                    'tardinessMinutes' => $occ->getTardinessMinutes());
                $occArray[$index++] = $tempOcc;
            }
            $cumulativeOccurrence = array('startDate' => $cumulativeStartDate,
                'endDate' => $cumulativeEndDate,
                'cumulativeOccurrence' => $occArray);
            $jsonData[2] = $cumulativeOccurrence;
        }


        return new JsonResponse($jsonData);
    }

    /**
     * @Route("/occurrence_summary", name="view_occurrence_summary")
     *
     * @param Request $request
     * @return Response
     */
    public function viewMentorSummaryOccurrences(Request $request)
    {
        $pendingOccurrences = null;
        $closedOccurrences = null;

        $requestedUser = null;

        $userId = $request->query->get('user');
        $foundMentor = false;
        if ($userId) {
            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(array('username' => $userId));
            if ($user && $user->hasRole('mentor')) {
                $foundMentor = true;
                $requestedUser = $user;
                $pendingOccurrences = $this->getDoctrine()->getRepository(Occurrence::class)
                    ->findPendingOccurrencesForDisplaying($user);
                $closedOccurrences = $this->getDoctrine()->getRepository(Occurrence::class)
                    ->findClosedOccurrencesForDisplaying($user);
            }
        }

        if (!$foundMentor) {
            $pendingOccurrences = $this->getDoctrine()->getRepository(Occurrence::class)
                ->findPendingOccurrencesForDisplaying();
            $closedOccurrences = $this->getDoctrine()->getRepository(Occurrence::class)
                ->findClosedOccurrencesForDisplaying();
        }

        $occurrenceTypes = $this->getEntityManager()->getRepository(OccurrenceType::class)->findAll();
        $occurrenceTypes = array_map(function ($occurrenceType) {
            return array(
                'description' => $occurrenceType->getDescription(),
                'defaultPoints' => $occurrenceType->getDefaultPoints()
            );
        }, $occurrenceTypes);

        $penaltyManager = AttendancePenaltyPersistenceManager::loadModel($this->getDoctrine()->getManager());
        $unjustifiedAbsencePenalties = $penaltyManager->getUnjustifiedAbsenceWithNoticePenalties();
        $justifiedAbsencePenalties = $penaltyManager->getJustifiedAbsenceWithNoticePenalties();

        $unjustifiedAbsencePenalties = array_map(function ($penalty) {
            return array(
                'hoursBefore' => $penalty->getHoursBefore(),
                'penaltyAmount' => $penalty->getPenaltyAmount()
            );
        }, $unjustifiedAbsencePenalties);

        $justifiedAbsencePenalties = array_map(function ($penalty) {
            return array(
                'hoursBefore' => $penalty->getHoursBefore(),
                'penaltyAmount' => $penalty->getPenaltyAmount()
            );
        }, $justifiedAbsencePenalties);

        $noNoticePenaltyAmount = $penaltyManager->getAbsenceWithoutNoticePenalty();
        if ($noNoticePenaltyAmount) {
            $noNoticePenaltyAmount = $noNoticePenaltyAmount->getPenaltyAmount();
        }

        $noticeAmounts = [];

        /** @var AbsenceOccurrence[] $absenceOccurrences */
        $absenceOccurrences = $this->getDoctrine()->getRepository(AbsenceOccurrence::class)->findAll();

        foreach ($absenceOccurrences as $absenceOccurrence) {
            $noticeAmounts[$absenceOccurrence->getId()] = $absenceOccurrence->getHoursNotice();
        }

        return $this->render('role/admin/occurrences.html.twig', array(
            'pendingOccurrences' => $pendingOccurrences,
            'closedOccurrences' => $closedOccurrences,
            'occurrenceTypes' => json_encode($occurrenceTypes),
            'unjustifiedPenalties' => json_encode($unjustifiedAbsencePenalties),
            'justifiedPenalties' => json_encode($justifiedAbsencePenalties),
            'noNoticePenaltyAmount' => $noNoticePenaltyAmount,
            'noticeAmounts' => json_encode($noticeAmounts),
            'requestedUser' => $requestedUser
        ));
    }

    /**
     * @Route("/ajax/update_occurrence", name="update_occurrence")
     * @throws \Doctrine\ORM\ORMException
     */
    public function saveOccurrenceAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedException(array());
        }

        $occurrenceId = $request->request->get('id');
        $points = $request->request->get('points');
        $type = $request->request->get('type');
        $adminNotes = $request->request->get('adminNotes');
        $status = $request->request->get('status');

        $occurrence = $this->getDoctrine()->getRepository(Occurrence::class)->find($occurrenceId);

        if (!$occurrence) {
            return new Response('Invalid data', 400);
        }

        if (!is_null($points)) {
            $occurrence->setPoints($points);
        }

        if (!is_null($type)) {
            if ($occurrence instanceof BehaviorOccurrence) {
                $occurrence->setType($type);
            } else if ($occurrence instanceof AbsenceOccurrence) {
                $occurrence->setJustified($type === "Absence (Excused)");
            }
        }

        if (!is_null($adminNotes)) {
            $occurrence->setAdminNotes($adminNotes);
        }

        if ($status === Occurrence::STATUS_REJECTED || $status === Occurrence::STATUS_APPROVED) {
            $occurrence->setStatus($status);
        }

        $em = $this->getEntityManager();

        $em->persist($occurrence);
        $em->flush();

        return new Response('Updated occurrence successfully!', 200);
    }

    /**
     * @Route("/view_mentor_summary", name="view_mentor_summary")
     *
     * @return Response
     */
    public function viewMentorSummary()
    {
        $mentors = $this->getDoctrine()->getRepository(User::class)->findMentorsForSummaryPage();
        $mentorIdsWithOccurrence = $this->getDoctrine()->getRepository(User::class)->findMentorIdsWithPendingOccurrences();
        $coursesOfAction = $this->getDoctrine()->getRepository(CourseOfAction::class)->findAll();

        $negativeThresholds = array();
        $positiveThresholds = array();
        foreach($coursesOfAction as $courseOfAction)
        {
            if($courseOfAction->getThreshold() >= 0)
            {
                array_push($positiveThresholds,$courseOfAction);
            }
            else{
                array_push($negativeThresholds,$courseOfAction);
            }
        }
        /**
         * sort negative thresholds in ascending order
         */
        usort($negativeThresholds,function($a, $b)
        {
            return $a->getThreshold() > $b->getThreshold() ? 1 : -1;
        });
        /**
         * sort positive thresholds in descending order
         */
        usort($positiveThresholds,function($a, $b)
        {
            return $b->getThreshold() > $a->getThreshold() ? 1 : -1;
        });

        $coursesOfActionForMentors = array();
        $mentorIds = array();
        foreach ($mentors as $mentor)
        {
            array_push($mentorIds,$mentor->getId());
            if($mentor->getTotalOccurrencePoints() < 0) {
                $courseOfAction = $this->getCourseOfActionForMentor($mentor->getTotalOccurrencePoints(), $negativeThresholds,true);
            }else{
                $courseOfAction = $this->getCourseOfActionForMentor($mentor->getTotalOccurrencePoints(), $positiveThresholds,false);
            }
            array_push($coursesOfActionForMentors,$courseOfAction);
        }
        /**
         * creates a map of mentor ids and respective course of action
         */
        $coursesOfActionForMentors = array_combine($mentorIds,$coursesOfActionForMentors);

        // We turn values into keys for efficient querying
        $mentorIdsWithOccurrence = array_combine($mentorIdsWithOccurrence, $mentorIdsWithOccurrence);

        // TODO: figure out pagination
        return $this->render('role/admin/mentor_summary.html.twig',
            array(
                'mentors' => $mentors,
                'mentorIdsWithOccurrence' => $mentorIdsWithOccurrence,
                'coursesOfAction' => $coursesOfActionForMentors
            ));
    }


    /**
     * @Route("/view_mentor_summary_settings", name="view_mentor_summary_settings")
     */
    public function viewMentorSummarySettings(Request $request)
    {
        $entityManager = $this->getEntityManager();
        $attendancePenaltyPersistenceManager = AttendancePenaltyPersistenceManager::loadModel($entityManager);
        $occurrenceTypes = $entityManager->getRepository(OccurrenceType::class)->findAll();
        $coursesOfAction = $entityManager->getRepository(CourseOfAction::class)->findAll();

        return $this->render('role/admin/settings.html.twig', array(
            'pTable' => json_encode($attendancePenaltyPersistenceManager->getPointsTable()),
            'iTable' => json_encode($attendancePenaltyPersistenceManager->getIntervalTable()),
            'occurrenceTypes' => $occurrenceTypes,
            'coursesOfAction' => $coursesOfAction
        ));

    }

    /**
     * @Route("/view_mentor_specialty", name="view_mentor_specialty")
     *
     * @return Response
     */
    public function viewMentorSpecialty()
    {
        $mentors = $this->getDoctrine()->getRepository(User::class)->findByRole('mentor');
        $subjects = $this->getDoctrine()->getRepository(Subject::class)->findAll();

        // TODO: figure out pagination
        return $this->render('role/admin/specialty.html.twig',
            array(
                'mentors' => $mentors,
                'subjects' => $subjects
            ));
    }

    /**
     * @Route("/ajax/penalty/configuration", name="ajax_penalty_configuration")
     * @throws \Doctrine\ORM\ORMException
     */
    public function savePenaltyConfigurationAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedException(array());
        }

        $pTable = json_decode($request->request->get('penaltyAmount'));
        $iTable = json_decode($request->request->get('intervalSlots'));
        $coursesOfAction = json_decode($request->request->get('coursesOfAction'));
        $occurrenceTypes = json_decode($request->request->get('occurrenceTypes'));

        $tardinessPoints = $pTable->{'tardiness-settings'};
        $tardinessIntervals = $iTable->{'tardiness-settings'};

        $intervalBounds = array();
        $prevBound = 0;
        foreach ($tardinessIntervals as $tardinessInterval) {
            array_push($intervalBounds, $tardinessInterval + $prevBound);
            $prevBound = $prevBound + $tardinessInterval;
        }

        $tardinessIsCumulative = !empty($tardinessPoints) && $tardinessPoints[0] === 'cumulative';

        $justifiedAbsencePoints = $pTable->{'exc-abs-settings'};
        $justifiedAbsenceIntervalSizes = $iTable->{'exc-abs-settings'};
        $justifiedAbsenceIntervals = array();
        $acc = 0;
        foreach ($justifiedAbsenceIntervalSizes as $intervalSize) {
            $acc += $intervalSize;
            $justifiedAbsenceIntervals[] = $acc;
        }

        $unJustifiedAbsencePoints = $pTable->{'unexc-abs-settings'};
        $unJustifiedAbsenceIntervalSizes = $iTable->{'unexc-abs-settings'};
        $unJustifiedAbsenceIntervals = array();
        $acc = 0;
        foreach ($unJustifiedAbsenceIntervalSizes as $intervalSize) {
            $acc += $intervalSize;
            $unJustifiedAbsenceIntervals[] = $acc;
        }

        $absenceWithoutNoticePoints = $pTable->{'unnotified-abs'};
        $shiftCovered = $pTable->{'shift-covered'};
        $coverShift = $pTable->{'cover-shift'};

        $entityManager = $this->getEntityManager();
        $attendancePenaltyPersistenceManager = AttendancePenaltyPersistenceManager::loadModel($entityManager);
        $attendancePenaltyPersistenceManager->createTardinessPenalties($tardinessPoints, $intervalBounds, $tardinessIsCumulative);
        $attendancePenaltyPersistenceManager->createJustifiedAbsenceWithNoticePenalties($justifiedAbsencePoints, $justifiedAbsenceIntervals);
        $attendancePenaltyPersistenceManager->createUnjustifiedAbsenceWithNoticePenalties($unJustifiedAbsencePoints, $unJustifiedAbsenceIntervals);
        $attendancePenaltyPersistenceManager->createAbsenceWithoutNoticePenalty($absenceWithoutNoticePoints);
        $attendancePenaltyPersistenceManager->createShiftCoveredBonus($shiftCovered);
        $attendancePenaltyPersistenceManager->createClaimShiftBonus($coverShift);
        $attendancePenaltyPersistenceManager->persistModel($entityManager);

        $existingCoursesOfAction = $entityManager->getRepository(CourseOfAction::class)->findAll();
        $existingOccurrenceTypes = $entityManager->getRepository(OccurrenceType::class)->findAll();

        foreach ($existingCoursesOfAction as $courseOfAction) {
            $entityManager->remove($courseOfAction);
        }

        foreach ($existingOccurrenceTypes as $occurrenceType) {
            $entityManager->remove($occurrenceType);
        }


        foreach ($coursesOfAction as $coa) {
            $courseOfAction = new CourseOfAction($coa->points, $coa->description);
            $entityManager->persist($courseOfAction);
        }

        foreach ($occurrenceTypes as $ot) {
            $occurrenceType = new OccurrenceType($ot->points, $ot->description);
            $entityManager->persist($occurrenceType);
        }

        $entityManager->flush();

        return new Response('Saved settings successfully!', 200);
    }

    /**
     * @Route("/approve_profile_modification_request/{id}", name="approve_mod_request")
     *
     * @param ProfileModificationRequest $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function approveProfileModificationRequest(ProfileModificationRequest $request)
    {
        $request->approve();

        // Persists the changes to the profile and also removes the request
        $this->getDoctrine()->getManager()->flush();

        // TODO: maybe add a flash message
        return $this->redirectToRoute('admin_view_mentor_summary');
    }

    /**
     * @Route("/reject_profile_modification_request/{id}", name="reject_mod_request")
     *
     * @param ProfileModificationRequest $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function rejectProfileModificationRequest(ProfileModificationRequest $request)
    {
        $request->reject();

        $this->getDoctrine()->getManager()->flush();

        // TODO: maybe add a flash message
        return $this->redirectToRoute('admin_view_mentor_summary');
    }

    /**
     * @Route("/approve_occurrence", name="approve_occurrence")
     *
     * @param Request $request
     * @return Response
     */
    public function approveOccurrence(Request $request)
    {
        $id = $request->request->get('id');

        $occurrence = $this->getDoctrine()->getRepository(Occurrence::class)->find($id);
        $occurrence->approve();

        $this->getDoctrine()->getManager()->flush();

        return new Response(null, 200);
    }

    /**
     * @Route("/reject_occurrence", name="reject_occurrence")
     *
     * @param Request $request
     * @return Response
     */
    public function rejectOccurrence(Request $request)
    {
        $id = $request->request->get('id');

        $occurrence = $this->getDoctrine()->getRepository(Occurrence::class)->find($id);
        $occurrence->reject();

        $this->getDoctrine()->getManager()->flush();

        return new Response(null, 200);
    }

    /**
     * @Route("/ajax/modify_specialties", name="modify_specialties")
     */
    public function modifySpecialties(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedException();
        }

        $new_specialties = json_decode($request->request->get('specialties'), true);
        $user_id = $request->request->get('user');

        $em = $this->getDoctrine()->getManager();

        $user = $this->getDoctrine()->getRepository(User::class)->find($user_id);

        $specialties = $this->getDoctrine()->getRepository(Specialty::class)->findBy(array('profile' => $user_id));

        $isAdmin = $this->isGranted('admin');
        if ($isAdmin) {
            foreach ($specialties as $specialty) {
                $specialty->setRating($new_specialties[$specialty->getSubject()->getName()]);
                $em->persist($specialty);
                unset($new_specialties[$specialty->getSubject()->getName()]);
            }
            foreach ($new_specialties as $key => $value) {
                $profile = $user->getProfile();
                $subject = $this->getDoctrine()->getRepository(Subject::class)->findOneBy(array('name' => $key));
                $new_specialty = new Specialty($profile, $subject, $value);
                $em->persist($new_specialty);
            }
        }
        $em->flush();

        return new Response('success', 200);
    }

    /**
     * returns a course of action based on a mentor's total points
     * @param float $totalPoints
     * @param CourseOfAction[]|null $coursesOfAction
     * @return string
     */
    private function getCourseOfActionForMentor(float $totalPoints, array $coursesOfAction,bool $isNegative)
    {
        $prevThreshold = 0;
        $count = 0;
        foreach ($coursesOfAction as $courseOfAction)
        {
            if($isNegative)
            {
                if( $count === 0 && $totalPoints <= $courseOfAction->getThreshold())
                {
                    return $courseOfAction->getDescription();
                }
                if( $count !== 0  && $totalPoints > $prevThreshold && $totalPoints <= $courseOfAction->getThreshold() )
                {
                    return $courseOfAction->getDescription();
                }
            }
            else
            {
                if( $count === 0 && $totalPoints >= $courseOfAction->getThreshold())
                {
                    return $courseOfAction->getDescription();
                }
                if( $count !== 0  && $totalPoints < $prevThreshold && $totalPoints >= $courseOfAction->getThreshold() )
                {
                    return $courseOfAction->getDescription();
                }
            }

            $count++;
            $prevThreshold = $courseOfAction->getThreshold();
        }
        return "";
    }
}