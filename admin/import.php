<?php
require dirname(__DIR__) . '/bootstrap.php';
require_admin();

$columns = ['office_key','office_name','office_type','region_code','region_name','address','telephone','fax','office_email','website','office_sort_order','office_active','official_name','position','official_email','official_primary','official_sort_order'];
$types = ['Central Office','Regional Office','Provincial Office','TESDA Technology Institution'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $upload = $_FILES['import_file'] ?? null;
    if (!$upload || $upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 5 * 1024 * 1024 || !is_uploaded_file($upload['tmp_name'])) {
        $errors[] = 'Choose a CSV file smaller than 5 MB.';
    } else {
        $handle = fopen($upload['tmp_name'], 'rb');
        $header = fgetcsv($handle);
        if ($header) $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        if ($header !== $columns) {
            $errors[] = 'The column headers do not match the template. Download the template and keep its first row unchanged.';
        } else {
            $offices = [];
            $officials = [];
            $line = 1;
            while (($values = fgetcsv($handle)) !== false) {
                $line++;
                if (count($values) === 1 && trim($values[0]) === '') continue;
                if ($line > 1001) { $errors[] = 'The file can contain at most 1,000 data rows.'; break; }
                if (count($values) !== count($columns)) { $errors[] = "Row $line has the wrong number of columns."; continue; }
                $row = array_combine($columns, array_map('trim', $values));
                foreach (['office_key'=>80,'office_name'=>200,'region_code'=>20,'region_name'=>120,'telephone'=>120,'fax'=>120,'office_email'=>190,'website'=>255,'official_name'=>190,'position'=>190,'official_email'=>190] as $field => $max) {
                    if (mb_strlen($row[$field]) > $max) $errors[] = "Row $line: $field exceeds $max characters.";
                }
                if ($row['office_key'] === '' || $row['office_name'] === '' || !in_array($row['office_type'], $types, true)) $errors[] = "Row $line: office_key, office_name, and a valid office_type are required.";
                foreach (['office_email','official_email'] as $field) if ($row[$field] !== '' && !filter_var($row[$field], FILTER_VALIDATE_EMAIL)) $errors[] = "Row $line: $field is not a valid email address.";
                foreach (['office_sort_order','official_sort_order'] as $field) if ($row[$field] !== '' && !preg_match('/^-?\d{1,9}$/', $row[$field])) $errors[] = "Row $line: $field must be an integer.";
                foreach (['office_active','official_primary'] as $field) if ($row[$field] !== '' && !in_array($row[$field], ['0','1'], true)) $errors[] = "Row $line: $field must be 0 or 1.";
                if (($row['official_name'] === '') !== ($row['position'] === '')) $errors[] = "Row $line: official_name and position must both be filled or both blank.";
                if ($row['official_name'] === '' && ($row['official_email'] !== '' || $row['official_primary'] !== '' || $row['official_sort_order'] !== '')) $errors[] = "Row $line: official details need an official_name and position.";
                $key = $row['office_key'];
                $office = array_intersect_key($row, array_flip(array_slice($columns, 0, 12)));
                if (isset($offices[$key]) && $offices[$key] !== $office) $errors[] = "Row $line: office details differ from earlier rows with office_key $key.";
                else $offices[$key] = $office;
                if ($row['official_name'] !== '') $officials[] = ['key'=>$key, 'row'=>$row];
            }
            if (!$offices && !$errors) $errors[] = 'The file contains no data rows.';
            $primaryCounts = [];
            foreach ($officials as $item) if ($item['row']['official_primary'] === '1') $primaryCounts[$item['key']] = ($primaryCounts[$item['key']] ?? 0) + 1;
            foreach ($primaryCounts as $key => $count) if ($count > 1) $errors[] = "Office $key has more than one primary official.";
        }
        fclose($handle);
    }

    if (!$errors) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $existing = $pdo->prepare('SELECT id FROM offices WHERE name = ? AND office_type = ? AND COALESCE(region_code, \'\') = ? LIMIT 1');
            $addOffice = $pdo->prepare('INSERT INTO offices (name,office_type,region_code,region_name,address,telephone,fax,email,website,sort_order,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $addOfficial = $pdo->prepare('INSERT INTO officials (office_id,name,position,email,is_primary,sort_order) VALUES (?,?,?,?,?,?)');
            $ids = [];
            foreach ($offices as $key => $office) {
                $existing->execute([$office['office_name'], $office['office_type'], $office['region_code']]);
                if ($existing->fetchColumn()) throw new RuntimeException("Office $key already exists. Imports only add new offices.");
                $addOffice->execute([$office['office_name'],$office['office_type'],$office['region_code'] ?: null,$office['region_name'] ?: null,$office['address'] ?: null,$office['telephone'] ?: null,$office['fax'] ?: null,$office['office_email'] ?: null,$office['website'] ?: null,$office['office_sort_order'] === '' ? 0 : (int)$office['office_sort_order'],$office['office_active'] === '' ? 1 : (int)$office['office_active']]);
                $ids[$key] = $pdo->lastInsertId();
            }
            foreach ($officials as $item) {
                $row = $item['row'];
                $addOfficial->execute([$ids[$item['key']],$row['official_name'],$row['position'],$row['official_email'] ?: null,$row['official_primary'] === '1' ? 1 : 0,$row['official_sort_order'] === '' ? 0 : (int)$row['official_sort_order']]);
            }
            $pdo->commit();
            flash('success', count($offices) . ' offices and ' . count($officials) . ' officials imported.');
            redirect('admin/index.php');
        } catch (Throwable $error) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $error instanceof RuntimeException && !($error instanceof PDOException) ? $error->getMessage() : 'The import could not be saved. Check the file and try again.';
        }
    }
}

$pageTitle = 'Import directory data | TESDA';
require dirname(__DIR__) . '/partials/header.php';
?>
<section class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
  <a href="<?= url('admin/index.php') ?>" class="font-semibold text-tesda-blue">← Back to management</a>
  <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
    <h1 class="text-2xl font-black text-tesda-navy">Import offices and officials</h1>
    <p class="mt-3 text-slate-600">Download the template, fill it in with Excel or another spreadsheet app, save it as CSV UTF-8, then upload it here. One row represents one official. Repeat the office details and office_key for additional officials in the same office. Leave official columns blank for an office without officials.</p>
    <a href="<?= url('templates/directory-import.csv') ?>" download class="mt-5 inline-block rounded-xl bg-blue-50 px-5 py-3 font-bold text-tesda-blue hover:bg-blue-100">Download CSV template</a>
    <p class="mt-4 text-sm text-slate-500">office_type must be Central Office, Regional Office, Provincial Office, or TESDA Technology Institution. Use 1 or 0 for office_active and official_primary. Blank sort order means 0; blank office_active means 1. Imports add new offices only; an existing office with the same name, type, and region code stops the entire import.</p>
    <?php if ($errors): ?><div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><p class="font-bold">Import not saved:</p><ul class="mt-2 list-disc pl-5"><?php foreach (array_slice($errors, 0, 20) as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php if (count($errors) > 20): ?><p class="mt-2">And <?= count($errors) - 20 ?> more errors.</p><?php endif; ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="mt-7 space-y-5">
      <input type="hidden" name="_token" value="<?= csrf_token() ?>">
      <div><label for="import_file" class="label">Completed CSV file</label><input id="import_file" name="import_file" type="file" accept=".csv,text/csv" required class="field mt-1"></div>
      <button class="rounded-xl bg-tesda-blue px-6 py-3 font-bold text-white hover:bg-tesda-navy">Import data</button>
    </form>
  </div>
</section>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
