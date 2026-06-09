<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>Track Order</title>
 <link rel="stylesheet" href="/oil_supply/css/style.css">
 <link rel="stylesheet" href="/oil_supply/css/track.css">
 <!-- Leaflet Maps CSS and JS -->
 <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
 <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
<div class="app">
 <?php require_once __DIR__.'/../../includes/sidebar.php'; ?>
 <div class="main">
 <div class="topbar">
 <div class="topbar-title">Track Order<?=$sel?' #'.$sel['order_id']:''?></div>
 <div class="topbar-actions">
 <a href="/oil_supply/<?=$role?>/messages.php<?=$sel?'?order_id='.$sel['order_id']:''?>" class="btn btn-outline btn-sm"> Chat</a>
 </div>
 </div>
 <div class="content" style="padding:1rem 2rem;">
 <div class="track-wrap">
 <div class="order-list">
 <div style="padding:.7rem 1rem;border-bottom:1px solid var(--border);font-family:'Share Tech Mono',monospace;font-size:.62rem;color:var(--muted);letter-spacing:.15em;text-transform:uppercase;">Active Orders</div>
 <?php if(empty($list)): ?><div class="empty-state">No active orders.</div>
 <?php else: foreach($list as $l): ?>
 <a href="?order_id=<?=$l['order_id']?>" style="text-decoration:none;">
 <div class="ol-item <?=$sel&&$sel['order_id']==$l['order_id']?'active':''?>">
 <div style="font-family:'Share Tech Mono',monospace;font-size:.68rem;color:var(--accent);">Order #<?=$l['order_id']?></div>
 <div style="font-size:.82rem;margin:.18rem 0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars(substr($l['address'],0,32))?></div>
 <?=statusBadge($l['status'])?>
 </div>
 </a>
 <?php endforeach; endif; ?>
 </div>
 <div class="map-frame">
 <div id="leaflet-map" style="width:100%; height:100%; min-height: 400px; background: #0c0d12;"></div>
 </div>
 </div>
 <?php if($sel): ?>
 <div class="card" style="margin-top:1rem;">
 <div style="display:flex;gap:2rem;flex-wrap:wrap;align-items:center;">
 <div><span style="color:var(--muted);font-size:.75rem;">Order</span><br><strong>#<?=$sel['order_id']?></strong></div>
 <div><span style="color:var(--muted);font-size:.75rem;">Status</span><br><?=statusBadge($sel['status'])?></div>
 <div><span style="color:var(--muted);font-size:.75rem;">Delivery Date</span><br><strong><?=$sel['delivery_date']?:'TBD'?></strong></div>
 <div><span style="color:var(--muted);font-size:.75rem;">Slot</span><br><strong><?=$sel['delivery_slot']?:'—'?></strong></div>
 <div style="flex:1;"><span style="color:var(--muted);font-size:.75rem;">Address</span><br><strong><?=htmlspecialchars($sel['address'])?></strong></div>
 </div>
 </div>
 <?php endif; ?>
 </div>
 </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
 // Coordinates calculation
 const defaultLat = 23.8103;
 const defaultLng = 90.4125;
 
 let mapCenter = [defaultLat, defaultLng];
 let showRoute = false;
 let depotCoords = [defaultLat, defaultLng];
 let destCoords = [defaultLat, defaultLng];

 <?php if($sel): ?>
 showRoute = true;
 const oid = <?=intval($sel['order_id'])?>;
 // Generate coordinates deterministically from order ID
 const offsetLat = ((oid % 10) - 5) * 0.004;
 const offsetLng = ((oid % 7) - 3) * 0.005;
 
 depotCoords = [defaultLat - 0.01, defaultLng - 0.01];
 destCoords = [defaultLat + offsetLat, defaultLng + offsetLng];
 mapCenter = [(depotCoords[0] + destCoords[0]) / 2, (depotCoords[1] + destCoords[1]) / 2];
 <?php endif; ?>

 // Initialize Leaflet map
 const map = L.map('leaflet-map', {
 zoomControl: true,
 attributionControl: false
 }).setView(mapCenter, <?=$sel ? '14' : '13'?>);

 // Dark theme tile layer
 L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
 maxZoom: 20
 }).addTo(map);

 if (showRoute) {
 // Depot marker
 const depotMarker = L.circleMarker(depotCoords, {
 radius: 8,
 fillColor: "#3b82f6",
 color: "#fff",
 weight: 2,
 opacity: 1,
 fillOpacity: 0.8
 }).addTo(map).bindPopup("<b> Distribution Center</b>");

 // Destination marker
 const destMarker = L.circleMarker(destCoords, {
 radius: 8,
 fillColor: "#f0820a",
 color: "#fff",
 weight: 2,
 opacity: 1,
 fillOpacity: 0.8
 }).addTo(map).bindPopup("<b> Delivery Destination</b><br><?=htmlspecialchars($sel['address'] ?? '')?>");

 // Draw line representing route
 const routeLine = L.polyline([depotCoords, destCoords], {
 color: 'rgba(240, 130, 10, 0.4)',
 weight: 4,
 dashArray: '5, 10'
 }).addTo(map);

 // Simulated truck movement
 const truckMarker = L.circleMarker(depotCoords, {
 radius: 6,
 fillColor: "#10b981",
 color: "#fff",
 weight: 2,
 opacity: 1,
 fillOpacity: 1
 }).addTo(map).bindPopup("<b> Delivery Truck (Live Position)</b>");

 let progress = 0;
 setInterval(() => {
 progress = (progress + 0.01) % 1.0;
 const lat = depotCoords[0] + (destCoords[0] - depotCoords[0]) * progress;
 const lng = depotCoords[1] + (destCoords[1] - depotCoords[1]) * progress;
 truckMarker.setLatLng([lat, lng]);
 }, 100);
 } else {
 // Default center marker
 L.marker(mapCenter).addTo(map).bindPopup("<b> Central Depot</b><br>Select an order to track delivery.");
 }
});
</script>
</body>
</html>
