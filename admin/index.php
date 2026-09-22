<?php
require dirname(__DIR__).'/bootstrap.php'; require_admin();
$q=trim((string)($_GET['q']??''));
$type=(string)($_GET['type']??'');
$region=(string)($_GET['region']??'');
$status=(string)($_GET['status']??'');
$types=['Central Office','Regional Office','Provincial Office','TESDA Technology Institution'];
$where=[];$params=[];
if($q!==''){$where[]='(o.name LIKE :name OR o.address LIKE :address)';$params['name']="%$q%";$params['address']="%$q%";}
if(in_array($type,$types,true)){$where[]='o.office_type=:type';$params['type']=$type;}
if($region!==''){$where[]='o.region_code=:region';$params['region']=$region;}
if($status==='active'||$status==='hidden'){$where[]='o.is_active=:active';$params['active']=$status==='active'?1:0;}
$sql='SELECT o.*,(SELECT COUNT(*) FROM officials f WHERE f.office_id=o.id) official_count FROM offices o';
if($where)$sql.=' WHERE '.implode(' AND ',$where);
$sql.=' ORDER BY o.sort_order,o.name';
$stmt=db()->prepare($sql);$stmt->execute($params);$offices=$stmt->fetchAll();
$regions=db()->query("SELECT DISTINCT region_code,region_name FROM offices WHERE region_code IS NOT NULL AND region_code<>'' ORDER BY region_name")->fetchAll();
$hasFilters=$q!==''||$type!==''||$region!==''||$status!=='';
$pageTitle='Manage directory | TESDA'; require dirname(__DIR__).'/partials/header.php';
?>
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"><div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm font-bold uppercase tracking-wider text-tesda-blue">Administration</p><h1 class="mt-1 text-3xl font-black tracking-tight text-tesda-navy">Manage directory</h1><p class="mt-2 text-slate-500">Signed in as <?= e($_SESSION['admin_name']??'Administrator') ?> · <a href="<?= url('admin/logout.php') ?>" class="font-semibold text-tesda-blue hover:underline">Sign out</a></p></div><div class="flex flex-wrap gap-3"><a href="<?= url('admin/import.php') ?>" class="rounded-xl border border-tesda-blue px-5 py-3 font-bold text-tesda-blue hover:bg-blue-50">Import data</a><button type="button" id="open-add-office" class="rounded-xl bg-tesda-red px-5 py-3 font-bold text-white hover:bg-red-700">Add office</button></div></div>
<form method="get" class="mt-8 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_200px_200px_140px_auto]">
  <label class="sr-only" for="q">Search offices</label><input id="q" name="q" value="<?= e($q) ?>" class="field" placeholder="Search office or address">
  <label class="sr-only" for="type">Office type</label><select id="type" name="type" class="field"><option value="">All office types</option><?php foreach($types as $item): ?><option value="<?= e($item) ?>" <?= $type===$item?'selected':'' ?>><?= e($item) ?></option><?php endforeach; ?></select>
  <label class="sr-only" for="region">Region</label><select id="region" name="region" class="field"><option value="">All regions</option><?php foreach($regions as $item): ?><option value="<?= e($item['region_code']) ?>" <?= $region===$item['region_code']?'selected':'' ?>><?= e($item['region_name']) ?></option><?php endforeach; ?></select>
  <label class="sr-only" for="status">Visibility</label><select id="status" name="status" class="field"><option value="">All statuses</option><option value="active" <?= $status==='active'?'selected':'' ?>>Active</option><option value="hidden" <?= $status==='hidden'?'selected':'' ?>>Hidden</option></select>
  <button class="rounded-xl bg-tesda-blue px-5 py-3 font-bold text-white hover:bg-tesda-navy">Filter</button>
</form>
<div class="mt-3 flex items-center justify-between text-sm text-slate-500"><p><?= count($offices) ?> office<?= count($offices)===1?'':'s' ?> found</p><?php if($hasFilters): ?><a href="<?= url('admin/index.php') ?>" class="font-semibold text-tesda-blue hover:underline">Clear filters</a><?php endif; ?></div>
<div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white"><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-4">Office</th><th class="px-5 py-4">Type</th><th class="px-5 py-4">Region</th><th class="px-5 py-4">Officials</th><th class="px-5 py-4">Status</th><th class="px-5 py-4 text-right">Actions</th></tr></thead><tbody class="divide-y divide-slate-100"><?php foreach($offices as $o): ?><tr><td class="px-5 py-4"><div class="font-bold text-slate-900"><?= e($o['name']) ?></div><div class="mt-1 max-w-xl truncate text-slate-500"><?= e($o['address']) ?></div></td><td class="px-5 py-4 text-slate-600"><?= e($o['office_type']) ?></td><td class="px-5 py-4 text-slate-600"><?= e($o['region_name']) ?></td><td class="px-5 py-4 text-slate-600"><?= (int)$o['official_count'] ?></td><td class="px-5 py-4"><span class="rounded-full px-2 py-1 text-xs font-bold <?= $o['is_active']?'bg-emerald-50 text-emerald-700':'bg-slate-100 text-slate-500' ?>"><?= $o['is_active']?'Active':'Hidden' ?></span></td><td class="px-5 py-4 text-right"><a class="font-bold text-tesda-blue hover:underline" href="<?= url('admin/office-form.php?id='.(int)$o['id']) ?>">Edit</a><span class="mx-2 text-slate-300">|</span><a class="font-bold text-tesda-blue hover:underline" href="<?= url('admin/officials.php?office_id='.(int)$o['id']) ?>">Officials</a></td></tr><?php endforeach; ?><?php if(!$offices): ?><tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">No offices match these filters.</td></tr><?php endif; ?></tbody></table></div></div></section>
<?php
$id = 0;
$office = ['name'=>'', 'office_type'=>'Regional Office', 'region_code'=>'', 'region_name'=>'', 'address'=>'', 'telephone'=>'', 'fax'=>'', 'email'=>'', 'website'=>'', 'sort_order'=>0, 'is_active'=>1];
?>
<dialog id="add-office-dialog" aria-labelledby="add-office-title" class="w-[calc(100%-2rem)] max-w-3xl rounded-2xl border border-slate-200 p-0 shadow-2xl">
  <div class="flex max-h-[calc(100vh-2rem)] flex-col">
    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
      <h2 id="add-office-title" class="text-2xl font-black text-tesda-navy">Add office</h2>
      <button type="button" id="close-add-office" aria-label="Close add office" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900">✕</button>
    </div>
    <form id="add-office-form" method="post" action="<?= url('admin/office-form.php') ?>" class="grid gap-5 overflow-y-auto p-6 sm:grid-cols-2">
      <?php require __DIR__.'/office-fields.php'; ?>
      <div class="sm:col-span-2 flex justify-end gap-3 border-t border-slate-200 pt-5">
        <button type="button" id="cancel-add-office" class="rounded-xl border border-slate-300 px-5 py-3 font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
        <button class="rounded-xl bg-tesda-blue px-6 py-3 font-bold text-white hover:bg-tesda-navy">Save office</button>
      </div>
    </form>
  </div>
</dialog>
<script>
(() => {
  const dialog = document.getElementById('add-office-dialog');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let closeTimer;
  const closeModal = () => {
    if (!dialog.open || dialog.classList.contains('is-closing')) return;
    if (reduceMotion.matches) {
      dialog.close();
      return;
    }
    dialog.classList.remove('is-opening');
    dialog.classList.add('is-closing');
    closeTimer = window.setTimeout(() => dialog.close(), 240);
  };
  document.getElementById('open-add-office').addEventListener('click', () => {
    dialog.classList.add('is-opening');
    dialog.showModal();
  });
  document.getElementById('close-add-office').addEventListener('click', closeModal);
  document.getElementById('cancel-add-office').addEventListener('click', closeModal);
  dialog.addEventListener('cancel', event => {
    event.preventDefault();
    closeModal();
  });
  dialog.addEventListener('close', () => {
    window.clearTimeout(closeTimer);
    dialog.classList.remove('is-opening', 'is-closing');
  });
  dialog.addEventListener('click', event => {
    if (event.target === dialog) closeModal();
  });
})();
</script>
<?php require dirname(__DIR__).'/partials/footer.php'; ?>
