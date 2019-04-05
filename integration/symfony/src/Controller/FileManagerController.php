<?php

namespace App\Controller;
use Artgris\Bundle\FileManagerBundle\Controller\ManagerController;

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

class FileManagerController extends ManagerController
{
    /**
     * @Route("/profile/{username}/fms", name="file_management")
     * Open a page to access file management system.
     */
    public function fms(Request $request, User $user, LoggerInterface $l)
    {
      $isAdmin = $this->isGranted('admin');

      // Protect against users outside the system to access the file manager.
      if ($this->getUser() != $user && !$isAdmin) {
          // Redirect to home instead of displaying a forbidden message
          return $this->redirectToRoute('home');
      }
      return $this->indexAction($request);
    }








    /**
     * @Route("/profile/{username}/mkdir", name="mkdir")
     * Current user shall create a folder with the given name. Intended to use with Javascript on front end ajax.
     *
     * Path shall contain the full path to the folder, not current directory. Filesystem can handle it. Don't worry.
     */
    public function mkdir(Request $request, LoggerInterface $l) {
      $fileSystem = new Filesystem();
      try {
        $folderPath = json_decode($request->getContent(),true); //also contain folder name
        //$l->error(var_dump($folderPath));
        $l->info("Created folder ".$folderPath["input1"]);
        $fileSystem->mkdir($folderPath["input1"]);
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
        $folderPath = json_decode($request->getContent(),true); //also contain folder name
        $l->info("Created file ".$folderPath["input1"]);
        $fileSystem->touch($folderPath["input1"]);
        return new Response("SUCCESS");
      } catch (IOExceptionInterface $exception) {
        return new Response("Fail:".$exception->getPath());
      }
    }

    /**
     * @Route("/profile/{username}/copy", name="copy")
     * Copy from one place to another. Overwrite destination.
     */
    public function copy(Request $request, LoggerInterface $l) {
      $fileSystem = new Filesystem();
      try {
        $folderPath = json_decode($request->getContent(),true); //also contain folder name
        $l->info("Copy from ".$folderPath['input1']);
        $l->info("Copy to ".$folderPath['input2']);
        $fileSystem->copy($folderPath['input1'],$folderPath['input2'],true);
        return new Response("SUCCESS");  //it exists
      } catch (IOExceptionInterface $exception) {
        return new Response("Fail:".$exception->getPath());
      }
    }

    /**
     * @Route("/profile/{username}/exists", name="exists")
     * Current user shall create a file with the given name. Intended to use with Javascript on front end ajax.
     *
     * Path shall contain the full path to the file, not current directory. Filesystem can handle it. Don't worry.
     */
    public function exists(Request $request, LoggerInterface $l) {
      $fileSystem = new Filesystem();
      try {
        $folderPath = json_decode($request->getContent(),true); //also contain folder name
        $l->info("Check exist ".$folderPath["input1"]);
        if($fileSystem->touch($folderPath["input1"]))
          return new Response("SUCCESS");  //it exists
        else return new Response("NOT_EXISTS");
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
        $folderPath = json_decode($request->getContent(),true); //also contain folder name
        $l->info("Deleted ".$folderPath["input1"]);
        $fileSystem->remove($folderPath["input1"]);
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
        $folderPath = json_decode($request->getContent(),true); //also contain folder name
        $l->info("Old name: ".$folderPath['input1']);
        $l->info("New name: ".$folderPath['input2']);
        $fileSystem->rename($folderPath['input1'], $folderPath['input2']);
        return new Response("SUCCESS");
      } catch (IOExceptionInterface $exception) {
        return new Response("Fail:".$exception->getPath());
      }
    }

    /**
     * @Route("/profile/{username}/mirror", name="mirror")
     * Copy content of one folder to the other.
     */
    public function mirror(Request $request, LoggerInterface $l) {
      $fileSystem = new Filesystem();
      try {
        $folderPath = json_decode($request->getContent(),true); //also contain folder name
        $l->info("Origin mirror: ".$folderPath['input1']);
        $l->info("Target mirror: ".$folderPath['input2']);
        $fileSystem->mirror($folderPath['input1'], $folderPath['input2']);
        return new Response("SUCCESS");
      } catch (IOExceptionInterface $exception) {
        return new Response("Fail:".$exception->getPath());
      }
    }

    /**
     * @Route("/profile/{username}/makePathRelative", name="makePathRelative")
     * Extract a relative path from an absolute path.
     */
    public function makePathRelative(Request $request, LoggerInterface $l) {
      $fileSystem = new Filesystem();
      try {
        $folderPath = json_decode($request->getContent(),true); //also contain folder name
        $l->info("Longer path: ".$folderPath['input1']);
        $l->info("Longer path: ".$folderPath['input2']);
        $relativePath = $fileSystem->makePathRelative($folderPath['input1'], $folderPath['input2']);
        return new Response($relativePath);
      } catch (IOExceptionInterface $exception) {
        return new Response("Fail:".$exception->getPath());
      }
    }
}