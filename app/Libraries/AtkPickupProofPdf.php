<?php

namespace App\Libraries;

use RuntimeException;

final class AtkPickupProofPdf
{
    private const PAGE_WIDTH = 595.2756;
    private const PAGE_HEIGHT = 841.8898;

    public function __construct(private readonly string $templatePath = APPPATH . 'Resources/pdf/bukti_pengambilan_atk.jpg')
    {
    }

    /**
     * @param list<array{nama_pengambil:string,nama_barang:string,tanggal_pengambilan:string,jumlah_diambil:int,satuan:string}> $items
     */
    public function render(array $items): string
    {
        if (! is_file($this->templatePath)) throw new RuntimeException('Template bukti pengambilan ATK tidak tersedia.');

        $image = file_get_contents($this->templatePath);
        $size = getimagesize($this->templatePath);
        if ($image === false || $size === false || ($size['mime'] ?? '') !== 'image/jpeg') {
            throw new RuntimeException('Template bukti pengambilan ATK tidak valid.');
        }

        $content = sprintf("q %.4F 0 0 %.4F 0 0 cm /Im0 Do Q\n", self::PAGE_WIDTH, self::PAGE_HEIGHT);
        $rowY = 645.0;
        foreach (array_slice($items, 0, 30) as $item) {
            $content .= $this->centeredText($item['nama_pengambil'], 43.5, $rowY, 187.5, 7.5);
            $content .= $this->centeredText($item['nama_barang'], 231.0, $rowY, 124.0, 7.5);
            $content .= $this->centeredText($this->formatDate($item['tanggal_pengambilan']), 355.0, $rowY, 84.0, 7.2);
            $content .= $this->centeredText(number_format($item['jumlah_diambil'], 0, ',', '.') . ' ' . $item['satuan'], 439.0, $rowY, 71.0, 7.2);
            $rowY -= 20.35;
        }

        return $this->buildPdf([
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.4F %.4F] /Resources << /XObject << /Im0 4 0 R >> /Font << /F1 5 0 R >> >> /Contents 6 0 R >>', self::PAGE_WIDTH, self::PAGE_HEIGHT),
            4 => sprintf("<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>\nstream\n%s\nendstream", $size[0], $size[1], strlen($image), $image),
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            6 => sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($content), $content),
        ]);
    }

    private function centeredText(string $value, float $x, float $y, float $width, float $fontSize): string
    {
        [$value, $fontSize] = $this->fitText($value, $fontSize, $width);
        $textWidth = $this->estimatedWidth($value, $fontSize);
        return sprintf("BT /F1 %.2F Tf 0.000 0.145 0.420 rg 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n", $fontSize, $x + max(0, ($width - $textWidth) / 2), $y, $this->escapePdfText($value));
    }

    /** @return array{0:string,1:float} */
    private function fitText(string $value, float $fontSize, float $maxWidth): array
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        while ($fontSize > 5.5 && $this->estimatedWidth($value, $fontSize) > $maxWidth) $fontSize -= .5;
        if ($this->estimatedWidth($value, $fontSize) <= $maxWidth) return [$value, $fontSize];
        while (mb_strlen($value) > 1 && $this->estimatedWidth($value . '...', $fontSize) > $maxWidth) $value = rtrim(mb_substr($value, 0, -1));
        return [$value . '...', $fontSize];
    }

    private function formatDate(string $date): string
    {
        $value = \DateTimeImmutable::createFromFormat('!Y-m-d', trim($date));
        return $value === false ? trim($date) : $value->format('d/m/Y');
    }

    private function estimatedWidth(string $value, float $fontSize): float
    {
        $encoded = $this->encodeText($value);
        $narrow = substr_count($encoded, 'i') + substr_count($encoded, 'l') + substr_count($encoded, ' ') + substr_count($encoded, '.');
        return (strlen($encoded) * .54 - $narrow * .23) * $fontSize;
    }

    private function escapePdfText(string $value): string
    {
        return str_replace(["\\", '(', ')', "\r", "\n"], ["\\\\", '\\(', '\\)', '', ' '], $this->encodeText($value));
    }

    private function encodeText(string $value): string
    {
        return mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
    }

    /** @param array<int,string> $objects */
    private function buildPdf(array $objects): string
    {
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($objects as $number => $_object) $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        return $pdf . sprintf("trailer\n<< /Size %d /Root 1 0 R >>\nstartxref\n%d\n%%%%EOF\n", count($objects) + 1, $xref);
    }
}
