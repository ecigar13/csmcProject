<?php

namespace App\Entity\User;

use App\Entity\Interfaces\ModifiableInterface;
use App\Entity\Traits\ModifiableTrait;
use \Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="role")
 *
 * @UniqueEntity(
 *     fields = {"name"},
 *     message = "The role {{ value }} already exists!"
 *  )
 */
class Role extends \Symfony\Component\Security\Core\Role\Role implements \Serializable, ModifiableInterface {
    use ModifiableTrait;
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="name", length=32, unique=true)
     *
     * @Assert\Length(
     *      min = 1,
     *      max = 36,
     *      maxMessage = "The role name cannot be longer than {{ limit }} characters!"
     * )
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="roles")
     * @ORM\JoinTable(name="user_roles")
     */
    private $users;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\File\VirtualFile",  mappedBy="roles")
     */
    private $virtualFiles;

    /**
     * Constructor
     */
    public function __construct(string $name) {
        $this->name = $name;
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->virtualFiles = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return string $name
     */
    public function __toString() {
        return $this->name;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Add user
     *
     * @param \App\Entity\User\User $user
     *
     * @return Role
     */
    public function addUser(\App\Entity\User\User $user) {
        $this->users [] = $user;

        return $this;
    }

    /**
     * Remove user
     *
     * @param \App\Entity\User\User $user
     */
    public function removeUser(\App\Entity\User\User $user) {
        $this->users->removeElement($user);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers() {
        return $this->users;
    }

    /**
     * Get role
     *
     * ROLE_NAME
     *
     * @return string
     */
    public function getRole() {
        return 'ROLE_' . str_replace(" ", "_", strtoupper($this->name));
    }

    public function serialize() {
        return json_encode(array(
            $this->id,
            $this->name,
            $this->users
        ));
    }

    public function unserialize($serialized) {
        list ($this->id, $this->name, $this->users) = json_decode($serialized);
    }
}
