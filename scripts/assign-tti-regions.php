<?php
declare(strict_types=1);

// Assign regions to the TESDA technology institutions imported from the 2026 directory snapshot.
// IDs are explicit so address/name discrepancies can be reviewed before changing the database.
ini_set('session.save_path', sys_get_temp_dir());
require dirname(__DIR__) . '/bootstrap.php';
if (PHP_SAPI !== 'cli') exit("Run from the command line.\n");

$groups = [
    'NCR' => [183,204,212,239,258,260,298],
    'CAR' => [170,195,224,231,252,266,276,316],
    'I' => [171,207,214,238,251,273,315,319,337,340,349],
    'II' => [172,208,216,237,247,272,317,318,338,339,350],
    'III' => [173,189,190,201,222,236,253,265,280,287,311,323],
    'IVA' => [174,206,213,241,246,269,281,286,293,314,320,336],
    'IVB' => [175,209,223,242,243,271,300,301,302,303,304],
    'V' => [176,205,219,234,250,267,275,291,312,322,333,342,348,351,355],
    'VI' => [177,200,220,257,259,282,313,321,335,341],
    'NIR' => [188,233,245,289,296,334],
    'VII' => [182,196,221,232,268,279,288,292,295,297,299],
    'VIII' => [178,210,211,240,249,270,274,309,324,332,343,347,352,354],
    'IX' => [180,193,194,203,218,228,244,307,326],
    'X' => [179,187,197,215,230,248,263,277,308,325,331,344,346,353],
    'XI' => [181,198,217,229,254,264,278,290,294,306,327,330,345],
    'XII' => [199,226,235,256,261,284,285,310],
    'XIII' => [184,202,225,227,255,262,283,305,328,329],
    'BARMM' => [185,186,191,192],
];

$regions = [
    'NCR' => 'National Capital Region (NCR)',
    'CAR' => 'Cordillera Administrative Region (CAR)',
    'I' => 'Region I - Ilocos',
    'II' => 'Region II - Cagayan Valley',
    'III' => 'Region III - Central Luzon',
    'IVA' => 'Region IVA - CALABARZON',
    'IVB' => 'Region IVB - MIMAROPA',
    'V' => 'Region V - Bicol',
    'VI' => 'Region VI - Western Visayas',
    'NIR' => 'Negros Island Region',
    'VII' => 'Region VII - Central Visayas',
    'VIII' => 'Region VIII - Eastern Visayas',
    'IX' => 'Region IX - Zamboanga Peninsula',
    'X' => 'Region X - Northern Mindanao',
    'XI' => 'Region XI - Davao',
    'XII' => 'Region XII - SOCCSKSARGEN',
    'XIII' => 'Region XIII - CARAGA',
    'BARMM' => 'Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)',
];

$mapping = [];
foreach ($groups as $code => $ids) {
    foreach ($ids as $id) {
        if (isset($mapping[$id])) exit("Duplicate mapping for office $id.\n");
        $mapping[$id] = $code;
    }
}
$offices = db()->query("SELECT id,name,address FROM offices WHERE office_type='TESDA Technology Institution'")->fetchAll();
$known = array_column($offices, null, 'id');
$missing = array_diff(array_keys($known), array_keys($mapping));
$unknown = array_diff(array_keys($mapping), array_keys($known));
if ($missing || $unknown) {
    echo 'Missing office IDs: ' . implode(', ', $missing) . "\n";
    echo 'Unknown office IDs: ' . implode(', ', $unknown) . "\n";
    exit(1);
}
foreach ($groups as $code => $ids) echo "$code: " . count($ids) . " institutions\n";
echo 'Total: ' . count($mapping) . "\n";
if (!in_array('--apply', $argv, true)) exit("Dry run complete. Use --apply to save.\n");

$pdo = db();
$pdo->beginTransaction();
try {
    $update = $pdo->prepare("UPDATE offices SET region_code=?,region_name=? WHERE id=? AND office_type='TESDA Technology Institution'");
    foreach ($mapping as $id => $code) $update->execute([$code,$regions[$code],$id]);
    $pdo->commit();
    echo "Saved regions for " . count($mapping) . " institutions.\n";
} catch (Throwable $error) {
    $pdo->rollBack();
    throw $error;
}
