<?php
namespace Core\MambaBundle\Tests\Utility;

class GeoTest extends MambaTest {

    public function testGetCountries() {
        $this->Geo()->getCountries();
    }

    public function testGetRegions() {
        $countries = $this->Geo()->getCountries();
        $this->Geo()->getRegions($countries[array_rand($countries)]['id']);
    }

    public function testGetCities() {
        $countries = $this->Geo()->getCountries();
        $regions = $this->Geo()->getRegions($countries[array_rand($countries)]['id']);
        $this->Geo()->getCities($regions[array_rand($regions)]['id']);
    }

    public function testGetMetro() {
        $countries = $this->Geo()->getCountries();
        $regions = $this->Geo()->getRegions($countries[array_rand($countries)]['id']);
        $cities = $this->Geo()->getCities($regions[array_rand($regions)]['id']);
        $this->Geo()->getMetro($cities[array_rand($cities)]['id']);
    }

    protected function Geo() {
        return $this->getMamba()->nocache()->Geo();
    }
}