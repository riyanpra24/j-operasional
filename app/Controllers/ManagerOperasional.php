<?php

namespace App\Controllers;

class ManagerOperasional extends BaseController
{
    public function rekapPks(): string
    {
        $records = db_connect()->table('pks_kerjasama')
            ->select('pks_kerjasama.kode_internal, pks_kerjasama.nama_kerjasama, pks_kerjasama.unit_pengelola, pks_kerjasama.pic_internal, pks_mitra.nama_mitra, d.nomor_dokumen, d.periode_selesai, d.nilai')
            ->join('pks_mitra', 'pks_mitra.id = pks_kerjasama.mitra_id AND pks_mitra.deleted_at IS NULL')
            ->join('pks_dokumen_kerjasama d', "d.kerjasama_id = pks_kerjasama.id AND d.jenis_dokumen = 'PKS' AND d.deleted_at IS NULL", 'left')
            ->where('pks_kerjasama.deleted_at', null)
            ->orderBy('pks_kerjasama.unit_pengelola', 'ASC')->orderBy('pks_kerjasama.nama_kerjasama', 'ASC')->get()->getResultArray();

        return view('manager_operasional/rekap_pks', ['title' => 'Rekap PKS Barang dan Jasa', 'records' => $records]);
    }
}
