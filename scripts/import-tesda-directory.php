<?php
declare(strict_types=1);

// One-time import from locally downloaded pages of https://www.tesda.gov.ph/directory.
// Run without --apply to inspect counts; use --apply to save to the configured database.
ini_set('session.save_path', sys_get_temp_dir());
require dirname(__DIR__) . '/bootstrap.php';

if (PHP_SAPI !== 'cli') exit("Run this script from the command line.\n");
$root = dirname(__DIR__);
$sources = [
    ['file' => $root . '/database/tesda-sources/central.html', 'type' => 'Central Office'],
    ['file' => $root . '/database/tesda-sources/regions.html', 'type' => 'regional'],
    ['file' => $root . '/database/tesda-sources/institutes.html', 'type' => 'TESDA Technology Institution'],
];

function clean_text(?string $value): string {
    return trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $value ?? '')) ?? '');
}
function first_node(DOMXPath $xpath, string $query, DOMNode $context): ?DOMNode {
    return $xpath->query($query, $context)->item(0);
}
function node_text(DOMXPath $xpath, string $query, DOMNode $context): string {
    return clean_text(first_node($xpath, $query, $context)?->textContent);
}
function region_code(string $name): string {
    if (str_contains($name, '(NCR)')) return 'NCR';
    if (str_contains($name, '(CAR)')) return 'CAR';
    if (str_contains($name, '(BARMM)')) return 'BARMM';
    if (str_contains($name, 'Negros Island')) return 'NIR';
    if (preg_match('/^Region\s+([IVX]+[AB]?)/', $name, $match)) return $match[1];
    return '';
}
function detail(string $text, string $label, ?string $next): string {
    $pattern = '/' . preg_quote($label, '/') . '\s*(.*?)' . ($next ? '(?=' . preg_quote($next, '/') . '|$)' : '$') . '/isu';
    return preg_match($pattern, $text, $match) ? clean_text($match[1]) : '';
}

$records = [];
$sourceCount = ['Central Office'=>0,'Regional Office'=>0,'Provincial Office'=>0,'TESDA Technology Institution'=>0];
foreach ($sources as $source) {
    if (!is_file($source['file'])) exit("Missing source: {$source['file']}\n");
    $document = new DOMDocument();
    libxml_use_internal_errors(true);
    $document->loadHTML(file_get_contents($source['file']));
    libxml_clear_errors();
    $xpath = new DOMXPath($document);
    $top = $xpath->query("//div[contains(concat(' ',normalize-space(@class),' '),' directory-list-container ')]/div[contains(@class,'row')]/div[contains(@class,'col-lg-12')]/div[contains(concat(' ',normalize-space(@class),' '),' directory-list ')]");
    foreach ($top as $group) {
        if ($source['type'] === 'Central Office') {
            $items = [$group];
            $type = 'Central Office';
            $regionName = '';
        } else {
            $heading = node_text($xpath, './div[contains(@class,"directory-name")]/h4', $group);
            $items = $xpath->query('./div[contains(concat(" ",normalize-space(@class)," ")," directory-list ")]', $group);
            $type = $source['type'] === 'regional' && str_starts_with($heading, 'Central Office') ? 'Central Office' : ($source['type'] === 'regional' && str_ends_with($heading, 'Regional Office') ? 'Regional Office' : ($source['type'] === 'regional' ? 'Provincial Office' : 'TESDA Technology Institution'));
            $regionName = $source['type'] === 'regional' && $type !== 'Central Office' ? preg_replace('/ - (Regional|Provincial) Office$/', '', $heading) : '';
        }
        foreach ($items as $item) {
            $name = node_text($xpath, './div[contains(@class,"directory-name")]/h4 | ./div[contains(@class,"directory-name")]/strong', $item);
            if ($name === '') continue;
            $recordType = $source['type'] === 'regional' && preg_match('/^Provincial Office\b/i', $name) ? 'Provincial Office' : $type;
            if ($type === 'Central Office') {
                $official = node_text($xpath, './div[contains(@class,"d-flex")]/div[contains(@class,"directory-description")]/h4', $item);
                $position = node_text($xpath, './div[contains(@class,"d-flex")]/div[contains(@class,"directory-description")]/span', $item);
                $email = node_text($xpath, './div[contains(@class,"d-flex")]/div[contains(@class,"directory-info")]/p[contains(@class,"text-blue")][1]/a', $item);
                $phone = node_text($xpath, './div[contains(@class,"d-flex")]/div[contains(@class,"directory-info")]/p[contains(@class,"text-blue")][2]', $item);
                $address = $fax = '';
            } else {
                $italic = first_node($xpath, './i', $item);
                $parts = $italic ? $xpath->query('./text()', $italic) : [];
                $official = clean_text($parts[0]?->textContent ?? '');
                $position = clean_text($parts[1]?->textContent ?? '');
                $raw = clean_text($item->textContent);
                $address = detail($raw, 'Address:', 'Tel. No:');
                $phone = detail($raw, 'Tel. No:', 'Fax No.:');
                $fax = detail($raw, 'Fax No.:', 'Email:');
                $email = node_text($xpath, './a', $item);
            }
            $email = trim(explode('/', $email)[0]);
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $email = '';
            if ($official === '.' || $position === '.') { $official = ''; $position = ''; }
            $records[] = ['name'=>$name,'type'=>$recordType,'regionName'=>$regionName,'address'=>$address,'phone'=>$phone,'fax'=>$fax,'email'=>$email,'official'=>$official,'position'=>$position];
            $sourceCount[$recordType]++;
        }
    }
}

foreach ($sourceCount as $type => $count) echo "$type: $count source entries\n";
echo 'Total: ' . count($records) . "\n";
if (count($records) !== 361 || $sourceCount !== ['Central Office'=>66,'Regional Office'=>20,'Provincial Office'=>89,'TESDA Technology Institution'=>186]) {
    exit("Source counts differ from the TESDA pages; no changes saved.\n");
}
foreach ($records as $index => $record) {
    foreach (['name'=>200,'phone'=>120,'fax'=>120,'email'=>190,'official'=>190,'position'=>190] as $field => $limit) {
        if (mb_strlen($record[$field]) > $limit) exit('Entry ' . ($index + 1) . " has an oversized $field; no changes saved.\n");
    }
}
echo 'Entries with no official: ' . count(array_filter($records, fn($record) => $record['official'] === '')) . "\n";
if (in_array('--preview', $argv, true)) {
    foreach (array_slice($records, 0, 3) as $record) echo json_encode($record, JSON_UNESCAPED_UNICODE) . "\n";
    foreach (array_slice($records, -3) as $record) echo json_encode($record, JSON_UNESCAPED_UNICODE) . "\n";
}
if (!in_array('--apply', $argv, true)) exit("Dry run complete. Use --apply to save.\n");

$pdo = db();
$pdo->beginTransaction();
try {
    $findOffice = $pdo->prepare('SELECT id FROM offices WHERE name = ? AND office_type = ? LIMIT 1');
    $addOffice = $pdo->prepare('INSERT INTO offices (name,office_type,region_code,region_name,address,telephone,fax,email,sort_order) VALUES (?,?,?,?,?,?,?,?,?)');
    $updateOffice = $pdo->prepare('UPDATE offices SET region_name = COALESCE(NULLIF(?, \'\'), region_name), address = COALESCE(NULLIF(?, \'\'), address), telephone = COALESCE(NULLIF(?, \'\'), telephone), fax = COALESCE(NULLIF(?, \'\'), fax), email = COALESCE(NULLIF(?, \'\'), email) WHERE id = ?');
    $findOfficial = $pdo->prepare('SELECT id FROM officials WHERE office_id = ? AND name = ? LIMIT 1');
    $hasPrimary = $pdo->prepare('SELECT 1 FROM officials WHERE office_id = ? AND is_primary = 1 LIMIT 1');
    $addOfficial = $pdo->prepare('INSERT INTO officials (office_id,name,position,email,is_primary,sort_order) VALUES (?,?,?,?,?,?)');
    $updateOfficial = $pdo->prepare('UPDATE officials SET position = ?, email = COALESCE(NULLIF(?, \'\'), email) WHERE id = ?');
    $officeIds = [];
    $primaryAssigned = [];
    $addedOffices = $addedOfficials = $updatedOffices = $updatedOfficials = 0;
    foreach ($records as $order => $record) {
        $key = $record['type'] . "\0" . $record['name'];
        if (!isset($officeIds[$key])) {
            $findOffice->execute([$record['name'], $record['type']]);
            $id = $findOffice->fetchColumn();
            if ($id) {
                $officeIds[$key] = (int)$id;
                $updatedOffices++;
            } else {
                $addOffice->execute([$record['name'],$record['type'],region_code($record['regionName']) ?: null,$record['regionName'] ?: null,$record['address'] ?: null,$record['phone'] ?: null,$record['fax'] ?: null,$record['email'] ?: null,$order]);
                $officeIds[$key] = (int)$pdo->lastInsertId();
                $addedOffices++;
            }
        }
        $officeId = $officeIds[$key];
        if (!array_key_exists($officeId, $primaryAssigned)) {
            $hasPrimary->execute([$officeId]);
            $primaryAssigned[$officeId] = (bool)$hasPrimary->fetchColumn();
        }
        $updateOffice->execute([$record['regionName'],$record['address'],$record['phone'],$record['fax'],$record['email'],$officeId]);
        if ($record['official'] === '' || $record['position'] === '') continue;
        $findOfficial->execute([$officeId, $record['official']]);
        $officialId = $findOfficial->fetchColumn();
        if ($officialId) {
            $updateOfficial->execute([$record['position'],$record['email'],$officialId]);
            $updatedOfficials++;
        } else {
            $addOfficial->execute([$officeId,$record['official'],$record['position'],$record['email'] ?: null,$primaryAssigned[$officeId] ? 0 : 1,$order]);
            $primaryAssigned[$officeId] = true;
            $addedOfficials++;
        }
    }
    $pdo->commit();
    echo "Saved: $addedOffices new offices, $updatedOffices matched offices, $addedOfficials new officials, $updatedOfficials matched officials.\n";
} catch (Throwable $error) {
    $pdo->rollBack();
    throw $error;
}
