<?php

namespace Mamba\EncountersBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mamba\EncountersBundle\Entity\Billing
 *
 * @ORM\Table(name="Billing")
 * @ORM\Entity
 */
class Billing
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
     * @var integer $appId
     *
     * @ORM\Column(name="app_id", type="integer", nullable=true)
     */
    private $appId;

    /**
     * @var integer $userId
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $userId;

    /**
     * @var integer $operationId
     *
     * @ORM\Column(name="operation_id", type="integer", nullable=true)
     */
    private $operationId;

    /**
     * @var float $amount
     *
     * @ORM\Column(name="amount", type="float", nullable=true)
     */
    private $amount;

    /**
     * @var float $amountDeveloper
     *
     * @ORM\Column(name="amount_developer", type="float", nullable=true)
     */
    private $amountDeveloper;

    /**
     * @var integer $validationId
     *
     * @ORM\Column(name="validation_id", type="integer", nullable=true)
     */
    private $validationId;

    /**
     * @var integer $time
     *
     * @ORM\Column(name="time", type="integer", nullable=true)
     */
    private $time;

    /**
     * @var string $sig
     *
     * @ORM\Column(name="sig", type="string", length=64, nullable=true)
     */
    private $sig;

    /**
     * @var boolean $billed
     *
     * @ORM\Column(name="billed", type="boolean", nullable=true)
     */
    private $billed;



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
     * Set appId
     *
     * @param integer $appId
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    }

    /**
     * Get appId
     *
     * @return integer 
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

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
     * Set operationId
     *
     * @param integer $operationId
     */
    public function setOperationId($operationId)
    {
        $this->operationId = $operationId;
    }

    /**
     * Get operationId
     *
     * @return integer 
     */
    public function getOperationId()
    {
        return $this->operationId;
    }

    /**
     * Set amount
     *
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * Get amount
     *
     * @return float 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set amountDeveloper
     *
     * @param float $amountDeveloper
     */
    public function setAmountDeveloper($amountDeveloper)
    {
        $this->amountDeveloper = $amountDeveloper;
    }

    /**
     * Get amountDeveloper
     *
     * @return float 
     */
    public function getAmountDeveloper()
    {
        return $this->amountDeveloper;
    }

    /**
     * Set validationId
     *
     * @param integer $validationId
     */
    public function setValidationId($validationId)
    {
        $this->validationId = $validationId;
    }

    /**
     * Get validationId
     *
     * @return integer 
     */
    public function getValidationId()
    {
        return $this->validationId;
    }

    /**
     * Set time
     *
     * @param integer $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * Get time
     *
     * @return integer 
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set sig
     *
     * @param string $sig
     */
    public function setSig($sig)
    {
        $this->sig = $sig;
    }

    /**
     * Get sig
     *
     * @return string 
     */
    public function getSig()
    {
        return $this->sig;
    }

    /**
     * Set billed
     *
     * @param boolean $billed
     */
    public function setBilled($billed)
    {
        $this->billed = $billed;
    }

    /**
     * Get billed
     *
     * @return boolean 
     */
    public function getBilled()
    {
        return $this->billed;
    }
}