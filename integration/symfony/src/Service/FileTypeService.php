<?php

namespace App\Service;

use App\Helpers\FileManager;
use SplFileInfo;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;
use App\Entity\File\File as CSMCFile;
use Psr\Log\LoggerInterface;
class FileTypeService
{
    /**
     * @var LoggerInterface
     */
    public $logger;
    const IMAGE_SIZE = [
        FileManager::VIEW_LIST => '22',
        FileManager::VIEW_THUMBNAIL => '100',
    ];

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * FileTypeService constructor.
     *
     * @param RouterInterface $router
     * @param Packages $packages
     */
    public function __construct(RouterInterface $router,LoggerInterface $logger)
    {
        $this->router = $router;
        $this->logger=$logger;
    }

    public function preview(FileManager $fileManager, SplFileInfo $file, CSMCFile $csmcFile )
    {
        if ($fileManager->getImagePath()) {
            $filePath = htmlentities($fileManager->getBasePath(). '/' .$csmcFile->getPhysicalDirectory() . '/'. rawurlencode($file->getFilename()));
        } else {
            $filePath = $this->router->generate('file_manager_file',
                array_merge($fileManager->getQueryParameters(), ['fileName' => rawurlencode($file->getFilename())]));
        }
        $extension = $file->getExtension();
        $size = $this::IMAGE_SIZE[$fileManager->getView()];
        return $this->fileIcon($filePath, $extension, $size, true);
    }

    public function accept($type)
    {
        switch ($type) {
            case 'image':
                $accept = 'image/*';
                break;
            case 'media':
                $accept = 'video/*';
                break;
            case 'file':
                return false;
            default:
                return false;
        }

        return $accept;
    }

    public function fileIcon($filePath, $extension = null, $size = 75, $lazy = false)
    {
        // $this->logger->info("filePath");
        // $this->logger->info($filePath);
        // $this->logger->info("Extension");
        // $this->logger->info($extension);

        if (null === $extension) {
            $filePathTmp = strtok($filePath, '?');
            $extension = pathinfo($filePathTmp, PATHINFO_EXTENSION);
        }
        switch (true) {
            case $this->isYoutubeVideo($filePath):
            case preg_match('/(mp4|ogg|webm|avi|wmv|mov)$/i', $extension):
                $fa = 'far fa-file-video';
                break;
            case preg_match('/(mp3|wav)$/i', $extension):
                $fa = 'far fa-file-audio';
                break;
            case is_array(@getimagesize($filePath)):
            case preg_match('/(gif|png|jpe?g|svg)$/i', $extension):
                /*$query = parse_url($filePath, PHP_URL_QUERY);
                // $this->logger->info("query");
                // $this->logger->info($query);
                $time = 'time=' . time();
                // $this->logger->info("time");
                // $this->logger->info($time);
                $fileName = $query ? $filePath . '&' . $time : $filePath . '?' . $time;
                // $this->logger->info("fileName");
                // $this->logger->info($fileName);

                if ($lazy) {
                    $html = "<img class=\"lazy\" data-src=\"{$fileName}\" height='{$size}'>";
                } else {
                    $html = "<img src=\"{$fileName}\" height='{$size}'>";
                }

                return [
                    'path' => $filePath,
                    'html' => $html,
                    'image' => true
                ];*/
                $fa = 'far fa-file-image';
                break;
            case preg_match('/(pdf)$/i', $extension):
                $fa = 'far fa-file-pdf';
                break;
            case preg_match('/(docx?)$/i', $extension):
                $fa = 'far fa-file-word';
                break;
            case preg_match('/(xlsx?|csv)$/i', $extension):
                $fa = 'far fa-file-excel';
                break;
            case preg_match('/(pptx?)$/i', $extension):
                $fa = 'far fa-file-powerpoint';
                break;
            case preg_match('/(zip|rar|gz)$/i', $extension):
                $fa = 'far fa-file-archive';
                break;
            case filter_var($filePath, FILTER_VALIDATE_URL):
                $fa = 'fab fa-internet-explorer';
                break;
            default:
                $fa = 'far fa-file';
        }

        return [
            'path' => $filePath,
            'html' => "<i class='{$fa}' aria-hidden='true'></i>",
        ];
    }

    public function isYoutubeVideo($url)
    {
        $rx = '~
              ^(?:https?://)?                            
               (?:www[.])?                               
               (?:youtube[.]com/watch[?]v=|youtu[.]be/)  
               ([^&]{11})                                
                ~x';

        return $has_match = preg_match($rx, $url, $matches);
    }
}