<?php
namespace Core\MambaBundle\Tests\Utility;

use Core\MambaBundle\API\Mamba;

abstract class MambaTest extends \PHPUnit_Framework_TestCase {

    const

        /**
         * Test environment
         *
         * @var str
         */
        ENVIRONMENT = 'dev'
    ;

    protected

        /**
         * Application kernel
         */
        $Kernel,

        /**
         * Container
         */
        $Container,

        /**
         * @var \Core\MambaBundle\API\Mamba
         */
        $Mamba
    ;

    public function setUp() {
        require_once(getcwd() . DIRECTORY_SEPARATOR . "app/AppKernel.php");

        $this->Kernel = new \AppKernel(self::ENVIRONMENT, true);
        $this->Kernel->boot();

        $this->Container = $this->Kernel->getContainer();
        $this->Mamba = $this->Container->get('mamba');

        parent::setUp();
    }

    public function tearDown() {
        $this->Kernel->shutdown();

        parent::tearDown();
    }

    /**
     * @return \Core\MambaBundle\API\Mamba
     */
    public function getMamba() {
        return $this->Mamba;
    }
}