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
     * @return array<int, array{partner_id: int, content: string, sent_at: string, is_read: bool, sender_id: int}>
     */
    public function findConversations(Utilisateur $user): array
    {
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
            $sender   = $msg->getSender();
            $receiver = $msg->getReceiver();
            if ($sender === null || $receiver === null) {
                continue;
            }

            $partner = ($sender->getIdUser() === $user->getIdUser()) ? $receiver : $sender;
            $partnerId = $partner->getIdUser();
            if ($partnerId === null) {
                continue;
            }

            if (!isset($partnersHandled[$partnerId])) {
                $partnersHandled[$partnerId] = true;
                $sentAt = $msg->getSentAt();
                $conversations[] = [
                    'partner_id' => $partnerId,
                    'content'    => $msg->getContent(),
                    'sent_at'    => $sentAt !== null ? $sentAt->format('Y-m-d H:i:s') : '',
                    'is_read'    => $msg->isRead(),
                    'sender_id'  => $sender->getIdUser() ?? 0,
                ];
            }
        }

        return $conversations;
    }

    /**
     * Récupère l'historique des messages entre deux utilisateurs
     * @return Message[]
     */
    public function findChatHistory(Utilisateur $user1, Utilisateur $user2): array
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
    public function countUnreadMessages(Utilisateur $user): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('count(m.id)')
            ->where('m.receiver = :user')
            ->andWhere('m.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
