<?php
session_start(); require_once __DIR__.'/../includes/config.php';
requireLogin();
$uid=$_SESSION['user_id']; $role=$_SESSION['role'];
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['send'])) {
    $conn=getDB(); $oid=(int)$_POST['order_id']; $rid=(int)$_POST['receiver_id'];
    $txt=$conn->real_escape_string(trim($_POST['message']));
    if($txt){$conn->query("INSERT INTO messages(order_id,sender_id,receiver_id,message) VALUES($oid,$uid,$rid,'$txt')");addNotification($rid,"New message on Order #$oid",$oid);}
    $conn->close(); header("Location: ?order_id=$oid"); exit();
}
$conn=getDB(); $oid_sel=(int)($_GET['order_id']??0);
if($role==='customer') $convs=$conn->query("SELECT DISTINCT o.order_id,GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') items FROM orders o JOIN order_items oi ON oi.order_id=o.order_id JOIN products p ON p.product_id=oi.product_id WHERE o.customer_id=$uid GROUP BY o.order_id ORDER BY o.order_id DESC LIMIT 20");
elseif($role==='supplier') $convs=$conn->query("SELECT DISTINCT o.order_id,GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') items FROM orders o JOIN order_items oi ON oi.order_id=o.order_id JOIN products p ON p.product_id=oi.product_id WHERE oi.seller_id=$uid GROUP BY o.order_id ORDER BY o.order_id DESC LIMIT 20");
else $convs=$conn->query("SELECT DISTINCT o.order_id,GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') items FROM orders o JOIN order_items oi ON oi.order_id=o.order_id JOIN products p ON p.product_id=oi.product_id GROUP BY o.order_id ORDER BY o.order_id DESC LIMIT 20");
$chats=[]; while($r=$convs->fetch_assoc()) $chats[]=$r;
$messages=[]; $other=null;
if($oid_sel>0){
    $conn->query("UPDATE messages SET is_read=1 WHERE order_id=$oid_sel AND receiver_id=$uid");
    $ms=$conn->query("SELECT m.*,u.username sname FROM messages m JOIN users u ON u.user_id=m.sender_id WHERE m.order_id=$oid_sel ORDER BY m.created_at ASC");
    while($r=$ms->fetch_assoc()) $messages[]=$r;
    $o=$conn->query("SELECT o.*,u.username cname FROM orders o JOIN users u ON u.user_id=o.customer_id WHERE o.order_id=$oid_sel LIMIT 1")->fetch_assoc();
    if($o){ if($role==='customer'){$s=$conn->query("SELECT DISTINCT u.user_id,u.username FROM order_items oi JOIN users u ON u.user_id=oi.seller_id WHERE oi.order_id=$oid_sel LIMIT 1")->fetch_assoc();$other=$s;}else{$other=['user_id'=>$o['customer_id'],'username'=>$o['cname']];} }
}
$conn->close();
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Messages</title><link rel="stylesheet" href="/oil_supply/css/style.css"></head><body><div class="app">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
<div class="main">
  <div class="topbar"><div class="topbar-title">Messages</div></div>
  <div class="content" style="padding:1rem 2rem;">
    <div class="chat-layout">
      <div class="conv-list">
        <div style="padding:.65rem 1rem;border-bottom:1px solid var(--border);font-family:'Share Tech Mono',monospace;font-size:.6rem;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;">Conversations</div>
        <?php foreach($chats as $c): ?><a href="?order_id=<?=$c['order_id']?>" style="text-decoration:none;"><div class="conv-item <?=$oid_sel===$c['order_id']?'active':''?>"><div style="font-family:'Share Tech Mono',monospace;font-size:.66rem;color:var(--accent);">Order #<?=$c['order_id']?></div><div style="font-size:.82rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars(substr($c['items'],0,30))?></div></div></a><?php endforeach; ?>
        <?php if(empty($chats)): ?><div class="empty-state" style="padding:1.5rem;font-size:.75rem;">No conversations yet.</div><?php endif; ?>
      </div>
      <div class="chat-box">
        <?php if($oid_sel>0&&$other): ?>
          <div style="padding:.75rem 1rem;border-bottom:1px solid var(--border);font-weight:700;font-size:.9rem;">💬 Order #<?=$oid_sel?> — <?=htmlspecialchars($other['username'])?></div>
          <div class="chat-msgs" id="cm">
            <?php if(empty($messages)): ?><div style="color:var(--muted);font-size:.82rem;text-align:center;margin:auto;">No messages yet.</div><?php endif; ?>
            <?php foreach($messages as $m): $mine=$m['sender_id']==$uid; ?>
            <div class="bubble <?=$mine?'mine':'theirs'?>"><div class="bname"><?=$mine?'You':htmlspecialchars($m['sname'])?></div><?=htmlspecialchars($m['message'])?><div class="btime"><?=date('M d, h:i A',strtotime($m['created_at']))?></div></div>
            <?php endforeach; ?>
          </div>
          <form method="POST" class="chat-input"><input type="hidden" name="order_id" value="<?=$oid_sel?>"><input type="hidden" name="receiver_id" value="<?=$other['user_id']?>"><input type="text" name="message" placeholder="Type a message..." autocomplete="off" required><button type="submit" name="send" class="btn btn-primary btn-sm">Send</button></form>
        <?php else: ?><div style="display:flex;align-items:center;justify-content:center;flex:1;color:var(--muted);font-family:'Share Tech Mono',monospace;font-size:.78rem;">Select a conversation</div><?php endif; ?>
      </div>
    </div>
  </div>
</div></div>
<script>const cm=document.getElementById('cm');if(cm)cm.scrollTop=cm.scrollHeight;</script>
</body></html>
