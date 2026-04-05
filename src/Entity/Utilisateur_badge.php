<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Utilisateur_badge
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $id_user;

    #[ORM\Column(type: "integer")]
    private int $id_badge;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_attribution;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getId_user()
    {
        return $this->id_user;
    }

    public function setId_user($value)
    {
        $this->id_user = $value;
    }

    public function getId_badge()
    {
        return $this->id_badge;
    }

    public function setId_badge($value)
    {
        $this->id_badge = $value;
    }

    public function getDate_attribution()
    {
        return $this->date_attribution;
    }

    public function setDate_attribution($value)
    {
        $this->date_attribution = $value;
    }
}
