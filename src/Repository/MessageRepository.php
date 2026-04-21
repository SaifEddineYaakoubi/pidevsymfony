<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Récupère toutes les conversations d'un utilisateur (le dernier message de chaque conversation)
     */
    public function findConversations(Utilisateur $user)
    {
        // 1. Récupérer tous les messages impliquant l'utilisateur, triés par date décroissante
        $messages = $this->createQueryBuilder('m')
            ->where('m.sender = :user OR m.receiver = :user')
            ->setParameter('user', $user)
            ->orderBy('m.sentAt', 'DESC')
            ->getQuery()
            ->getResult();

        $conversations = [];
        $partnersHandled = [];

        /** @var Message $msg */
        foreach ($messages as $msg) {
            $partner = ($msg->getSender()->getIdUser() === $user->getIdUser()) 
                ? $msg->getReceiver() 
                : $msg->getSender();
            
            $partnerId = $partner->getIdUser();

            // Ne garder que le message le plus récent pour chaque partenaire
            if (!isset($partnersHandled[$partnerId])) {
                $partnersHandled[$partnerId] = true;
                $conversations[] = [
                    'partner_id' => $partnerId,
                    'content' => $msg->getContent(),
                    'sent_at' => $msg->getSentAt()->format('Y-m-d H:i:s'),
                    'is_read' => $msg->isRead(),
                    'sender_id' => $msg->getSender()->getIdUser()
                ];
            }
        }

        return $conversations;
    }

    /**
     * Récupère l'historique des messages entre deux utilisateurs
     */
    public function findChatHistory(Utilisateur $user1, Utilisateur $user2)
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.receiver = :user2) OR (m.sender = :user2 AND m.receiver = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les messages non lus pour un utilisateur
     */
    public function countUnreadMessages(Utilisateur $user)
    {
        return $this->createQueryBuilder('m')
            ->select('count(m.id)')
            ->where('m.receiver = :user')
            ->andWhere('m.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
