<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Disputes – Admin</title>
    <link rel="stylesheet" href="/oil_supply/css/style.css">
</head>
<body>
<div class="app">
    <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar"><div class="topbar-title">Dispute Management</div></div>
        <div class="content">
            <?=$msg?>
            <div class="card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Order</th>
                                <th>Reporter</th>
                                <th>Issue Type</th>
                                <th>Description</th>
                                <th>Evidence</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!$disputes || $disputes->num_rows===0): ?>
                                <tr><td colspan="9"><div class="empty-state">No disputes filed.</div></td></tr>
                            <?php else: while($d=$disputes->fetch_assoc()): ?>
                                <tr>
                                    <td><?=$d['dispute_id']?></td>
                                    <td><a href="/oil_supply/customer/order_detail.php?id=<?=$d['order_id']?>">#<?=$d['order_id']?></a></td>
                                    <td><?=htmlspecialchars($d['customer_name']??$d['reporter'])?></td>
                                    <td><span class="badge badge-open"><?=$d['issue_type']?></span></td>
                                    <td style="max-width:180px;font-size:.8rem;"><?=htmlspecialchars(substr($d['description'],0,70))?></td>
                                    <td><?php if($d['evidence']&&file_exists(UPLOAD_DIR.$d['evidence'])): ?><a href="<?=UPLOAD_URL.htmlspecialchars($d['evidence'])?>" target="_blank" class="btn btn-outline btn-sm">View</a><?php else: ?><span style="color:var(--muted);font-size:.75rem;">None</span><?php endif; ?></td>
                                    <td><span class="badge badge-<?=$d['status']?>"><?=$d['status']?></span></td>
                                    <td style="color:var(--muted);font-size:.78rem;"><?=date('M d, Y',strtotime($d['created_at']))?></td>
                                    <td>
                                        <?php if($d['status']==='open'): ?>
                                            <form method="POST"><input type="hidden" name="dispute_id" value="<?=$d['dispute_id']?>"><button type="submit" name="status" value="resolved" class="btn btn-success btn-sm">Resolve</button></form>
                                        <?php else: ?>
                                            <span style="color:var(--muted);font-size:.75rem;">Done</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
