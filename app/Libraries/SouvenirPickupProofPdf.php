<?php

namespace App\Libraries;

use RuntimeException;

class SouvenirPickupProofPdf
{
    private const PAGE_WIDTH = 595.2756;
    private const PAGE_HEIGHT = 841.8898;

    public function __construct(
        private readonly string $templatePath = APPPATH . 'Resources/pdf/bukti_pengambilan_souvenir_baru.jpg',
        private readonly string $textColor = '0.000 0.145 0.420'
    ) {
    }

    /**
     * @param array{nama_pengambil: string, divisi_bagian: string, keperluan_kegiatan: string, tanggal_pengambilan: string, keterangan?: string|null, petugas?: string|null, items: list<array{nama_item: string, jumlah_diambil: int, satuan: string}>} $data
     */
    public function render(array $data): string
    {
        if (! is_file($this->templatePath)) {
            throw new RuntimeException('Template bukti pengambilan souvenir tidak tersedia.');
        }

        $image = file_get_contents($this->templatePath);
        $size = getimagesize($this->templatePath);
        if ($image === false || $size === false || ($size['mime'] ?? '') !== 'image/jpeg') {
            throw new RuntimeException('Template bukti pengambilan souvenir tidak valid.');
        }

        $content = sprintf("q %.4F 0 0 %.4F 0 0 cm /Im0 Do Q\n", self::PAGE_WIDTH, self::PAGE_HEIGHT);
        $fieldValues = [
            [$data['nama_pengambil'], 184.0, 656.0, 340.0],
            [$data['divisi_bagian'], 184.0, 623.5, 340.0],
            [$data['keperluan_kegiatan'], 184.0, 591.5, 340.0],
            [$this->formatDate($data['tanggal_pengambilan']), 184.0, 560.0, 340.0],
        ];
        foreach ($fieldValues as [$value, $x, $y, $width]) {
            $content .= $this->text((string) $value, $x, $y, $width, 13.0);
        }

        $itemY = 480.0;
        foreach (array_slice($data['items'], 0, 8) as $index => $item) {
            $content .= $this->text($item['nama_item'], 84.0, $itemY, 310.0, 12.5);
            $content .= $this->centeredText(
                number_format((int) $item['jumlah_diambil'], 0, ',', '.') . ' ' . $item['satuan'],
                404.0,
                $itemY,
                145.0,
                12.5
            );
            $itemY -= 29.0;
        }

        $note = trim((string) ($data['keterangan'] ?? ''));
        if ($note !== '') {
            foreach ($this->wrapText($note, 10.0, 470.0, 2) as $index => $line) {
                $content .= $this->text($line, 130.0, 218.0 - ($index * 13.0), 470.0, 11.0);
            }
        }

        $content .= $this->text($this->formatDate($data['tanggal_pengambilan']), 475.0, 164.0, 89.0, 12.0);
        $content .= $this->centeredText($data['nama_pengambil'], 231.0, 64.5, 165.0, 12.0);
        $content .= $this->centeredText(trim((string) ($data['petugas'] ?? 'Petugas Agendaris')), 403.0, 64.5, 165.0, 12.0);

        return $this->buildPdf([
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.4F %.4F] /Resources << /XObject << /Im0 4 0 R >> /Font << /F1 5 0 R /F2 6 0 R >> >> /Contents 7 0 R >>',
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
            6 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            7 => sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($content), $content),
        ]);
    }

    private function text(string $value, float $x, float $y, float $maxWidth, float $fontSize, bool $bold = false): string
    {
        [$value, $fontSize] = $this->fitText($value, $fontSize, $maxWidth);
        return sprintf(
            "BT /F%d %.2F Tf %s rg 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
            $bold ? 2 : 1,
            $fontSize,
            $this->textColor,
            $x,
            $y,
            $this->escapePdfText($value)
        );
    }

    private function centeredText(string $value, float $x, float $y, float $width, float $fontSize, bool $bold = false): string
    {
        [$value, $fontSize] = $this->fitText($value, $fontSize, $width);
        return $this->text($value, $x + max(0, ($width - $this->estimatedWidth($value, $fontSize)) / 2), $y, $width, $fontSize, $bold);
    }

    /** @return array{0: string, 1: float} */
    private function fitText(string $value, float $fontSize, float $maxWidth): array
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        while ($fontSize > 7.0 && $this->estimatedWidth($value, $fontSize) > $maxWidth) {
            $fontSize -= .5;
        }
        if ($this->estimatedWidth($value, $fontSize) <= $maxWidth) return [$value, $fontSize];

        while (mb_strlen($value) > 1 && $this->estimatedWidth($value . '...', $fontSize) > $maxWidth) {
            $value = rtrim(mb_substr($value, 0, -1));
        }
        return [$value . '...', $fontSize];
    }

    /** @return list<string> */
    private function wrapText(string $value, float $fontSize, float $maxWidth, int $maxLines): array
    {
        $lines = [];
        $line = '';
        foreach (preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;
            if ($this->estimatedWidth($candidate, $fontSize) <= $maxWidth) {
                $line = $candidate;
                continue;
            }
            if ($line !== '') $lines[] = $line;
            $line = $word;
        }
        if ($line !== '') $lines[] = $line;
        if (count($lines) <= $maxLines) return $lines;
        return array_merge(array_slice($lines, 0, $maxLines - 1), [$this->fitText(implode(' ', array_slice($lines, $maxLines - 1)), $fontSize, $maxWidth)[0]]);
    }

    private function formatDate(string $value): string
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));
        return $date === false ? trim($value) : $date->format('d-m-Y');
    }

    private function estimatedWidth(string $value, float $fontSize): float
    {
        $encoded = $this->encodeText($value);
        $narrow = substr_count($encoded, 'i') + substr_count($encoded, 'l') + substr_count($encoded, ' ') + substr_count($encoded, '.');
        return (strlen($encoded) * 0.54 - $narrow * 0.23) * $fontSize;
    }

    private function escapePdfText(string $value): string
    {
        return str_replace(["\\", '(', ')', "\r", "\n"], ["\\\\", '\\(', '\\)', '', ' '], $this->encodeText($value));
    }

    private function encodeText(string $value): string
    {
        return mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
    }

    /** @param array<int, string> $objects */
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
