<?php
declare(strict_types=1);

// Link the user-provided central office portraits to officials with matching names.
ini_set('session.save_path', sys_get_temp_dir());
require dirname(__DIR__) . '/bootstrap.php';
if (PHP_SAPI !== 'cli') exit("Run from the command line.\n");

$photos = [
    'JOSE FRANCISCO "KIKO" B. BENITEZ' => 'Sec Kiko Benitez.jpg',
    'ROSANNA A. URDANETA' => 'DDG Rosanna A Urdaneta_NEW copy.jpg',
    'VIDAL D. VILLANUEVA III' => 'DDG Vidal D. Villanueva.jpg',
    'NELLY NITA N. DILLERA' => 'DDG Nelly Nita Dillera.jpg',
    'FELIZARDO R. COLAMBO' => 'DDG Felizardo Colambo.jpg',
    'GALO B. GLINO III' => 'DDG Galo Glino III.png',
    'David B. Bungallon' => 'DAVID B. BUNGALLON.jpg',
    'GILBERT M. CASTRO' => 'GILBERT M. CASTRO.jpg',
    'ROGELIO F. LLOVIT, JR.' => 'ROGELIO F. LLOVIT, JR.jpg',
    'ROSALINA S. CONSTANTINO' => 'ROSALINA S. CONSTANTINO.jpg',
    'CHARLYN B. JUSTIMBASTE' => 'CHARLYN B. JUSTIMBASTE.jpg',
    'KATHERINE AMOR A. ZARSADIAS' => 'KATHERINE AMOR A. ZARSADIAS.jpg',
    'EL CID H. CASTILLO' => 'EL CID H. CASTILLO.jpg',
    'REDILYN C. AGUB' => 'REDILYN C. AGUB.jpg',
    'REA M. DALUMPINES' => 'REA M. DALUMPINES.jpg',
    'JANET M. ABASOLO' => 'JANET M. ABASOLO.jpg',
    'ARMELA B. GUTIERREZ' => 'ARMELA B. GUTIERREZ.jpg',
    'LORRIENNE JUDITH MARIE G. JUANANI' => 'LORRIENNE JUDITH MARIE G. JUANANI.jpg',
];

$base = 'assets/img/tesda-officials/central_office/';
$find = db()->prepare('SELECT COUNT(*) FROM officials WHERE name = ?');
$update = db()->prepare('UPDATE officials SET photo_path = ? WHERE name = ?');
$matched = 0;
foreach ($photos as $name => $file) {
    if (!is_file(dirname(__DIR__) . '/' . $base . $file)) exit("Missing image: $file\n");
    $find->execute([$name]);
    $count = (int)$find->fetchColumn();
    if ($count === 0) exit("No matching official: $name\n");
    echo "$name: $count record(s)\n";
    $matched += $count;
}
if (!in_array('--apply', $argv, true)) exit("Matched $matched records. Use --apply to save.\n");

$pdo = db();
$pdo->beginTransaction();
try {
    foreach ($photos as $name => $file) $update->execute([$base . $file, $name]);
    $pdo->commit();
    echo "Saved photo paths for $matched official records.\n";
} catch (Throwable $error) {
    $pdo->rollBack();
    throw $error;
}
