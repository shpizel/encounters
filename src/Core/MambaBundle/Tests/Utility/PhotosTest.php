<?php
namespace Core\MambaBundle\Tests\Utility;

class PhotosTest extends MambaTest {

    /**
     * photos.getAlbums test
     */
    public function getAlbums() {
        $this->Photos()->getAlbums(560015854);
        $this->Photos()->getAlbums(159206311);
    }

    /**
     * photos.get test
     */
    public function testGet() {
        $this->Photos()->get(560015854);
        $this->Photos()->get(159206311);
    }

    protected function Photos() {
        return $this->getMamba()->nocache()->Photos();
    }
}