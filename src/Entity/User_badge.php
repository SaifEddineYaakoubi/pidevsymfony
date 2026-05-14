<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User_badge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: false)]
    private Utilisateur $user;

    #[ORM\ManyToOne(targetEntity: Badge::class)]
    #[ORM\JoinColumn(name: 'badge_id', referencedColumnName: 'id', nullable: false)]
    private Badge $badge;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): Utilisateur
    {
        return $this->user;
    }

    public function setUser(Utilisateur $user): self
    {
        $this->user = $user;
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

    public function getCreated_at(): \DateTimeInterface
    {
        return $this->created_at;
    }

    // Setter removed - created_at is managed automatically

    // Legacy methods for compatibility
    public function getUser_id(): ?int
    {
        return $this->user?->getIdUser();
    }

    public function getBadge_id(): ?int
    {
        return $this->badge?->getId();
    }
}
