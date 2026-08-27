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
            [$data['nomor_agendaris'] ?? '', 138.0, 337.8, 280.0, false],
            [$this->formatDate($data['tanggal_agendaris'] ?? ''), 138.0, 320.3, 280.0, false],
            [$data['nomor_surat'] ?? '', 138.0, 302.8, 280.0, false],
            [$this->formatDate($data['tanggal_surat'] ?? ''), 138.0, 285.3, 280.0, false],
            // Posisi tengah visual area Perihal; sedikit diturunkan agar
            // teks multibaris tidak menyentuh garis batas atas maupun bawah.
            [$data['perihal_surat'] ?? '', 138.0, 262.0, 280.0, true],
        ];

        $content = sprintf(
            "q %.4F 0 0 %.4F 0 0 cm /Im0 Do Q\n",
            self::PAGE_WIDTH,
            self::PAGE_HEIGHT
        );

        // Perbarui judul dua baris pertama tanpa mengubah gambar template.
        // Area biru lama ditutup lalu label baru ditulis rata tengah.
        $content .= "0.000 0.306 0.647 rg 27.50 332.75 92.15 16.65 re f\n";
        $content .= "0.000 0.306 0.647 rg 27.50 315.50 92.15 16.40 re f\n";
        $content .= "BT /F2 8.00 Tf 1 1 1 rg 1 0 0 1 37.57 338.50 Tm (Nomor Agendaris) Tj ET\n";
        $content .= "BT /F2 8.00 Tf 1 1 1 rg 1 0 0 1 37.57 321.25 Tm (Tanggal Agendaris) Tj ET\n";

        foreach ($values as [$value, $x, $y, $maxWidth, $multiline]) {
            if (trim((string) $value) === '') {
                continue;
            }

            if ($multiline) {
                [$lines, $fontSize, $lineHeight] = $this->wrapText((string) $value, (float) $maxWidth, 3);
                $lineY = (float) $y + ((count($lines) - 1) * $lineHeight / 2);

                foreach ($lines as $line) {
                    $content .= sprintf(
                        "BT /F1 %.2F Tf 0.000 0.145 0.420 rg 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
                        $fontSize,
                        $x,
                        $lineY,
                        $this->escapePdfText($line)
                    );
                    $lineY -= $lineHeight;
                }

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

    /** @return array{0: list<string>, 1: float, 2: float} */
    private function wrapText(string $value, float $maxWidth, int $maxLines): array
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

        for ($fontSize = 8.0; $fontSize >= 6.0; $fontSize -= 0.5) {
            $lines = $this->wrapAtWidth($value, $fontSize, $maxWidth);
            if (count($lines) <= $maxLines) {
                return [$lines, $fontSize, $fontSize + 1.0];
            }
        }

        $fontSize = 6.0;
        $lines = $this->wrapAtWidth($value, $fontSize, $maxWidth);
        $visibleLines = array_slice($lines, 0, $maxLines);
        $lastIndex = $maxLines - 1;
        $remaining = implode(' ', array_slice($lines, $lastIndex));
        $visibleLines[$lastIndex] = $this->truncateText($remaining, $fontSize, $maxWidth);

        return [$visibleLines, $fontSize, $fontSize + 1.0];
    }

    /** @return list<string> */
    private function wrapAtWidth(string $value, float $fontSize, float $maxWidth): array
    {
        $lines = [];
        $line = '';

        foreach (preg_split('/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;
            if ($this->estimatedWidth($candidate, $fontSize) <= $maxWidth) {
                $line = $candidate;
                continue;
            }

            if ($line !== '') {
                $lines[] = $line;
                $line = '';
            }

            if ($this->estimatedWidth($word, $fontSize) <= $maxWidth) {
                $line = $word;
                continue;
            }

            $chunks = $this->splitLongWord($word, $fontSize, $maxWidth);
            foreach ($chunks as $index => $chunk) {
                if ($index < count($chunks) - 1) {
                    $lines[] = $chunk;
                    continue;
                }

                $line = $chunk;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines === [] ? [''] : $lines;
    }

    /** @return list<string> */
    private function splitLongWord(string $word, float $fontSize, float $maxWidth): array
    {
        $chunks = [];
        $chunk = '';

        foreach (mb_str_split($word) as $character) {
            if ($chunk !== '' && $this->estimatedWidth($chunk . $character, $fontSize) > $maxWidth) {
                $chunks[] = $chunk;
                $chunk = '';
            }
            $chunk .= $character;
        }

        if ($chunk !== '') {
            $chunks[] = $chunk;
        }

        return $chunks;
    }

    private function truncateText(string $value, float $fontSize, float $maxWidth): string
    {
        $suffix = '...';
        while ($value !== '' && $this->estimatedWidth($value . $suffix, $fontSize) > $maxWidth) {
            $value = rtrim(mb_substr($value, 0, -1));
        }

        return $value . $suffix;
    }

    private function estimatedWidth(string $value, float $fontSize): float
    {
        static $widths = [
            ' ' => 278, '!' => 278, '"' => 355, '#' => 556, '$' => 556, '%' => 889, '&' => 667, "'" => 191,
            '(' => 333, ')' => 333, '*' => 389, '+' => 584, ',' => 278, '-' => 333, '.' => 278, '/' => 278,
            '0' => 556, '1' => 556, '2' => 556, '3' => 556, '4' => 556, '5' => 556, '6' => 556, '7' => 556, '8' => 556, '9' => 556,
            ':' => 278, ';' => 278, '<' => 584, '=' => 584, '>' => 584, '?' => 556, '@' => 1015,
            'A' => 667, 'B' => 667, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778, 'H' => 722, 'I' => 278,
            'J' => 500, 'K' => 667, 'L' => 556, 'M' => 833, 'N' => 722, 'O' => 778, 'P' => 667, 'Q' => 778, 'R' => 722,
            'S' => 667, 'T' => 611, 'U' => 722, 'V' => 667, 'W' => 944, 'X' => 667, 'Y' => 667, 'Z' => 611,
            '[' => 278, '\\' => 278, ']' => 278, '^' => 469, '_' => 556, '`' => 333,
            'a' => 556, 'b' => 556, 'c' => 500, 'd' => 556, 'e' => 556, 'f' => 278, 'g' => 556, 'h' => 556, 'i' => 222,
            'j' => 222, 'k' => 500, 'l' => 222, 'm' => 833, 'n' => 556, 'o' => 556, 'p' => 556, 'q' => 556, 'r' => 333,
            's' => 500, 't' => 278, 'u' => 556, 'v' => 500, 'w' => 722, 'x' => 500, 'y' => 500, 'z' => 500,
            '{' => 334, '|' => 260, '}' => 334, '~' => 584,
        ];

        $encoded = $this->encodeText($value);
        $units = 0;
        for ($index = 0, $length = strlen($encoded); $index < $length; $index++) {
            $units += $widths[$encoded[$index]] ?? 556;
        }

        return $units * $fontSize / 1000;
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
