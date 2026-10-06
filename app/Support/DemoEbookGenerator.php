<?php

namespace App\Support;

use FPDF;
use RuntimeException;
use ZipArchive;

/**
 * Produit de vrais fichiers PDF / EPUB de démonstration (contenu court),
 * pour tester téléchargement, extrait et filigrane sans fichiers réels.
 */
class DemoEbookGenerator
{
    private const FILLER = [
        'Chaque page ouvre une porte vers un monde nouveau, et chaque idée en appelle une autre.',
        'Le lecteur attentif découvrira, au fil des chapitres, que la connaissance se construit pas à pas.',
        'Il faut du temps pour comprendre, mais il en faut davantage encore pour oublier ce que l\'on a vraiment compris.',
        'Les exemples qui suivent illustrent la manière dont ces principes s\'appliquent dans la vie quotidienne.',
        'Rien n\'est plus précieux qu\'une question bien posée : elle contient déjà la moitié de sa réponse.',
        'À mesure que l\'on avance, les notions se répondent et dessinent une carte cohérente du sujet.',
        'Cette édition numérique est fournie à titre de démonstration par Univers Connaissance.',
        'On retiendra surtout que la curiosité, cultivée avec méthode, devient un véritable savoir-faire.',
    ];

    /**
     * @param  list<string>  $chapters
     */
    public function pdf(string $title, string $author, string $opening, array $chapters, bool $sample = false): string
    {
        $pdf = new FPDF('P', 'mm', 'A5');
        $pdf->SetTitle($this->enc($title), false);
        $pdf->SetAuthor($this->enc($author), false);
        $pdf->SetMargins(16, 18, 16);
        $pdf->SetAutoPageBreak(true, 20);

        // Page de titre.
        $pdf->AddPage();
        $pdf->SetFillColor(16, 186, 241);
        $pdf->Rect(0, 0, 148, 70, 'F');
        $pdf->SetFillColor(11, 31, 42);
        $pdf->Rect(0, 70, 148, 140, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetY(95);
        $pdf->SetFont('Times', 'B', 24);
        $pdf->MultiCell(0, 11, $this->enc($title), 0, 'C');
        $pdf->Ln(6);
        $pdf->SetFont('Helvetica', '', 13);
        $pdf->MultiCell(0, 7, $this->enc($author), 0, 'C');
        $pdf->SetY(185);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->MultiCell(0, 5, $this->enc($sample ? 'EXTRAIT GRATUIT - Univers Connaissance' : 'Univers Connaissance - Le savoir à portée de clic.'), 0, 'C');

        $chapters = $sample ? array_slice($chapters, 0, 1) : $chapters;
        $pdf->SetTextColor(30, 41, 59);

        foreach ($chapters as $i => $chapter) {
            $pdf->AddPage();
            $pdf->SetFont('Times', 'B', 17);
            $pdf->SetTextColor(10, 119, 156);
            $pdf->MultiCell(0, 9, $this->enc($chapter), 0, 'L');
            $pdf->Ln(4);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('Times', '', 11);

            $text = $i === 0 ? $opening.' ' : '';
            for ($p = 0; $p < 6; $p++) {
                $paragraph = $p === 0 ? $text : '';
                for ($s = 0; $s < 4; $s++) {
                    $paragraph .= self::FILLER[($i * 3 + $p * 2 + $s) % count(self::FILLER)].' ';
                }
                $pdf->MultiCell(0, 5.6, $this->enc(trim($paragraph)), 0, 'J');
                $pdf->Ln(3);
            }
        }

        if ($sample) {
            $pdf->AddPage();
            $pdf->SetY(80);
            $pdf->SetFont('Times', 'B', 15);
            $pdf->MultiCell(0, 8, $this->enc('Fin de l\'extrait'), 0, 'C');
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->MultiCell(0, 6, $this->enc('La suite est disponible en téléchargement immédiat sur Univers Connaissance.'), 0, 'C');
        }

        return $pdf->Output('S');
    }

    /**
     * @param  list<string>  $chapters
     */
    public function epub(string $title, string $author, string $opening, array $chapters, string $language = 'fr'): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'epub');
        $zip = new ZipArchive;
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Impossible de créer l\'EPUB.');
        }

        $e = fn (string $s) => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $uid = 'urn:uuid:'.md5($title.$author);

        $zip->addFromString('mimetype', 'application/epub+zip');
        $zip->setCompressionName('mimetype', ZipArchive::CM_STORE);
        $zip->addFromString('META-INF/container.xml', '<?xml version="1.0" encoding="UTF-8"?><container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container"><rootfiles><rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/></rootfiles></container>');

        $manifest = '';
        $spine = '';
        $navItems = '';
        foreach ($chapters as $i => $chapter) {
            $n = $i + 1;
            $body = '<h1>'.$e($chapter).'</h1>';
            if ($i === 0) {
                $body .= '<p>'.$e($opening).'</p>';
            }
            for ($p = 0; $p < 5; $p++) {
                $body .= '<p>'.$e(implode(' ', array_slice(array_merge(self::FILLER, self::FILLER), ($i + $p) % 8, 4))).'</p>';
            }
            $zip->addFromString("OEBPS/chap{$n}.xhtml", '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$language.'"><head><title>'.$e($chapter).'</title></head><body>'.$body.'</body></html>');
            $manifest .= '<item id="c'.$n.'" href="chap'.$n.'.xhtml" media-type="application/xhtml+xml"/>';
            $spine .= '<itemref idref="c'.$n.'"/>';
            $navItems .= '<li><a href="chap'.$n.'.xhtml">'.$e($chapter).'</a></li>';
        }

        $zip->addFromString('OEBPS/nav.xhtml', '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops"><head><title>Sommaire</title></head><body><nav epub:type="toc"><h1>Sommaire</h1><ol>'.$navItems.'</ol></nav></body></html>');
        $zip->addFromString('OEBPS/content.opf', '<?xml version="1.0" encoding="UTF-8"?><package xmlns="http://www.idpf.org/2007/opf" version="3.0" unique-identifier="uid"><metadata xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:identifier id="uid">'.$uid.'</dc:identifier><dc:title>'.$e($title).'</dc:title><dc:creator>'.$e($author).'</dc:creator><dc:language>'.$language.'</dc:language><dc:publisher>Univers Connaissance</dc:publisher><meta property="dcterms:modified">2026-01-01T00:00:00Z</meta></metadata><manifest><item id="nav" href="nav.xhtml" media-type="application/xhtml+xml" properties="nav"/>'.$manifest.'</manifest><spine>'.$spine.'</spine></package>');
        $zip->close();

        $content = file_get_contents($tmp);
        @unlink($tmp);

        return $content;
    }

    private function enc(string $text): string
    {
        return mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
    }
}
