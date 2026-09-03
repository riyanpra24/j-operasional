<?php

namespace App\Controllers;

use App\Models\AgendarisModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Disposition;

class Sdm extends BaseController
{
    public function index(): string
    {
        return view('sdm/index', [
            'title' => 'SDM & Teller',
        ]);
    }

    public function incomingDocuments(): string
    {
        return $this->documentList(false);
    }

    public function incomingDocumentHistory(): string
    {
        return $this->documentList(true);
    }

    public function synchronizeIncomingDocuments(): RedirectResponse
    {
        $model = new AgendarisModel();
        $documents = $model->findAll();
        $updatedDocuments = 0;
        $normalizedRecipients = 0;
        $db = db_connect();

        $db->transStart();

        foreach ($documents as $document) {
            $updates = [];

            for ($step = 1; $step <= Disposition::MAX_STEPS; $step++) {
                $field = "disposisi_{$step}";
                $currentRecipient = trim((string) ($document[$field] ?? ''));
                if ($currentRecipient === '') {
                    continue;
                }

                $canonicalRecipient = $this->canonicalRecipient($currentRecipient);
                if ($canonicalRecipient !== null && $canonicalRecipient !== $currentRecipient) {
                    $updates[$field] = $canonicalRecipient;
                    $normalizedRecipients++;
                }
            }

            if ($updates !== []) {
                if (! $model->update((int) $document['id'], $updates)) {
                    $db->transRollback();

                    return redirect()->to(site_url('sdm/dokumen-masuk'))
                        ->with('error', 'Sinkronisasi belum berhasil. Silakan coba kembali.');
                }

                $updatedDocuments++;
            }
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to(site_url('sdm/dokumen-masuk'))
                ->with('error', 'Sinkronisasi belum berhasil. Silakan coba kembali.');
        }

        $message = $updatedDocuments > 0
            ? "{$updatedDocuments} dokumen lama berhasil disinkronkan ({$normalizedRecipients} nama penerima diperbaiki)."
            : 'Seluruh dokumen Agendaris sudah tersinkron dengan SDM & Teller.';

        return redirect()->to(site_url('sdm/dokumen-masuk'))->with('sync_success', $message);
    }

    private function documentList(bool $historyMode): string
    {
        $recipientName = trim((string) session()->get('auth_display_name'));
        $currentRole = (string) session()->get('auth_role');
        $keyword = trim((string) $this->request->getGet('q'));
        $status = trim((string) $this->request->getGet('status'));
        $perPage = (int) $this->request->getGet('per_page');

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        $allowedStatuses = Disposition::STATUSES;
        if (! in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        $latestRecipientParts = [];
        $latestStatusParts = [];
        $latestTimeParts = [];
        $latestNoteParts = [];
        $latestStepParts = [];
        for ($step = Disposition::MAX_STEPS; $step >= 1; $step--) {
            $filled = "TRIM(COALESCE(agendaris.disposisi_{$step}, '')) <> ''";
            $latestRecipientParts[] = "WHEN {$filled} THEN agendaris.disposisi_{$step}";
            $latestStatusParts[] = "WHEN {$filled} THEN COALESCE(NULLIF(TRIM(agendaris.disposisi_{$step}_status), ''), 'Menunggu')";
            $latestTimeParts[] = "WHEN {$filled} THEN agendaris.disposisi_{$step}_waktu";
            $latestNoteParts[] = "WHEN {$filled} THEN agendaris.disposisi_{$step}_catatan";
            $latestStepParts[] = "WHEN {$filled} THEN {$step}";
        }
        $latestRecipientSql = 'CASE ' . implode(' ', $latestRecipientParts) . " ELSE '' END";
        $latestStatusSql = 'CASE ' . implode(' ', $latestStatusParts) . " ELSE 'Menunggu' END";
        $latestTimeSql = 'CASE ' . implode(' ', $latestTimeParts) . ' ELSE NULL END';
        $latestNoteSql = 'CASE ' . implode(' ', $latestNoteParts) . ' ELSE NULL END';
        $latestStepSql = 'CASE ' . implode(' ', $latestStepParts) . ' ELSE 0 END';

        $model = new AgendarisModel();
        $model->select(
            "agendaris.*, {$latestRecipientSql} AS disposisi_terakhir, "
            . "{$latestStatusSql} AS status_disposisi_terakhir, "
            . "{$latestTimeSql} AS waktu_disposisi_terakhir, "
            . "{$latestNoteSql} AS catatan_disposisi_terakhir, "
            . "{$latestStepSql} AS tahap_disposisi_terakhir, "
            . 'dokumen_masuk.penyerahan_at AS sumber_penyerahan_at',
            false,
        );
        $model->join('dokumen_masuk', 'dokumen_masuk.id = agendaris.dokumen_masuk_id', 'left');

        $scopedRecipients = $currentRole === 'admin'
            ? $this->activeSdmRecipientNames()
            : ($recipientName !== '' ? [$recipientName] : []);
        $recipientSqlList = implode(', ', array_map(
            static fn (string $name): string => db_connect()->escape(mb_strtolower(trim($name))),
            $scopedRecipients,
        ));

        if ($scopedRecipients === []) {
            $model->where('1 = 0', null, false);
        } elseif ($historyMode) {
            $model->groupStart();
            for ($step = 1; $step <= Disposition::MAX_STEPS; $step++) {
                $condition = "LOWER(TRIM(agendaris.disposisi_{$step})) IN ({$recipientSqlList})";
                if ($step === 1) {
                    $model->where($condition, null, false);
                } else {
                    $model->orWhere($condition, null, false);
                }
            }
            $model->groupEnd();
            $model->where(
                'LOWER(TRIM(' . $latestRecipientSql . ")) NOT IN ({$recipientSqlList})",
                null,
                false,
            );
        } else {
            $model->groupStart()
                ->where('agendaris.progres', 'Selesai')
                ->orWhere('agendaris.sdm_processed_at IS NOT NULL', null, false)
                ->groupEnd();
            $model->where(
                'LOWER(TRIM(' . $latestRecipientSql . ")) IN ({$recipientSqlList})",
                null,
                false,
            );
        }

        if ($keyword !== '') {
            $model->groupStart()
                ->like('agendaris.nomor_agendaris', $keyword)
                ->orLike('agendaris.nomor_surat', $keyword)
                ->orLike('agendaris.perihal_surat', $keyword)
                ->orLike('agendaris.pengirim', $keyword)
                ->orLike('agendaris.jenis', $keyword)
                ->groupEnd();
        }

        if ($status !== '') {
            $model->where($latestStatusSql . ' = ' . db_connect()->escape($status), null, false);
        }

        $pagerGroup = $historyMode ? 'sdm_incoming_history' : 'sdm_incoming_documents';

        return view('sdm/dokumen_masuk', [
            'title' => ($historyMode ? 'Riwayat Dokumen Masuk' : 'Dokumen Masuk') . ' | SDM & Teller',
            'documents' => $model
                ->orderBy('waktu_disposisi_terakhir', 'DESC')
                ->orderBy('agendaris.id', 'DESC')
                ->paginate($perPage, $pagerGroup),
            'pager' => $model->pager,
            'recipientName' => $recipientName,
            'recipientScopeLabel' => $currentRole === 'admin' ? 'seluruh akun SDM & Teller' : $recipientName,
            'isAdminView' => $currentRole === 'admin',
            'statusOptions' => $allowedStatuses,
            'recipientOptions' => Disposition::RECIPIENTS,
            'filters' => compact('keyword', 'status', 'perPage'),
            'historyMode' => $historyMode,
            'pagerGroup' => $pagerGroup,
            'listUrl' => site_url($historyMode ? 'sdm/riwayat' : 'sdm/dokumen-masuk'),
        ]);
    }

    public function updateIncomingDocument(int $id): ResponseInterface
    {
        $model = new AgendarisModel();
        $document = $model->find($id);
        $latestStep = $document !== null ? $this->latestDispositionStep($document) : 0;

        if (
            $document === null
            || (($document['progres'] ?? '') !== 'Selesai' && empty($document['sdm_processed_at']))
            || $latestStep === 0
            || ! $this->belongsToCurrentUser($document, $latestStep)
        ) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Dokumen tidak tersedia atau disposisi sudah diteruskan kepada pengguna lain.',
                'csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        $status = trim((string) $this->request->getPost('current_status'));
        $date = trim((string) $this->request->getPost('current_date'));
        $note = trim((string) $this->request->getPost('current_note'));
        $addDisposition = $this->request->getPost('add_disposition') === '1';

        $validationData = [
            'current_status' => $status,
            'current_date' => $date,
            'current_note' => $note,
        ];
        $rules = [
            'current_status' => 'required|in_list[' . implode(',', Disposition::STATUSES) . ']',
            'current_date' => 'permit_empty|valid_date[Y-m-d]',
            'current_note' => 'permit_empty|max_length[1000]',
        ];

        if ($addDisposition) {
            if ($latestStep >= Disposition::MAX_STEPS) {
                return $this->validationError(['Seluruh lima tahap disposisi sudah terisi.']);
            }

            $validationData['next_recipient'] = trim((string) $this->request->getPost('next_recipient'));
            $validationData['next_status'] = trim((string) $this->request->getPost('next_status'));
            $validationData['next_date'] = trim((string) $this->request->getPost('next_date'));
            $validationData['next_note'] = trim((string) $this->request->getPost('next_note'));
            $rules['next_recipient'] = 'required|in_list[' . implode(',', Disposition::RECIPIENTS) . ']';
            $rules['next_status'] = 'required|in_list[' . implode(',', Disposition::STATUSES) . ']';
            $rules['next_date'] = 'required|valid_date[Y-m-d]';
            $rules['next_note'] = 'permit_empty|max_length[1000]';
        }

        $validation = service('validation')->setRules($rules);
        if (! $validation->run($validationData)) {
            return $this->validationError(array_values($validation->getErrors()));
        }

        $updates = [
            "disposisi_{$latestStep}_status" => $addDisposition ? 'Diteruskan' : $status,
            "disposisi_{$latestStep}_waktu" => $this->dateTimeValue($date, $document["disposisi_{$latestStep}_waktu"] ?? null),
            "disposisi_{$latestStep}_catatan" => $note !== '' ? $note : null,
        ];

        if ((string) session()->get('auth_role') === 'sdm' && empty($document['sdm_processed_at'])) {
            $updates['sdm_processed_at'] = date('Y-m-d H:i:s');
            $updates['sdm_processed_by'] = trim((string) session()->get('auth_display_name')) ?: 'SDM & Teller';
        }

        if ($addDisposition) {
            $nextStep = $latestStep + 1;
            $updates["disposisi_{$nextStep}"] = $validationData['next_recipient'];
            $updates["disposisi_{$nextStep}_status"] = $validationData['next_status'];
            $updates["disposisi_{$nextStep}_waktu"] = $validationData['next_date'] . ' 00:00:00';
            $updates["disposisi_{$nextStep}_catatan"] = $validationData['next_note'] !== '' ? $validationData['next_note'] : null;
        }

        if (! $model->update($id, $updates)) {
            return $this->validationError($model->errors() ?: ['Perubahan disposisi belum berhasil disimpan.']);
        }

        $message = $addDisposition
            ? 'Disposisi berikutnya berhasil ditambahkan dan dokumen telah diteruskan.'
            : 'Status dan catatan disposisi berhasil diperbarui.';
        session()->setFlashdata('success', $message);

        return $this->response->setJSON([
            'success' => true,
            'message' => $message,
            'csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()],
        ]);
    }

    private function latestDispositionStep(array $document): int
    {
        for ($step = Disposition::MAX_STEPS; $step >= 1; $step--) {
            if (trim((string) ($document["disposisi_{$step}"] ?? '')) !== '') {
                return $step;
            }
        }

        return 0;
    }

    private function belongsToCurrentUser(array $document, int $latestStep): bool
    {
        $recipient = mb_strtolower(trim((string) ($document["disposisi_{$latestStep}"] ?? '')));
        $currentUser = mb_strtolower(trim((string) session()->get('auth_display_name')));

        return $recipient !== '' && $currentUser !== '' && hash_equals($recipient, $currentUser);
    }

    private function canonicalRecipient(string $recipient): ?string
    {
        $recipientParts = $this->recipientNameParts($recipient);
        if ($recipientParts === []) {
            return null;
        }

        $matches = [];
        foreach (Disposition::RECIPIENTS as $canonicalRecipient) {
            $canonicalParts = $this->recipientNameParts($canonicalRecipient);
            if ($recipientParts === $canonicalParts) {
                return $canonicalRecipient;
            }

            // Nama lama minimal dua kata boleh dicocokkan dengan awalan nama
            // lengkap, misalnya "Kiki Ramadhani" ke "Kiki Ramadhani Suyono".
            if (
                count($recipientParts) >= 2
                && count($recipientParts) < count($canonicalParts)
                && array_slice($canonicalParts, 0, count($recipientParts)) === $recipientParts
            ) {
                $matches[] = $canonicalRecipient;
            }
        }

        return count($matches) === 1 ? $matches[0] : null;
    }

    private function activeSdmRecipientNames(): array
    {
        $rows = db_connect()->table('users')
            ->select('display_name')
            ->where('role', 'sdm')
            ->where('deleted_at IS NULL', null, false)
            ->get()
            ->getResultArray();

        return array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['display_name'] ?? '')),
            $rows,
        )));
    }

    private function recipientNameParts(string $name): array
    {
        $normalized = mb_strtolower(trim($name));
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $normalized) ?? '';

        return array_values(array_filter(preg_split('/\s+/u', trim($normalized)) ?: []));
    }

    private function dateTimeValue(string $date, mixed $fallback): string
    {
        if ($date !== '') {
            return $date . ' 00:00:00';
        }

        return trim((string) $fallback) !== '' ? (string) $fallback : date('Y-m-d H:i:s');
    }

    private function validationError(array $errors): ResponseInterface
    {
        return $this->response->setStatusCode(422)->setJSON([
            'success' => false,
            'message' => $errors[0] ?? 'Data disposisi belum valid.',
            'errors' => $errors,
            'csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()],
        ]);
    }
}
