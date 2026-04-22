<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Vente;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service pour générer des QR Codes pour les entités Client et Vente
 */
class QrCodeService
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Génère un QR Code pour une vente
     * 
     * @param Vente $vente
     * @param bool $asBase64 Si true, retourne en base64, sinon retourne l'objet Result
     * @param bool $withUrl Si true, encode une URL, sinon encode les données JSON
     * @return string|object
     */
    public function generateVenteQrCode(Vente $vente, bool $asBase64 = true, bool $withUrl = true): string|object
    {
        // Pointer vers la page de téléchargement automatique
        $data = $this->urlGenerator->generate(
            'app_vente_download_page',
            ['idVente' => $vente->getIdVente()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->labelText('Vente #' . $vente->getIdVente())
            ->labelAlignment(LabelAlignment::Center)
            ->validateResult(false)
            ->build();

        if ($asBase64) {
            return base64_encode($result->getString());
        }

        return $result;
    }

    /**
     * Génère un QR Code pour un client
     * 
     * @param Client $client
     * @param bool $asBase64 Si true, retourne en base64, sinon retourne l'objet Result
     * @param bool $withUrl Si true, encode une URL, sinon encode les données JSON
     * @return string|object
     */
    public function generateClientQrCode(Client $client, bool $asBase64 = true, bool $withUrl = true): string|object
    {
        // Pointer vers la page de téléchargement automatique
        $data = $this->urlGenerator->generate(
            'app_client_download_page',
            ['id_client' => $client->getId_client()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->labelText($client->getNom())
            ->labelAlignment(LabelAlignment::Center)
            ->validateResult(false)
            ->build();

        if ($asBase64) {
            return base64_encode($result->getString());
        }

        return $result;
    }

    /**
     * Génère un QR Code avec logo personnalisé
     * 
     * @param string $data Données à encoder
     * @param string|null $logoPath Chemin vers le logo (optionnel)
     * @param string|null $label Label sous le QR code
     * @param int $size Taille du QR code
     * @return string Base64 du QR code
     */
    public function generateCustomQrCode(
        string $data,
        ?string $logoPath = null,
        ?string $label = null,
        int $size = 300
    ): string {
        $builder = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size($size)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->validateResult(false);

        // Ajouter un logo si fourni
        if ($logoPath && file_exists($logoPath)) {
            $builder->logoPath($logoPath)
                ->logoResizeToWidth(60)
                ->logoPunchoutBackground(true);
        }

        // Ajouter un label si fourni
        if ($label) {
            $builder->labelText($label)
                ->labelAlignment(LabelAlignment::Center);
        }

        $result = $builder->build();

        return base64_encode($result->getString());
    }

    /**
     * Génère un QR Code et le retourne comme réponse HTTP pour téléchargement
     * 
     * @param string $base64QrCode QR code en base64
     * @param string $filename Nom du fichier
     * @return array ['content' => string, 'filename' => string, 'mimeType' => string]
     */
    public function prepareQrCodeForDownload(string $base64QrCode, string $filename = 'qrcode.png'): array
    {
        return [
            'content' => base64_decode($base64QrCode),
            'filename' => $filename,
            'mimeType' => 'image/png'
        ];
    }

    /**
     * Génère plusieurs QR codes en batch
     * 
     * @param array $items Tableau d'entités (Client ou Vente)
     * @return array Tableau de QR codes en base64
     */
    public function generateBatchQrCodes(array $items): array
    {
        $qrCodes = [];

        foreach ($items as $item) {
            if ($item instanceof Vente) {
                $qrCodes[$item->getIdVente()] = $this->generateVenteQrCode($item);
            } elseif ($item instanceof Client) {
                $qrCodes[$item->getId_client()] = $this->generateClientQrCode($item);
            }
        }

        return $qrCodes;
    }
}
