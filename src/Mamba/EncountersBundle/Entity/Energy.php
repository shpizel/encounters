<?php

namespace Mamba\EncountersBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mamba\EncountersBundle\Entity\Energy
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Energy
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
     * @var integer $energy
     *
     * @ORM\Column(name="energy", type="integer")
     */
    private $energy;


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
     * Set energy
     *
     * @param integer $energy
     */
    public function setEnergy($energy)
    {
        $this->energy = $energy;
    }

    /**
     * Get energy
     *
     * @return integer 
     */
    public function getEnergy()
    {
        return $this->energy;
    }
}