<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class PdfRenderer
{
    private const Y_SHIFT_MM = 3.0;

    public function render(string $templatePath, array $data, string $outputPath): void
    {
        $fpdfPathCandidates = [
            ROOT_PATH . '/vendor/setasign/fpdf/fpdf.php',
            ROOT_PATH . '/vendor/fpdf/fpdf.php',
        ];

        $fpdfPath = null;
        foreach ($fpdfPathCandidates as $candidate) {
            if (is_file($candidate)) {
                $fpdfPath = $candidate;
                break;
            }
        }

        if ($fpdfPath === null) {
            throw new RuntimeException('FPDF library niet gevonden. Upload de vendor map of installeer setasign/fpdf.');
        }

        require_once $fpdfPath;

        if (!class_exists('FPDF')) {
            throw new RuntimeException('FPDF class kon niet geladen worden.');
        }

        /** @var \FPDF $pdf */
        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);

        $pages = [
            ROOT_PATH . '/public/assets/cmr-pages/page-1.jpg',
            ROOT_PATH . '/public/assets/cmr-pages/page-2.jpg',
            ROOT_PATH . '/public/assets/cmr-pages/page-3.jpg',
            ROOT_PATH . '/public/assets/cmr-pages/page-4.jpg',
        ];

        foreach ($pages as $imagePath) {
            if (!is_file($imagePath)) {
                throw new RuntimeException('CMR pagina-afbeelding niet gevonden: ' . $imagePath);
            }

            $pdf->AddPage();
            $pdf->Image($imagePath, 0, 0, 210, 297, 'JPG');
            $this->drawAllFields($pdf, $data);
        }

        $pdf->Output('F', $outputPath);
    }

    private function drawAllFields(\FPDF $pdf, array $data): void
    {
        $write = function (float $xPt, float $yPt, float $wPt, string $text, float $fontSize = 8.0, ?int $maxLines = null) use ($pdf): void {
            $text = trim($text);
            if ($text === '') {
                return;
            }

            $x = $this->mmX($xPt);
            $y = $this->mmY($yPt) + self::Y_SHIFT_MM;
            $w = $this->mmX($wPt);
            $lineHeight = max(2.9, $fontSize * 0.38);

            $pdf->SetFont('Helvetica', '', $fontSize);
            $lines = $this->wrapText($pdf, $text, $w, $fontSize);
            if ($maxLines !== null) {
                $lines = array_slice($lines, 0, $maxLines);
            }

            $currentY = $y;
            foreach ($lines as $line) {
                $pdf->SetXY($x, $currentY);
                $pdf->Cell($w, $lineHeight, $line, 0, 0, 'L');
                $currentY += $lineHeight;
            }
        };

        $write(44, 36, 250, (string)($data['field1'] ?? ''), 8.5);
        $write(44, 106, 250, (string)($data['field2'] ?? ''), 8.5);
        $write(44, 178, 250, (string)($data['field3'] ?? ''), 8);
        $write(44, 226, 250, (string)($data['field4'] ?? ''), 8);
        $write(44, 273, 250, (string)($data['field5'] ?? ''), 8);

        $write(304, 36, 248, (string)($data['field16'] ?? ''), 8.5);
        $write(304, 106, 248, (string)($data['field17'] ?? ''), 8.5);
        $write(304, 178, 248, (string)($data['field18'] ?? ''), 8);

        $startY = 322;
        $rowHeight = 20;
        foreach (($data['items'] ?? []) as $idx => $item) {
            if ($idx > 9) {
                break;
            }
            $y = $startY + ($idx * $rowHeight);
            $write(44, $y, 166, (string)($item['field6'] ?? ''), 7, 2);
            $write(150, $y, 58, (string)($item['field7'] ?? ''), 7, 2);
            $write(205, $y, 60, (string)($item['field8'] ?? ''), 7, 2);
            $write(266, $y, 96, (string)($item['field9'] ?? ''), 7, 2);
            $write(366, $y, 60, (string)($item['field10'] ?? ''), 7, 2);
            $write(429, $y, 62, (string)($item['field11'] ?? ''), 7, 2);
            $write(495, $y, 58, (string)($item['field12'] ?? ''), 7, 2);
        }

        $write(44, 538, 250, (string)($data['field13'] ?? ''), 8);
        $write(44, 616, 250, (string)($data['field14'] ?? ''), 8);
        $write(304, 538, 248, (string)($data['field19'] ?? ''), 8);
        $write(304, 615, 248, (string)($data['field20'] ?? ''), 7.5);

        $write(44, 697, 250, (string)($data['field21'] ?? ''), 8);
        $write(304, 697, 248, (string)($data['field15'] ?? ''), 8);

        $write(44, 728, 162, (string)($data['field22'] ?? ''), 8);
        $write(216, 728, 162, (string)($data['field23'] ?? ''), 8);
        $write(389, 728, 162, (string)($data['field24'] ?? ''), 8);
    }

    private function wrapText(\FPDF $pdf, string $text, float $wMm, float $fontSize): array
    {
        $lines = [];
        $rawLines = preg_split('/\r\n|\r|\n/', $text) ?: [''];

        foreach ($rawLines as $rawLine) {
            $rawLine = trim($rawLine);
            if ($rawLine === '') {
                $lines[] = '';
                continue;
            }

            $words = preg_split('/\s+/', $rawLine) ?: [$rawLine];
            $current = '';

            foreach ($words as $word) {
                $candidate = $current === '' ? $word : $current . ' ' . $word;
                $width = $pdf->GetStringWidth($candidate);
                if ($width <= $wMm) {
                    $current = $candidate;
                } else {
                    if ($current !== '') {
                        $lines[] = $current;
                    }
                    $current = $word;
                }
            }

            if ($current !== '') {
                $lines[] = $current;
            }
        }

        return $lines;
    }

    private function mmX(float $pt): float
    {
        return $pt * (210.0 / 595.0);
    }

    private function mmY(float $pt): float
    {
        return $pt * (297.0 / 842.0);
    }
}
