<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Utilisateur;
use App\Repository\MessageRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/messages')]
class MessageController extends AbstractController
{
    #[Route('/', name: 'app_messages_index')]
    public function index(MessageRepository $messageRepository, UtilisateurRepository $utilisateurRepository): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        
        // Récupérer les conversations (interlocuteurs)
        $conversations = $messageRepository->findConversations($user);
        
        // Transformer les résultats associatifs en objets Utilisateur pour Twig
        $processedConversations = [];
        foreach ($conversations as $conv) {
            $partner = $utilisateurRepository->find($conv['partner_id']);
            if ($partner) {
                $processedConversations[] = [
                    'partner' => $partner,
                    'lastMessage' => $conv['content'],
                    'sentAt' => new \DateTime($conv['sent_at']),
                    'isRead' => (bool)$conv['is_read'],
                    'isMe' => ($conv['sender_id'] == $user->getIdUser())
                ];
            }
        }

        // Liste de tous les utilisateurs pour pouvoir démarrer une nouvelle conversation
        $allUsers = $utilisateurRepository->findAll();
        // Filtrer pour ne pas s'inclure soi-même
        $allUsers = array_filter($allUsers, function($u) use ($user) {
            return $u->getIdUser() !== $user->getIdUser();
        });

        return $this->render('messages/index.html.twig', [
            'conversations' => $processedConversations,
            'allUsers' => $allUsers
        ]);
    }

    #[Route('/chat/{id}', name: 'app_messages_chat')]
    public function chat(Utilisateur $partner, MessageRepository $messageRepository, EntityManagerInterface $em): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        
        // Récupérer l'historique
        $messages = $messageRepository->findChatHistory($user, $partner);
        
        // Marquer les messages entrants comme lus
        foreach ($messages as $msg) {
            if ($msg->getReceiver() === $user && !$msg->isRead()) {
                $msg->setIsRead(true);
            }
        }
        $em->flush();

        return $this->render('messages/conversation.html.twig', [
            'partner' => $partner,
            'messages' => $messages
        ]);
    }

    #[Route('/send', name: 'app_messages_send', methods: ['POST'])]
    public function send(Request $request, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $em): JsonResponse
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        
        $receiverId = $request->request->get('receiver_id');
        $content = $request->request->get('content');
        
        if (!$receiverId || !$content) {
            return new JsonResponse(['success' => false, 'message' => 'Données manquantes'], 400);
        }

        $receiver = $utilisateurRepository->find($receiverId);
        if (!$receiver) {
            return new JsonResponse(['success' => false, 'message' => 'Destinataire introuvable'], 404);
        }

        $message = new Message();
        $message->setSender($user);
        $message->setReceiver($receiver);
        $message->setContent((string) $content);
        
        $em->persist($message);
        $em->flush();

        $sentAt = $message->getSentAt();
        return new JsonResponse([
            'success' => true,
            'message' => 'Message envoyé',
            'sentAt' => $sentAt !== null ? $sentAt->format('d/m H:i') : ''
        ]);
    }

    #[Route('/unread-count', name: 'app_messages_unread_count')]
    public function unreadCount(MessageRepository $messageRepository): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if (!$user) return new Response('0');

        $count = $messageRepository->countUnreadMessages($user);
        return new Response((string)$count);
    }
}
