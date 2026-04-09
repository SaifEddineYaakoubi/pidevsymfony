<?php
// src/Controller/UtilisateurController.php
namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/utilisateur', name: 'app_admin_utilisateur_')]
class UtilisateurController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(UtilisateurRepository $utilisateurRepository): Response
    {
        return $this->render('admin/pages/utilisateur_index.html.twig', [
            'utilisateurs' => $utilisateurRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('mot_de_passe')->getData();
            if ($plainPassword) {
                $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
                $utilisateur->setMotDePasse($hashedPassword);
            }

            $utilisateur->setDateCreation(new \DateTime());
            if ($utilisateur->getStatut() === null) {
                $utilisateur->setStatut(true);
            }

            $entityManager->persist($utilisateur);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true, 'message' => 'Utilisateur créé avec succès !']);
            }

            $this->addFlash('success', 'Utilisateur créé avec succès !');
            return $this->redirectToRoute('app_admin_utilisateur_index');
        }

        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView('admin/pages/utilisateur_form_modal.html.twig', [
                'form' => $form->createView(),
                'form_title' => 'Ajouter un utilisateur',
                'button_label' => 'Créer',
                'form_action' => $this->generateUrl('app_admin_utilisateur_new')
            ]);
            return $this->json(['html' => $html]);
        }

        return $this->render('admin/pages/utilisateur_form.html.twig', [
            'form' => $form->createView(),
            'form_title' => 'Ajouter un utilisateur',
            'button_label' => 'Créer',
            'form_action' => $this->generateUrl('app_admin_utilisateur_new')
        ]);
    }

    #[Route('/{id_user}', name: 'show', methods: ['GET'])]
    public function show(Utilisateur $utilisateur, Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView('admin/pages/utilisateur_show_modal.html.twig', [
                'utilisateur' => $utilisateur,
            ]);
            return $this->json(['html' => $html]);
        }

        return $this->render('admin/pages/utilisateur_show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    #[Route('/{id_user}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('mot_de_passe')->getData();
            if ($plainPassword) {
                $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
                $utilisateur->setMotDePasse($hashedPassword);
            }

            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true, 'message' => 'Utilisateur modifié avec succès !']);
            }

            $this->addFlash('success', 'Utilisateur modifié avec succès !');
            return $this->redirectToRoute('app_admin_utilisateur_index');
        }

        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView('admin/pages/utilisateur_form_modal.html.twig', [
                'form' => $form->createView(),
                'form_title' => 'Modifier l\'utilisateur',
                'button_label' => 'Mettre à jour',
                'form_action' => $this->generateUrl('app_admin_utilisateur_edit', ['id_user' => $utilisateur->getIdUser()])
            ]);
            return $this->json(['html' => $html]);
        }

        return $this->render('admin/pages/utilisateur_form.html.twig', [
            'form' => $form->createView(),
            'form_title' => 'Modifier l\'utilisateur',
            'button_label' => 'Mettre à jour',
            'form_action' => $this->generateUrl('app_admin_utilisateur_edit', ['id_user' => $utilisateur->getIdUser()])
        ]);
    }

    #[Route('/{id_user}/delete', name: 'delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($this->isCsrfTokenValid('delete' . $utilisateur->getIdUser(), $request->request->get('_token'))) {
            $entityManager->remove($utilisateur);
            $entityManager->flush();
            return $this->json(['success' => true, 'message' => 'Utilisateur supprimé avec succès !']);
        }

        return $this->json(['success' => false, 'message' => 'Token CSRF invalide !'], 400);
    }

    #[Route('/{id}/toggle-status', name: 'toggle_status', methods: ['POST'])]
    public function toggleStatus(Utilisateur $utilisateur, EntityManagerInterface $entityManager): JsonResponse
    {
        $utilisateur->setStatut(!$utilisateur->getStatut());
        $entityManager->flush();

        return $this->json(['success' => true, 'message' => 'Statut mis à jour']);
    }
}

