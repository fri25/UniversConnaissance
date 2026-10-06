<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;
use Throwable;

/**
 * Ajoute un filigrane discret (pied de page) sur chaque page d'un PDF.
 */
class PdfWatermarker
{
    /**
     * @return string|null contenu du PDF filigrané, ou null si le PDF n'est pas
     *                     importable (ex. compression non supportée par FPDI gratuit)
     */
    public function stamp(string $absolutePath, string $text): ?string
    {
        try {
            $pdf = new Fpdi;
            $pdf->SetAutoPageBreak(false);
            $pageCount = $pdf->setSourceFile($absolutePath);
            $label = $this->encode($text);

            for ($page = 1; $page <= $pageCount; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template);

                $pdf->SetFont('Helvetica', '', 7);
                $pdf->SetTextColor(150, 160, 170);
                $pdf->SetXY(0, $size['height'] - 8);
                $pdf->Cell($size['width'], 4, $label, 0, 0, 'C');
            }

            return $pdf->Output('S');
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function encode(string $text): string
    {
        // Les polices standard FPDF attendent du Windows-1252.
        return mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
    }
}
