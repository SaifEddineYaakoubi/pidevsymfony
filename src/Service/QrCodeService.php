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

class QrCodeService
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function generateVenteQrCode(Vente $vente, bool $asBase64 = true, bool $withUrl = true): string|object
    {
        $data = $this->urlGenerator->generate(
            'app_vente_download_page',
            ['idVente' => $vente->getIdVente()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $builder = new Builder(
            writer: new PngWriter(),
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            labelText: 'Vente #' . $vente->getIdVente(),
            labelAlignment: LabelAlignment::Center
        );

        $result = $builder->build();

        if ($asBase64) {
            return base64_encode($result->getString());
        }

        return $result;
    }

    public function generateClientQrCode(Client $client, bool $asBase64 = true, bool $withUrl = true): string|object
    {
        $data = $this->urlGenerator->generate(
            'app_client_download_page',
            ['id_client' => $client->getId_client()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $builder = new Builder(
            writer: new PngWriter(),
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            labelText: $client->getNom(),
            labelAlignment: LabelAlignment::Center
        );

        $result = $builder->build();

        if ($asBase64) {
            return base64_encode($result->getString());
        }

        return $result;
    }

    public function generateCustomQrCode(
        string $data,
        ?string $logoPath = null,
        ?string $label = null,
        int $size = 300
    ): string {
        $builder = new Builder(
            writer: new PngWriter(),
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            logoPath: ($logoPath && file_exists($logoPath)) ? $logoPath : '',
            labelText: $label ?? '',
            labelAlignment: LabelAlignment::Center
        );

        $result = $builder->build();

        return base64_encode($result->getString());
    }

    public function prepareQrCodeForDownload(string $base64QrCode, string $filename = 'qrcode.png'): array
    {
        return [
            'content' => base64_decode($base64QrCode),
            'filename' => $filename,
            'mimeType' => 'image/png'
        ];
    }

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
