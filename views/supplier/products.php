<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>My Products</title>
 <link rel="stylesheet" href="/oil_supply/css/style.css">
 <link rel="stylesheet" href="/oil_supply/css/supplier_products.css">
</head>
<body>
<div class="app">
 <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
 <div class="main">
 <div class="topbar">
 <div class="topbar-title">My Products</div>
 <div class="topbar-actions"><a href="?action=add" class="btn btn-primary btn-sm">+ Add Product</a></div>
 </div>
 <div class="content">
 <?=$msg?>
 <?php if($action==='add'||$action==='edit'): ?>
 <div class="card" style="max-width:580px;margin-bottom:1.3rem;">
 <div class="card-title"><?=$action==='edit'?' Edit':'+ Add'?> Product</div>
 <form method="POST" enctype="multipart/form-data">
 <input type="hidden" name="product_id" value="<?=$ep['product_id']??0?>">
 <div class="form-group"><label class="lbl">Product Name *</label><input type="text" name="name" value="<?=htmlspecialchars($ep['name']??'')?>" required></div>
 <div class="form-group"><label class="lbl">Product Details</label><textarea name="details" rows="3"><?=htmlspecialchars($ep['details']??'')?></textarea></div>
 <div class="form-row">
 <div class="form-group"><label class="lbl">Price ($/unit) *</label><input type="number" name="price" step="0.01" min="0" value="<?=$ep['price']??''?>" required></div>
 <div class="form-group"><label class="lbl">Quantity *</label><input type="number" name="quantity" min="0" value="<?=$ep['quantity']??''?>" required></div>
 </div>
 <div class="form-row">
 <div class="form-group"><label class="lbl">Bulk Threshold (units)</label><input type="number" name="bulk" min="1" value="<?=$ep['bulk_threshold']??50?>"></div>
 <?php if($action==='edit'): ?>
 <div class="form-group"><label class="lbl">Status</label><select name="status"><option value="available" <?=($ep['status']??'')==='available'?'selected':''?>>Available</option><option value="unavailable" <?=($ep['status']??'')==='unavailable'?'selected':''?>>Unavailable</option></select></div>
 <?php endif; ?>
 </div>
 <div class="form-group">
 <label class="lbl">Product Photo (JPG/PNG/WEBP, max 5MB)</label>
 <?php if(!empty($ep['photo'])&&file_exists(UPLOAD_DIR.$ep['photo'])): ?>
 <img src="<?=UPLOAD_URL.htmlspecialchars($ep['photo'])?>" id="previewImg" style="display:block;">
 <p style="font-size:.72rem;color:var(--muted);margin:.2rem 0 .5rem;">Upload a new photo to replace.</p>
 <?php else: ?>
 <img src="" id="previewImg" style="display:none;">
 <?php endif; ?>
 <div class="photo-drop" id="dropArea"><input type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif" onchange="prevPhoto(this)"><div class="pdi"></div><div class="pdt">Click or drag to upload<br><span style="font-size:.68rem;">JPG, PNG, WEBP — max 5MB</span></div></div>
 </div>
 <div style="display:flex;gap:.8rem;margin-top:.3rem;">
 <button type="submit" name="save" class="btn btn-primary"><?=$action==='edit'?'Save Changes':'Add Product'?></button>
 <a href="products.php" class="btn btn-outline">Cancel</a>
 </div>
 </form>
 </div>
 <?php endif; ?>
 <div class="card">
 <div class="card-title"> Product List</div>
 <?php if($prods->num_rows===0): ?><div class="empty-state">No products yet. <a href="?action=add">Add one →</a></div>
 <?php else: ?><div class="table-wrap"><table><thead><tr><th>Photo</th><th>Name</th><th>Price</th><th>Stock</th><th>Sold</th><th>Bulk</th><th>Status</th><th>Actions</th></tr></thead><tbody>
 <?php while($p=$prods->fetch_assoc()): ?>
 <tr><td><?=thumb($p['photo'],42)?></td><td><strong><?=htmlspecialchars($p['name'])?></strong><br><span style="font-size:.72rem;color:var(--muted);"><?=htmlspecialchars(substr($p['details'] ?? '',0,45))?></span></td><td style="color:var(--accent);font-weight:700;">$<?=number_format($p['price'],2)?></td><td><?=$p['quantity']?></td><td><?=$p['sold']?></td><td style="font-size:.78rem;color:var(--muted);"><?=$p['bulk_threshold']?>+</td><td><span class="badge badge-<?=$p['status']?>"><?=$p['status']?></span></td>
 <td style="display:flex;gap:.35rem;"><a href="?action=edit&id=<?=$p['product_id']?>" class="btn btn-outline btn-sm">Edit</a><a href="?del=<?=$p['product_id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Del</a></td></tr>
 <?php endwhile; ?></tbody></table></div><?php endif; ?>
 </div>
 </div>
 </div>
</div>
<script>
function prevPhoto(inp){const pr=document.getElementById('previewImg'),dr=document.getElementById('dropArea');if(inp.files&&inp.files[0]){const r=new FileReader();r.onload=e=>{pr.src=e.target.result;pr.style.display='block';dr.querySelector('.pdi').textContent='';dr.querySelector('.pdt').textContent=inp.files[0].name;};r.readAsDataURL(inp.files[0]);}}
const da=document.getElementById('dropArea');if(da){da.addEventListener('dragover',e=>{e.preventDefault();da.style.borderColor='var(--accent)';});da.addEventListener('dragleave',()=>{da.style.borderColor='';});}
</script>
</body>
</html>
