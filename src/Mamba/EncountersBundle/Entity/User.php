<?php

namespace Mamba\EncountersBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mamba\EncountersBundle\Entity\User
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class User
{
    /**
     * @var integer $user_id
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="integer")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $user_id;

    /**
     * @var string $gender
     *
     * @ORM\Column(name="gender", type="string", length=1)
     */
    private $gender;

    /**
     * @var integer $age
     *
     * @ORM\Column(name="age", type="integer")
     */
    private $age;

    /**
     * @var integer $country_id
     *
     * @ORM\Column(name="country_id", type="integer")
     */
    private $country_id;

    /**
     * @var integer $region_id
     *
     * @ORM\Column(name="region_id", type="integer")
     */
    private $region_id;

    /**
     * @var integer $city_id
     *
     * @ORM\Column(name="city_id", type="integer")
     */
    private $city_id;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user_id
     *
     * @param integer $userId
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;
    }

    /**
     * Get user_id
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->user_id;
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
     * Set country_id
     *
     * @param integer $countryId
     */
    public function setCountryId($countryId)
    {
        $this->country_id = $countryId;
    }

    /**
     * Get country_id
     *
     * @return integer 
     */
    public function getCountryId()
    {
        return $this->country_id;
    }

    /**
     * Set region_id
     *
     * @param integer $regionId
     */
    public function setRegionId($regionId)
    {
        $this->region_id = $regionId;
    }

    /**
     * Get region_id
     *
     * @return integer 
     */
    public function getRegionId()
    {
        return $this->region_id;
    }

    /**
     * Set city_id
     *
     * @param integer $cityId
     */
    public function setCityId($cityId)
    {
        $this->city_id = $cityId;
    }

    /**
     * Get city_id
     *
     * @return integer 
     */
    public function getCityId()
    {
        return $this->city_id;
    }
}