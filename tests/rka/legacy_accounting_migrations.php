<?php

/** Integration test. Creates NEW isolated databases; never migrates the source. */
$root = dirname(__DIR__, 2);
chdir($root);
$backup = $argv[1] ?? '';
$prefix = $argv[2] ?? '';
if (!preg_match('/^jaksa_migration_test_[0-9]{8}_[0-9]{6}$/D', $prefix)) {
    throw new RuntimeException('Supply a new jaksa_migration_test_YYYYMMDD_HHMMSS prefix.');
}
$manifest = json_decode(file_get_contents($backup . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$baseline = json_decode(file_get_contents($backup . '/source-fingerprint.json'), true, 512, JSON_THROW_ON_ERROR);
$dump = $backup . '/operasional.sql';
if ($manifest['database'] !== 'operasional' || hash_file('sha256', $dump) !== $manifest['sha256']) {
    throw new RuntimeException('Backup identity or checksum mismatch.');
}
if (preg_match('/^(?:USE\s|CREATE\s+DATABASE)/mi', file_get_contents($dump))) {
    throw new RuntimeException('Dump must not switch or create databases.');
}
require $root . '/system/Config/DotEnv.php';
(new CodeIgniter\Config\DotEnv($root))->load();
$host = getenv('database.default.hostname');
$port = (int) (getenv('database.default.port') ?: 3306);
$user = getenv('database.default.username');
$password = getenv('database.default.password');
if (!in_array($host, ['127.0.0.1', 'localhost', '::1'], true)
    || getenv('database.default.database') !== 'operasional') {
    throw new RuntimeException('Expected local operasional source.');
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$admin = new mysqli($host, $user, $password, '', $port);
$clone = $prefix . '_restore';
$fresh = $prefix . '_fresh';
foreach ([$clone, $fresh] as $name) {
    // No IF NOT EXISTS: refuse reuse or destructive cleanup of an existing database.
    $admin->query("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
}
$env = getenv();
$env['MYSQL_PWD'] = $password;
$process = proc_open([
    'C:/xampp/mysql/bin/mysql.exe', '--host=' . $host, '--port=' . $port,
    '--user=' . $user, '--database=' . $clone, '--default-character-set=utf8mb4',
], [0 => ['file', $dump, 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]); fclose($pipes[2]);
if (proc_close($process) !== 0) throw new RuntimeException('Restore failed: ' . $stderr);

function fingerprint(mysqli $db): array
{
    $db->query('START TRANSACTION READ ONLY');
    $result = [];
    foreach ($db->query('SHOW TABLES')->fetch_all() as [$table]) {
        $hash = hash_init('sha256');
        $rows = $db->query("SELECT * FROM `$table` ORDER BY id");
        while ($row = $rows->fetch_assoc()) hash_update($hash, json_encode($row) . "\n");
        $result[$table] = ['count' => $rows->num_rows, 'sha256' => hash_final($hash),
            'ddl' => $db->query("SHOW CREATE TABLE `$table`")->fetch_row()[1]];
    }
    $db->rollback();
    return $result;
}
function check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function normalizedDdl(string $sql): string
{
    return preg_replace('/ AUTO_INCREMENT=\d+/', '', $sql);
}
$copy = new mysqli($host, $user, $password, $clone, $port);
$restored = fingerprint($copy);
check($restored === $baseline, 'Restored backup differs from source fingerprint.');

// Override every configuration source BEFORE booting the application.
putenv('database.default.database=' . $clone);
$_ENV['database.default.database'] = $_SERVER['database.default.database'] = $clone;
define('FCPATH', $root . '/public/');
define('ENVIRONMENT', 'development');
require $root . '/app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
$db = db_connect();
check($db->query('SELECT DATABASE() db')->getRowArray()['db'] === $clone, 'Wrong default test database.');
$runner = new CodeIgniter\Database\MigrationRunner(config('Migrations'), $db);
check($runner->setNamespace('App')->latest(), 'Clone migration runner failed.');
check(fingerprint($copy) === $restored, 'Already applied migrations changed restored database.');

$config = config('Database')->default;
$config['database'] = $fresh;
$freshDb = Config\Database::connect($config, false);
check($freshDb->query('SELECT DATABASE() db')->getRowArray()['db'] === $fresh, 'Wrong fresh test database.');
$forge = Config\Database::forge($freshDb);
$versions = ['2026-09-16-130000', '2026-09-16-140000', '2026-09-16-150000',
    '2026-09-16-160000', '2026-09-16-170000', '2026-09-16-180000',
    '2026-09-18-110000', '2026-09-18-120000'];
$migrations = [];
foreach ($versions as $version) {
    $files = glob($root . '/app/Database/Migrations/' . $version . '_*.php');
    check(count($files) === 1, 'Missing or ambiguous migration: ' . $version);
    require_once $files[0];
    $class = 'App\\Database\\Migrations\\' . substr(basename($files[0], '.php'), 18);
    $migrations[] = new $class($forge);
}
foreach ($migrations as $migration) $migration->up();
$freshRaw = new mysqli($host, $user, $password, $fresh, $port);
$built = fingerprint($freshRaw);
check(count($built) === 5, 'Expected exactly five legacy accounting tables.');
foreach ($built as $table => $info) {
    check(normalizedDdl($info['ddl']) === normalizedDdl($baseline[$table]['ddl']), 'Schema mismatch: ' . $table);
}
check($built['accounting_lr_lob_mappings']['count'] === 3, 'LOB seed mismatch.');
$aliases = $freshDb->table('accounting_lr_account_mappings')->where('unit_scope', 'all')
    ->where('source_description', 'Tranportasi dinas luar negeri')->where('sign_mode', 'keep')->countAllResults();
check($aliases === 1, 'Historical alias missing or duplicated.');
$premium = $freshDb->table('accounting_lr_account_mappings')->where('unit_scope', 'branch')
    ->where('source_description', 'Pendapatan premi penjaminan kredit')->where('sign_mode', 'invert')->countAllResults();
check($premium === 1, 'Historical premium mapping mismatch.');
function mappingValues(mysqli $db): array
{
    return $db->query('SELECT unit_scope,source_description,source_description_normalized,report_label,sign_mode,is_active FROM accounting_lr_account_mappings ORDER BY unit_scope,source_description_normalized')->fetch_all(MYSQLI_ASSOC);
}
$expectedMappings = mappingValues($freshRaw);
foreach (array_reverse($migrations) as $migration) $migration->down();
check($freshRaw->query('SHOW TABLES')->num_rows === 0, 'Rollback left tables behind.');
foreach ($migrations as $migration) $migration->up();
check(mappingValues($freshRaw) === $expectedMappings, 'Rebuild changed historical mappings.');
$source = new mysqli($host, $user, $password, 'operasional', $port);
check(fingerprint($source) === $baseline, 'Source changed during the test; investigate concurrent activity.');
$report = ['backup_restored_exactly' => true, 'source_unchanged' => true,
    'clone_latest_no_changes' => true, 'eight_migrations_up_down_up' => true,
    'five_table_schemas_match_source' => true, 'mapping_rows' => count($expectedMappings),
    'databases' => [$clone, $fresh], 'scope' => 'Eight restored migrations, not full fresh application install'];
file_put_contents($backup . '/verification.json', json_encode($report, JSON_PRETTY_PRINT));
echo json_encode($report, JSON_PRETTY_PRINT) . PHP_EOL;
