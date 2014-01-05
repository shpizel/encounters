<?php
namespace Core\MambaBundle\Tests\Utility;

class SearchTest extends MambaTest {

    /**
     * search.get test
     */
    public function testGet() {
        $this->Search()->get();
    }

    protected function Search() {
        return $this->getMamba()->nocache()->Search();
    }
}