<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Soil_analysis
{

    #[ORM\Id]
    #[ORM\Column(type: "bigint")]
    private string $id;

    #[ORM\Column(type: "float")]
    private float $latitude;

    #[ORM\Column(type: "float")]
    private float $longitude;

    #[ORM\Column(type: "float")]
    private float $ph;

    #[ORM\Column(type: "float")]
    private float $sand_percent;

    #[ORM\Column(type: "float")]
    private float $silt_percent;

    #[ORM\Column(type: "float")]
    private float $clay_percent;

    #[ORM\Column(type: "float")]
    private float $nitrogen;

    #[ORM\Column(type: "float")]
    private float $phosphorus;

    #[ORM\Column(type: "float")]
    private float $potassium;

    #[ORM\Column(type: "float")]
    private float $organic_carbon;

    #[ORM\Column(type: "string", length: 128)]
    private string $source;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $collected_at;

    #[ORM\Column(type: "float")]
    private float $sand;

    #[ORM\Column(type: "float")]
    private float $clay;

    #[ORM\Column(type: "float")]
    private float $silt;

    #[ORM\Column(type: "float")]
    private float $organic_matter;

    #[ORM\Column(type: "float")]
    private float $cation_exchange_capacity;

    #[ORM\Column(type: "string", length: 100)]
    private string $soil_type;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getLatitude()
    {
        return $this->latitude;
    }

    public function setLatitude($value)
    {
        $this->latitude = $value;
    }

    public function getLongitude()
    {
        return $this->longitude;
    }

    public function setLongitude($value)
    {
        $this->longitude = $value;
    }

    public function getPh()
    {
        return $this->ph;
    }

    public function setPh($value)
    {
        $this->ph = $value;
    }

    public function getSand_percent()
    {
        return $this->sand_percent;
    }

    public function setSand_percent($value)
    {
        $this->sand_percent = $value;
    }

    public function getSilt_percent()
    {
        return $this->silt_percent;
    }

    public function setSilt_percent($value)
    {
        $this->silt_percent = $value;
    }

    public function getClay_percent()
    {
        return $this->clay_percent;
    }

    public function setClay_percent($value)
    {
        $this->clay_percent = $value;
    }

    public function getNitrogen()
    {
        return $this->nitrogen;
    }

    public function setNitrogen($value)
    {
        $this->nitrogen = $value;
    }

    public function getPhosphorus()
    {
        return $this->phosphorus;
    }

    public function setPhosphorus($value)
    {
        $this->phosphorus = $value;
    }

    public function getPotassium()
    {
        return $this->potassium;
    }

    public function setPotassium($value)
    {
        $this->potassium = $value;
    }

    public function getOrganic_carbon()
    {
        return $this->organic_carbon;
    }

    public function setOrganic_carbon($value)
    {
        $this->organic_carbon = $value;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setSource($value)
    {
        $this->source = $value;
    }

    public function getCollected_at()
    {
        return $this->collected_at;
    }

    public function setCollected_at($value)
    {
        $this->collected_at = $value;
    }

    public function getSand()
    {
        return $this->sand;
    }

    public function setSand($value)
    {
        $this->sand = $value;
    }

    public function getClay()
    {
        return $this->clay;
    }

    public function setClay($value)
    {
        $this->clay = $value;
    }

    public function getSilt()
    {
        return $this->silt;
    }

    public function setSilt($value)
    {
        $this->silt = $value;
    }

    public function getOrganic_matter()
    {
        return $this->organic_matter;
    }

    public function setOrganic_matter($value)
    {
        $this->organic_matter = $value;
    }

    public function getCation_exchange_capacity()
    {
        return $this->cation_exchange_capacity;
    }

    public function setCation_exchange_capacity($value)
    {
        $this->cation_exchange_capacity = $value;
    }

    public function getSoil_type()
    {
        return $this->soil_type;
    }

    public function setSoil_type($value)
    {
        $this->soil_type = $value;
    }
}
