<?php
namespace Mamba\EncountersBundle\Script;

use Mamba\EncountersBundle\Script\ScriptTrait;

/**
 * CronScript
 *
 * @package EncountersBundle
 */
abstract class CronScript extends \Core\ScriptBundle\CronScript {
    use ScriptTrait;
}