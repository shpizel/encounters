<?php
namespace Core\MambaBundle\Tests\Utility;

use Core\MambaBundle\Tests\Utility\MambaTest;

class AnketaTest extends MambaTest {

    /**
     * anketa.getInfo test
     *
     * Cases:
     * 1) id(s), login(s), array or scalar
     * 2) ids non integers
     * 3) login non strings
     * 4) blocks
     * 5) multi
     */
    public function testGetInfo() {
        $this->Anketa()->getInfo('shpizel');
        $this->Anketa()->getInfo(560015854);
        $this->Anketa()->getInfo('esciloner');
        $this->Anketa()->getInfo(159206311);
        $this->Anketa()->getInfo(['shpizel, esciloner']);
        $this->Anketa()->getInfo([560015854, 159206311]);
    }

    /**
     * anketa.getTravel test
     *
     *
     */
    public function testGetTravel() {
        $this->Anketa()->getTravel(560015854);
        $this->Anketa()->getTravel(159206311);
    }

    /**
     * anketa.getFlags test
     *
     *
     */
    public function testGetFlags() {
        $this->Anketa()->getFlags(159206311);
        $this->Anketa()->getFlags(560015854);
        $this->Anketa()->getFlags([159206311, 560015854]);
    }

    /**
     * anketa.isOnline test
     *
     *
     */
    public function testIsOnline() {
        $this->Anketa()->isOnline(159206311);
        $this->Anketa()->isOnline(560015854);
        $this->Anketa()->isOnline([159206311, 560015854]);
    }

    /**
     * anketa.isAppUser test
     *
     *
     */
    public function testIsAppUser() {
        $this->Anketa()->isAppUser(159206311);
        $this->Anketa()->isAppUser(560015854);
        $this->Anketa()->isAppUser([159206311, 560015854]);
    }

    protected function Anketa() {
        return $this->getMamba()->nocache()->Anketa();
    }
}