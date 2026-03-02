<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12">
				<div class="panel_s">
					<div class="panel-body">
		<h3 class="no-margin"><?php echo _l('topics_overview'); ?></h3>
		<p class="text-muted" style="margin-top:8px;">
			<?php echo _l('topics_overview_subtitle'); ?>
		</p>

		<hr />

		<h4><?php echo _l('topics_overview_scope'); ?></h4>
		<ul>
			<li><?php echo _l('topics_overview_scope_lifecycle'); ?></li>
			<li><?php echo _l('topics_overview_scope_automation'); ?></li>
			<li><?php echo _l('topics_overview_scope_realtime'); ?></li>
			<li><?php echo _l('topics_overview_scope_ui'); ?></li>
		</ul>

		<h4><?php echo _l('topics_overview_features'); ?></h4>
		<ul>
			<li><?php echo _l('topics_overview_features_topics'); ?></li>
			<li><?php echo _l('topics_overview_features_types_states'); ?></li>
			<li><?php echo _l('topics_overview_features_buttons'); ?></li>
			<li><?php echo _l('topics_overview_features_online'); ?></li>
			<li><?php echo _l('topics_overview_features_notifications'); ?></li>
			<li><?php echo _l('topics_overview_features_processors'); ?></li>
			<li><?php echo _l('topics_overview_features_dashboard'); ?></li>
			<li><?php echo _l('topics_overview_features_assets'); ?></li>
		</ul>

		<h4><?php echo _l('topics_overview_data_model'); ?></h4>
		<div class="alert alert-warning" style="background:#fff8d5; border:1px solid #ffe89a; color:#574a00;">
			<strong>Notes</strong>: The ER diagram below shows per-table purposes and live total record counts.
		</div>
		<div class="overflow-auto" style="max-width:100%;">
			<pre class="mermaid" style="white-space:pre;">
erDiagram
  tbltopic_action_types ||--o{ tbltopic_action_states : has
  tbltopic_master ||--o{ tbltopics : has
  tbltopic_target ||--o{ tbltopics : referenced-by
  tbltopic_action_types ||--o{ tbltopics : referenced-by
  tbltopic_action_states ||--o{ tbltopics : referenced-by
  tbltopic_master ||--o{ tbltopic_automation_logs : has
  tbltopic_master ||--o{ tbltopic_external_data : has
  tbltopic_controllers ||--o{ tbltopic_master : manages
  tbltopic_controllers ||--o{ tbltopic_controller : links
  tbltopic_master ||--o{ tbltopic_controller : links
  tbltopic_controllers ||--o{ tbltopic_sync_logs : logs
  tbltopics ||--o{ tbltopic_editor_drafts : drafts

  tbltopic_action_types {
    int id PK
    varchar name
    varchar action_type_code UK
    int parent_id FK
    int position
    datetime datecreated
    datetime dateupdated
  }
  tbltopic_action_states {
    int id PK
    varchar name
    varchar action_state_code UK
    varchar action_type_code FK
    varchar color
    int position
    tinyint valid_data
    datetime datecreated
    datetime dateupdated
  }
  tbltopic_master {
    int id PK
    varchar topicid UK
    varchar topictitle
    tinyint status
    int controller_id FK
    datetime datecreated
    datetime dateupdated
  }
  tbltopics {
    int id PK
    varchar topicid FK
    varchar topictitle
    int position
    longtext data
    text log
    varchar action_type_code FK
    varchar action_state_code FK
    int target_id FK
    tinyint status
    varchar automation_id
    longtext editor_settings
    int active_draft_id
    datetime datecreated
    datetime dateupdated
  }
  tbltopic_target {
    int id PK
    int target_id
    varchar title
    varchar target_type
    tinyint status
    datetime datecreated
    datetime dateupdated
  }
  tbltopic_automation_logs {
    int id PK
    varchar topic_id FK
    varchar automation_id
    varchar workflow_id
    varchar status
    text response_data
    datetime datecreated
    datetime dateupdated
  }
  tbltopic_external_data {
    int id PK
    int topic_master_id FK
    varchar rel_type
    varchar rel_id
    text rel_data
    longtext rel_data_raw
    datetime datecreated
    datetime dateupdated
  }
  tbltopic_controllers {
    int id PK
    tinyint status
    varchar site
    enum platform
    text logo_url
    text slogan
    longtext writing_style
    varchar project_id
    text expanded_categories
    varchar raw_data
    varchar page_mapping
    datetime datecreated
    datetime dateupdated
    longtext login_config
    datetime last_login
    tinyint login_status
    datetime tags_last_sync
    varchar tags_sync_session_id
    text tags_state
    datetime categories_last_sync
    text categories_state
    text selected_categories
  }
  tbltopic_controller {
    int id PK
    int controller_id FK
    int topic_id FK
    int staff_id FK
    datetime datecreated
  }

  tbltopic_sync_logs {
    int id PK
    int controller_id FK
    varchar session_id
    varchar rel_type
    varchar status
    text summary_data
    int processed_count
    longtext log_data
    datetime start_time
    datetime end_time
    datetime datecreated
    datetime dateupdated
  }

  tbltopic_editor_drafts {
    int id PK
    int topic_id FK
    varchar draft_title
    longtext draft_content
    longtext draft_sections
    longtext draft_metadata
    varchar status
    int version
    int created_by
    int last_edited_by
    datetime created_at
    datetime updated_at
  }
			</pre>
		</div>

		<h4 style="margin-top:24px;">Inspector (Visualize a Topic Master)</h4>
		<div class="panel_s" style="padding:12px;">
			<div class="row" style="margin-bottom:8px;">
				<div class="col-md-7">
					<label>Search Topic (by title) — Perfex style fast search</label>
					<input type="text" class="form-control" id="inspect_search" placeholder="Type to search topics..." autocomplete="off" />
					<div id="inspect_search_results" class="dropdown-menu" style="display:none; max-height:260px; overflow:auto; width:100%; border:1px solid #eee; padding:0;">
						<!-- results injected here -->
					</div>
				</div>
				<div class="col-md-5" style="padding-top:22px;">
					<span class="text-muted" id="inspect_selected_info"></span>
				</div>
			</div>
			<div class="row" style="margin-bottom:8px;">
				<div class="col-md-3">
					<label>topic_master_id</label>
					<input type="number" class="form-control" id="inspect_topic_master_id" placeholder="e.g. 123" />
				</div>
				<div class="col-md-4">
					<label>topic_id (string)</label>
					<input type="text" class="form-control" id="inspect_topic_id" placeholder="e.g. TOPIC-ABCD1234" />
				</div>
				<div class="col-md-5" style="padding-top:22px;">
					<button class="btn btn-info" id="btn_inspect">Inspect</button>
					<button class="btn btn-default" id="btn_fullscreen" type="button" style="margin-left:6px;">Fullscreen</button>
					<span id="inspect_status" class="text-muted" style="margin-left:8px;"></span>
				</div>
			</div>
			<div class="row">
				<div class="col-md-7">
					<div id="inspect_graph" class="mermaid mermaid-inspect" style="min-height:320px; border:1px solid #eee; border-radius:4px; padding:8px; overflow:auto;">
						graph TD; A[Inspector]-->B[Enter an ID];
					</div>
				</div>
				<div class="col-md-5">
					<label>Raw Data</label>
					<pre id="inspect_json" style="max-height:320px; overflow:auto; background:#fafafa; border:1px solid #eee; padding:8px;"></pre>
				</div>
			</div>
		</div>

		<h4><?php echo _l('topics_overview_integrations'); ?></h4>
		<ul>
			<li><?php echo _l('topics_overview_integrations_n8n'); ?></li>
			<li><?php echo _l('topics_overview_integrations_pusher'); ?></li>
			<li><?php echo _l('topics_overview_integrations_wp_social'); ?></li>
		</ul>

		<h4><?php echo _l('topics_overview_permissions_settings'); ?></h4>
		<ul>
			<li><?php echo _l('topics_overview_permissions'); ?></li>
			<li><?php echo _l('topics_overview_settings'); ?></li>
		</ul>

		<h4><?php echo _l('topics_overview_navigation'); ?></h4>
		<ul>
			<li><?php echo _l('topics_overview_nav_pages'); ?></li>
			<li><?php echo _l('topics_overview_nav_assets'); ?></li>
		</ul>

		<div class="alert alert-info" style="margin-top:16px;">
			<?php echo _l('topics_overview_notes'); ?>
		</div>
 					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php init_tail(); ?>

<style>
#inspect_graph.fs {
	position: fixed;
	left: 0; top: 0; right: 0; bottom: 0;
	width: 100vw; height: 100vh;
	z-index: 10000;
	background: #fff;
	border-radius: 0;
	border: 1px solid #ddd;
	padding: 12px;
	overflow: auto;
}
#inspect_graph svg { max-width: none !important; height: auto; }
#inspect_fs_close.fs-close{position:fixed;top:12px;right:14px;background:rgba(0,0,0,0.75);color:#fff;border:none;border-radius:4px;padding:6px 10px;z-index:10001;cursor:pointer}
</style>
<script>
(function(){
	// Lazy-load Mermaid if available globally in the admin theme; otherwise fallback
	function ensureMermaid(cb){
		if (window.mermaid) { cb(); return; }
		var s = document.createElement('script');
		s.src = 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js';
		s.onload = cb;
		document.body.appendChild(s);
	}
	ensureMermaid(function(){
		try {
			window.mermaid.initialize({
				startOnLoad: true,
				theme: 'default',
				securityLevel: 'loose',
				// Do not force-fit to container width; allow overflow scrolling
				flowchart: { htmlLabels: true, useMaxWidth: false }
			});
			window.mermaid.init();
		} catch(e) { console && console.warn && console.warn(e); }
	});

	// --- Inspector ---
	function sanitizeId(id){
		return String(id).replace(/[^A-Za-z0-9_]/g,'_');
	}

	// store raw data per rendered node id for tooltips
	var nodeMeta = { map: {} };

	function buildMermaid(nodes, links){
		let out = ['graph LR'];
		const idMap = {};
		const MAX_NODES = 120;
		const MAX_LINKS = 200;
		const safeNodes = Array.isArray(nodes) ? nodes.slice(0, MAX_NODES) : [];
		const safeLinks = Array.isArray(links) ? links.slice(0, MAX_LINKS) : [];

		function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

		// Build nodes and record actual ids
		nodeMeta.map = {};
		safeNodes.forEach(function(n){
			const originalId = n && (n.id || n.label) ? (n.id || n.label) : Math.random().toString(36).slice(2);
			const sid = sanitizeId(originalId);
			idMap[originalId] = sid;
			nodeMeta.map[sid] = n && n.data ? n.data : {};
			const rawLabel = (n && n.label) ? String(n.label) : String(originalId);
			const singleLine = rawLabel.replace(/\s+/g,' ').trim();
			const trimmed = singleLine.length > 80 ? singleLine.slice(0,77) + '...' : singleLine;
			const label = '<span class="m-node" data-nodeid="'+sid+'">'+escHtml(trimmed)+'</span> <span class="m-exp" data-nodeid="'+sid+'" style="color:#0b7; border:1px solid #0b7; border-radius:3px; padding:0 4px; font-size:11px;">+ expand</span>';
			out.push(sid+"[\""+label+"\"]");
		});
		// Build links using recorded ids
		safeLinks.forEach(function(l){
			if (!l) return;
			const sKey = l.source; const tKey = l.target;
			if (!sKey || !tKey) return;
			const s = idMap[sKey] || sanitizeId(sKey);
			const t = idMap[tKey] || sanitizeId(tKey);
			const lbl = l.type ? `|${String(l.type).slice(0,24)}|` : '';
			out.push(`${s}-->${lbl}${t}`);
		});
		return out.join('\n');
	}
	function setGraph(def){
		var el = document.getElementById('inspect_graph');
		if (!window.mermaid) { el.textContent = def; return; }
		// Prefer modern render API for dynamic content; fallback to init
		try {
			if (typeof window.mermaid.render === 'function') {
				var uid = 'mmd_'+Date.now();
				var p = window.mermaid.render(uid, def);
				if (p && typeof p.then === 'function') {
					p.then(function(res){ el.innerHTML = res.svg; bindNodeTooltips(); }).catch(function(err){ console.warn(err); el.textContent = def; });
				} else {
					// Older API with callback signature
					window.mermaid.render(uid, def, function(svg){ el.innerHTML = svg; bindNodeTooltips(); });
				}
			} else {
				el.textContent = def;
				window.mermaid.init(undefined, el);
			}
		} catch(e) { console && console.warn && console.warn(e); el.textContent = def; }
	}

	function bindNodeTooltips(){
		var container = document.getElementById('inspect_graph');
		var svgel = container.querySelector('svg');
		if (!svgel) return;
		var tip = document.getElementById('overview-tip');
		if (!tip){
			tip = document.createElement('div');
			tip.id = 'overview-tip';
			tip.style.position = 'fixed';
			tip.style.background = 'rgba(0,0,0,0.85)';
			tip.style.color = '#fff';
			tip.style.padding = '8px 10px';
			tip.style.borderRadius = '6px';
			tip.style.fontSize = '12px';
			tip.style.maxWidth = '360px';
			tip.style.zIndex = '10005';
			tip.style.display = 'none';
			document.body.appendChild(tip);
		}
		var outsideHandler = null;
		function hideTip(){
			if (outsideHandler) { document.removeEventListener('click', outsideHandler, true); outsideHandler = null; }
			tip.style.display='none';
		}
		function showTip(html, x, y){
			tip.innerHTML = html;
			tip.style.left = (x+12)+'px';
			tip.style.top  = (y+12)+'px';
			tip.style.display='block';
			if (outsideHandler) { document.removeEventListener('click', outsideHandler, true); }
			outsideHandler = function(ev){ if (!tip.contains(ev.target)) { hideTip(); } };
			setTimeout(function(){ document.addEventListener('click', outsideHandler, true); }, 0);
		}
		tip.addEventListener('click', function(e){ e.stopPropagation(); }, true);
		container.querySelectorAll('.m-node').forEach(function(span){
			span.style.cursor = 'pointer';
			span.addEventListener('click', function(ev){
				var sid = this.getAttribute('data-nodeid');
				var d = nodeMeta.map[sid] || {};
				var rows = Object.keys(d).slice(0,12).map(function(k){
					var v = d[k]; if (v===null||v===undefined) v=''; v = String(v); if (v.length>120) v=v.slice(0,117)+'...';
					return '<div><strong>'+k+':</strong> '+v.replace(/</g,'&lt;')+'</div>';
				}).join('');
				if (!rows) rows = '<em>No details</em>';
				showTip(rows, ev.clientX, ev.clientY);
				ev.stopPropagation();
			});
		});

		// Expand button: fetch additional related nodes from server without hardcoding types
		container.querySelectorAll('.m-exp').forEach(function(btn){
			btn.style.cursor = 'pointer';
			btn.addEventListener('click', function(ev){
				var sid = this.getAttribute('data-nodeid');
				try{
					var d = nodeMeta.map[sid] || {};
					var meta = (function(){
						// Find node meta by scanning last nodes using original id mapping
						var found = null;
						(window.__lastNodes||[]).forEach(function(n){
							var key = sanitizeId((n.id || n.label));
							if (key === sid && n.meta) { found = n.meta; }
						});
						return found || {};
					})();
					if (!meta || !meta.table || !meta.keys){ return; }
					var qs = '?table='+encodeURIComponent(meta.table)+'&keys='+encodeURIComponent(btoa(JSON.stringify(meta.keys)));
					fetch('<?php echo admin_url('topics/overview/expand'); ?>'+qs, {credentials:'same-origin'})
						.then(r=>r.json())
						.then(function(payload){
							if (!payload || !payload.nodes) return;
							var newNodes = (payload.nodes||[]).map(function(n){ return { id: n.id, label: n.label, data: n.data, meta: n.meta }; });
							var newLinks = (payload.nodes||[]).map(function(n){ return { source: sid, target: sanitizeId(n.id||n.label), type: 'rel' }; });
							window.__lastNodes = (window.__lastNodes||[]).concat(newNodes);
							window.__lastLinks = (window.__lastLinks||[]).concat(newLinks);
							var def = buildMermaid(window.__lastNodes, window.__lastLinks);
							setGraph(def);
						})
						.catch(function(){ /* noop */ });
				}catch(e){}
				ev.stopPropagation();
			});
		});
		// Outside-click handler is attached per tooltip show in showTip()
	}
	function pretty(obj){ try{ return JSON.stringify(obj, null, 2);}catch(e){return String(obj);} }

	// --- Search (Perfex-like quick search) ---
	function debounce(fn, wait){ let t; return function(){ clearTimeout(t); const args=arguments,ctx=this; t=setTimeout(()=>fn.apply(ctx,args), wait); }; }
	const searchBox = document.getElementById('inspect_search');
	const resultsBox = document.getElementById('inspect_search_results');
	const selectedInfo = document.getElementById('inspect_selected_info');

	function hideResults(){ resultsBox.style.display='none'; resultsBox.innerHTML=''; }
	function showResults(items){
		if (!items || !items.length){ hideResults(); return; }
		resultsBox.innerHTML = items.map(function(it){
			const safeTitle = (it.topictitle||it.topicid||'').replace(/</g,'&lt;');
			return '<a href="#" class="dropdown-item" data-topicid="'+(it.topicid||'')+'" style="display:block; padding:6px 10px; border-bottom:1px solid #f2f2f2;">'+safeTitle+' <span class="text-muted">('+it.topicid+')</span></a>';
		}).join('');
		resultsBox.style.display='block';
		Array.prototype.forEach.call(resultsBox.querySelectorAll('a.dropdown-item'), function(a){
			a.addEventListener('click', function(e){ e.preventDefault(); const topicid = this.getAttribute('data-topicid'); onSelectTopic(topicid, this.textContent); });
		});
	}

	function onSelectTopic(topicid, label){
		document.getElementById('inspect_topic_id').value = topicid;
		selectedInfo.textContent = 'Selected: '+label;
		hideResults();
		// Resolve topic_master_id
		fetch('<?php echo admin_url('topics/get_topic_master_id'); ?>?topic_id='+encodeURIComponent(topicid), {credentials:'same-origin'})
			.then(r=>r.json())
			.then(d=>{ if (d && d.success && d.topic_master_id){ document.getElementById('inspect_topic_master_id').value = d.topic_master_id; } })
			.catch(()=>{});
	}

	searchBox.addEventListener('input', debounce(function(){
		var q = this.value.trim();
		if (q.length < 2){ hideResults(); return; }
		fetch('<?php echo admin_url('topics/search'); ?>?q='+encodeURIComponent(q), {credentials:'same-origin'})
			.then(r=>r.json())
			.then(items=> showResults(items))
			.catch(()=> hideResults());
	}, 250));

	document.getElementById('btn_inspect').addEventListener('click', function(){
		var tmId = document.getElementById('inspect_topic_master_id').value.trim();
		var tId  = document.getElementById('inspect_topic_id').value.trim();
		var status = document.getElementById('inspect_status');
		status.textContent = 'Loading...';
		var url = '<?php echo admin_url('topics/overview/inspect'); ?>';
		var qs = [];
		if (tmId) qs.push('topic_master_id='+encodeURIComponent(tmId));
		if (tId)  qs.push('topic_id='+encodeURIComponent(tId));
		if (qs.length) url += '?' + qs.join('&');
		fetch(url, { credentials:'same-origin' })
			.then(r=>r.json())
			.then(data=>{
				status.textContent = (data && data.success) ? ((data.stats && data.stats.truncated) ? 'OK (truncated)' : 'OK') : 'No data';
				document.getElementById('inspect_json').textContent = pretty(data.raw||data);
				window.__lastNodes = (data && data.nodes) || [];
				window.__lastLinks = (data && data.links) || [];
				var g = buildMermaid(window.__lastNodes, window.__lastLinks);
				setGraph(g);
			})
			.catch(err=>{ status.textContent = 'Error'; console.warn(err); });
	});

	// Fullscreen toggle for graph container
	var fsBtn = document.getElementById('btn_fullscreen');
	if (fsBtn){
		fsBtn.addEventListener('click', function(){
			var el = document.getElementById('inspect_graph');
			if (!el) return;
			if (el.classList.contains('fs')){
				exitFs();
			} else {
				enterFs();
			}
		});
	}

	function enterFs(){
		var el = document.getElementById('inspect_graph');
		if (!el) return;
		el.classList.add('fs');
		var btn = document.getElementById('inspect_fs_close');
		if (!btn){
			btn = document.createElement('button');
			btn.id = 'inspect_fs_close';
			btn.className = 'fs-close';
			btn.type = 'button';
			btn.textContent = 'Exit Fullscreen';
			document.body.appendChild(btn);
			btn.addEventListener('click', exitFs);
		}
		btn.style.display = 'block';
	}
	function exitFs(){
		var el = document.getElementById('inspect_graph');
		if (el) el.classList.remove('fs');
		var btn = document.getElementById('inspect_fs_close');
		if (btn) btn.style.display = 'none';
	}
	// ESC to exit fullscreen
	document.addEventListener('keydown', function(e){
		if (e.key === 'Escape'){
			var el = document.getElementById('inspect_graph');
			if (el && el.classList.contains('fs')){ exitFs(); }
		}
	});
})();
</script>

</body>
</html>
