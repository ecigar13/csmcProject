<?php

namespace App\Controller;

use Artgris\Bundle\FileManagerBundle\Event\FileManagerEvents;
use App\Helpers\File;
use Doctrine\ORM\EntityRepository;
use App\Entity\File\Directory;
use App\Entity\File\Link;
use App\Entity\User\User;
use App\Entity\User\Role;
use App\Entity\Course\Section;
use App\Entity\Course\Course;
use App\Entity\Course\Department;
//use Artgris\Bundle\FileManagerBundle\Helpers\FileManager;
use App\Helpers\FileManager;
use App\Twig\CSMCOrderExtension;
use App\Entity\File\FileHash;
//need to rename this after gutting all Artgris File usage (if possible)
use App\Entity\File\File as CSMCFile;
use App\Entity\File\VirtualFile;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\DataTransferObject\FileData;

class FileManagerController extends Controller
{
    /**
     * @Route("/fms/")
     * @Route("/fms", name="file_management")
     * Open a page to access file management system.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function indexAction(Request $request, LoggerInterface $logger)
    {
        $queryParameters = $request->query->all();
        $translator      = $this->get('translator');
       // $isJson          = $request->get('json') ? true : false;
        $isJson          = $request->get('json') ? true : false;
        if ($isJson) {
            unset($queryParameters['json']);
        }
        $fileManager = $this->newFileManager($queryParameters);

        $this->createDirectory($fileManager,$logger);
        // $logger->info("Logging");
        // $logger->info($fileManager->getDirName());
        // $logger->info($fileManager->getBaseName());
        // Folder search
       $directoriesArbo = $this->retrieveSubDirectories($fileManager, DIRECTORY_SEPARATOR,$logger,true);

        // File search
        $logger->info($fileManager->getCurrentRoute());
        $finderFiles = $this->retrieveFiles($fileManager, $fileManager->getCurrentRoute());

        $regex = $fileManager->getRegex();
	
        $orderBy   = $fileManager->getQueryParameter('orderby');
        $orderDESC = CSMCOrderExtension::DESC === $fileManager->getQueryParameter('order');
        switch ($orderBy) {
            case 'name':
                // $finderFiles->sort(function (SplFileInfo $a, SplFileInfo $b) {
                //     return strcmp(strtolower($b->getFileName()), strtolower($a->getFileName()));
                // });
                usort($finderFiles,  function (CSMCFile $first,CSMCFile $second) {
                    return strcmp(strtolower($first->getName()), strtolower($second->getName()));
                }); 
                break;
            case 'date':
                // $finderFiles->sortByModifiedTime();
                usort($finderFiles,  function (CSMCFile $first,CSMCFile $second) {
                    return ($first->giveDate() > $second->giveDate());
                }); 
                break;
            case 'size':
            usort($finderFiles,  function (CSMCFile $first,CSMCFile $second) {
                    return $first->get('size') - $second->get('size');
                });
                break;
            default :
            usort($finderFiles,  function (CSMCFile $first,CSMCFile $second) {
                return strcmp(strtolower($first->get('extension')) ,strtolower($second->get('extension')));
                });
                break;
        }

        //to be enabled while using regex for file type matching

        // if ($fileManager->getTree()) {
        //     $finderFiles->files()->name($regex)->filter(function (SplFileInfo $file) {
        //         return $file->isReadable();
        //     });
        // } else {
        //     $finderFiles->filter(function (SplFileInfo $file) use ($regex) {
        //         if ('file' === $file->getType()) {
        //             if (preg_match($regex, $file->getFilename())) {
        //                 return $file->isReadable();
        //             }

        //             return false;
        //         }

        //         return $file->isReadable();
        //     });
        // }

        //create delete form for FMS
        $formDelete = $this->createDeleteForm()->createView();
        $fileArray  = [];
        foreach ($finderFiles as $file) {
            $logger->info("path");
            $logger->info($file->getPhysicalDirectory());
            $fileArray[] = new File($file, $this->get('translator'), $this->get('app.file_type_service'), $fileManager);
        }

        if ($orderDESC) {
            $fileArray = array_reverse($fileArray);
        }

        $parameters = [
            'fileManager' => $fileManager,
            'fileArray'   => $fileArray,
            'formDelete'  => $formDelete,
            'username'    => $this->getUser()->getUsername(),
        ];

        if ($isJson) {
            $fileList = $this->renderView('fileManager/_manager_view.html.twig', $parameters);

            return new JsonResponse(['data' => $fileList, 'badge' => $finderFiles->count(), 'treeData' => $directoriesArbo]);
        }
        $parameters['treeData'] = json_encode($directoriesArbo);
        $form = $this->get('form.factory')->createNamedBuilder('rename', FormType::class)
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'label'       => false,
                'data'        => $translator->trans('input.default'),
            ])
            ->add('send', SubmitType::class, [
                'attr'  => [
                    'class' => 'btn btn-primary',
                ],
                'label' => $translator->trans('button.save'),
            ])
            ->getForm();

        /* @var Form $form */
        $form->handleRequest($request);
        /** @var Form $formRename */
        $formRename = $this->createRenameForm();
        $formMove = $this->createMoveForm();
        

        //Uploading Folder---------------------------------------->
        if ($form->isSubmitted() && $form->isValid()) {
            $data      = $form->getData();

            // Get Name and Parent Path
            $directoryName = $data['name'];
            $logger->info("UploadDirectory");

            // print_r($fileManager->getQueryParameters());
            $parentPath= 'root';
            if(array_key_exists('route',$fileManager->getQueryParameters()) && !is_null($fileManager->getQueryParameters()['route'])){
                $parentPath = $fileManager->getQueryParameters()['route'];
            }
            $logger->info("parentDDDDDDDDDDDDD");
            $logger->info($parentPath);
            $directoryPath =  $parentPath . DIRECTORY_SEPARATOR . $data['name'];

            //Search for Directory in Table
            $directoryClass = $this->getDoctrine()->getRepository(Directory::class);
            $directory=$directoryClass->findByPath($directoryPath);
            if($directory){
                $this->addFlash('danger', $translator->trans('folder.add.danger', ['%message%' => $data['name']]));
                return $this->redirectToRoute('file_management', $fileManager->getQueryParameters());
            }

            //Get Parent, create directory, set parent
            
            $parent=$directoryClass->findOneBy(array('path' => $parentPath));
            if (!$parent) {
                $this->addFlash('danger', $translator->trans('folder.add.danger', ['%message%' => $data['name']]));
                return $this->redirectToRoute('file_management', $fileManager->getQueryParameters());
                
            }
            try{
                $directory  = new Directory($directoryName,$this->getUser(),$directoryPath,);
                $directory->setParent($parent);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($directory);
                $entityManager->flush();
                $this->addFlash('success', $translator->trans('folder.add.success'));
            }
            catch (IOExceptionInterface $e) {
                $this->addFlash('danger', $translator->trans('folder.add.danger', ['%message%' => $data['name']]));
            }

            return $this->redirectToRoute('file_management', $fileManager->getQueryParameters());
        }
        $parameters['form']       = $form->createView();
        $parameters['formRename'] = $formRename->createView();
        $parameters['formMove']   = $formMove->createView();

        return $this->render('fileManager/manager.html.twig', $parameters);
    }
    /**
     * @Route("/fms/rename/", name="file_management_rename")
     *
     * rename the file in the database. Does not deal with moving files or changing file path. Request will contain old file name, new file name.
     * Need to fix this in the future.
     *
     * TODO: check if the person who initiated is admin or the owner.
     * TODO: it is possible to change extension too.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Exception
     */
    public function renameFileAction(Request $request, LoggerInterface $l)
    {
        /** @var Form $formRename */
        $formRename = $this->createRenameForm();
        $translator = $this->get('translator');
        $queryParameters = $request->query->all();
        $em = $this->getDoctrine()->getManager();

        /* @var Form $formRename */
        $formRename->handleRequest($request);
        if ($formRename->isSubmitted() && $formRename->isValid()) {
            //perform updating database
            $data = $formRename->getData();
            
            if(isset($data['id'])){
                //TODO: find by ID, or hash. Not by file name
                //TODO: avoid sending file name in query parameter.
                $file = $this->getDoctrine()->getRepository(VirtualFile::class)->findOneBy(array('id' => $data['id']));
                if(is_null($file)){
                    //TODO: what if file doesn't exist in database?
                    $this->addFlash('warning', "Can't find the file to rename: ".$data['id']);
                    return $this->redirectToRoute('file_management', $queryParameters);
                }
                
                //update name
                if (isset($data['name']) && $data['name'] !== $file->getName()) {
                    //can be multiple because files are not unique. Can't fix it for now.
                    $file->setName($data['name']);
                    $em->persist($file);
                } else {
                    $this->addFlash('warning', $translator->trans('file.renamed.nochanged'));
                }
            }else{
                $this->addFlash('danger', 'Did not provide a file name.');
            }
        }
        $em->flush();


        return $this->redirectToRoute('file_management', $queryParameters);
    }

    /**
     * @Route("/fms/move/", name="fms_move")
     *
     * TODO: check if the person who initiated is admin or the owner.
     * TODO: it is possible to change extension too.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Exception
     */
    public function moveFileAction(Request $request, LoggerInterface $l)
    {
        $file_id = $request->request->get('file_id');
        $parent_id = $request->request->get('new_parent_id');

        $translator = $this->get('translator');
        $queryParameters = $request->query->all();
        $em = $this->getDoctrine()->getManager();

        if(!empty($file_id)){
            $file = $this->getDoctrine()
                ->getRepository(VirtualFile::class)
                ->findOneBy(array('id' => $file_id));

            $parent = $this->getDoctrine()
                ->getRepository(Directory::class)
                ->findOneBy(array('id' => $parent_id));

            $file->setParent($parent);
            $em->persist($file);
        }
        else{
            $this->addFlash('danger', 'Did not provide a valid files.');
        }

        $em->flush();


        return $this->redirectToRoute('file_management', $queryParameters);
    }

    /**
     * @Route("/fms/file/{fileName}", name="file_management_file")
     *
     * @param Request $request
     * @param $fileName
     *
     * @return BinaryFileResponse
     *
     * @throws \Exception
     */
    public function binaryFileResponseAction(Request $request, $fileName)
    {
        $fileManager = $this->newFileManager($request->query->all());

        return new BinaryFileResponse($fileManager->getCurrentPath().DIRECTORY_SEPARATOR.urldecode($fileName));
    }

    /**
     * @Route("/fms/delete/", name="file_management_delete", methods={"DELETE"})
     *
     * Should expect a file/folder name array to delete. Assumming file names are unique.
     * TODO: check with Steven. Does recursive delete in database also trigger multiple preRemove event?
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Exception
     */
    public function deleteAction(Request $request)
    {
        $form = $this->createDeleteForm();
        $form->handleRequest($request);
        $queryParameters = $request->query->all();

        $em = $this->getDoctrine()->getManager();
        if ($form->isSubmitted() && $form->isValid()) {
            if (isset($queryParameters['delete'])) {
                //delete from disk is in FileSubscriber, preRemove
                //delete from database
                foreach ($queryParameters['delete'] as $fileName) {
                    $file = $this->getDoctrine()->getRepository(VirtualFile::class)->findOneBy(array('name'=>$fileName));
                    if($file !== null) $em->remove($file);  //this will remove files/folders inside this one.
                }

                unset($queryParameters['delete']);
            }
        }
        $em->flush();

        return $this->redirectToRoute('file_management', $queryParameters);
    }

    /**
     * @param $queryParameters
     *
     * @return FileManager
     *
     * @throws \Exception
     */
    protected function newFileManager(array $queryParameters)
    {
        if (!isset($queryParameters['conf'])) {
            //echo "conf variable not set. Switching to default.";
            $queryParameters['conf'] = 'default';
        }
        $webDir = $this->getParameter('artgris_file_manager')['web_dir'];

        $this->fileManager = new FileManager($queryParameters, $this->getBasePath($queryParameters), $this->getKernelRoute(), $this->get('router'), $webDir);

        return $this->fileManager;
    }

    /*
     * Base Path.
     * conf parameter is already set in indexAction().
     */
    protected function getBasePath($queryParameters)
    {
        if (!isset($queryParameters['conf'])) {
            //echo "conf variable not set. Switching to default.";
            $queryParameters['conf'] = 'default';
        }

        $conf        = $queryParameters['conf'];
        $managerConf = $this->getParameter('artgris_file_manager')['conf'];
        if (isset($managerConf[$conf]['dir'])) {
            return $managerConf[$conf];
        }

        if (isset($managerConf[$conf]['service'])) {
            echo $managerConf[$conf]['service'];
            $extra = isset($queryParameters['extra']) ? $queryParameters['extra'] : [];
            $conf  = $this->get($managerConf[$conf]['service'])->getConf($extra);

            return $conf;
        }

        throw new \RuntimeException('Please define a "dir" or a "service" parameter in your config.yml');
    }

    /**
     * @return mixed
     */
    protected function getKernelRoute()
    {
        return $this->getParameter('kernel.root_dir');
    }





    /**
     * @return Form|\Symfony\Component\Form\FormInterface
     */
    protected function createDeleteForm()
    {
        return $this->createFormBuilder()
            ->setMethod('DELETE')
            ->add('DELETE', SubmitType::class, [
                'translation_domain' => 'messages',
                'attr'               => [
                    'class' => 'btn btn-danger',
                ],
                'label'              => 'button.delete.action',
            ])
            ->getForm();
    }

    /**
     * @return mixed
     */
    protected function createMoveForm()
    {
        return $this->createFormBuilder()
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'label'       => false,
            ])->add('id', HiddenType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'label'       => false,
            ])->add('send', SubmitType::class, [
                'attr'  => [
                    'class' => 'btn btn-primary',
                ],
                'label' => 'button.move.action',
            ])
            ->getForm();
    }

    /**
     * @return mixed
     */
    protected function createRenameForm()
    {
        return $this->createFormBuilder()
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'label'       => false,
            ])->add('id', HiddenType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'label'       => false,
            ])->add('extension', HiddenType::class)
            ->add('send', SubmitType::class, [
                'attr'  => [
                    'class' => 'btn btn-primary',
                ],
                'label' => 'button.rename.action',
            ])
            ->getForm();
    }

    /**
     * @Route("/fms/upload/", name="file_management_upload")
     *
     * Get the configuration from URL (acceptable types, dir), create file hash, move it and insert into database.
     * Pitfall: what if file upload failed?
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function uploadFileAction(Request $request, LoggerInterface $logger)
    {
        //only accept httpRequest
        $translator = $this->get('translator');
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedException();
        }

        //create File Manager
        $em = $this->getDoctrine()->getManager();
        $queryParameters = $request->query->all();
        $fileManager = $this->newFileManager($queryParameters);

        //TODO: Check that parent exist to prevent crashing. Maybe front-end.
        $parentPath = $fileManager->getQueryParameters()['route'];
        $directoryClass = $this->getDoctrine()->getRepository(Directory::class);
        $parent=$directoryClass->findOneBy(array('path' => $parentPath));
        if (!$parent) {
            $this->addFlash('danger', "Parent not found");
            return new Response(Response::HTTP_NOT_FOUND);

        }
        $uploadedFiles = $request->files->get('files');
        $response = [];
        foreach ($uploadedFiles as $uploadedFile){
            $filePath=$parentPath . DIRECTORY_SEPARATOR . $uploadedFile->getClientOriginalName();
            $logger->info("FilePath");
            $logger->info($filePath);

            //check if file with same name already exixt
            $fileClass = $this->getDoctrine()->getRepository(CSMCFile::class);
            
            $file=$fileClass->findByPath($filePath);
            if($file){
                $this->addFlash('danger', "can't add file, File already exist-".$data['name']);
                return new Response(401);
                
            }
            
            //create a file object with its hash. Moving file to its folder fires during prePersist.
            try{
                $fileData = new FileData($uploadedFile, $this->getUser(),$filePath);
                $file = CSMCFile::fromUploadData($fileData, $em);
                $file->setParent($parent);
                $em->persist($file);
            }
            catch (IOExceptionInterface $e) {
                $this->addFlash('danger', "cant add file-".$data['name']);
                return new Response(Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $response[] = [
                'files'=>[
                    [
                    'originalName'=> $uploadedFile->getClientOriginalName(),
                    'fileExtension' => $uploadedFile->getClientOriginalExtension(),
                    'size'=> $uploadedFile->getClientSize(),
                    'mimeType'=> $uploadedFile->getClientMimeType()
                    ],
                ]
            ];
        }

        try{
            $em->flush();
        }
        catch (IOExceptionInterface $e) {
            $this->addFlash('danger', "Error while flushing to server");
            return new Response(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        //should respond with name of file
        //TODO: need to refresh the page on front-end. 
        return new JsonResponse($response,200);
    }
    
    protected function dispatch($eventName, array $arguments = [])
    {
        $arguments = array_replace([
            'filemanager' => $this->fileManager,
        ], $arguments);

        $subject = $arguments['filemanager'];
        $event = new GenericEvent($subject, $arguments);
        $this->get('event_dispatcher')->dispatch($eventName, $event);
    }

    private function createHash($file, $entityManager) {
        $file_path = '../public' . $file->url;
        $file_path = utf8_encode($file_path);
        $hash = sha1_file($file_path);
        $size = filesize($file_path);
        $extension = $this->guessExtension($file);
        $fileHash = $entityManager->getRepository(FileHash::class)
            ->findOneByPath($hash . '.' . $extension);
        if ($fileHash == null) {
            $fileHash = new FileHash($hash, $extension, $size);
        }

        return $fileHash;
    }

    public function guessExtension($file)
    {
        $guesser = ExtensionGuesser::getInstance();
        return $guesser->guess($file->type);
    }

    /**
     * @param $path
     * @param string $parent
     *
     * @return array|null
     * 
     * 
     */
    protected function retrieveSubDirectories(FileManager $fileManager, $parentPath = DIRECTORY_SEPARATOR,LoggerInterface $logger,$baseFolderName = false)
    {
        $directoriesList = null;
        $logger->info("RetrieveDirectory");
        $logger->info($parentPath);
        $directoryClass = $this->getDoctrine()->getRepository(Directory::class);
        $root=$directoryClass->findOneBy(array('path' => '/root'));

        //Find parent from id and children from parent
        if($baseFolderName){
            $logger->info("in Base Folder");
            $fileName = DIRECTORY_SEPARATOR . 'root';
            //$fileName = '/root';
            $queryParameters          = $fileManager->getQueryParameters();
            $queryParameters['route'] = $fileName;
            $queryParametersRoute     = $queryParameters;
            unset($queryParametersRoute['route']);

            // $filesNumber = $this->retrieveFilesNumber($directory->getPathname(), $fileManager->getRegex());
            // $fileSpan    = $filesNumber > 0 ? " <span class='label label-default'>{$filesNumber}</span>" : '';

            $directoriesList[] = [
                'text'     => 'root',
                'id'     => $root->getId(),
                'icon'     => 'far fa-folder-open',
                'children' => $this->retrieveSubDirectories($fileManager, $fileName,$logger),
                'a_attr'   => [
                    'href' => $fileName ? $this->generateUrl('file_management', $queryParameters) : $this->generateUrl('file_management', $queryParametersRoute),
                ], 'state' => [
                    'selected' => $fileManager->getCurrentRoute() === $fileName,
                    'opened'   => $fileManager->getCurrentRoute() === $fileName,
                ],
            ];

            return $directoriesList;
        }

        $parent=$directoryClass->findOneBy(array('path' => $parentPath));
        $directories = $directoryClass->findByParent($parent);

		dump($this->getDoctrine()->getRepository(VirtualFile::class));

        //List for tree
		//dump($directories);

        foreach ($directories as $directory) {
			
            $fileName = $parentPath . '/' . $directory->getName();
            $queryParameters          = $fileManager->getQueryParameters();
            $queryParameters['route'] = $fileName;
            $queryParametersRoute     = $queryParameters;
            unset($queryParametersRoute['route']);

            // $filesNumber = $this->retrieveFilesNumber($directory->getPathname(), $fileManager->getRegex());
            // $fileSpan    = $filesNumber > 0 ? " <span class='label label-default'>{$filesNumber}</span>" : '';
            $directoryRoles=[];
            foreach($directory->getRoles() as $role){
                array_push($directoryRoles, $role->getName());
            }

            $directoryUsers=[];
            foreach($directory->getUsers() as $user){
                array_push($directoryUsers, $user->getUsername());
            }
            
			$userRoles=[];
            foreach($this->getUser()->getRoles() as $role){
                array_push($userRoles, $role->getName());
            }

			// Regulate who see what directories / files here

			$isAdmin = false;

			if(in_array('admin', $userRoles)) {
				$isAdmin = true; // If the user is considered an admin, set this variable true
			}

            $access = $isAdmin || false; // Admins should be able to see every folder

			if(!$access) {
				if (in_array($this->getUser()->getUsername(), $directoryUsers)){

					    $access=true;
				}
				else{
	                foreach($userRoles as $r){
						if(in_array($r, $directoryRoles)){
	                        $access=true;
							break;
						}
						$access=false;
					}
				}	
			}
            if($access){
                $directoriesList[] = [
                    'text'     => $directory->getName(),
                    'id'       => $directory->getId(),
                    'icon'     => 'far fa-folder-open',
                    'children' => $this->retrieveSubDirectories($fileManager, $fileName,$logger),
                    'a_attr'   => [
                        'href' => $fileName ? $this->generateUrl('file_management', $queryParameters) : $this->generateUrl('file_management', $queryParametersRoute),
                    ], 'state' => [
                        'selected' => $fileManager->getCurrentRoute() === $fileName,
                        'opened'   => $fileManager->getCurrentRoute() === $fileName,

                    ],
                ];
        }
        }

        return $directoriesList;
    }

    /**
     * Retrive all files in a directory/path
     * 
     * @param $path
     * @param string $parent
     *
     * @return array|null
     */
    protected function retrieveFiles(FileManager $fileManager, $path)
    {
        //Find parent from id and children from parent
        $directoryClass = $this->getDoctrine()->getRepository(Directory::class);
        $FileClass=$this->getDoctrine()->getRepository(CSMCFile::class);
        $parent=$directoryClass->findByPath($path);
        $FileList = $FileClass->findByParent($parent);
        return $FileList;
    }

    /**
     * Tree Iterator.
     *
     * @param $path
     * @param $regex
     *
     * @return int
     */
    protected function retrieveFilesNumber($path, $regex)
    {
        $files = new Finder();
        $files->in($path)->files()->depth(0)->name($regex);

        return iterator_count($files);
    }


    /**
     * Automatic Folder Creation.
     *
     *
     * @param $FileManager
     *
     * @return null
     */
    protected function createDirectory(FileManager $FileManager,LoggerInterface $logger)
    {
        $user = $this->getUser();
        $roles=[];
        foreach($user->getRoles() as $role){
            array_push($roles, $role->getName());
        }
        $netId = $user->getUsername();
        $firstName = $user->getFirstName();
        $lastName = $user->getLastName();
        $entityManager = $this->getDoctrine()->getManager();
        $userClass = $this->getDoctrine()->getRepository(User::class);
        $roleClass = $this->getDoctrine()->getRepository(Role::class);
        $directoryClass = $this->getDoctrine()->getRepository(Directory::class);
        $admin = $userClass->findOneBy(array('username' => $netId));
        $sectionClass = $userClass->findOneBy(array('username' => $netId));
        $Instructor = $roleClass->findOneByName('instructor');
        $Mentor = $roleClass->findOneByName('mentor');
        $Admin = $roleClass->findOneByName('admin');
        $Student = $roleClass->findOneByName('student');
        $Developer = $roleClass->findOneByName('developer');

        try{
            // Create root folder if it's not there
            $root=$directoryClass->findOneBy(array('path' => '/root'));
            if(!$root){
                $root  = new Directory('root',$admin,'/root');        
                $entityManager->persist($root);
                $entityManager->flush();
            }

            // check for instructor role if not there create section directory
            if (in_array("instructor", $roles)){
                $sections=$directoryClass->findOneBy(array('path' => '/root/sections'));
                if(!$sections){
                    $sections  = new Directory('sections',$admin,'/root/sections');
                    $sections->setParent($root);
                    $sections->addRole($Instructor);
                    $sections->addRole($Admin);
                    $sections->addRole($Mentor);
                    $sections->addRole($Developer);
                    $entityManager->persist($sections);
                    $entityManager->flush();
                }
                    // Find all sections related to Instructor and create directories for them
                $SectionForInstructor = $user->getSections();
                foreach($SectionForInstructor as $section){
                        $seasonName=$section->getSemester()->getSeason(). '_' . $section->getSemester()->getYear();
                        $seasonPath='/root/sections/' . $seasonName;
                        $logger->info("Season");
                        $logger->info($seasonName);
                        $logger->info("seasonPath");
                        $logger->info($seasonPath);
                        $season = $directoryClass->findOneBy(array('path' => $seasonPath));
                        if(!$season){
                            $season = new Directory($seasonName,$admin,$seasonPath);
                            $season->setParent($sections);
                            $season->addRole($Instructor);
                            $season->addRole($Mentor);
                            $season->addRole($Developer);
                            $season->addRole($Admin);
                            $entityManager->persist($season);
                        }
                        $sectionName = $section->getCourse()->getDepartment()->getAbbreviation(). '_' . $section->getCourse()->getNumber(). '_' .$section->getNumber();
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
                        }
                        $instructorName = $netId. '_' .$lastName;
                        $instructionPath = $sectionPath . '/' . $instructorName;
                        $instructorFolder = $directoryClass->findOneBy(array('path' => $instructionPath));
                        if(!$instructorFolder){
                            $instructorFolder = new Directory($instructorName,$user,$instructionPath);
                            $instructorFolder->setParent($sectionFolder);
                            $instructorFolder->addUser($user);
                            $instructorFolder->addRole($Mentor);
                            $instructorFolder->addRole($Developer);
                            $instructorFolder->addRole($Admin);
                            $entityManager->persist($instructorFolder);
                        }
                        $entityManager->flush();
                }

                $roles = array_diff($roles, array('instructor'));
            }
            foreach($roles as $r){
                $folder = $directoryClass->findOneBy(array('path' => '/root/' . $r));
                if(!$folder){
                    $folder = new Directory($r,$admin,'/root/' . $r);
                    $folder->setParent($root);
                    $folder->addRole($Admin);
                    $folder->addRole($Developer);
                    switch ($r) {
                        case 'admin': 
                        $entityManager->persist($folder);
                        break;
                        case 'mentor':
                        $folder->addRole($Mentor);
                        $entityManager->persist($folder);
                        break;
                        case 'student':
                        $folder->addRole($Mentor);
                        $folder->addRole($Student);
                        $entityManager->persist($folder);
                        break;
                        default:
                        $entityManager->persist($folder);
                        break;
                    }
                }
                if(!($r=='student')){
                    $Name = $netId. '_' . $lastName;
                    $NameFolder = $directoryClass->findOneBy(array('path' => '/root/' . $r . '/' .$Name));
                    if(!$NameFolder){
                        $NameFolder = new Directory( $Name,$user,'/root/' . $r . '/' .$Name);
                        $NameFolder->setparent($folder);
                        $NameFolder->addRole($Admin);
                        $NameFolder->addRole($Developer);
                        switch ($r) {
                            case 'admin': 
                            $entityManager->persist($NameFolder);
                            break;
                            case 'mentor':
                            $NameFolder->addRole($Mentor);
                            $entityManager->persist($NameFolder);
                            break;
                            default:
                            $entityManager->persist($NameFolder);
                            break;
                        }

                    }
                }
                $entityManager->flush();
            }
            
        }
        catch (IOExceptionInterface $e) {
            return new Response(Response::HTTP_NOT_IMPLEMENTED);
        }
        return null;
    }



}
