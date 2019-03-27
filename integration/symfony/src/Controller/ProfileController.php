<?php

namespace App\Controller;

use App\Entity\Occurrence\AbsenceOccurrence;
use App\Entity\Occurrence\BehaviorOccurrence;
use App\Entity\Occurrence\CumulativeTardinessOccurrence;
use App\Entity\Occurrence\Occurrence;
use App\Entity\Occurrence\OccurrenceType;
use App\Entity\User\Info\Profile;
use App\DataTransferObject\FileData;
use App\Entity\Misc\Subject;
use App\Entity\File\File;
use App\Entity\User\User;
use App\Form\Data\ProfileFormData;
use App\Form\ProfileType;
use App\Utils\ImageEditor;
use App\Utils\AttendancePenaltyPersistenceManager;
use Doctrine\ORM\EntityManager;
use Deployer\Exception\Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Psr\Log\LoggerInterface;

class ProfileController extends Controller
{
    /**
     * @Route("/profile/{username}/mkdir", name="mkdir")
     * Current user shall create a folder with the given name. Intended to use with Javascript on front end ajax.
     *
     * Path shall contain the full path to the folder, not current directory. Filesystem can handle it. Don't worry.
     */
    public function mkdir(Request $request, LoggerInterface $l) {
      $fileSystem = new Filesystem();
      try {
        $folderPath = $request->getContent(); //also contain folder name
        $fileSystem->mkdir($folderPath);
        $l->info("Created folder ".$folderPath);
        return new Response("SUCCESS");
      } catch (IOExceptionInterface $exception) {
        return new Response("Fail:".$exception->getPath());
      }
    }

    /**
     * @Route("/profile/{username}/touch", name="touch")
     * Current user shall create a file with the given name. Intended to use with Javascript on front end ajax.
     *
     * Path shall contain the full path to the file, not current directory. Filesystem can handle it. Don't worry.
     */
    public function touch(Request $request, LoggerInterface $l) {
      $fileSystem = new Filesystem();
      try {
        $filePath = $request->getContent(); //also contain folder name
        $fileSystem->touch($filePath);
        $l->info("Created file ".$filePath);
        return new Response("SUCCESS");
      } catch (IOExceptionInterface $exception) {
        return new Response("Fail:".$exception->getPath());
      }
    }

    /**
     * @Route("/profile/{username}/remove", name="remove")
     * Current user shall remove a file/folder with a fully qualified path. Intended to use with Javascript on front end ajax.
     */
    public function remove(Request $request, LoggerInterface $l) {
      $fileSystem = new Filesystem();
      try {
        $path = $request->getContent(); //also contain folder name
        $fileSystem->touch($path);
        $l->info("Deleted ".$path);
        return new Response("SUCCESS");
      } catch (IOExceptionInterface $exception) {
        return new Response("Fail:".$exception->getPath());
      }
    }

    /**
     * @Route("/profile/{username}/rename", name="rename")
     * Current user shall rename a file/folder with a fully qualified path. Intended to use with Javascript on front end ajax.
     */
    public function rename(Request $request, LoggerInterface $l) {
      $fileSystem = new Filesystem();
      try {
        $content = $request->getContent(); //contains old path and new path
        $paths = json_decode($content, true);  //decode to associative array
        $fileSystem->touch($paths['oldName'], $paths['newName']);
        $l->info("Old name: ".$paths['oldName']);
        $l->info("New name: ".$paths['newName']);
        return new Response("SUCCESS");
      } catch (IOExceptionInterface $exception) {
        return new Response("Fail:".$exception->getPath());
      }
    }

    /**
     * @Route("/profile/{username}", name="profile")
     */
  public function viewProfile(Request $request, User $user, LoggerInterface $l)
  {
    $isAdmin = $this->isGranted('admin');

        // Protect against non-admin user trying to view someone else's profile
        if ($this->getUser() != $user && !$isAdmin) {
            // Redirect to home instead of displaying a forbidden message
            return $this->redirectToRoute('home');
        }

    $occurrences = $this->getDoctrine()->getRepository(Occurrence::class)->findAll();

    //find accummulated score of approved occurrences.
    $totalScore = 0;
    $userId = $user->getId();
    foreach ($occurrences as $occurrence) {
      if ($occurrence->getSubject()->getId() == $userId && $occurrence->getStatus() == "approved") {
        $totalScore += $occurrence->getPoints();
      }
    }

    return $this->render('role/mentor/profile.html.twig', array(
      'totalScore' => $totalScore,
      'user' => $user,
      'isAdmin' => $isAdmin
    ));
  }

  /**
     * @Route("/profile/{username}/ajaxDuplicatePrefName", name="ajax_duplicate_pref_name")
     * Query the db for a mentor with matching username. If it exists, then return NODUP. Else HASDUP.
     * @param Request $request
     * @return Response
     */
  public function queryForDuplicatePreferredName(Request $request, LoggerInterface $logger)
  {
    $payload = $request->getContent();
    //$logger->critical($payload);
    $criteria = json_decode($payload, true);
    $preferredName = array("preferredName" => $criteria["preferredName"]);

    $userId = $criteria["id"];
    //return an array of Profile object by Preferred name
    $preferredNames = $this->getDoctrine()->getRepository(Profile::class)->findOneBy($preferredName);

    //Find by key. Return a Profile object
    $ids = $this->getDoctrine()->getRepository(Profile::class)->find($userId);

    //if preferred name does not exist then NODUP
    if (empty($preferredNames)) {
      return new Response("NODUP");
    }
    //if the name belongs to this user, then it's not a duplicate.
    if ($preferredNames->getUser()->getId() == $userId) {
      return new Response("NODUP");
    }
    return new Response("HASDUP");
  }
  /**
     * @Route("/profile/{username}/showDetailedScore", name="show_detailed_score")
     * Query the db for a mentor with matching username. Return all occurences of that user.
     */
  public function showScoreDetails(Request $request, User $user)
  {
    //only show some sections if the user is admin.
    $isAdmin = $this->isGranted('admin');

    $pendingOccurrences = null;
    $closedOccurrences = null;

    $requestedUser = null;

    $userId = $request->query->get('user');
    $foundMentor = false;
    $user = $user;

    //if look for multiple mentor, then query everything.
    $pendingOccurrences = $this->getDoctrine()->getRepository(Occurrence::class)
      ->findPendingOccurrencesForDisplaying($user);
    $closedOccurrences = $this->getDoctrine()->getRepository(Occurrence::class)
      ->findClosedOccurrencesForDisplaying($user);

    $occurrenceTypes = $this->getDoctrine()->getManager()->getRepository(OccurrenceType::class)->findAll();
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

    return $this->render('role/mentor/detailed_score.html.twig', array(
      'user' => $user,
      'pendingOccurrences' => $pendingOccurrences,
      'closedOccurrences' => $closedOccurrences,
      'occurrenceTypes' => json_encode($occurrenceTypes),
      'unjustifiedPenalties' => json_encode($unjustifiedAbsencePenalties),
      'justifiedPenalties' => json_encode($justifiedAbsencePenalties),
      'noNoticePenaltyAmount' => $noNoticePenaltyAmount,
      'noticeAmounts' => json_encode($noticeAmounts),
      'requestedUser' => $requestedUser,
      'isAdmin' => $isAdmin
    ));
  }
    /**
     * @Route("/profile/{username}/edit", name="edit_profile")
     */
    public function editProfile(Request $request, User $mentor)
    {
        // Protect against non-admin user trying to edit someone else's profile
        $isAdmin = $this->isGranted('admin');
        if ($this->getUser() != $mentor && !$isAdmin) {
            // Redirect to home instead of displaying a forbidden message
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(ProfileType::class,
            ProfileFormData::createFromProfile($mentor->getProfile(), $this->getDoctrine()->getManager()),
            array('is_admin' => $isAdmin));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mentor->getProfile()->updateFromFormData($form->getData(), $isAdmin);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($mentor);
            $entityManager->flush();
            return $this->redirectToRoute('profile', array(
                'username' => $mentor->getUsername()
            ));
        }

        return $this->render('role/mentor/edit_profile.html.twig', array(
            'user' => $mentor,
            'form' => $form->createView(),
            'isAdmin' => $isAdmin
        ));
    }

    /**
     * @Route("/profile/{username}/save_image", name="save_profile_image")
     */
    public function saveImageAction(Request $request,LoggerInterface $l)
    {

        //$l->debug("im in 1");
        // $l->debug($request->getContent());
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedException();
        }
        //$l->debug("im in 2");
        $file = new
        FileData();
        //$l->debug("im in 3");
        $file->file = $request->files->get('file');
        $crop = $request->request->get('crop');
        $canvas = $request->request->get('canvas');
        $image = $request->request->get('image');

        $this->get('logger')->debug($crop);

        $em = $this->getDoctrine()->getManager();
        //$l->debug("im in 4");
        $file = File::fromUploadData($file, $em, array(
            'crop' => $crop,
            'canvas' => $canvas,
            'image' => $image
        ));
        //$l->debug("im in 5");

        $em->persist($file);

        $user_id = $request->request->get('user');
        $user = $this->getDoctrine()->getRepository(User::class)->find($user_id);

        $isAdmin = $this->isGranted('admin');
        //$l->debug("im in 6");
        $user->updateProfilePicture($file, $isAdmin);
        //$l->debug("im in 7");
        $em->flush();

        return new Response('success', 200);
    }

    /**
     * @Route("/profile/{username}/update_image", name="update_profile_image")
     */
    public function updateImageAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedException();
        }
        $user_id = $request->request->get('user');
        $user = $this->getDoctrine()->getRepository(User::class)->find($user_id);
        $file = $user->getProfile()->getProfilePictureModificationRequest()->getValue();

        $crop = $request->request->get('crop');
        $canvas = $request->request->get('canvas');
        $image = $request->request->get('image');

        $this->get('logger')->debug($crop);

        $file->set('crop', $crop);
        $file->set('canvas', $canvas);
        $file->set('image', $image);

        $em = $this->getDoctrine()->getManager();
        $em->persist($file);

        $em->flush();

        return new Response('success', 200);
    }

    /**
     * @Route("/profile/{username}/image", name="profile_image")
     */
    public function imageAction(User $user, ImageEditor $imageEditor)
    {
        $image = $user->getProfilePicture();
        $image_name = $user->getUsername() . $image->get('extension');

        return $this->cropImage($imageEditor, $image_name, $image);
    }

    /**
     * @Route("/profile/{username}/new_image", name="profile_requested_image")
     * @param User $user
     * @param ImageEditor $editor
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function requestedProfileImage(User $user, ImageEditor $editor)
    {
        $image = $user->getProfile()->getProfilePictureModificationRequest()->getValue();
        $name = $user->getUsername() . '-request' . $image->get('extension');

        return $this->cropImage($editor, $name, $image);
    }

    /**
     * @Route("/profile/{username}/origin_requested_image", name="origin_requested_image")
     * @param User $user
     * @param ImageEditor $editor
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function originRequestedImage(User $user, ImageEditor $editor)
    {
        $image = $user->getProfile()->getProfilePictureModificationRequest()->getValue();
        $name = $user->getUsername() . '-origin.jpeg' . $image->get('extension');
        $origin_image = $editor->getOriginImage($image);
        return $this->file($origin_image, $name, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * @param ImageEditor $imageEditor
     * @param string $image_name
     * @param File $image
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    private function cropImage(ImageEditor $imageEditor, string $image_name, File $image = null)
    {
        if (!$image) {
            throw new ResourceNotFoundException();
        }

        $cropped_image = $imageEditor->getCroppedImage($image);

        return $this->file($cropped_image, $image_name, ResponseHeaderBag::DISPOSITION_INLINE);
    }


}