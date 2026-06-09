<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>Messages</title>
 <link rel="stylesheet" href="/oil_supply/css/style.css">
</head>
<body>
<div class="app">
 <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
 <div class="main">
 <div class="topbar"><div class="topbar-title">Messages</div></div>
 <div class="content" style="padding:1rem 2rem;">
 <div class="chat-layout">
 <div class="conv-list">
 <div style="padding:.65rem 1rem;border-bottom:1px solid var(--border);font-family:'Share Tech Mono',monospace;font-size:.6rem;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;">Conversations</div>
 <?php foreach($chats as $c): ?>
 <a href="?order_id=<?=$c['order_id']?>" style="text-decoration:none;">
 <div class="conv-item <?=$oid_sel===$c['order_id']?'active':''?>">
 <div style="font-family:'Share Tech Mono',monospace;font-size:.66rem;color:var(--accent);">Order #<?=$c['order_id']?></div>
 <div style="font-size:.82rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars(substr($c['items'],0,30))?></div>
 </div>
 </a>
 <?php endforeach; ?>
 <?php if(empty($chats)): ?><div class="empty-state" style="padding:1.5rem;font-size:.75rem;">No conversations yet.</div><?php endif; ?>
 </div>
 <div class="chat-box">
 <?php if($oid_sel>0&&$other): ?>
 <div style="padding:.75rem 1rem;border-bottom:1px solid var(--border);font-weight:700;font-size:.9rem;"> Order #<?=$oid_sel?> — <?=htmlspecialchars($other['username'])?></div>
 <div class="chat-msgs" id="cm">
 <?php if(empty($messages)): ?><div style="color:var(--muted);font-size:.82rem;text-align:center;margin:auto;">No messages yet.</div><?php endif; ?>
 <?php foreach($messages as $m): $mine=$m['sender_id']==$uid; ?>
 <div class="bubble <?=$mine?'mine':'theirs'?>">
 <div class="bname"><?=$mine?'You':htmlspecialchars($m['sname']??$m['sender'])?></div>
 <?=htmlspecialchars($m['message'])?>
 <div class="btime"><?=date('M d, h:i A',strtotime($m['created_at']))?></div>
 </div>
 <?php endforeach; ?>
 </div>
 <form method="POST" class="chat-input">
 <input type="hidden" name="order_id" value="<?=$oid_sel?>">
 <input type="hidden" name="receiver_id" value="<?=$other['user_id']?>">
 <input type="text" name="message" placeholder="Type a message..." autocomplete="off" required>
 <button type="submit" name="send" class="btn btn-primary btn-sm">Send</button>
 </form>
 <?php else: ?><div style="display:flex;align-items:center;justify-content:center;flex:1;color:var(--muted);font-family:'Share Tech Mono',monospace;font-size:.78rem;">Select a conversation</div><?php endif; ?>
 </div>
 </div>
 </div>
 </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
 const cm = document.getElementById('cm');
 if (cm) cm.scrollTop = cm.scrollHeight;

 const currentUserId = <?=intval($uid)?>;
 const orderId = <?=intval($oid_sel)?>;
 
 function escapeHtml(str) {
 return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
 }

 function loadMessages() {
 if (!orderId) return;
 fetch(`/oil_supply/api.php?action=get_messages&order_id=${orderId}`)
 .then(res => res.json())
 .then(data => {
 if (data.success) {
 const wasAtBottom = cm.scrollHeight - cm.clientHeight <= cm.scrollTop + 30;
 
 if (data.messages.length === 0) {
 cm.innerHTML = `<div style="color:var(--muted);font-size:.82rem;text-align:center;margin:auto;">No messages yet.</div>`;
 return;
 }
 
 let html = '';
 data.messages.forEach(m => {
 const mine = m.sender_id === currentUserId;
 html += `
 <div class="bubble ${mine ? 'mine' : 'theirs'}">
 <div class="bname">${escapeHtml(m.sender_name)}</div>
 ${escapeHtml(m.message)}
 <div class="btime">${m.created_at}</div>
 </div>
 `;
 });
 cm.innerHTML = html;
 
 if (wasAtBottom) {
 cm.scrollTop = cm.scrollHeight;
 }
 }
 })
 .catch(err => console.error("Error fetching messages:", err));
 }

 // Submit message via AJAX
 const chatForm = document.querySelector('.chat-input');
 if (chatForm) {
 chatForm.addEventListener('submit', function(e) {
 e.preventDefault();
 const input = this.querySelector('input[name="message"]');
 const txt = input.value.trim();
 if (!txt) return;

 const formData = new FormData(this);
 fetch('/oil_supply/api.php?action=send_message', {
 method: 'POST',
 body: formData
 })
 .then(res => res.json())
 .then(data => {
 if (data.success) {
 input.value = '';
 loadMessages();
 // Scroll to bottom
 setTimeout(() => { cm.scrollTop = cm.scrollHeight; }, 100);
 } else {
 showToast(data.error || 'Failed to send message', 'danger');
 }
 })
 .catch(err => {
 console.error(err);
 showToast('Failed to send message', 'danger');
 });
 });
 }

 if (orderId > 0) {
 // Poll every 3 seconds
 setInterval(loadMessages, 3000);
 }
});
</script>
</body>
</html>
