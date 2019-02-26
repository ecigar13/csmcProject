<?php

namespace App\Entity\User\Info;

use App\Entity\File\File;
use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_profile")
 */
class Profile {
    /**
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="App\Entity\User\User", inversedBy="profile")
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=17, nullable=true)
     */
    private $preferredName;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\File\File")
     * @ORM\JoinColumn(name="image_file_id", referencedColumnName="id")
     */
    private $image;

    // TODO keep track of past images
    private $pastImages;

    private function __construct(User $user, string $preferredName) {
        $this->user = $user;
        $this->preferredName = $preferredName;
    }

    public static function createForUser(User $user) {
        $f = $user->getFirstName();
        $f = strtok($f, ' ');
        if(strlen($f) > 17) {
            $f = substr($f, 0, 17);
        }
        $profile = new Profile($user, $f);
        return $profile;
    }

    public function resetPreferredName() {
        $this->preferredName = $this->user->getFirstName();
    }

    public function getPreferredName() {
        return $this->preferredName;
    }

    public function updatePreferredName(string $name) {
        $this->preferredName = $name;

        return $this;
    }

    /**
     * @return File|null
     */
    public function getProfilePicture() {
        return $this->image;
    }

    /**
     * @param File $image
     */
    public function updateProfilePicture(File $image) {
        $this->image = $image;
    }
}