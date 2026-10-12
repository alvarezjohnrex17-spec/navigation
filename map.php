<?php
session_start();
$departments = require __DIR__ . '/campus_departments.php';
$student = $_SESSION['current_student'] ?? null;
if (!$student) {
    // Allow guest but encourage login
    $student = ['name'=>'Guest','course'=>'-','year'=>'-'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Campus Map - DEBESMSCAT</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="map-layout">
  <!-- Top Bar -->
  <div class="topbar">
    <div>Hi, <strong><?= htmlspecialchars($student['name']) ?></strong> - <?= htmlspecialchars($student['course']) ?> <?= htmlspecialchars($student['year']) ?></div>
    <div class="top-actions">
      <span class="badge green">✓ No Location Needed - Works Offline</span>
      <a href="index.php" class="btn btn-light small">Exit</a>
    </div>
  </div>

  <div class="map-grid">
    <!-- Left: Quick Access -->
    <div class="panel">
      <h3>Quick Access Menu</h3>
      <input type="text" id="searchBox" placeholder="Search building..." onkeyup="filterBuildings()">
      <div id="quickList"></div>
      <hr>
      <h4>How to use:</h4>
      <ol class="howto">
        <li>Pomili ng building sa baba</li>
        <li>Pindot ang Start Navigation</li>
        <li>Sundan arrow na nakadikit sa daan</li>
        <li>No need open location!</li>
      </ol>
    </div>

    <!-- Center: Map -->
    <div class="panel map-panel">
      <div class="map-container" id="mapContainer">
        <div class="map-zoom-layer" id="mapZoomLayer">
          <img src="assets/campus_map.jpg" id="mapImg" alt="DEBESMSCAT Map">
          <svg id="roadSvg" viewBox="0 0 100 100" preserveAspectRatio="none">
            <path id="routePath" fill="none" stroke="#2563EB" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="2 1.5" opacity="0.9"/>
            <path id="roadBase" fill="none" stroke="#9CA3AF" stroke-width="0.6" opacity="0.25"/>
          </svg>
          <div id="pins"></div>
          <div id="navArrow" class="nav-arrow" style="display:none;">
            <div class="arrow-icon">➤</div>
            <div class="arrow-pulse"></div>
          </div>
        </div>
        <div class="map-zoom-controls" role="group" aria-label="Map zoom controls">
          <button type="button" class="map-zoom-button" onclick="zoomMap(0.2)" aria-label="Zoom in">+</button>
          <button type="button" class="map-zoom-button" onclick="zoomMap(-0.2)" aria-label="Zoom out">−</button>
          <button type="button" class="map-zoom-reset" onclick="resetMapZoom()">Reset</button>
        </div>
      </div>
      <div class="map-controls">
        <button onclick="prevTurn()" class="btn btn-light small">← Prev</button>
        <button onclick="nextTurn()" class="btn btn-light small">Next →</button>
        <span class="map-zoom-level" id="mapZoomLevel">100%</span>
      </div>
      <div class="turn-info" id="turnInfo">Select destination to start</div>
    </div>

    <!-- Right: Info / Navigation -->
    <div class="panel">
      <div id="destSelector">
        <h3>Select Destination</h3>
        <select id="destSelect">
          <option value="">-- Select department and room --</option>
          <?php foreach ($departments as $department): ?>
            <?php if (!$department['rooms']): ?>
              <option value="<?= htmlspecialchars($department['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($department['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php else: ?>
              <optgroup label="<?= htmlspecialchars($department['name'], ENT_QUOTES, 'UTF-8') ?>">
                <?php foreach ($department['rooms'] as $room): ?>
                  <option value="<?= htmlspecialchars($department['id'] . '-room-' . $room['number'], ENT_QUOTES, 'UTF-8') ?>">Room <?= (int) $room['number'] ?></option>
                <?php endforeach; ?>
              </optgroup>
            <?php endif; ?>
          <?php endforeach; ?>
        </select>
        <button onclick="startNav()" class="btn btn-green full">Start Navigation →</button>
        <div class="note">✓ Arrow follows road line (gray roads sa mapa)</div>
      </div>

      <div id="navPanel" style="display:none;">
        <h3>Turn-by-Turn</h3>
        <div id="turnList"></div>
        <div class="distance"><span id="distLeft">0m</span> left • ETA <span id="eta">0 min</span></div>
        <button onclick="stopNav()" class="btn btn-red full">Stop Navigation</button>
      </div>

      <hr>
      <div id="buildingInfo"></div>

      <details class="optional-gps">
        <summary>Optional: Use Real GPS if on campus</summary>
        <p>Click only if nasa campus ka. Kung hindi, gumagana pa rin guide.</p>
        <button onclick="useRealGPS()" class="btn btn-light small full">Enable Real GPS</button>
        <small id="gpsStatus"></small>
      </details>
    </div>
  </div>
</div>

<script>
// --- DATA (same as React version) ---
const DEPARTMENTS = <?= json_encode($departments, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const ROAD_NODES = {
  TOP:{x:45,y:8}, EDU_J:{x:45,y:21}, MID_J:{x:45,y:30}, LIB_J:{x:47,y:65}, MAIN_F:{x:55,y:73}, SOUTH:{x:56,y:90}
};
const EDGES = [["TOP","EDU_J"],["EDU_J","MID_J"],["MID_J","LIB_J"],["LIB_J","MAIN_F"],["MAIN_F","SOUTH"]];
const BUILDINGS = DEPARTMENTS.flatMap(department => department.rooms.map(room => {
  const id = `${department.id}-room-${room.number}`;
  ROAD_NODES[id] = room.pos;
  EDGES.push([department.connection, id]);
  return {
    id,
    name: `${department.name} - Room ${room.number}`,
    shortName: `${department.shortName} ${room.number}`,
    node: id,
    pos: room.pos,
    color: department.color,
    desc: `${department.desc} - Room ${room.number}`,
    hours: department.hours,
    contact: department.contact
  };
}));
DEPARTMENTS.filter(department => department.rooms.length === 0).forEach(department => {
  ROAD_NODES[department.node] = department.pos;
  EDGES.push([department.connection, department.node]);
  BUILDINGS.push({
    id: department.id,
    name: department.name,
    shortName: department.shortName,
    node: department.node,
    pos: department.pos,
    color: department.color,
    desc: department.desc,
    hours: department.hours,
    contact: department.contact
  });
});

let currentPath = [];
let progress = 0;
let currentTurnIndex = 0;
let mapZoom = 1;

function applyMapZoom(){
  document.getElementById('mapZoomLayer').style.transform = `scale(${mapZoom})`;
  document.getElementById('mapZoomLevel').textContent = `${Math.round(mapZoom * 100)}%`;
}
function zoomMap(change){
  mapZoom = Math.min(2.4, Math.max(1, Math.round((mapZoom + change) * 10) / 10));
  applyMapZoom();
}
function resetMapZoom(){
  mapZoom = 1;
  applyMapZoom();
}

// Build road base path
function drawRoadBase(){
  const base = document.getElementById('roadBase');
  let d = "";
  EDGES.forEach(([a,b])=>{
    const A = ROAD_NODES[a], B = ROAD_NODES[b];
    d += `M ${A.x} ${A.y} L ${B.x} ${B.y} `;
  });
  base.setAttribute('d', d);
}
drawRoadBase();

// Pins
function renderPins(){
  const pinsDiv = document.getElementById('pins');
  const quickList = document.getElementById('quickList');
  pinsDiv.innerHTML = ""; quickList.innerHTML="";
  BUILDINGS.forEach(b=>{
    // Map pin
    const pin = document.createElement('div');
    pin.className = 'pin';
    pin.style.left = b.pos.x + '%';
    pin.style.top = b.pos.y + '%';
    pin.style.borderColor = b.color;
    pin.title = b.name;
    pin.innerHTML = `<div class="pin-dot" style="background:${b.color}"></div><span>${b.shortName}</span>`;
    pin.onclick = ()=>selectBuilding(b.id);
    pinsDiv.appendChild(pin);
    // Quick list
    const q = document.createElement('div');
    q.className = 'quick-item';
    q.innerHTML = `<strong>${b.name}</strong><button onclick="quickGo('${b.id}')" class="btn btn-green small">Go</button>`;
    quickList.appendChild(q);
  });
}
renderPins();

function selectBuilding(id){
  const b = BUILDINGS.find(x=>x.id===id);
  if(!b) return;
  document.getElementById('buildingInfo').innerHTML = `
    <h3>${b.name}</h3>
    <p>${b.desc}</p>
    <p><small>⏰ ${b.hours}${b.contact ? `<br>📧 ${b.contact}` : ''}</small></p>
    <button onclick="quickGo('${b.id}')" class="btn btn-green full">Navigate Here →</button>
  `;
  document.getElementById('destSelect').value = id;
}

function quickGo(id){
  document.getElementById('destSelect').value = id;
  startNav();
}

function filterBuildings(){
  const q = document.getElementById('searchBox').value.toLowerCase();
  document.querySelectorAll('.quick-item').forEach(el=>{
    el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// Pathfinding Dijkstra
function findPath(start,end){
  const dist={}, prev={}, visited=new Set();
  Object.keys(ROAD_NODES).forEach(n=>{dist[n]=Infinity; prev[n]=null;});
  dist[start]=0;
  while(visited.size < Object.keys(ROAD_NODES).length){
    let u=null, min=Infinity;
    for(let n in dist) if(!visited.has(n) && dist[n]<min){min=dist[n]; u=n;}
    if(!u) break; if(u===end) break;
    visited.add(u);
    EDGES.forEach(([a,b])=>{
      let v=null; if(a===u) v=b; if(b===u) v=a;
      if(!v || visited.has(v)) return;
      const dx=ROAD_NODES[u].x-ROAD_NODES[v].x, dy=ROAD_NODES[u].y-ROAD_NODES[v].y;
      const w=Math.hypot(dx,dy);
      if(dist[u]+w < dist[v]){dist[v]=dist[u]+w; prev[v]=u;}
    });
  }
  let path=[]; let cur=end;
  while(cur){path.unshift(cur); cur=prev[cur]; if(path.length>20) break;}
  if(path[0]!==start) return [start,end];
  return path;
}
function pathLength(path){
  let len=0;
  for(let i=0;i<path.length-1;i++){ const a=ROAD_NODES[path[i]], b=ROAD_NODES[path[i+1]]; len+=Math.hypot(a.x-b.x, a.y-b.y); }
  return len||1;
}
function pointAt(path, t){
  const total=pathLength(path); const target=total*t; let acc=0;
  for(let i=0;i<path.length-1;i++){
    const a=ROAD_NODES[path[i]], b=ROAD_NODES[path[i+1]]; const seg=Math.hypot(a.x-b.x,a.y-b.y);
    if(acc+seg>=target){ const r=(target-acc)/seg; return {x:a.x+(b.x-a.x)*r, y:a.y+(b.y-a.y)*r, idx:i, angle:Math.atan2(b.y-a.y,b.x-a.x)}; }
    acc+=seg;
  }
  const last=ROAD_NODES[path[path.length-1]]; const prev=ROAD_NODES[path[path.length-2]];
  return {x:last.x, y:last.y, idx:path.length-2, angle:prev?Math.atan2(last.y-prev.y, last.x-prev.x):0};
}

function startNav(){
  const destId = document.getElementById('destSelect').value;
  if(!destId) {alert('Pili ka muna building'); return;}
  const dest = BUILDINGS.find(b=>b.id===destId);
  if(!dest) {alert('Hindi mahanap ang napiling room. Pumili ulit.'); return;}
  const startNode = "TOP"; // default start at main entrance, could be made selectable
  currentPath = findPath(startNode, dest.node);
  progress = 0;
  currentTurnIndex = 0;
  document.getElementById('navPanel').style.display = 'block';
  document.getElementById('destSelector').style.display = 'none';
  drawRoute();
  updateArrow(0);
  buildTurnList();
  document.getElementById('navArrow').style.display = 'block';
}

function drawRoute(){
  const route = document.getElementById('routePath');
  let d="";
  currentPath.forEach((node,i)=>{
    const p=ROAD_NODES[node];
    if(i===0) d+=`M ${p.x} ${p.y} `;
    else d+=`L ${p.x} ${p.y} `;
  });
  route.setAttribute('d', d);
}

function updateArrow(t){
  const pt = pointAt(currentPath, t);
  const arrow = document.getElementById('navArrow');
  arrow.style.left = pt.x + '%';
  arrow.style.top = pt.y + '%';
  arrow.style.transform = `translate(-50%,-50%) rotate(${pt.angle}rad)`;
  // update turn highlight
  if(pt.idx !== currentTurnIndex){ currentTurnIndex = pt.idx; highlightTurn(); }
  const remain = (1-t)*pathLength(currentPath);
  document.getElementById('distLeft').textContent = Math.round(remain*5) + 'm'; // scale factor
  document.getElementById('eta').textContent = Math.max(1, Math.round(remain*2)) + ' min';
  document.getElementById('turnInfo').textContent = `On road - ${Math.round(t*100)}% - Following gray road line`;
}

function buildTurnList(){
  const list = document.getElementById('turnList');
  list.innerHTML = "";
  currentPath.forEach((node,i)=>{
    if(i===currentPath.length-1) return;
    const a=ROAD_NODES[node], b=ROAD_NODES[currentPath[i+1]];
    const angle=Math.atan2(b.y-a.y,b.x-a.x)*180/Math.PI;
    let dir="Go straight →";
    if(angle>30 && angle<150) dir="Turn right ↘";
    else if(angle<-30 && angle>-150) dir="Turn left ↙";
    const div=document.createElement('div');
    div.className='turn-item'; div.id='turn-'+i;
    div.innerHTML=`<span class="turn-num">${i+1}</span><span>${dir} to ${currentPath[i+1]}</span>`;
    list.appendChild(div);
  });
}
function highlightTurn(){
  document.querySelectorAll('.turn-item').forEach(el=>el.classList.remove('active'));
  const el=document.getElementById('turn-'+currentTurnIndex);
  if(el) el.classList.add('active');
}

function prevTurn(){
  if(currentPath.length===0) return;
  progress = Math.max(0, progress-0.15);
  updateArrow(progress);
}
function nextTurn(){
  if(currentPath.length===0) return;
  progress = Math.min(1, progress+0.15);
  updateArrow(progress);
}
function stopNav(){
  document.getElementById('navPanel').style.display='none';
  document.getElementById('destSelector').style.display='block';
  document.getElementById('routePath').setAttribute('d','');
  document.getElementById('navArrow').style.display='none';
  progress=0;
}

function useRealGPS(){
  if(!navigator.geolocation){ document.getElementById('gpsStatus').textContent='Geolocation not supported'; return; }
  document.getElementById('gpsStatus').textContent='Requesting GPS...';
  navigator.geolocation.watchPosition(pos=>{
    document.getElementById('gpsStatus').textContent=`GPS: ${pos.coords.latitude.toFixed(5)}, ${pos.coords.longitude.toFixed(5)} - Snapped to road`;
    // For demo, just move progress slightly when GPS moves
    if(currentPath.length>0){
      progress = Math.min(1, progress+0.02);
      updateArrow(progress);
    }
  }, err=>{ document.getElementById('gpsStatus').textContent='GPS denied - okay lang, gumagana pa rin offline'; }, {enableHighAccuracy:true});
}

// Tap road to move
document.getElementById('mapContainer').addEventListener('click', (e)=>{
  if(e.target.closest('button, .pin, .map-zoom-controls')) return;
  if(currentPath.length===0) return;
  const rect=e.currentTarget.getBoundingClientRect();
  const y=0.5+(e.clientY-rect.top-rect.height/2)/(rect.height*mapZoom);
  progress = Math.max(0, Math.min(1, y));
  updateArrow(progress);
});
</script>
</body>
</html>
