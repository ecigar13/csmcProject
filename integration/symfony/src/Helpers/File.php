<?php

namespace App\Helpers;

use App\Service\FileTypeService;
use App\Entity\File\File as CSMCFile;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Translation\TranslatorInterface;

class File
{
    /**
     *  @var CSMCFile
     */
    private $CSMCFile;

    /**
     * @var SplFileInfo
     */
    private $fileinfo;

    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var FileTypeService
     */
    private $fileTypeService;
    /**
     * @var FileManager
     */
    private $fileManager;
    private $preview;

    private $date;

    /**
     * File constructor.
     *
     * @param CSMCFile              $file
     * @param TranslatorInterface $translator
     * @param FileTypeService     $fileTypeService
     * @param FileManager         $fileManager
     *
     * @internal param $module
     */
    public function __construct(CSMCFile $CSMCFile, TranslatorInterface $translator, FileTypeService $fileTypeService, FileManager $fileManager)
    {
        $this->CSMCFile = $CSMCFile;
        $this->fileinfo = new SplFileInfo($CSMCFile->getPhysicalName(),$fileManager->getBasePath(). '/' .$CSMCFile->getPhysicalDirectory(),$fileManager->getBasePath(). '/' .$CSMCFile->getPhysicalDirectory() . '/'.$CSMCFile->getPhysicalName());
        $this->translator = $translator;
        $this->fileTypeService = $fileTypeService;
        $this->fileManager = $fileManager;
        $this->preview = $this->fileTypeService->preview($this->fileManager, $this->fileinfo, $this->CSMCFile);
        $this->date = $CSMCFile->giveDate();
    }

    public function getDimension()
    {
        return preg_match('/(gif|png|jpe?g|svg)$/i', $this->CSMCFile->get("extension")) ?
            getimagesize($this->fileinfo->getPathname()) : '';
    }

    public function getPath()
    {
        return $fileManager->getBasePath(). '/' .$CSMCFile->getPhysicalDirectory() . '/'.$CSMCFile->getPhysicalName();
    }


    public function getHTMLDimension()
    {
        $dimension = $this->getDimension();
        if ($dimension) {
            return "{$dimension[0]} × {$dimension[1]}";
        }
    }

    public function getdate()
    {
        return $this->date;
    }

    public function getHTMLSize()
    {
            $size = $this->CSMCFile->get("size") / 1000;
            $kb = $this->translator->trans('size.kb');
            $mb = $this->translator->trans('size.mb');

            return $size > 1000 ? number_format(($size / 1000), 1, '.', '').' '.$mb : number_format($size, 1, '.', '').' '.$kb;
    }

    public function getAttribut()
    {
        if ($this->fileManager->getModule()) {
            $attr = '';
            $dimension = $this->getDimension();
            if ($dimension) {
                $width = $dimension[0];
                $height = $dimension[1];
                $attr .= "data-width=\"{$width}\" data-height=\"{$height}\" ";
            }

            if ('file' === $this->fileinfo->getType()) {
                $attr .= "data-path=\"{$this->getPreview()['path']}\"";
                $attr .= ' class="select"';
            }

            return $attr;
        }
    }

    public function isImage() {

        return array_key_exists('image', $this->preview);

    }

    /**
     * @return CSMCFile
     */
    public function getFile()
    {
        return $this->CSMCFile;
    }

    /**
     * @return string
     */
    public function getextension()
    {
        return $this->CSMCFile->get("extension") ;
    }

    /**
     * @param SplFileInfo $file
     */
    public function setFile($file)
    {
        $this->fileinfo = $fileinfo;
    }

    /**
     * @return array
     */
    public function getPreview()
    {
        return $this->preview;
    }

    /**
     * @param array $preview
     */
    public function setPreview($preview)
    {
        $this->preview = $preview;
    }
}
