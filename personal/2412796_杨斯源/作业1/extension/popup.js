/* 弹窗逻辑：注入内容脚本 → 取回表格数据 → 交给 SqlBuilder 生成 SQL → 展示/复制/下载 */
'use strict';

const $ = function (id) { return document.getElementById(id); };

const state = {
  page: null,
  tables: [],
  current: null,
  sql: ''
};

// ------------------------------------------------------------ 界面小工具

function showNotice(html, kind) {
  const el = $('notice');
  if (!html) { el.hidden = true; el.textContent = ''; return; }
  el.className = 'notice' + (kind ? ' ' + kind : '');
  el.textContent = html;
  el.hidden = false;
}

function setStatus(text) { $('status').textContent = text || ''; }

function skippedText(res) {
  const s = res.skipped || {};
  const parts = [];
  if (s.hidden) { parts.push(s.hidden + ' 个不可见'); }
  if (s.empty) { parts.push(s.empty + ' 个无数据行'); }
  return parts.length ? '（已跳过 ' + parts.join('、') + '的表格）' : '';
}

// ------------------------------------------------------------ 与页面通信

async function getActiveTab() {
  const tabs = await chrome.tabs.query({ active: true, currentWindow: true });
  return tabs && tabs[0];
}

function injectable(url) {
  return /^https?:\/\//.test(url || '') || /^file:\/\//.test(url || '');
}

async function injectCollector(tabId) {
  await chrome.scripting.executeScript({ target: { tabId: tabId }, files: ['content.js'] });
}

/** 极端情况下内容脚本刚注入、监听器还没挂好，重试一次。 */
async function askCollect(tabId) {
  try {
    return await chrome.tabs.sendMessage(tabId, { type: 'COLLECT_TABLES' });
  } catch (e) {
    await new Promise(function (r) { setTimeout(r, 150); });
    return await chrome.tabs.sendMessage(tabId, { type: 'COLLECT_TABLES' });
  }
}

// ------------------------------------------------------------ 加载流程

async function load() {
  showNotice('');
  setStatus('');
  $('main').hidden = true;

  const tab = await getActiveTab();
  if (!tab || tab.id === undefined) {
    showNotice('找不到当前标签页。', 'error');
    return;
  }
  if (!injectable(tab.url)) {
    showNotice('当前页面不支持抓取。\n请切换到普通网页（http/https）后重试；chrome:// 设置页、扩展商店、PDF 阅读器都不允许插件注入脚本。', 'error');
    return;
  }

  try {
    await injectCollector(tab.id);
  } catch (err) {
    showNotice('注入脚本失败：' + err.message +
               '\n常见原因：页面是浏览器内置页、扩展商店页，或本地 PDF。', 'error');
    return;
  }

  let res;
  try {
    res = await askCollect(tab.id);
  } catch (err) {
    showNotice('与页面通信失败：' + err.message, 'error');
    return;
  }

  if (!res || res.ok !== true) {
    showNotice('抓取失败：' + ((res && res.error) || '页面没有返回数据'), 'error');
    return;
  }

  state.page = res.page;
  state.tables = res.tables || [];

  $('page-info').textContent = res.page.title || res.page.url;
  $('page-info').title = res.page.url;

  if (!state.tables.length) {
    showNotice('本页共扫描到 ' + res.scanned + ' 个 <table>，其中没有可导出的表格' +
               skippedText(res) + '。', 'warn');
    return;
  }

  $('main').hidden = false;
  fillTableSelect();
  selectTable(0);

  if (res.skipped && (res.skipped.hidden || res.skipped.empty)) {
    setStatus('已跳过' + skippedText(res).replace(/[（）]/g, '') + '。');
  }
}

// ------------------------------------------------------------ 表格选择

function labelOf(t, i) {
  const name = t.caption || t.heading || (t.id ? '#' + t.id : '') || '（无标题）';
  return '#' + (i + 1) + ' · ' + t.rowCount + '行 × ' + t.colCount + '列 · ' + name;
}

function fillTableSelect() {
  const sel = $('table-select');
  sel.textContent = '';
  state.tables.forEach(function (t, i) {
    const opt = document.createElement('option');
    opt.value = String(i);
    opt.textContent = labelOf(t, i);
    sel.appendChild(opt);
  });
}

function defaultNameFor(t, i) {
  const raw = t.caption || t.heading || t.id || '';
  if (raw) { return SqlBuilder.sanitizeIdentifier(raw, 'table_' + (i + 1)); }
  return 'table_' + (i + 1);
}

function selectTable(index) {
  const t = state.tables[index];
  if (!t) { return; }
  state.current = t;
  $('table-select').value = String(index);

  $('table-name').value = t.customName || defaultNameFor(t, index);
  $('has-header').checked = t.firstRowIsHeader !== false;
  $('max-rows').value = String(Math.min(t.rowCount || 1, 200) || 1);
  generate();
}

// ------------------------------------------------------------ SQL 生成

function generate() {
  const t = state.current;
  if (!t) { return; }

  const useHeader = $('has-header').checked;
  // 取消勾选「首行作为表头」时，首行要退回成数据行，不能丢
  const rows = useHeader ? t.rows : (t.allRows || t.rows);
  const headers = useHeader ? t.headers : [];

  const result = SqlBuilder.build({
    headers: headers,
    rows: rows,
    tableName: $('table-name').value,
    hasHeader: useHeader,
    dialect: $('dialect').value,
    dropIfExists: $('drop-table').checked,
    maxRows: parseInt($('max-rows').value, 10) || 0,
    pageTitle: state.page ? state.page.title : '',
    pageUrl: state.page ? state.page.url : ''
  });

  state.sql = result.sql;
  $('preview').textContent = result.sql;
  $('preview').scrollTop = 0;

  const distinctTypes = [];
  result.columns.forEach(function (c) {
    if (distinctTypes.indexOf(c.type) === -1) { distinctTypes.push(c.type); }
  });

  const headerNote = {
    'thead': '表头取自 <thead>',
    'first-row-th': '表头取自首行 <th>',
    'first-row-guess': '首行疑似表头（原表没有 <thead>，可取消勾选）',
    'none': '表格没有行'
  }[t.headerSource] || '';

  $('meta').textContent =
    '第 ' + (t.index + 1) + ' 个表格：原表 ' + t.totalRows + ' 行 × ' + t.colCount + ' 列' +
    '，本次导出 ' + result.stats.rows + ' 行 ' + result.stats.cols + ' 列' +
    '（' + distinctTypes.join(' / ') + '）· ' + headerNote;

  const warnEl = $('warnings');
  const warnings = result.warnings.slice();
  if (t.truncated) {
    warnings.push('原表超过单次抓取上限，已丢弃末尾 ' + t.truncated + ' 行。');
  }
  if (warnings.length) {
    warnEl.innerHTML = '';
    const ul = document.createElement('ul');
    warnings.forEach(function (w) {
      const li = document.createElement('li');
      li.textContent = w;
      ul.appendChild(li);
    });
    warnEl.appendChild(ul);
    warnEl.hidden = false;
  } else {
    warnEl.hidden = true;
  }

  setStatus('已生成 ' + result.sql.split('\n').length + ' 行 SQL，' + result.sql.length + ' 字符。');
}

// ------------------------------------------------------------ 复制 / 下载

async function copySql() {
  const text = state.sql;
  if (!text) { setStatus('还没有可复制的内容。'); return; }
  try {
    await navigator.clipboard.writeText(text);
    setStatus('已复制到剪贴板（' + text.length + ' 字符）。');
    return;
  } catch (e) {
    // 剪贴板 API 在部分情况下会被拒绝，降级到 execCommand
  }
  const ta = document.createElement('textarea');
  ta.value = text;
  ta.style.position = 'fixed';
  ta.style.opacity = '0';
  document.body.appendChild(ta);
  ta.select();
  let ok = false;
  try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
  ta.remove();
  setStatus(ok ? '已复制到剪贴板。' : '复制被拒绝，请在预览框里全选后手动复制。');
}

function downloadSql() {
  if (!state.sql) { setStatus('还没有可下载的内容。'); return; }
  const name = SqlBuilder.sanitizeIdentifier($('table-name').value, 'table') + '.sql';
  const blob = new Blob([state.sql], { type: 'text/plain;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = name;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(function () { URL.revokeObjectURL(url); }, 5000);
  setStatus('已下载 ' + name);
}

// ------------------------------------------------------------ 事件绑定

let renameTimer = null;

document.addEventListener('DOMContentLoaded', function () {
  $('table-select').addEventListener('change', function () {
    selectTable(parseInt(this.value, 10) || 0);
  });

  $('table-name').addEventListener('input', function () {
    if (state.current) { state.current.customName = this.value; }
    clearTimeout(renameTimer);
    renameTimer = setTimeout(generate, 150);
  });

  ['has-header', 'drop-table', 'dialect', 'max-rows'].forEach(function (id) {
    $(id).addEventListener('change', generate);
    $(id).addEventListener('input', generate);
  });

  $('btn-copy').addEventListener('click', copySql);
  $('btn-download').addEventListener('click', downloadSql);
  $('btn-reload').addEventListener('click', load);

  load();
});
