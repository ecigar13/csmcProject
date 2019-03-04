<?php


namespace App\DataType;


class GraduationSemester
{
    const SEASONS = array('Spring', 'Summer', 'Fall');

    /**
     * @var string|null
     */
    private $season;

    /**
     * @var integer|null
     */
    private $year;

    private function __construct()
    {
    }

    /**
     * Exists mostly to encapsulate the invariant that if both fields are null, the whole object should be null.
     *
     * @param array $array
     * @return GraduationSemester|null
     */
    public static function createFromArray(array $array)
    {
        if (!isset($array['season']) && !isset($array['year'])) {
            return null;
        }

        $semester = new self();
        $semester->season = isset($array['season']) ? $array['season'] : null;
        $semester->year = isset($array['year']) ? $array['year'] : null;

        return $semester;
    }

    /**
     * @return null|string
     */
    public function getSeason()
    {
        return $this->season;
    }

    /**
     * @return int|null
     */
    public function getYear()
    {
        return $this->year;
    }

    public function __toString()
    {
        return sprintf('%s %d', $this->season, $this->year);
    }

}