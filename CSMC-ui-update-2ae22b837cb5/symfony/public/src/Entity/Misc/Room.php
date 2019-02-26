<?php

namespace App\Entity\Misc;

use App\Entity\Interfaces\ModifiableInterface;
use App\Entity\Traits\ModifiableTrait;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Misc\RoomRepository")
 * @ORM\Table(name="room")
 *
 * @UniqueEntity(
 *     fields={"building", "floor", "number"},
 *     message="Room already exists!"
 * )
 */
class Room implements ModifiableInterface {
    use ModifiableTrait;
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="building", length=4)
     *
     * @Assert\Length(
     *     min = 2,
     *     max = 4,
     *     minMessage = "Building Abbreviation must be at least {{ limit }} characters long",
     *     maxMessage = "Building Abbreviation cannot be longer than {{ limit }} characters"
     * )
     */
    private $building;

    /**
     * @ORM\Column(type="integer", name="floor")
     */
    private $floor;

    /**
     * @ORM\Column(type="integer", name="number")
     */
    private $number;

    /**
     * @ORM\Column(type="string", name="description", length=64)
     */
    private $description;

    /**
     * @ORM\Column(type="integer", name="capacity")
     */
    private $capacity;

    /**
     * @ORM\Column(type="boolean", name="active")
     */
    private $active;

    public function __construct(string $building, int $floor, int $number, string $description, int $capacity, bool $active) {
        $this->building = $building;
        $this->floor = $floor;
        $this->number = $number;
        $this->description = $description;
        $this->capacity = $capacity;
        $this->active = $active;
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
     * Set building
     *
     * @param string $building
     *
     * @return Room
     */
    public function setBuilding($building) {
        $this->building = $building;

        return $this;
    }

    /**
     * Get building
     *
     * @return string
     */
    public function getBuilding() {
        return $this->building;
    }

    /**
     * Set floor
     *
     * @param integer $floor
     *
     * @return Room
     */
    public function setFloor($floor) {
        $this->floor = $floor;

        return $this;
    }

    /**
     * Get floor
     *
     * @return integer
     */
    public function getFloor() {
        return $this->floor;
    }

    /**
     * Set number
     *
     * @param integer $number
     *
     * @return Room
     */
    public function setNumber($number) {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number
     *
     * @return integer
     */
    public function getNumber() {
        return $this->number;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Room
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
     * Set capacity
     *
     * @param integer $capacity
     *
     * @return Room
     */
    public function setCapacity($capacity) {
        $this->capacity = $capacity;

        return $this;
    }

    /**
     * Get capacity
     *
     * @return integer
     */
    public function getCapacity() {
        return $this->capacity;
    }

    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return Room
     */
    public function setActive($active) {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive() {
        return $this->active;
    }

    public function __toString() {
        return sprintf('%s %d.%03d', $this->building, $this->floor, $this->number);
    }
}
