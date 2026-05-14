<?php

namespace App\Entity;

use App\Repository\ResetPasswordRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestTrait;
use Symfony\Component\Serializer\Annotation\Ignore;
use SensitiveParameter;

#[ORM\Entity(repositoryClass: ResetPasswordRequestRepository::class)]
class ResetPasswordRequest implements ResetPasswordRequestInterface
{
    use ResetPasswordRequestTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: "user_id_id", referencedColumnName: "id_user", nullable: false)]
    private ?Utilisateur $user = null;

    public function __construct(Utilisateur $user, \DateTimeInterface $expiresAt, #[SensitiveParameter] string $selector, #[SensitiveParameter] string $hashedToken)
    {
        $this->user = $user;
        $this->initialize($expiresAt, $selector, $hashedToken);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    #[Ignore]
    public function getUser(): Utilisateur
    {
        return $this->user;
    }

    #[Ignore]
    public function getHashedToken(): string
    {
        return $this->hashedToken;
    }

    #[Ignore]
    public function getSelector(): string
    {
        return $this->selector;
    }
}
