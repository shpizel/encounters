<?php

namespace Mamba\EncountersBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mamba\EncountersBundle\Entity\Users
 *
 * @ORM\Table(name="Users")
 * @ORM\Entity
 */
class Users
{
    /**
     * @var integer $userId
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $userId;

    /**
     * @var string $gender
     *
     * @ORM\Column(name="gender", type="string", length=4, nullable=true)
     */
    private $gender;

    /**
     * @var integer $age
     *
     * @ORM\Column(name="age", type="integer", nullable=true)
     */
    private $age;

    /**
     * @var integer $countryId
     *
     * @ORM\Column(name="country_id", type="integer", nullable=true)
     */
    private $countryId;

    /**
     * @var integer $regionId
     *
     * @ORM\Column(name="region_id", type="integer", nullable=true)
     */
    private $regionId;

    /**
     * @var integer $cityId
     *
     * @ORM\Column(name="city_id", type="integer", nullable=true)
     */
    private $cityId;

    /**
     * @var integer $updated
     *
     * @ORM\Column(name="updated", type="integer", nullable=true)
     */
    private $updated;



    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set gender
     *
     * @param string $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * Get gender
     *
     * @return string 
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set age
     *
     * @param integer $age
     */
    public function setAge($age)
    {
        $this->age = $age;
    }

    /**
     * Get age
     *
     * @return integer 
     */
    public function getAge()
    {
        return $this->age;
    }

    /**
     * Set countryId
     *
     * @param integer $countryId
     */
    public function setCountryId($countryId)
    {
        $this->countryId = $countryId;
    }

    /**
     * Get countryId
     *
     * @return integer 
     */
    public function getCountryId()
    {
        return $this->countryId;
    }

    /**
     * Set regionId
     *
     * @param integer $regionId
     */
    public function setRegionId($regionId)
    {
        $this->regionId = $regionId;
    }

    /**
     * Get regionId
     *
     * @return integer 
     */
    public function getRegionId()
    {
        return $this->regionId;
    }

    /**
     * Set cityId
     *
     * @param integer $cityId
     */
    public function setCityId($cityId)
    {
        $this->cityId = $cityId;
    }

    /**
     * Get cityId
     *
     * @return integer 
     */
    public function getCityId()
    {
        return $this->cityId;
    }

    /**
     * Set updated
     *
     * @param integer $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * Get updated
     *
     * @return integer 
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}