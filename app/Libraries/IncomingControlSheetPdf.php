<?php

namespace App\Libraries;

use RuntimeException;

class IncomingControlSheetPdf
{
    private const PAGE_WIDTH = 595.2756;
    private const PAGE_HEIGHT = 419.5276;

    public function __construct(
        private readonly string $templatePath = APPPATH . 'Resources/pdf/lembar_pengendalian_surat_masuk_a5.jpg'
    ) {
    }

    /**
     * @param array{nomor_agendaris?: string|null, tanggal_agendaris?: string|null, nomor_surat?: string|null, tanggal_surat?: string|null, perihal_surat?: string|null} $data
     */
    public function render(array $data): string
    {
        if (! is_file($this->templatePath) || ! is_readable($this->templatePath)) {
            throw new RuntimeException('Template Lembar Pengendalian Surat Masuk tidak tersedia.');
        }

        $image = file_get_contents($this->templatePath);
        $size  = getimagesize($this->templatePath);
        if ($image === false || $size === false || ($size['mime'] ?? '') !== 'image/jpeg') {
            throw new RuntimeException('Template Lembar Pengendalian Surat Masuk tidak valid.');
        }

        $values = [
            [$data['nomor_agendaris'] ?? '', 138.0, 337.8, 280.0],
            [$this->formatDate($data['tanggal_agendaris'] ?? ''), 138.0, 320.3, 280.0],
            [$data['nomor_surat'] ?? '', 138.0, 302.8, 280.0],
            [$this->formatDate($data['tanggal_surat'] ?? ''), 138.0, 285.3, 280.0],
            [$data['perihal_surat'] ?? '', 138.0, 264.8, 280.0],
        ];

        $content = sprintf(
            "q %.4F 0 0 %.4F 0 0 cm /Im0 Do Q\n",
            self::PAGE_WIDTH,
            self::PAGE_HEIGHT
        );
        foreach ($values as [$value, $x, $y, $maxWidth]) {
            if (trim((string) $value) === '') {
                continue;
            }
            [$text, $fontSize] = $this->fitText((string) $value, (float) $maxWidth);
            $content .= sprintf(
                "BT /F1 %.2F Tf 0.000 0.145 0.420 rg 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
                $fontSize,
                $x,
                $y,
                $this->escapePdfText($text)
            );
        }

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.4F %.4F] /Resources << /XObject << /Im0 4 0 R >> /Font << /F1 5 0 R >> >> /Contents 6 0 R >>',
                self::PAGE_WIDTH,
                self::PAGE_HEIGHT
            ),
            4 => sprintf(
                "<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>\nstream\n%s\nendstream",
                $size[0],
                $size[1],
                strlen($image),
                $image
            ),
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            6 => sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($content), $content),
        ];

        return $this->buildPdf($objects);
    }

    private function formatDate(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false ? $date->format('d-m-Y') : $value;
    }

    /** @return array{0: string, 1: float} */
    private function fitText(string $value, float $maxWidth): array
    {
        $value    = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        $fontSize = 10.0;

        while ($fontSize > 7.0 && $this->estimatedWidth($value, $fontSize) > $maxWidth) {
            $fontSize -= 0.5;
        }

        if ($this->estimatedWidth($value, $fontSize) <= $maxWidth) {
            return [$value, $fontSize];
        }

        $suffix = '...';
        while (mb_strlen($value) > 1 && $this->estimatedWidth($value . $suffix, $fontSize) > $maxWidth) {
            $value = rtrim(mb_substr($value, 0, -1));
        }

        return [$value . $suffix, $fontSize];
    }

    private function estimatedWidth(string $value, float $fontSize): float
    {
        return strlen($this->encodeText($value)) * $fontSize * 0.52;
    }

    private function escapePdfText(string $value): string
    {
        return str_replace(
            ["\\", '(', ')', "\r", "\n"],
            ["\\\\", '\\(', '\\)', '', ' '],
            $this->encodeText($value)
        );
    }

    private function encodeText(string $value): string
    {
        return mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
    }

    /** @param array<int, string> $objects */
    private function buildPdf(array $objects): string
    {
        $pdf     = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($objects as $number => $_object) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }
        $pdf .= sprintf(
            "trailer\n<< /Size %d /Root 1 0 R >>\nstartxref\n%d\n%%%%EOF\n",
            count($objects) + 1,
            $xref
        );

        return $pdf;
    }
}
