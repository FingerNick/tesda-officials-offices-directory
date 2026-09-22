<?php
require dirname(__DIR__).'/bootstrap.php'; require_admin();
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
$office=['name'=>'','office_type'=>'Regional Office','region_code'=>'','region_name'=>'','address'=>'','telephone'=>'','fax'=>'','email'=>'','website'=>'','sort_order'=>0,'is_active'=>1];
if($id){$stmt=db()->prepare('SELECT * FROM offices WHERE id=:id');$stmt->execute(['id'=>$id]);$office=$stmt->fetch()?:$office;}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $payload=[];
 foreach(['name','office_type','region_code','region_name','address','telephone','fax','email','website','sort_order'] as $key){$payload[$key]=trim((string)($_POST[$key]??''));}
 $payload['is_active']=isset($_POST['is_active'])?1:0;
 if($id){$sql='UPDATE offices SET name=:name,office_type=:office_type,region_code=:region_code,region_name=:region_name,address=:address,telephone=:telephone,fax=:fax,email=:email,website=:website,sort_order=:sort_order,is_active=:is_active WHERE id=:id';$payload['id']=$id;}else{$sql='INSERT INTO offices (name,office_type,region_code,region_name,address,telephone,fax,email,website,sort_order,is_active) VALUES (:name,:office_type,:region_code,:region_name,:address,:telephone,:fax,:email,:website,:sort_order,:is_active)';}
 db()->prepare($sql)->execute($payload); flash('success',$id?'Office updated.':'Office added.'); redirect('admin/index.php');
}
$pageTitle=($id?'Edit':'Add').' office | TESDA'; require dirname(__DIR__).'/partials/header.php';
?>
<section class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
  <a href="<?= url('admin/index.php') ?>" class="text-sm font-bold text-tesda-blue">← Back to management</a>
  <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">
    <h1 class="text-2xl font-black text-tesda-navy"><?= $id ? 'Edit office' : 'Add office' ?></h1>
    <form method="post" class="mt-7 grid gap-5 sm:grid-cols-2">
      <?php require __DIR__.'/office-fields.php'; ?>
      <div class="sm:col-span-2 flex justify-end"><button class="rounded-xl bg-tesda-blue px-6 py-3 font-bold text-white hover:bg-tesda-navy">Save office</button></div>
    </form>
  </div>
</section>
<?php require dirname(__DIR__).'/partials/footer.php'; ?>
