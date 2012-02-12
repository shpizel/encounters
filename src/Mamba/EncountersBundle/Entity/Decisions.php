<?php

namespace Mamba\EncountersBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mamba\EncountersBundle\Entity\Decisions
 */
class Decisions
{
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var integer $webUserId
     */
    private $webUserId;

    /**
     * @var integer $currentUserId
     */
    private $currentUserId;

    /**
     * @var integer $decision
     */
    private $decision;

    /**
     * @var datetime $time
     */
    private $time;

    /**
     * @var boolean $opened
     */
    private $opened;


    /**
     * Set id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

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
     * Set webUserId
     *
     * @param integer $webUserId
     */
    public function setWebUserId($webUserId)
    {
        $this->webUserId = $webUserId;
    }

    /**
     * Get webUserId
     *
     * @return integer 
     */
    public function getWebUserId()
    {
        return $this->webUserId;
    }

    /**
     * Set currentUserId
     *
     * @param integer $currentUserId
     */
    public function setCurrentUserId($currentUserId)
    {
        $this->currentUserId = $currentUserId;
    }

    /**
     * Get currentUserId
     *
     * @return integer 
     */
    public function getCurrentUserId()
    {
        return $this->currentUserId;
    }

    /**
     * Set decision
     *
     * @param integer $decision
     */
    public function setDecision($decision)
    {
        $this->decision = $decision;
    }

    /**
     * Get decision
     *
     * @return integer 
     */
    public function getDecision()
    {
        return $this->decision;
    }

    /**
     * Set time
     *
     * @param datetime $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * Get time
     *
     * @return datetime 
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set opened
     *
     * @param boolean $opened
     */
    public function setOpened($opened)
    {
        $this->opened = $opened;
    }

    /**
     * Get opened
     *
     * @return boolean 
     */
    public function getOpened()
    {
        return $this->opened;
    }
}