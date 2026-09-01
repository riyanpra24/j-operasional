<?php

namespace App\Libraries;

final class AgendarisNotificationService
{
    /**
     * @return array{
     *     total: int,
     *     incoming_count: int,
     *     outgoing_count: int,
     *     items: array<int, array{
     *         id: string,
     *         type: string,
     *         title: string,
     *         description: string,
     *         time: string,
     *         sort_at: int,
     *         url: string
     *     }>
     * }
     */
    public function summary(int $limit = 6): array
    {
        $empty = [
            'total' => 0,
            'incoming_count' => 0,
            'outgoing_count' => 0,
            'items' => [],
        ];

        try {
            $incomingCount = $this->pendingIncomingBuilder()->countAllResults();
            $outgoingCount = $this->pendingOutgoingBuilder()->countAllResults();
            $incomingRows = $this->pendingIncomingBuilder()
                ->select('id, pengirim, perihal_surat, jenis, tanggal_diterima, created_at, updated_at')
                ->orderBy('updated_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();
            $outgoingRows = $this->pendingOutgoingBuilder()
                ->select('id, nomor_surat, jenis_surat, pelaksana, up, tanggal_pengiriman, progres, status_agendaris, diambil_ekspedisi_at')
                ->orderBy('tanggal_pengiriman', 'DESC')
                ->orderBy('id', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();

            $items = [];
            foreach ($incomingRows as $row) {
                $timestamp = $this->timestamp($row['updated_at'] ?? null, $row['tanggal_diterima'] ?? null);
                $items[] = [
                    'id' => 'agendaris-incoming-' . (int) $row['id'],
                    'type' => 'incoming',
                    'title' => 'Dokumen masuk menunggu penyelesaian',
                    'description' => $this->joinDescription([$row['pengirim'] ?? null, $row['perihal_surat'] ?? null]),
                    'time' => $this->formatTimestamp($timestamp),
                    'sort_at' => $timestamp,
                    'url' => site_url('agendaris/progres-dokumen?q=' . rawurlencode((string) ($row['pengirim'] ?? ''))),
                ];
            }

            foreach ($outgoingRows as $row) {
                $isReady = ($row['progres'] ?? '') === 'Diambil Ekspedisi';
                $timestamp = $this->timestamp($row['diambil_ekspedisi_at'] ?? null, $row['tanggal_pengiriman'] ?? null);
                $items[] = [
                    'id' => 'agendaris-outgoing-' . (int) $row['id'],
                    'type' => 'outgoing',
                    'title' => $isReady ? 'Dokumen keluar siap diselesaikan' : 'Dokumen keluar menunggu Security',
                    'description' => $this->joinDescription([$row['nomor_surat'] ?? null, $row['jenis_surat'] ?? null]),
                    'time' => $this->formatTimestamp($timestamp),
                    'sort_at' => $timestamp,
                    'url' => site_url('agendaris/progres-dokumen-keluar?q=' . rawurlencode((string) ($row['nomor_surat'] ?? ''))),
                ];
            }

            usort($items, static fn (array $left, array $right): int => $right['sort_at'] <=> $left['sort_at']);

            return [
                'total' => $incomingCount + $outgoingCount,
                'incoming_count' => $incomingCount,
                'outgoing_count' => $outgoingCount,
                'items' => array_slice($items, 0, $limit),
            ];
        } catch (\Throwable $error) {
            log_message('error', 'Notifikasi Agendaris gagal dimuat: {message}', ['message' => $error->getMessage()]);

            return $empty;
        }
    }

    private function pendingIncomingBuilder()
    {
        return db_connect()->table('agendaris')
            ->where('deleted_at', null)
            ->where('progres', 'Menunggu Penyelesaian');
    }

    private function pendingOutgoingBuilder()
    {
        return db_connect()->table('dokumen_keluar')
            ->where('deleted_at', null)
            ->groupStart()
                ->where('status_agendaris', null)
                ->orWhere('status_agendaris !=', 'Selesai')
            ->groupEnd();
    }

    private function timestamp(?string $preferred, ?string $fallback): int
    {
        $value = trim((string) ($preferred ?: $fallback));
        $timestamp = $value !== '' ? strtotime($value) : false;

        return $timestamp === false ? 0 : $timestamp;
    }

    private function formatTimestamp(int $timestamp): string
    {
        if ($timestamp <= 0) {
            return 'Waktu belum tersedia';
        }

        $months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $month = $months[(int) date('n', $timestamp)];
        $time = date('H:i', $timestamp);
        $suffix = $time === '00:00' ? '' : ' · ' . $time . ' WIB';

        return date('d', $timestamp) . ' ' . $month . ' ' . date('Y', $timestamp) . $suffix;
    }

    /** @param array<int, mixed> $parts */
    private function joinDescription(array $parts): string
    {
        $values = array_values(array_filter(
            array_map(static fn (mixed $value): string => trim((string) $value), $parts),
            static fn (string $value): bool => $value !== ''
        ));

        return $values === [] ? 'Detail dokumen belum tersedia' : implode(' · ', $values);
    }
}
