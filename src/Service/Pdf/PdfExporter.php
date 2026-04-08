<?php

namespace App\Service\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;

final class PdfExporter
{
    public function renderHtmlToPdf(string $html, string $paper = 'A4', string $orientation = 'portrait'): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->output();
    }
}

