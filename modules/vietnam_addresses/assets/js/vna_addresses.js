(function () {
  function onReady(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  var LOCAL_BASE = (typeof window !== 'undefined' && window.VNA_DATA_BASE_URL) ? window.VNA_DATA_BASE_URL : '';

  const LOCAL_CITIES = 'cities.json';
  const LOCAL_DISTRICTS = 'districts.json';
  const LOCAL_WARDS = 'wards.json';
  const LOCAL_ZIP = 'dvhcvn.json';

  async function fetchJSONLocal(file) {
    const url = LOCAL_BASE + file;
    console.log('[VN-Addr] loading', file, 'from', url);
    const res = await fetch(url, { cache: 'force-cache' });
    if (!res.ok) throw new Error('Failed to load ' + url + ' (' + res.status + ')');
    const data = await res.json();
    console.log('[VN-Addr] loaded', file);
    return data;
  }

  function getCountrySelects(scope) {
    const root = scope || document;
    return Array.from(
      root.querySelectorAll(
        'select[name="country"], select[name="billing_country"], select[name="shipping_country"]'
      )
    );
  }

  function findInputs(root) {
    const scope = root || document;
    return {
      state: scope.querySelector('input[name="state"]'),
      city: scope.querySelector('input[name="city"]'),
      billing_state: scope.querySelector('input[name="billing_state"]'),
      billing_city: scope.querySelector('input[name="billing_city"]'),
      shipping_state: scope.querySelector('input[name="shipping_state"]'),
      shipping_city: scope.querySelector('input[name="shipping_city"]'),
      zip: scope.querySelector('input[name="zip"]'),
      billing_zip: scope.querySelector('input[name="billing_zip"]'),
      shipping_zip: scope.querySelector('input[name="shipping_zip"]'),
    };
  }

  function createSelect(placeholder) {
    const sel = document.createElement('select');
    sel.className = 'form-control selectpicker';
    sel.setAttribute('data-live-search', 'true');
    sel.setAttribute('data-none-selected-text', placeholder);
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = placeholder;
    sel.appendChild(opt);
    return sel;
  }

  function refreshPicker(el) {
    if (typeof $ !== 'undefined' && $(el).selectpicker) {
      if (!$(el).parent().hasClass('bootstrap-select')) {
        $(el).selectpicker({ liveSearch: true });
      } else {
        $(el).selectpicker('refresh');
      }
    }
  }

  function replaceInputWithSelect(input, select) {
    if (!input || !input.parentNode) return;
    select.name = input.name;
    select.id = input.id || input.name;
    select.setAttribute('data-existing', input.value || '');
    input.parentNode.replaceChild(select, input);
    refreshPicker(select);
  }

  function setSelectValue(select, value) {
    if (!select) return;
    select.value = value || '';
    refreshPicker(select);
  }

  function setZipValue(input, value) {
    if (!input) return;
    console.log('[VN-Addr] set ZIP', { name: input.name, id: input.id, value: value });
    input.value = value || '';
    const event = new Event('input', { bubbles: true });
    input.dispatchEvent(event);
  }

  function getSelectedText(select) {
    const opt = select.options[select.selectedIndex];
    return (opt && (opt.text || '')).trim();
  }

  function isVietnamSelected(select) {
    const txt = getSelectedText(select).toLowerCase();
    if (txt.includes('viet') && txt.includes('nam')) return true;
    const val = (select.value || '').toString();
    if (val === '243' || val === '230') return true;
    const btn = document.querySelector('button[data-id="' + select.id + '"]');
    const title = btn ? (btn.getAttribute('title') || '').toLowerCase() : '';
    return title.includes('viet') && title.includes('nam');
  }

  let cache = { cities: null, districts: null, wards: null, zipTree: null };
  let dataPromise = null;
  let zipPromise = null;

  function ensureDataLoading() {
    if (!dataPromise) {
      console.log('[VN-Addr] loading datasets (local only)...');
      dataPromise = (async function () {
        const [cities, districts, wards] = await Promise.all([
          fetchJSONLocal(LOCAL_CITIES),
          fetchJSONLocal(LOCAL_DISTRICTS),
          fetchJSONLocal(LOCAL_WARDS).catch(() => ({})),
        ]);
        cache.cities = cities;
        cache.districts = districts;
        cache.wards = wards;
        console.log('[VN-Addr] datasets ready', {
          provinces: Object.keys(cities || {}).length,
          districts: Object.keys(districts || {}).length,
        });
        return cache;
      })();
    }
    return dataPromise;
  }

  function ensureZipLoading() {
    if (!zipPromise) {
      console.log('[VN-Addr] loading postcode dataset (local only)...');
      zipPromise = fetchJSONLocal(LOCAL_ZIP)
        .then(function (tree) {
          cache.zipTree = tree;
          console.log('[VN-Addr] postcode dataset ready');
          return tree;
        })
        .catch(function (e) {
          console.warn('[VN-Addr] postcode dataset failed', e);
          cache.zipTree = null;
          return null;
        });
    }
    return zipPromise;
  }

  function buildProvinceOptions(provinceSelect, cities) {
    provinceSelect.innerHTML = '';
    provinceSelect.appendChild(new Option('— Chọn tỉnh / thành —', ''));
    const entries = Object.entries(cities || {}).map(([, c]) => c);
    entries.sort((a, b) => a.name.localeCompare(b.name, 'vi'));
    entries.forEach((c) => {
      const o = document.createElement('option');
      o.value = c.name;
      o.textContent = c.name;
      o.setAttribute('data-code', c.code);
      provinceSelect.appendChild(o);
    });
    refreshPicker(provinceSelect);
    console.log('[VN-Addr] province options built:', entries.length);
  }

  function buildDistrictOptions(districtSelect, districts, provinceCode) {
    districtSelect.innerHTML = '';
    districtSelect.appendChild(new Option('— Chọn quận / huyện —', ''));
    const list = Object.entries(districts || {})
      .map(([, d]) => d)
      .filter((d) => d.parent_code === String(provinceCode));
    list.sort((a, b) => a.name.localeCompare(b.name, 'vi'));
    list.forEach((d) => {
      const o = document.createElement('option');
      o.value = d.name;
      o.textContent = d.name;
      o.setAttribute('data-code', d.code);
      districtSelect.appendChild(o);
    });
    refreshPicker(districtSelect);
    console.log('[VN-Addr] district options built for province', provinceCode, 'count:', list.length);
  }

  function normalize(str) {
    return (str || '').toString().trim().toLowerCase();
  }

  function getZipProvinceMatch(tree) {
    if (!tree) return [];
    if (Array.isArray(tree)) return tree;
    if (tree.provinces) return tree.provinces; // structure of our local dvhcvn.json
    if (tree.level1s) return tree.level1s;     // alternative structures
    return [];
  }

  function getDistrictsFromProvince(p) {
    return p.districts || p.level2s || p.children || [];
  }

  function lookupZip(provinceName, districtName) {
    try {
      const provinces = getZipProvinceMatch(cache.zipTree);
      if (!provinces || !provinces.length) return '';
      const nProv = normalize(provinceName);
      let foundProvince = null;
      for (const p of provinces) {
        if (normalize(p.name).includes(nProv) || nProv.includes(normalize(p.name))) {
          foundProvince = p; break;
        }
      }
      if (!foundProvince) return '';

      // Try district-level first (most datasets don't include postcode per district)
      const nDist = normalize(districtName);
      if (nDist) {
        const districts = getDistrictsFromProvince(foundProvince);
        for (const d of districts) {
          if (normalize(d.name).includes(nDist) || nDist.includes(normalize(d.name))) {
            if (d.postcode || d.zip || d.code_postal) {
              return String(d.postcode || d.zip || d.code_postal);
            }
            break;
          }
        }
      }

      // Fallback to province-level postcode
      if (foundProvince.postcode || foundProvince.zip || foundProvince.code_postal) {
        return String(foundProvince.postcode || foundProvince.zip || foundProvince.code_postal);
      }
    } catch (e) {}
    return '';
  }

  async function setupForCountry(select) {
    if (!isVietnamSelected(select)) return;
    console.log('[VN-Addr] Vietnam detected on select', { name: select.name, id: select.id });

    const container = select.closest('form, .modal-body, .content, body');

    const provinceSelect = createSelect('— Chọn tỉnh / thành —');
    const districtSelect = createSelect('— Chọn quận / huyện —');

    const { state, city, billing_state, billing_city, shipping_state, shipping_city, zip, billing_zip, shipping_zip } = findInputs(container);
    let pair = { state, city, zip };
    if (select.name.includes('billing')) pair = { state: billing_state, city: billing_city, zip: billing_zip };
    if (select.name.includes('shipping')) pair = { state: shipping_state, city: shipping_city, zip: shipping_zip };

    const existingProvince = pair.state ? pair.state.value : '';
    const existingDistrict = pair.city ? pair.city.value : '';

    replaceInputWithSelect(pair.state, provinceSelect);
    replaceInputWithSelect(pair.city, districtSelect);
    console.log('[VN-Addr] replaced inputs with selects');

    provinceSelect.appendChild(new Option('Đang tải...', ''));
    districtSelect.appendChild(new Option('— Chọn quận / huyện —', ''));
    refreshPicker(provinceSelect);
    refreshPicker(districtSelect);

    Promise.all([ensureDataLoading(), ensureZipLoading()])
      .then(function () {
        buildProvinceOptions(provinceSelect, cache.cities);
        if (existingProvince) setSelectValue(provinceSelect, existingProvince);
        const selectedOpt = provinceSelect.options[provinceSelect.selectedIndex];
        const code = selectedOpt ? selectedOpt.getAttribute('data-code') : '';
        buildDistrictOptions(districtSelect, cache.districts, code);
        if (existingDistrict) setSelectValue(districtSelect, existingDistrict);
        // Prefill ZIP from province or district if available
        let autoZip = lookupZip(provinceSelect.value, districtSelect.value);
        if (!autoZip) autoZip = lookupZip(provinceSelect.value, '');
        if (autoZip) {
          console.log('[VN-Addr] autofill zip (initial):', autoZip);
          setZipValue(pair.zip, autoZip);
        }
      })
      .catch(function (e) {
        console.warn('[VN-Addr] data load error', e);
      });

    provinceSelect.addEventListener('change', function () {
      const selectedOpt = provinceSelect.options[provinceSelect.selectedIndex];
      const code = selectedOpt ? selectedOpt.getAttribute('data-code') : '';
      console.log('[VN-Addr] province changed', code);
      buildDistrictOptions(districtSelect, cache.districts, code);
      setSelectValue(districtSelect, '');
      ensureZipLoading().then(function () {
        const autoZip = lookupZip(provinceSelect.value, '');
        if (autoZip) {
          console.log('[VN-Addr] autofill zip (province):', autoZip);
          setZipValue(pair.zip, autoZip);
        }
      });
    });

    districtSelect.addEventListener('change', function () {
      ensureZipLoading().then(function () {
        let autoZip = lookupZip(provinceSelect.value, districtSelect.value);
        if (!autoZip) autoZip = lookupZip(provinceSelect.value, '');
        if (autoZip) {
          console.log('[VN-Addr] autofill zip (district/province fallback):', autoZip);
          setZipValue(pair.zip, autoZip);
        }
      });
    });
  }

  function bindAll(scope) {
    const selects = getCountrySelects(scope);
    selects.forEach(function (sel) {
      if (sel.getAttribute('data-vna-bound') === '1') return;
      sel.setAttribute('data-vna-bound', '1');
      console.log('[VN-Addr] binding country select', { name: sel.name, id: sel.id });
      sel.addEventListener('change', function () {
        setupForCountry(sel).catch(function () {});
      });
      if (typeof $ !== 'undefined' && $(sel).on) {
        $(sel).on('changed.bs.select', function () {
          setupForCountry(sel).catch(function () {});
        });
      }
      setupForCountry(sel).catch(function () {});
    });
  }

  function observeDom() {
    const debounced = (function () {
      let timer;
      return function (fn) {
        clearTimeout(timer);
        timer = setTimeout(fn, 120);
      };
    })();

    const observer = new MutationObserver(function (mutations) {
      let needsRebind = false;
      for (const m of mutations) {
        if (m.addedNodes && m.addedNodes.length) {
          needsRebind = true;
          break;
        }
      }
      if (needsRebind) debounced(function () { bindAll(document); });
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }

  onReady(function () {
    console.log('[VN-Addr] init, local base:', LOCAL_BASE);
    bindAll(document);
    observeDom();
  });
})();