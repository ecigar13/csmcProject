<?php

namespace App\Controller;

use Artgris\Bundle\FileManagerBundle\Event\FileManagerEvents;
use App\Helpers\File;
use Doctrine\ORM\EntityRepository;
use App\Entity\File\Directory;
use App\Entity\File\Link;
//use Artgris\Bundle\FileManagerBundle\Helpers\FileManager;
use App\Helpers\FileManager;
use App\Twig\CSMCOrderExtension;
use App\Entity\File\FileHash;
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
        $isJson          = $request->get('json') ? true : false;
        if ($isJson) {
            unset($queryParameters['json']);
        }
        $fileManager = $this->newFileManager($queryParameters);
        $logger->info("Logging");
        $logger->info($fileManager->getDirName());
        $logger->info($fileManager->getBaseName());
        // Folder search
       $directoriesArbo = $this->retrieveSubDirectories($fileManager, DIRECTORY_SEPARATOR,$logger,$fileManager->getBaseName());
       
        // File search

        
        $logger->info($fileManager->getCurrentRoute());
        // $finderFiles = new Finder();
        // $finderFiles->in($fileManager->getCurrentPath())->depth(0);
        $finderFiles = $this->retrieveFiles($fileManager, $fileManager->getCurrentRoute());
        $logger->info(print_r($finderFiles,true));
        $regex = $fileManager->getRegex();

        $orderBy   = $fileManager->getQueryParameter('orderby');
        $orderDESC = CSMCOrderExtension::DESC === $fileManager->getQueryParameter('order');
        // if (!$orderBy) {
        //     $finderFiles->sortByType();
        // }

        switch ($orderBy) {
            case 'name':
                $finderFiles->sort(function (SplFileInfo $a, SplFileInfo $b) {
                    return strcmp(strtolower($b->getFilename()), strtolower($a->getFilename()));
                });
                break;
            case 'date':
                $finderFiles->sortByModifiedTime();
                break;
            case 'size':
                $finderFiles->sort(function (\SplFileInfo $a, \SplFileInfo $b) {
                    return $a->getSize() - $b->getSize();
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

        $formDelete = $this->createDeleteForm()->createView();
        $fileArray  = [];
        foreach ($finderFiles as $file) {
            $fileArray[] = new File($file, $this->get('translator'), $this->get('file_type_service'), $fileManager);
        }

        if ('dimension' === $orderBy) {
            usort($fileArray, function (File $a, File $b) {
                $aDimension = $a->getDimension();
                $bDimension = $b->getDimension();
                if ($aDimension && !$bDimension) {
                    return 1;
                }

                if (!$aDimension && $bDimension) {
                    return -1;
                }

                if (!$aDimension && !$bDimension) {
                    return 0;
                }

                return ($aDimension[0] * $aDimension[1]) - ($bDimension[0] * $bDimension[1]);
            });
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
        

        //Uploading Folder---------------------------------------->
        if ($form->isSubmitted() && $form->isValid()) {
            $data      = $form->getData();

            // Get Name and Parent Path
            $directoryName = $data['name'];
            $logger->info("UploadDirectory");
            $parentPath = $fileManager->getQueryParameters()['route'];
            $logger->info("parent");
            //$logger->info($fileManager->getQueryParameters()['route']);
            $logger->info($parentPath);
            $directoryPath =  $parentPath . DIRECTORY_SEPARATOR . $data['name'];

            //Search for Directory in Table
            $directoryClass = $this->getDoctrine()->getRepository(Directory::class);
            $directory=$directoryClass->findByPath($directoryPath);
            if($directory){
                $this->addFlash('danger', $translator->trans('folder.add.danger', ['%message%' => $data['name']]));
            }

            //Get Parent, create directory, set parent
            
            $parent=$directoryClass->findOneBy(array('path' => $parentPath));
            if (!$parent) {
                throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Parent folder does not exist.');
            }
            try{
                $directory  = new Directory($directoryName,$directoryPath,$this->getUser());
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

        return $this->render('fileManager/manager.html.twig', $parameters);
    }

    /**
     * @Route("/fms/rename/{fileName}", name="file_management_rename")
     *
     * @param Request $request
     * @param $fileName
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Exception
     */
    public function renameFileAction(Request $request, $fileName)
    {
        $translator = $this->get('translator');
        $queryParameters = $request->query->all();
        $formRename = $this->createRenameForm();
        /* @var Form $formRename */
        $formRename->handleRequest($request);
        if ($formRename->isSubmitted() && $formRename->isValid()) {
            $data = $formRename->getData();
            $extension = $data['extension'] ? '.'.$data['extension'] : '';
            $newfileName = $data['name'].$extension;
            if ($newfileName !== $fileName && isset($data['name'])) {
                $fileManager = $this->newFileManager($queryParameters);
                $NewfilePath = $fileManager->getCurrentPath().DIRECTORY_SEPARATOR.$newfileName;
                $OldfilePath = realpath($fileManager->getCurrentPath().DIRECTORY_SEPARATOR.$fileName);
                if (0 !== strpos($NewfilePath, $fileManager->getCurrentPath())) {
                    $this->addFlash('danger', $translator->trans('file.renamed.unauthorized'));
                } else {
                    $fs = new Filesystem();
                    try {
                        $fs->rename($OldfilePath, $NewfilePath);
                        $this->addFlash('success', $translator->trans('file.renamed.success'));
                        //File has been renamed successfully
                    } catch (IOException $exception) {
                        $this->addFlash('danger', $translator->trans('file.renamed.danger'));
                    }
                }
            } else {
                $this->addFlash('warning', $translator->trans('file.renamed.nochanged'));
            }
        }

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
        if ($form->isSubmitted() && $form->isValid()) {
            // remove file
            $fileManager = $this->newFileManager($queryParameters);
            $fs = new Filesystem();
            if (isset($queryParameters['delete'])) {
                $is_delete = false;
                foreach ($queryParameters['delete'] as $fileName) {
                    $filePath = realpath($fileManager->getCurrentPath().DIRECTORY_SEPARATOR.$fileName);
                    if (0 !== strpos($filePath, $fileManager->getCurrentPath())) {
                        $this->addFlash('danger', 'file.deleted.danger');
                    } else {
                        $this->dispatch(FileManagerEvents::PRE_DELETE_FILE);
                        try {
                            $fs->remove($filePath);
                            $is_delete = true;
                        } catch (IOException $exception) {
                            $this->addFlash('danger', 'file.deleted.unauthorized');
                        }
                        $this->dispatch(FileManagerEvents::POST_DELETE_FILE);
                    }
                }
                if ($is_delete) {
                    $this->addFlash('success', 'file.deleted.success');
                }
                unset($queryParameters['delete']);
            } else {
                $this->dispatch(FileManagerEvents::PRE_DELETE_FOLDER);
                try {
                    $fs->remove($fileManager->getCurrentPath());
                    $this->addFlash('success', 'folder.deleted.success');
                } catch (IOException $exception) {
                    $this->addFlash('danger', 'folder.deleted.unauthorized');
                }

                $this->dispatch(FileManagerEvents::POST_DELETE_FOLDER);
                $queryParameters['route'] = dirname($fileManager->getCurrentRoute());
                if ($queryParameters['route'] = '/') {
                    unset($queryParameters['route']);
                }

                return $this->redirectToRoute('file_management', $queryParameters);
            }
        }

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
    protected function createRenameForm()
    {
        return $this->createFormBuilder()
            ->add('name', TextType::class, [
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
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function uploadFileAction(Request $request, LoggerInterface $l)
    {
        //only accept httpRequest
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedException();
        }

        $em = $this->getDoctrine()->getManager();

        $uploaded_files = $request->files->get('files');
        $fileData = new FileData();
        $fileData->file = $uploaded_files[0];
        $file = CSMCFile::fromUploadData($fileData, $em);
        $em->persist($file);
        //get translator service.
        if (isset($file->error)) {
            $file->error = $this->get('translator')->trans($file->error);
        }
        $em->flush();
        $response = [
            'files'=>[
                [
                    'name'=>'name',
                    'size'=>'name',
                    'type'=>'name',
                ],
            ]
        ];
        return new JsonResponse($response);
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

        //Find parent from id and children from parent
        if($baseFolderName){
            $fileName = DIRECTORY_SEPARATOR . $fileManager->getBaseName();
            //$fileName = '/root';
            $queryParameters          = $fileManager->getQueryParameters();
            $queryParameters['route'] = $fileName;
            $queryParametersRoute     = $queryParameters;
            unset($queryParametersRoute['route']);

            // $filesNumber = $this->retrieveFilesNumber($directory->getPathname(), $fileManager->getRegex());
            // $fileSpan    = $filesNumber > 0 ? " <span class='label label-default'>{$filesNumber}</span>" : '';

            $directoriesList[] = [
                //'text'     => 'root',
                'text'     => $fileManager->getBaseName(),
                'icon'     => 'far fa-folder-open',
                'children' => $this->retrieveSubDirectories($fileManager, $fileName,$logger),
                'a_attr'   => [
                    'href' => $fileName ? $this->generateUrl('file_management', $queryParameters) : $this->generateUrl('file_management', $queryParametersRoute),
                ], 'state' => [
                    'selected' => $fileManager->getCurrentRoute() === $fileName,
                    'opened'   => true,
                ],
            ];

            return $directoriesList;
        }
            
        $directoryClass = $this->getDoctrine()->getRepository(Directory::class);
        $parent=$directoryClass->findOneBy(array('path' => $parentPath));
            if (!$parent) {
                throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Parent folder does not exist.');
            }
        $directories = $directoryClass->findByParent($parent);

        //List for tree
       

        foreach ($directories as $directory) {
            $fileName = $parentPath . '/' . $directory->getName();
            $queryParameters          = $fileManager->getQueryParameters();
            $queryParameters['route'] = $fileName;
            $queryParametersRoute     = $queryParameters;
            unset($queryParametersRoute['route']);

            // $filesNumber = $this->retrieveFilesNumber($directory->getPathname(), $fileManager->getRegex());
            // $fileSpan    = $filesNumber > 0 ? " <span class='label label-default'>{$filesNumber}</span>" : '';

            $directoriesList[] = [
                'text'     => $directory->getName(),
                'icon'     => 'far fa-folder-open',
                'children' => $this->retrieveSubDirectories($fileManager, $fileName,$logger),
                'a_attr'   => [
                    'href' => $fileName ? $this->generateUrl('file_management', $queryParameters) : $this->generateUrl('file_management', $queryParametersRoute),
                ], 'state' => [
                    'selected' => $fileManager->getCurrentRoute() === $fileName,
                    'opened'   => true,
                ],
            ];
        }

        return $directoriesList;
    }

    /**
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



}
