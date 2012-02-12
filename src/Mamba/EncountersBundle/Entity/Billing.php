<?php

namespace Mamba\EncountersBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mamba\EncountersBundle\Entity\Billing
 */
class Billing
{
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var integer $appId
     */
    private $appId;

    /**
     * @var integer $userId
     */
    private $userId;

    /**
     * @var integer $operationId
     */
    private $operationId;

    /**
     * @var float $amount
     */
    private $amount;

    /**
     * @var float $amountDeveloper
     */
    private $amountDeveloper;

    /**
     * @var integer $validationId
     */
    private $validationId;

    /**
     * @var integer $time
     */
    private $time;

    /**
     * @var string $sig
     */
    private $sig;

    /**
     * @var boolean $billed
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