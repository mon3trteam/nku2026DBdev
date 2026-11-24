/* P1 任务2 —— jQuery 事件演示
 *
 * 本文件里的每个事件绑定都同时产生两样东西：
 *   - 一次可被 DevTools / 抓包工具记录的 HTTP 请求（任务1 的素材）
 *   - 一次可见的 DOM 变化（任务2 「响应前后不同状态」的素材）
 */
$(function () {

  var reqCount = 0;

  // ---------------------------------------------------------- 小工具

  function esc(s) {
    return $('<div>').text(s === null || s === undefined ? '' : String(s)).html();
  }

  function setStat(obj) {
    $('#stat-count').text(obj.count);
    if (obj.last) { $('#stat-last').text(obj.last); }
    if (obj.req !== false) {
      reqCount += 1;
      $('#stat-req').text(reqCount);
    }
  }

  function log(msg, cls) {
    var t = new Date().toLocaleTimeString('zh-CN', { hour12: false });
    $('#log').append($('<span>').addClass(cls || 'dim').text('[' + t + '] ' + msg))
           .append('\n')
           .scrollTop($('#log')[0].scrollHeight);
  }

  function rowCount() {
    return $('#tbody tr[data-id]').length;
  }

  // ---------------------------------------------------------- 渲染

  function render(rows) {
    var $tbody = $('#tbody').empty();
    if (!rows.length) {
      $tbody.append('<tr><td colspan="6" class="empty">没有匹配的记录</td></tr>');
      return;
    }
    $.each(rows, function (_, it) {
      $tbody.append(
        '<tr data-id="' + it.id + '">' +
          '<td class="num">' + it.id + '</td>' +
          '<td class="editable" data-field="name">' + esc(it.name) + '</td>' +
          '<td class="editable" data-field="category">' + esc(it.category) + '</td>' +
          '<td class="editable num" data-field="qty">' + it.qty + '</td>' +
          '<td class="editable num" data-field="price">' + it.price + '</td>' +
          '<td><button class="danger btn-del">删除</button></td>' +
        '</tr>');
    });
  }

  // ---------------------------------------------------------- 语句①：GET 加载列表

  function loadItems(keyword) {
    var before = rowCount();
    var url = '/api/items?page=1&size=50' +
              (keyword ? '&keyword=' + encodeURIComponent(keyword) : '');

    $.ajax({
      url: url,
      type: 'GET',
      dataType: 'json',
      success: function (res) {
        render(res.data);
        setStat({
          count: res.total,
          last: 'GET 列表' + (keyword ? '（keyword=' + keyword + '）' : '')
        });
        log('GET ' + url + ' → 200，total=' + res.total +
            '；渲染前 ' + before + ' 行 → 渲染后 ' + res.data.length + ' 行', 'ok');
      },
      error: function (xhr) {
        log('GET ' + url + ' → ' + xhr.status + ' ' + xhr.statusText + '，渲染未变（仍 ' + before + ' 行）', 'err');
      }
    });
  }

  // ---------------------------------------------------------- 语句②：click 事件

  $('#btn-add').on('click', function () {
    var visible = $('#form-panel').is(':visible');
    $('#form-panel').slideToggle(180);
    log('click 事件命中 #btn-add：新增表单 ' + (visible ? '收起' : '展开'));
    if (!visible) { $('#f-name').trigger('focus'); }
  });

  $('#btn-cancel').on('click', function () {
    $('#form-panel').slideUp(180);
    log('click 事件命中 #btn-cancel：表单收起，未发送任何请求');
  });

  $('#btn-reload').on('click', function () {
    loadItems($.trim($('#keyword').val()));
  });

  // ---------------------------------------------------------- 语句③：POST 提交

  $('#btn-save').on('click', function () {
    var name = $.trim($('#f-name').val());
    if (!name) {
      log('POST 已取消：名称不能为空（未发送请求）', 'err');
      $('#f-name').trigger('focus');
      return;
    }

    var payload = {
      name: name,
      category: $.trim($('#f-cat').val()) || '未分类',
      qty: parseInt($('#f-qty').val(), 10) || 0,
      price: parseFloat($('#f-price').val()) || 0
    };
    var before = rowCount();

    $.ajax({
      url: '/api/items',
      type: 'POST',
      contentType: 'application/json; charset=utf-8',
      data: JSON.stringify(payload),
      dataType: 'json',
      success: function (res) {
        log('POST /api/items\n           请求体 ' + JSON.stringify(payload) +
            '\n           → ' + res.code + '，返回 id=' + res.data.id +
            '；提交前 ' + before + ' 行 → 提交后 ' + (before + 1) + ' 行', 'ok');
        $('#form-panel').slideUp(180);
        $('#f-name, #f-cat').val('');
        $('#f-qty').val(1);
        $('#f-price').val(0);
        loadItems($.trim($('#keyword').val()));
      },
      error: function (xhr) {
        log('POST /api/items → ' + xhr.status + ' ' + xhr.statusText +
            '，表格保持 ' + before + ' 行不变', 'err');
      }
    });
  });

  // 表单里按回车等同于点「提交」
  $('#form-panel').on('keydown', 'input', function (e) {
    if (e.key === 'Enter') { $('#btn-save').trigger('click'); }
  });

  // ---------------------------------------------------------- 语句④：事件委托 + DELETE

  $('#tbody').on('click', '.btn-del', function () {
    var $tr = $(this).closest('tr');
    var id = $tr.data('id');
    var name = $tr.find('td[data-field="name"]').text();
    var before = rowCount();

    if (!window.confirm('确定删除「' + name + '」吗？')) {
      log('DELETE 已取消：用户点了取消（未发送请求）', 'dim');
      return;
    }

    $.ajax({
      url: '/api/items/' + id,
      type: 'DELETE',
      dataType: 'json',
      success: function (res) {
        $tr.fadeOut(160, function () {
          $(this).remove();
          setStat({ count: rowCount(), last: 'DELETE 删除 id=' + id + '「' + name + '」' });
          log('DELETE /api/items/' + id + ' → ' + res.code +
              '；删除前 ' + before + ' 行 → 删除后 ' + rowCount() + ' 行', 'ok');
        });
      },
      error: function (xhr) {
        log('DELETE /api/items/' + id + ' → ' + xhr.status + ' 失败，行未移除', 'err');
      }
    });
  });

  // ---------------------------------------------------------- 语句⑤：input 事件（防抖）

  var debounce = null;
  $('#keyword').on('input', function () {
    var kw = $.trim($(this).val());
    clearTimeout(debounce);
    debounce = setTimeout(function () {
      loadItems(kw);
    }, 250);
    log('input 事件：输入「' + kw + '」，250ms 后发起请求（期间继续输入则不请求）');
  });

  // ---------------------------------------------------------- 语句⑥：dblclick 单元格编辑 + PUT

  $('#tbody').on('dblclick', 'td.editable', function () {
    var $td = $(this);
    if ($td.find('input').length) { return; }

    var field = $td.data('field');
    var oldValue = $td.text();
    var $input = $('<input style="width:100%">').val(oldValue);
    $td.addClass('editing').empty().append($input);
    $input.trigger('focus').trigger('select');
    log('dblclick 事件命中 td[data-field=' + field + ']，进入编辑态，原值「' + oldValue + '」');

    var done = false;
    function commit(cancel) {
      if (done) { return; }
      done = true;
      var value = $input.val();
      $td.removeClass('editing').text(cancel ? oldValue : value);

      if (cancel || value === oldValue) {
        log('编辑结束：' + (cancel ? '按 Esc 取消' : '值未改变') + '，未发送请求', 'dim');
        return;
      }

      var id = $td.closest('tr').data('id');
      var payload = {};
      payload[field] = (field === 'qty' || field === 'price') ? Number(value) : value;

      $.ajax({
        url: '/api/items/' + id,
        type: 'PUT',
        contentType: 'application/json; charset=utf-8',
        data: JSON.stringify(payload),
        dataType: 'json',
        success: function (res) {
          setStat({ count: $('#stat-count').text(), last: 'PUT 修改 id=' + id + ' 的 ' + field });
          log('PUT /api/items/' + id + '\n           请求体 ' + JSON.stringify(payload) +
              '\n           → ' + res.code + '，单元格「' + oldValue + '」→「' + value + '」', 'ok');
        },
        error: function (xhr) {
          $td.text(oldValue);
          log('PUT /api/items/' + id + ' → ' + xhr.status + ' 失败，单元格已回滚为「' + oldValue + '」', 'err');
        }
      });
    }

    $input.on('blur', function () { commit(false); })
          .on('keydown', function (e) {
            if (e.key === 'Enter')  { $input.off('blur'); commit(false); }
            if (e.key === 'Escape') { $input.off('blur'); commit(true);  }
          });
  });

  // ---------------------------------------------------------- 启动

  log('页面就绪：jQuery ' + $.fn.jquery + '，开始加载数据…');
  loadItems('');
});
