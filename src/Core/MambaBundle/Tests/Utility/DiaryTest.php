<?php
namespace Core\MambaBundle\Tests\Utility;

class DiaryTest extends MambaTest {

    public function testGetPosts() {
        $this->Diary()->getPosts(560015854, 0);
        $this->Diary()->getPosts(159206311, 0);
    }

    protected function Diary() {
        return $this->getMamba()->nocache()->Diary();
    }
}