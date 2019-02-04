<?php

namespace App\Entity\User;

use App\Entity\Interfaces\ModifiableInterface;
use App\Entity\Traits\ModifiableTrait;
use \Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_group")
 *
 * @UniqueEntity(
 *     fields = {"name"},
 *     message = "This user group {{ value }} already exists!"
 * )
 */
// TODO not sure serializable is actually needed, not sure why team added it
class UserGroup implements \Serializable, ModifiableInterface {
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
     *      max = 32,
     *      maxMessage = "The user group name cannot be longer than {{ limit }} characters!"
     * )
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="groups")
     * @ORM\JoinTable(name="user_groups")
     */
    private $users;

    /**
     * Constructor
     */
    public function __construct(string $name) {
        $this->name = $name;
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @ORM\Column(type="string", name="description", length=1024, nullable=true)
     *
     * @Assert\Length(
     *      min = 1,
     *      max = 1025,
     *      maxMessage = "The description cannot be longer than {{ limit }} characters!"
     * )
     */
    private $description;

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
     * Set name
     *
     * @param string $name
     *
     * @return UserGroup
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
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
     * Set description
     *
     * @param string $description
     *
     * @return UserGroup
     */
    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Add user
     *
     * @param \App\Entity\User\User $user
     *
     * @return UserGroup
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
     * Get userGroup
     *
     * userGroup_NAME
     *
     * @return string
     */
    // TODO figure out what this is actually for
    public function getUserGroup() {
        return 'userGroup_' . str_replace(" ", "_", strtoupper($this->name));
    }

    public function serialize() {
        return json_encode(array(
            $this->id,
            $this->name,
            $this->description,
            $this->users
        ));
    }

    public function unserialize($serialized) {
        list ($this->id, $this->name, $this->description, $this->users) = json_decode($serialized);
    }
}
