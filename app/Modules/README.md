# Struktur Modul Aplikasi

Setiap fitur ditempatkan menurut domain bisnisnya. Rute URL dan hak akses role
tetap diatur dari `app/Config/Routes.php` dan `app/Config/UserRoles.php`.

- `Agendaris`: agenda surat, progres dokumen, ATK, dan souvenir/inventaris.
- `Akuntansi`: anggaran, laba/rugi, RKA, simulasi, dan bukti bayar.
- `Sdm`: SDM Jatim, kehadiran, lembur, magang, dan SPPD.
- `BagianUmum`: aset, SPK, pengadaan, kendaraan, dan non-belanja modal.
- `Security`: tampilan distribusi dokumen yang khusus untuk Security.
- `ManagerOperasional`: rekap PKS yang bersifat lihat saja.
- `Shared/Documents`: data dokumen masuk dan dokumen keluar lintas role.
- `Shared/Pks`: data PKS yang dipakai bersama Bagian Umum 1, Bagian Umum 2,
  administrator, dan rekap Manager Operasional.

Controller dan model berada di `app/Modules/<Modul>/...`. View mengikuti
kelompok yang sama di `app/Views/modules/<modul>/...`, sesuai konvensi view
CodeIgniter.
