<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Service pour l'envoi d'emails d'alertes de stock
 */
class MailService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private string $mailerFrom;
    private string $mailerAdmin;

    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->mailerFrom = getenv('MAILER_FROM') ?: 'Agrifarm <noreply@agrifarm.com>';
        $this->mailerAdmin = getenv('MAILER_ADMIN') ?: 'maram.abdeladhim@esprit.tn';
    }

    /**
     * Envoie une alerte de stock faible par email
     *
     * @param string $produitNom Le nom du produit en stock faible
     * @param int $stockActuel La quantité actuelle en stock
     * @param int $seuil Le seuil minimum configuré
     * @return bool True si l'email a été envoyé avec succès, false sinon
     */
    public function sendStockAlert(string $produitNom, int $stockActuel, int $seuil): bool
    {
        try {
            $date = date('d/m/Y H:i');

            $email = (new Email())
                ->from($this->mailerFrom)
                ->to($this->mailerAdmin)
                ->subject("⚠️ Stock Faible — $produitNom | Agrifarm")
                ->html("
                    <!DOCTYPE html>
                    <html>
                    <body style=\"font-family:Arial;background:#f5f5f5;padding:20px\">
                      <div style=\"max-width:600px;margin:auto;background:white;border-radius:10px;overflow:hidden\">
                        <div style=\"background:#2d6a4f;padding:20px;text-align:center\">
                          <h1 style=\"color:white;margin:0\">🌱 AGRIFARM</h1>
                          <p style=\"color:#95d5b2;margin:5px 0\">Système de gestion agricole</p>
                        </div>
                        <div style=\"padding:30px\">
                          <div style=\"background:#dc3545;color:white;padding:10px;border-radius:5px;text-align:center;margin-bottom:20px\">
                            <h2 style=\"margin:0\">⚠️ ALERTE STOCK FAIBLE</h2>
                          </div>
                          <table style=\"width:100%;border-collapse:collapse\">
                            <tr style=\"background:#f8f9fa\">
                              <td style=\"padding:10px;border:1px solid #dee2e6\">Produit</td>
                              <td style=\"padding:10px;border:1px solid #dee2e6\"><strong>$produitNom</strong></td>
                            </tr>
                            <tr>
                              <td style=\"padding:10px;border:1px solid #dee2e6\">Stock actuel</td>
                              <td style=\"padding:10px;border:1px solid #dee2e6;color:#dc3545\"><strong>$stockActuel kg</strong></td>
                            </tr>
                            <tr style=\"background:#f8f9fa\">
                              <td style=\"padding:10px;border:1px solid #dee2e6\">Seuil minimum</td>
                              <td style=\"padding:10px;border:1px solid #dee2e6\">$seuil kg</td>
                            </tr>
                            <tr>
                              <td style=\"padding:10px;border:1px solid #dee2e6\">Date alerte</td>
                              <td style=\"padding:10px;border:1px solid #dee2e6\">$date</td>
                            </tr>
                          </table>
                          <p style=\"margin-top:20px;color:#666\">
                            Veuillez réapprovisionner ce produit rapidement.
                          </p>
                          <a href=\"http://127.0.0.1:8000/stock/crud\"
                             style=\"display:inline-block;background:#2d6a4f;color:white;padding:10px 20px;border-radius:5px;text-decoration:none;margin-top:10px\">
                            Gérer le stock →
                          </a>
                        </div>
                        <div style=\"background:#f8f9fa;padding:15px;text-align:center;color:#666;font-size:12px\">
                          © 2026 Agrifarm — Email automatique, ne pas répondre
                        </div>
                      </div>
                    </body>
                    </html>
                ");

            $this->mailer->send($email);

            $this->logger->info("Alerte stock envoyée pour le produit: $produitNom (stock: $stockActuel, seuil: $seuil)");

            return true;

        } catch (TransportExceptionInterface $e) {
            $this->logger->error("Erreur lors de l'envoi de l'alerte stock pour $produitNom: " . $e->getMessage());
            return false;
        }
    }
}