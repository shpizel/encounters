<?php

namespace Mamba\EncountersBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mamba\EncountersBundle\Entity\Decisions
 *
 * @ORM\Table(name="Decisions")
 * @ORM\Entity
 */
class Decisions
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer $webUserId
     *
     * @ORM\Column(name="web_user_id", type="integer", nullable=true)
     */
    private $webUserId;

    /**
     * @var integer $currentUserId
     *
     * @ORM\Column(name="current_user_id", type="integer", nullable=true)
     */
    private $currentUserId;

    /**
     * @var integer $decision
     *
     * @ORM\Column(name="decision", type="integer", nullable=true)
     */
    private $decision;

    /**
     * @var integer $changed
     *
     * @ORM\Column(name="changed", type="integer", nullable=true)
     */
    private $changed;

    /**
     * @var integer $opened
     *
     * @ORM\Column(name="opened", type="integer", nullable=true)
     */
    private $opened;



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
     * Set changed
     *
     * @param integer $changed
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;
    }

    /**
     * Get changed
     *
     * @return integer 
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Set opened
     *
     * @param integer $opened
     */
    public function setOpened($opened)
    {
        $this->opened = $opened;
    }

    /**
     * Get opened
     *
     * @return integer 
     */
    public function getOpened()
    {
        return $this->opened;
    }
}