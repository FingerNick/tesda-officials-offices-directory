<?php
require __DIR__ . '/bootstrap.php';

$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? '';
$region = $_GET['region'] ?? '';
$officeId = filter_var($_GET['office_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
$allowedTypes = ['Central Office','Regional Office','Provincial Office','TESDA Technology Institution'];

$where = ['o.is_active = 1']; $params = [];
if ($q !== '') {
    $where[] = '(o.name LIKE :q1 OR o.address LIKE :q2 OR EXISTS (SELECT 1 FROM officials search_official WHERE search_official.office_id=o.id AND (search_official.name LIKE :q3 OR search_official.position LIKE :q4)))';
    $params += ['q1'=>"%{$q}%", 'q2'=>"%{$q}%", 'q3'=>"%{$q}%", 'q4'=>"%{$q}%"];
}
if (in_array($type, $allowedTypes, true)) { $where[] = 'o.office_type = :type'; $params['type'] = $type; }
if ($type === 'Central Office' && $officeId) { $where[] = 'o.id = :office_id'; $params['office_id'] = $officeId; }
elseif ($type !== 'Central Office' && $region !== '') { $where[] = 'o.region_code = :region'; $params['region'] = $region; }

$sql = 'SELECT o.*, f.id official_id, f.name official_name, f.position, f.email official_email, f.photo_path, a.id assistant_id, a.name assistant_name, a.position assistant_position, a.photo_path assistant_photo_path FROM offices o LEFT JOIN officials f ON f.office_id=o.id AND f.is_primary=1 LEFT JOIN officials a ON a.office_id=o.id AND LOWER(a.position) LIKE "%assistant%executive director%" WHERE ' . implode(' AND ', $where) . ' ORDER BY o.sort_order, o.name';
$stmt = db()->prepare($sql); $stmt->execute($params); $offices = $stmt->fetchAll();
$regions = db()->query("SELECT DISTINCT region_code, region_name FROM offices WHERE region_code IS NOT NULL AND region_code <> '' ORDER BY sort_order, region_name")->fetchAll();
$centralOffices = db()->query("SELECT id,name FROM offices WHERE office_type='Central Office' AND is_active=1 ORDER BY sort_order,name")->fetchAll();
$counts = db()->query("SELECT office_type, COUNT(*) total FROM offices WHERE is_active=1 GROUP BY office_type")->fetchAll(PDO::FETCH_KEY_PAIR);
$pageTitle = 'TESDA Officials Directory';
require __DIR__ . '/partials/header.php';
?>
<section class="bg-tesda-navy text-white">
  <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
    <div class="hero-intro max-w-3xl">
      <p class="mb-2 text-sm font-bold uppercase tracking-[.18em] text-blue-200">Unofficial Directory</p>
      <h1 class="text-3xl font-black tracking-tight sm:text-4xl">Find a TESDA office or official</h1>
      <p class="mt-3 max-w-2xl text-base leading-7 text-blue-100">Search contact details for the Central Office, Regional and Provincial Offices, and TESDA Technology Institutions nationwide.</p>
      <p class="mt-4 inline-flex rounded-full border border-blue-200/40 bg-white/10 px-3 py-1 text-sm font-semibold text-blue-100">Directory data as of September 2026</p>
    </div>
    <form class="search-panel mt-8 grid gap-3 rounded-2xl bg-white p-3 shadow-xl sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_210px_minmax(220px,280px)_auto]" method="get">
      <label class="sr-only" for="q">Search</label><input id="q" name="q" value="<?= e($q) ?>" class="field border-0 bg-slate-50" placeholder="Search office, official, position…">
      <label class="sr-only" for="type">Office type</label><select id="type" name="type" class="field border-0 bg-slate-50" onchange="this.form.submit()"><option value="">All office types</option><?php foreach($allowedTypes as $item): ?><option value="<?= e($item) ?>" <?= $type===$item?'selected':'' ?>><?= e($item) ?></option><?php endforeach; ?></select>
      <?php if($type === 'Central Office'): ?>
        <label class="sr-only" for="office_id">Central office</label><select id="office_id" name="office_id" class="field border-0 bg-slate-50"><option value="">All central offices</option><?php foreach($centralOffices as $item): ?><option value="<?= (int)$item['id'] ?>" <?= $officeId===(int)$item['id']?'selected':'' ?>><?= e($item['name']) ?></option><?php endforeach; ?></select>
      <?php else: ?>
        <label class="sr-only" for="region">Region</label><select id="region" name="region" class="field border-0 bg-slate-50"><option value="">All regions</option><?php foreach($regions as $item): ?><option value="<?= e($item['region_code']) ?>" <?= $region===$item['region_code']?'selected':'' ?>><?= e($item['region_name']) ?></option><?php endforeach; ?></select>
      <?php endif; ?>
      <button class="rounded-xl bg-tesda-red px-6 py-3 font-bold text-white hover:bg-red-700">Search</button>
    </form>
  </div>
</section>
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
  <div class="mb-7 grid grid-cols-2 gap-3 lg:grid-cols-4">
    <?php foreach($allowedTypes as $item): ?><a href="?type=<?= urlencode($item) ?>" class="filter-tile rounded-xl border border-slate-200 bg-white p-4 hover:border-blue-300"><div class="text-2xl font-black text-tesda-blue"><?= (int)($counts[$item] ?? 0) ?></div><div class="mt-1 text-sm font-semibold text-slate-600"><?= e($item) ?><?= $item==='Central Office'?'':'s' ?></div></a><?php endforeach; ?>
  </div>
  <div class="mb-5 flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-2xl font-extrabold tracking-tight text-tesda-navy"><?= $q||$type||$region||$officeId?'Search results':'All offices' ?></h2><p class="mt-1 text-sm text-slate-500"><?= count($offices) ?> office<?= count($offices)===1?'':'s' ?> found</p></div><?php if($q||$type||$region||$officeId): ?><a href="<?= url() ?>" class="text-sm font-bold text-tesda-blue hover:underline">Clear filters</a><?php endif; ?></div>
  <?php if(!$offices): ?><div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center"><h3 class="font-bold text-slate-800">No matching offices found</h3><p class="mt-2 text-slate-500">Try a broader keyword or clear one of the filters.</p></div><?php else: ?>
  <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
    <?php foreach($offices as $office): ?>
      <article class="directory-card flex flex-col rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex items-start justify-between gap-4"><div><span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-tesda-blue"><?= e($office['office_type']) ?></span><?php if($office['region_name']): ?><p class="mt-2 text-xs font-semibold text-slate-500"><?= e($office['region_name']) ?></p><?php endif; ?><h3 class="mt-3 text-xl font-extrabold leading-snug text-tesda-navy"><a href="<?= url('office.php?id='.(int)$office['id']) ?>" class="hover:text-tesda-blue"><?= e($office['name']) ?></a></h3></div><span class="mt-1 h-3 w-3 shrink-0 rounded-full bg-tesda-red" aria-hidden="true"></span></div>
        <?php if($office['official_name']): ?><div class="mt-5 flex items-center gap-3 border-l-4 border-tesda-gold pl-4"><?php if($office['photo_path']): ?><img src="<?= e(photo_url($office['photo_path'])) ?>" alt="Portrait of <?= e($office['official_name']) ?>" loading="lazy" decoding="async" class="h-14 w-14 shrink-0 rounded-full object-cover"><?php endif; ?><div><p class="font-bold uppercase tracking-wide text-slate-900"><a href="<?= url('official.php?id='.(int)$office['official_id']) ?>" class="hover:text-tesda-blue hover:underline"><?= e($office['official_name']) ?></a></p><p class="mt-1 text-sm italic text-slate-500"><?= e($office['position']) ?></p></div></div><?php endif; ?>
        <?php if($office['assistant_name']): ?><div class="mt-4 flex items-center gap-3 border-l-4 border-blue-200 pl-4"><?php if($office['assistant_photo_path']): ?><img src="<?= e(photo_url($office['assistant_photo_path'])) ?>" alt="Portrait of <?= e($office['assistant_name']) ?>" loading="lazy" decoding="async" class="h-14 w-14 shrink-0 rounded-full object-cover"><?php endif; ?><div><p class="font-bold uppercase tracking-wide text-slate-900"><a href="<?= url('official.php?id='.(int)$office['assistant_id']) ?>" class="hover:text-tesda-blue hover:underline"><?= e($office['assistant_name']) ?></a></p><p class="mt-1 text-sm italic text-slate-500"><?= e($office['assistant_position']) ?></p></div></div><?php endif; ?>
        <div class="mt-5 space-y-2 text-sm leading-6 text-slate-600"><p><?= e($office['address']) ?></p><?php if($office['telephone']): ?><p><span class="font-bold text-slate-700">Tel:</span> <?= e($office['telephone']) ?></p><?php endif; ?><?php if($office['email']): ?><p><a class="font-semibold text-tesda-blue hover:underline" href="mailto:<?= e($office['email']) ?>"><?= e($office['email']) ?></a></p><?php endif; ?></div>
        <a href="<?= url('office.php?id='.(int)$office['id']) ?>" class="mt-6 inline-flex items-center text-sm font-bold text-tesda-blue hover:underline">View complete details <span aria-hidden="true" class="ml-1">→</span></a>
      </article>
    <?php endforeach; ?>
  </div><?php endif; ?>
</section>
<script src="<?= url('assets/scroll-reveal.js') ?>" defer></script>
<?php require __DIR__ . '/partials/footer.php'; ?>
