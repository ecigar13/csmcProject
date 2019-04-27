<?php

namespace App\Utils;

use App\Entity\File\File;
use Psr\Log\LoggerInterface;

class ImageEditor {
    private $logger;
    private $uploadDirectory;

    public function __construct(LoggerInterface $logger, $uploadDirectory) {
        $this->logger = $logger;
        $this->uploadDirectory = $uploadDirectory;
    }

    public function getCroppedImage(File $image) {
        $path = $this->uploadDirectory . '/' . $image->getPhysicalPath();
        $this->logger->info("path");
        $this->logger->info($path);

        $image_data = json_decode($image->get('image'));
        $canvas_data = json_decode($image->get('canvas'));
        $crop_data = json_decode($image->get('crop'));
        $type = str_replace('image/', '', $image->get('extension'));

        $img = new \Imagick($path);
        $img->rotateImage('transparent', $image_data->{'rotate'} ?? 0);
        $img->scaleImage(
            $image_data->{'naturalWidth'} * ($image_data->{'scaleX'} ?? 1),
            $image_data->{'naturalHeight'} * ($image_data->{'scaleY'} ?? 1)
        );

        $canvas = new \Imagick();
        $canvas->newImage(
            $canvas_data->{'naturalWidth'},
            $canvas_data->{'naturalHeight'},
            new \ImagickPixel('transparent')
        );
        $canvas->setImageFormat($type);
        $canvas->compositeImage(
            $img,
            \Imagick::COMPOSITE_COPY,
            $image_data->{'left'},
            $image_data->{'top'}
        );

        $scale_x = $canvas_data->{'naturalWidth'} / $canvas_data->{'width'};
        $scale_y = $canvas_data->{'naturalHeight'} / $canvas_data->{'height'};

        $canvas->cropImage(
            $crop_data->{'width'} * $scale_x,
            $crop_data->{'height'} * $scale_y,
            ($crop_data->{'left'} - $canvas_data->{'left'}) * $scale_x,
            ($crop_data->{'top'} - $canvas_data->{'top'}) *$scale_y
        );

        $tmp = tempnam("/tmp", "csmc_profile_");
        $canvas->setFilename($tmp);
        $canvas->writeImage($tmp);

        return $canvas->getFilename();
    }

    public function getOriginImage(File $image) {
        $path = $this->uploadDirectory . '/' . $image->getPhysicalPath();

        $image_data = json_decode($image->get('image'));
        $canvas_data = json_decode($image->get('canvas'));
        $type = str_replace('image/', '', $image->get('extension'));

        $img = new \Imagick($path);
        $img->rotateImage('transparent', $image_data->{'rotate'} ?? 0);
        $img->scaleImage(
            $image_data->{'naturalWidth'} * ($image_data->{'scaleX'} ?? 1),
            $image_data->{'naturalHeight'} * ($image_data->{'scaleY'} ?? 1)
        );

        $canvas = new \Imagick();
        $canvas->newImage(
            $canvas_data->{'naturalWidth'},
            $canvas_data->{'naturalHeight'},
            new \ImagickPixel('transparent')
        );
        $canvas->setImageFormat($type);
        $canvas->compositeImage(
            $img,
            \Imagick::COMPOSITE_COPY,
            $image_data->{'left'},
            $image_data->{'top'}
        );

        $tmp = tempnam("/tmp", "csmc_profile_");
        $canvas->setFilename($tmp);
        $canvas->writeImage($tmp);

        return $canvas->getFilename();
    }
}