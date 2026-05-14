<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Utilisateur_badge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', nullable: false)]
    private Utilisateur $utilisateur;

    #[ORM\ManyToOne(targetEntity: Badge::class)]
    #[ORM\JoinColumn(name: 'id_badge', referencedColumnName: 'id', nullable: false)]
    private Badge $badge;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_attribution;

    public function __construct()
    {
        $this->date_attribution = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUtilisateur(): Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    public function getBadge(): Badge
    {
        return $this->badge;
    }

    public function setBadge(Badge $badge): self
    {
        $this->badge = $badge;
        return $this;
    }

    public function getDate_attribution(): \DateTimeInterface
    {
        return $this->date_attribution;
    }

    // Setter supprimé - date_attribution est gérée automatiquement
}
