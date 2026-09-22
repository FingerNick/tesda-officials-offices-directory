<?php
require dirname(__DIR__) . '/bootstrap.php';
if(is_admin()) redirect('admin/index.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf(); $stmt=db()->prepare('SELECT * FROM administrators WHERE email=:email LIMIT 1'); $stmt->execute(['email'=>strtolower(trim($_POST['email']??''))]); $admin=$stmt->fetch();
 if($admin && password_verify($_POST['password']??'', $admin['password_hash'])){ session_regenerate_id(true); $_SESSION['admin_id']=$admin['id']; $_SESSION['admin_name']=$admin['name']; redirect('admin/index.php'); }
 $error='Invalid email address or password.';
}
$pageTitle='Admin sign in | TESDA Directory'; require dirname(__DIR__).'/partials/header.php';
?>
<section class="mx-auto max-w-md px-4 py-16 sm:px-6"><div class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm"><h1 class="text-2xl font-black text-tesda-navy">Directory administration</h1><p class="mt-2 text-sm text-slate-500">Sign in to maintain office and official records.</p><?php if($error): ?><div class="mt-5 rounded-lg bg-red-50 p-3 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?><form method="post" class="mt-6 space-y-5"><input type="hidden" name="_token" value="<?= csrf_token() ?>"><div><label class="label" for="email">Email address</label><input class="field" id="email" name="email" type="email" required autocomplete="username"></div><div><label class="label" for="password">Password</label><input class="field" id="password" name="password" type="password" required autocomplete="current-password"></div><button class="w-full rounded-xl bg-tesda-blue px-4 py-3 font-bold text-white hover:bg-tesda-navy">Sign in</button></form></div></section>
<?php require dirname(__DIR__).'/partials/footer.php'; ?>

