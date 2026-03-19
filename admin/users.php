<?php
session_start(); require_once __DIR__.'/../includes/config.php';
requireLogin(); requireRole('admin');
$conn=getDB(); $msg="";
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $tid=(int)$_POST['target_id']; $act=$_POST['action']??''; $reason=$conn->real_escape_string(trim($_POST['reason']??''));
    if($act==='warn')   {$conn->query("UPDATE users SET status='warning',warn_reason='$reason' WHERE user_id=$tid"); $msg="<div class='alert alert-warning'>✔ User warned.</div>";}
    elseif($act==='ban'){$conn->query("UPDATE users SET status='banned',warn_reason='$reason' WHERE user_id=$tid");  $msg="<div class='alert alert-danger'>✔ User banned.</div>";}
    elseif($act==='unban'){$conn->query("UPDATE users SET status='active',warn_reason=NULL WHERE user_id=$tid");     $msg="<div class='alert alert-success'>✔ User reactivated.</div>";}
}
$q=$conn->real_escape_string(trim($_GET['q']??''));
$sw=$q?"WHERE (username LIKE '%$q%' OR email LIKE '%$q%') AND role!='admin'":'WHERE role!=\'admin\'';
$users=$conn->query("SELECT * FROM users $sw ORDER BY created_at DESC");
$conn->close();
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Users</title><link rel="stylesheet" href="/oil_supply/css/style.css">
<style>
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
</style>
</head><body><div class="app">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
<div class="main">
  <div class="topbar"><div class="topbar-title">User Management</div></div>
  <div class="content">
    <?=$msg?>
    <form method="GET" style="display:flex;gap:.8rem;margin-bottom:1.1rem;">
      <input type="text" name="q" placeholder="Search name or email..." value="<?=htmlspecialchars($q)?>" style="max-width:300px;">
      <button type="submit" class="btn btn-primary btn-sm">Search</button>
      <?php if($q): ?><a href="?" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
    </form>
    <div class="card"><div class="table-wrap"><table>
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Company</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if($users->num_rows===0): ?><tr><td colspan="8"><div class="empty-state">No users found.</div></td></tr>
      <?php else: while($u=$users->fetch_assoc()): ?>
      <tr>
        <td><strong><?=htmlspecialchars($u['username'])?></strong></td>
        <td style="font-size:.8rem;"><?=htmlspecialchars($u['email'])?></td>
        <td style="font-size:.78rem;color:var(--muted);"><?=htmlspecialchars($u['phone_number'])?></td>
        <td><span class="badge badge-confirmed"><?=$u['role']?></span></td>
        <td style="font-size:.78rem;color:var(--muted);"><?=htmlspecialchars($u['company']??'—')?></td>
        <td><?php if($u['status']==='active'): ?><span class="badge badge-delivered">Active</span>
            <?php elseif($u['status']==='warning'): ?><span class="badge badge-pending">Warning</span>
            <?php else: ?><span class="badge badge-cancelled">Banned</span><?php endif; ?>
        </td>
        <td style="font-size:.78rem;color:var(--muted);"><?=date('M d, Y',strtotime($u['created_at']))?></td>
        <td><button class="btn btn-outline btn-sm" onclick="openModal(<?=$u['user_id']?>,'<?=htmlspecialchars($u['username'],ENT_QUOTES)?>','<?=$u['status']?>')">Manage</button></td>
      </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table></div></div>
  </div>
</div></div>

<div class="modal-overlay" id="uModal">
  <div class="modal-box">
    <div class="modal-title" id="mTitle">Manage User</div>
    <form method="POST">
      <input type="hidden" name="target_id" id="mUid">
      <div style="color:var(--muted);font-size:.85rem;margin-bottom:1rem;">User: <strong id="mName"></strong></div>
      <div class="form-group"><label class="lbl">Reason</label><textarea name="reason" rows="2" placeholder="Enter reason..." required></textarea></div>
      <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
        <button type="submit" name="action" value="warn"  class="btn btn-sm" style="color:var(--warning);border:1px solid var(--warning);background:transparent;">⚠ Warning</button>
        <button type="submit" name="action" value="ban"   class="btn btn-danger btn-sm">🚫 Ban</button>
        <button type="submit" name="action" value="unban" class="btn btn-success btn-sm">✔ Reactivate</button>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>
<script>
function openModal(uid,name,status){document.getElementById('mUid').value=uid;document.getElementById('mName').textContent=name+' ('+status+')';document.getElementById('uModal').classList.add('open');}
function closeModal(){document.getElementById('uModal').classList.remove('open');}
document.getElementById('uModal').addEventListener('click',e=>{if(e.target===document.getElementById('uModal'))closeModal();});
</script>
</body></html>
