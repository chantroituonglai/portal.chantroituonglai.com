(function ($) {
  "use strict";

  var context = detectContext();
  if (!context.type) {
    return;
  }

  var currentPlan = null;
  var currentPreview = null;
  var previewMode = "qa";
  var lastMessage = "";
  var endpoints = {
    previewAssist: "futurecrmagent/preview_assist",
    previewActionPlan: "futurecrmagent/preview_action_plan",
    submitPreviewActionApproval: "futurecrmagent/submit_preview_action_approval",
    toolCatalog: "futurecrmagent/tool_catalog",
    toolAssist: "futurecrmagent/tool_assist",
    submitToolActionApproval: "futurecrmagent/submit_tool_action_approval",
  };

  injectStyles();
  injectButton();
  injectPanel();

  function detectContext() {
    var path = window.location.pathname;
    var editMatch = path.match(/\/admin\/(estimates|invoices|proposals)\/(estimate|invoice|proposal)\/([0-9]+)/);
    var typeMap = {
      estimates: "estimate",
      invoices: "invoice",
      proposals: "proposal",
    };

    if (editMatch) {
      var form = $("form._transaction_form[data-autosave-enabled=\"1\"]");
      return {
        mode: "edit",
        type: typeMap[editMatch[1]],
        id: parseInt(editMatch[3], 10),
        form: form,
        autosaveUrl: form.attr("data-autosave-url") || "",
      };
    }

    var previewMatch = path.match(/\/admin\/(estimates|invoices|proposals)\/list_(estimates|invoices|proposals)\/([0-9]+)/);
    if (previewMatch) {
      return {
        mode: "preview",
        type: typeMap[previewMatch[1]],
        id: parseInt(previewMatch[3], 10),
        form: $(),
      };
    }

    var manageMatch = path.match(/\/admin\/(estimates|invoices|proposals)(?:\/list_(?:estimates|invoices|proposals).*)?\/?$/);
    if (manageMatch) {
      var hashId = hashRecordId();
      if (hashId > 0) {
        return {
          mode: "preview",
          type: typeMap[manageMatch[1]],
          id: hashId,
          form: $(),
          fromHash: true,
        };
      }

      return {
        mode: "manage",
        type: typeMap[manageMatch[1]],
        id: 0,
        form: $(),
      };
    }

    return {};
  }

  function hashRecordId() {
    var hash = window.location.hash || "";
    var match = hash.match(/^#([0-9]+)$/);
    return match ? parseInt(match[1], 10) : 0;
  }

  function injectStyles() {
    if ($("#futurecrmagent-sales-doc-style").length) {
      return;
    }

    $("head").append(
      '<style id="futurecrmagent-sales-doc-style">' +
        '.futurecrmagent-sales-doc-fab{position:fixed;right:26px;bottom:26px;width:58px;height:58px;border-radius:999px;border:0;background:#059669;color:#fff;box-shadow:0 14px 34px rgba(5,150,105,.34);display:flex;align-items:center;justify-content:center;z-index:1060;transition:transform .18s ease,box-shadow .18s ease,background .18s ease;font-size:21px}' +
        '.futurecrmagent-sales-doc-fab:hover,.futurecrmagent-sales-doc-fab:focus{background:#047857;color:#fff;transform:translateY(-2px) scale(1.03);box-shadow:0 18px 42px rgba(5,150,105,.42);outline:none}' +
        '.futurecrmagent-sales-doc-overlay{position:fixed;inset:0;background:rgba(15,23,42,.18);backdrop-filter:blur(2px);z-index:1059;display:none}.futurecrmagent-sales-doc-overlay.is-open{display:block}' +
        'body.futurecrmagent-sales-doc-modal-open{overflow:hidden}' +
        '.futurecrmagent-sales-doc-panel{position:fixed;right:0;top:0;bottom:0;width:900px;max-width:calc(100vw - 20px);height:100vh;max-height:100vh;background:rgba(249,250,248,.96);border-left:1px solid #d9e2ec;border-radius:0;box-shadow:-22px 0 70px rgba(15,23,42,.20);z-index:1061;display:none;overflow:hidden;flex-direction:column;transform:translateX(18px);opacity:0;transition:transform .18s ease,opacity .18s ease}' +
        '.futurecrmagent-sales-doc-panel.is-open{display:flex}' +
        '.futurecrmagent-sales-doc-panel.is-open{transform:translateX(0);opacity:1}' +
        '.futurecrmagent-sales-doc-head{background:rgba(255,255,255,.88);color:#111827;padding:16px 22px;display:flex;align-items:center;justify-content:space-between;gap:14px;border-bottom:1px solid #e5e7eb;min-height:72px}' +
        '.futurecrmagent-sales-doc-brand{display:flex;align-items:center;gap:13px;min-width:0}.futurecrmagent-sales-doc-logo{width:42px;height:42px;border-radius:999px;background:#059669;color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 10px 28px rgba(5,150,105,.24)}' +
        '.futurecrmagent-sales-doc-title{font-weight:700;font-size:18px;line-height:1.2;display:block;color:#111827}.futurecrmagent-sales-doc-subtitle{font-size:12px;color:#6b7280;margin-top:2px}' +
        '.futurecrmagent-sales-doc-head-actions{display:flex;align-items:center;gap:14px}.futurecrmagent-sales-doc-model-toggle{display:flex;align-items:center;background:#eef0ed;border:1px solid #dde3dc;border-radius:999px;padding:4px}.futurecrmagent-sales-doc-model{border:0;background:transparent;border-radius:999px;padding:7px 16px;color:#374151}.futurecrmagent-sales-doc-model.active{background:#fff;color:#111827;box-shadow:0 2px 8px rgba(15,23,42,.10)}' +
        '.futurecrmagent-sales-doc-status-pill{font-size:12px;background:#ecfdf5;color:#047857;border-radius:999px;padding:5px 10px;display:inline-flex;align-items:center;gap:6px;white-space:nowrap}' +
        '.futurecrmagent-sales-doc-status-dot{width:8px;height:8px;border-radius:999px;background:#4ade80;box-shadow:0 0 0 4px rgba(74,222,128,.14)}' +
        '.futurecrmagent-sales-doc-close{border:0;background:transparent;color:#6b7280;font-size:24px;line-height:1;padding:4px 8px;border-radius:999px}.futurecrmagent-sales-doc-close:hover{color:#111827;background:#eef0ed}' +
        '.futurecrmagent-sales-doc-body{display:flex;min-height:0;flex:1}.futurecrmagent-sales-doc-main{position:relative;display:flex;flex-direction:column;min-width:0;flex:1}.futurecrmagent-sales-doc-context{width:290px;flex:0 0 290px;background:rgba(243,244,241,.75);border-left:1px solid #e5e7eb;padding:24px 18px;overflow:auto}' +
        '.futurecrmagent-sales-doc-modes{display:flex;gap:8px;padding:12px 18px;background:rgba(255,255,255,.80);border-bottom:1px solid #e5e7eb;flex-wrap:wrap}' +
        '.futurecrmagent-sales-doc-mode{border:1px solid #d1d5db;background:#fff;border-radius:999px;padding:5px 10px;font-size:12px;color:#374151}.futurecrmagent-sales-doc-mode.active{border-color:#059669;background:#ecfdf5;color:#047857;font-weight:600}' +
        '.futurecrmagent-sales-doc-thread{background:#fafbf9;padding:22px 24px;overflow:auto;max-height:none;min-height:0;flex:1;padding-bottom:170px}' +
        '.futurecrmagent-sales-doc-bubble{border-radius:18px;padding:12px 14px;margin-bottom:14px;max-width:88%;font-size:14px;line-height:1.45;box-shadow:0 1px 2px rgba(15,23,42,.06)}' +
        '.futurecrmagent-sales-doc-bubble.user{background:#fff;border:1px solid #e5e7eb;margin-left:auto;border-top-right-radius:5px;color:#1f2937}' +
        '.futurecrmagent-sales-doc-bubble.agent{background:#ecfdf5;border:1px solid #bbf7d0;margin-right:auto;border-top-left-radius:5px;color:#1f2937;max-width:96%}' +
        '.futurecrmagent-sales-doc-preview-card{background:#fff;border:1px solid #bbf7d0;border-radius:10px;padding:12px;margin-top:9px}' +
        '.futurecrmagent-sales-doc-preview-row{display:flex;justify-content:space-between;gap:14px;border-bottom:1px solid #eef2f7;padding:6px 0}.futurecrmagent-sales-doc-preview-row:last-child{border-bottom:0}' +
        '.futurecrmagent-sales-doc-item{display:flex;justify-content:space-between;gap:12px;padding:5px 0}.futurecrmagent-sales-doc-item small{display:block;color:#6b7280;margin-top:2px}' +
        '.futurecrmagent-sales-doc-list{margin:8px 0 0 18px;padding:0}.futurecrmagent-sales-doc-list li{margin-bottom:3px}' +
        '.futurecrmagent-sales-doc-markdown{line-height:1.6;color:#1f2937}.futurecrmagent-sales-doc-markdown p{margin:0 0 10px}.futurecrmagent-sales-doc-markdown p:last-child{margin-bottom:0}.futurecrmagent-sales-doc-markdown strong{font-weight:600;color:#111827}.futurecrmagent-sales-doc-markdown em{color:#374151}.futurecrmagent-sales-doc-markdown code{background:#f3f4f6;border:1px solid #e5e7eb;border-radius:5px;padding:1px 5px;font-size:12px;color:#374151}.futurecrmagent-sales-doc-markdown pre{background:#111827;color:#e5e7eb;border-radius:10px;padding:11px;overflow:auto;white-space:pre-wrap}.futurecrmagent-sales-doc-markdown h4,.futurecrmagent-sales-doc-markdown h5{margin:14px 0 7px;font-weight:600;color:#111827;line-height:1.35}.futurecrmagent-sales-doc-markdown ul,.futurecrmagent-sales-doc-markdown ol{margin:7px 0 12px 20px;padding:0}.futurecrmagent-sales-doc-markdown li{margin:4px 0}.futurecrmagent-sales-doc-markdown blockquote{margin:8px 0;padding:8px 12px;border-left:3px solid #10b981;background:#f0fdf4;border-radius:8px;color:#374151}' +
        '.futurecrmagent-sales-doc-insight{margin-top:12px;padding:12px;border-radius:12px;border:1px solid #dbeafe;background:#eff6ff;color:#1e3a8a}.futurecrmagent-sales-doc-insight.missing{background:#fff7ed;border-color:#fed7aa;color:#9a3412}.futurecrmagent-sales-doc-insight.risk{background:#fef2f2;border-color:#fecaca;color:#991b1b}.futurecrmagent-sales-doc-insight-title{display:flex;align-items:center;gap:8px;font-weight:600;margin-bottom:7px;color:inherit}.futurecrmagent-sales-doc-insight ul{margin:0 0 0 18px;padding:0}.futurecrmagent-sales-doc-insight li{margin:4px 0}.futurecrmagent-sales-doc-muted-section{margin-top:12px;color:#4b5563}.futurecrmagent-sales-doc-muted-section strong{color:#111827}' +
        '.futurecrmagent-sales-doc-action-card{background:#fff;border:1px solid #fde68a;border-left:3px solid #d97706;border-radius:10px;padding:12px;margin-top:10px}' +
        '.futurecrmagent-sales-doc-action-key{font-family:monospace;font-size:12px;color:#92400e;background:#fffbeb;border-radius:6px;padding:3px 6px;display:inline-block;margin-bottom:7px}' +
        '.futurecrmagent-sales-doc-actions{display:none;border-top:1px solid #e5e7eb;background:rgba(255,255,255,.92);padding:11px 24px;justify-content:flex-end;gap:9px}.futurecrmagent-sales-doc-actions.is-visible{display:flex}' +
        '.futurecrmagent-sales-doc-input{border-top:1px solid #e5e7eb;background:rgba(255,255,255,.92);padding:12px 20px 14px;position:absolute;left:0;right:0;bottom:0;z-index:2}' +
        '.futurecrmagent-sales-doc-command-chips{display:flex;gap:8px;overflow-x:auto;margin:0 0 10px}.futurecrmagent-sales-doc-chip{border:1px solid #d7ddd5;background:#f3f4f1;border-radius:999px;padding:6px 10px;font-size:12px;color:#4b5563;white-space:nowrap}.futurecrmagent-sales-doc-chip:hover{background:#e8eee7;color:#047857}' +
        '.futurecrmagent-sales-doc-composer{position:relative;background:#f8faf7;border:1px solid #d7ddd5;border-radius:18px;display:flex;align-items:flex-end;gap:8px;padding:8px 10px;box-shadow:0 8px 26px rgba(15,23,42,.06)}.futurecrmagent-sales-doc-composer:focus-within{border-color:#059669;box-shadow:0 0 0 1px #059669}' +
        '.futurecrmagent-sales-doc-tool{border:0;background:transparent;color:#6b7280;width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center}.futurecrmagent-sales-doc-tool:hover{background:#e5e7eb;color:#047857}' +
        '.futurecrmagent-sales-doc-input textarea{resize:none;padding:8px 4px;border-radius:0;min-height:38px;max-height:120px;border:0!important;background:transparent!important;box-shadow:none!important}' +
        '.futurecrmagent-sales-doc-send{border:0;background:#059669;color:#fff;font-size:16px;padding:0;line-height:1;width:38px;height:38px;border-radius:13px;display:flex;align-items:center;justify-content:center}.futurecrmagent-sales-doc-send:hover{background:#047857}.futurecrmagent-sales-doc-send[disabled]{opacity:.45}' +
        '.futurecrmagent-sales-doc-message{font-size:13px;margin:0 0 10px;padding:8px 10px;border-radius:8px;display:none}.futurecrmagent-sales-doc-message.info{display:block;background:#eff6ff;color:#1d4ed8}.futurecrmagent-sales-doc-message.success{display:block;background:#ecfdf5;color:#047857}.futurecrmagent-sales-doc-message.danger{display:block;background:#fef2f2;color:#b91c1c}' +
        '.futurecrmagent-sales-doc-context-section{margin-bottom:28px}.futurecrmagent-sales-doc-context-title{font-size:11px;text-transform:uppercase;letter-spacing:.12em;color:#4b5563;font-weight:700;margin:0 0 12px;display:flex;align-items:center;gap:8px}.futurecrmagent-sales-doc-context-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:13px;margin-bottom:12px;box-shadow:0 2px 10px rgba(15,23,42,.04)}.futurecrmagent-sales-doc-context-kicker{font-size:10px;text-transform:uppercase;color:#6b7280;font-weight:700;margin-bottom:4px}.futurecrmagent-sales-doc-context-value{font-size:14px;color:#111827;font-weight:600}.futurecrmagent-sales-doc-context-sub{font-size:12px;color:#6b7280;margin-top:3px}.futurecrmagent-sales-doc-quick{display:flex;width:100%;align-items:center;gap:10px;border:1px solid #e5e7eb;background:#fff;border-radius:13px;padding:10px 12px;margin-bottom:10px;text-align:left;color:#111827}.futurecrmagent-sales-doc-quick:hover{background:#f8faf7;color:#047857}.futurecrmagent-sales-doc-secure{margin-top:auto;border-top:1px solid #e5e7eb;padding-top:15px;color:#4b5563;font-size:12px;display:flex;align-items:center;gap:8px}' +
        '@media(max-width:991px){.futurecrmagent-sales-doc-panel{width:100vw;max-width:100vw}.futurecrmagent-sales-doc-context{display:none}}@media(max-width:767px){.futurecrmagent-sales-doc-fab{right:18px;bottom:18px}.futurecrmagent-sales-doc-head{padding:12px 14px}.futurecrmagent-sales-doc-model-toggle{display:none}.futurecrmagent-sales-doc-thread{padding:16px 14px 170px}.futurecrmagent-sales-doc-input{padding:10px 12px 12px}}' +
      '</style>'
    );
  }

  function injectButton() {
    if ($("#futurecrmagent-sales-doc-fab").length) {
      return;
    }

    $("body").append(
      '<button type="button" class="futurecrmagent-sales-doc-fab" id="futurecrmagent-sales-doc-fab" title="FutureCRM Agent Chat / Create" aria-label="FutureCRM Agent Chat / Create">' +
        '<i class="fa fa-wand-magic-sparkles"></i>' +
      '</button>'
    );
  }

  function injectPanel() {
    if ($("#futurecrmagent-sales-doc-panel").length) {
      return;
    }

    $("body").append(
      '<div class="futurecrmagent-sales-doc-overlay" id="futurecrmagent-sales-doc-overlay"></div>' +
      '<div class="futurecrmagent-sales-doc-panel" id="futurecrmagent-sales-doc-panel" data-mode="' + escapeHtml(context.mode) + '">' +
        '<div class="futurecrmagent-sales-doc-head">' +
          '<div class="futurecrmagent-sales-doc-brand">' +
            '<div class="futurecrmagent-sales-doc-logo"><i class="fa fa-wand-magic-sparkles"></i></div>' +
                '<div><div class="futurecrmagent-sales-doc-title">FutureCRM Assistant</div><div class="futurecrmagent-sales-doc-subtitle" id="futurecrmagent-sales-doc-subtitle">' + escapeHtml(contextSubtitle()) + '</div></div>' +
          '</div>' +
          '<div class="futurecrmagent-sales-doc-head-actions">' +
            '<div class="futurecrmagent-sales-doc-model-toggle">' +
              '<button type="button" class="futurecrmagent-sales-doc-model active" data-agent-profile="balanced">Balanced</button>' +
              '<button type="button" class="futurecrmagent-sales-doc-model" data-agent-profile="technical">Technical</button>' +
            '</div>' +
            '<span class="futurecrmagent-sales-doc-status-pill"><span class="futurecrmagent-sales-doc-status-dot"></span>Connected</span>' +
            '<button type="button" class="futurecrmagent-sales-doc-close" id="futurecrmagent-sales-doc-close" aria-label="Close">&times;</button>' +
          '</div>' +
        '</div>' +
        '<div class="futurecrmagent-sales-doc-body">' +
          '<div class="futurecrmagent-sales-doc-main">' +
            '<div class="futurecrmagent-sales-doc-modes">' +
              '<button type="button" class="futurecrmagent-sales-doc-mode active" data-preview-mode="qa">Hỏi/Tra cứu</button>' +
              '<button type="button" class="futurecrmagent-sales-doc-mode" data-preview-mode="summary">Tổng hợp</button>' +
              '<button type="button" class="futurecrmagent-sales-doc-mode" data-preview-mode="action">Đề xuất hành động</button>' +
            '</div>' +
            '<div class="futurecrmagent-sales-doc-thread" id="futurecrmagent-sales-doc-thread">' +
              '<div class="futurecrmagent-sales-doc-bubble agent">' +
                '<strong>FutureCRM Agent</strong><br>' + escapeHtml(contextIntro()) +
              '</div>' +
            '</div>' +
            '<div class="futurecrmagent-sales-doc-actions" id="futurecrmagent-sales-doc-actions">' +
              '<button type="button" class="btn btn-default" id="futurecrmagent-sales-doc-cancel">Hủy</button>' +
              '<button type="button" class="btn btn-success hide" id="futurecrmagent-sales-doc-apply"><i class="fa fa-check"></i> Cập nhật phiếu hiện tại</button>' +
              '<button type="button" class="btn btn-primary hide" id="futurecrmagent-sales-doc-create-draft" data-target-status="draft">Tạo Nháp</button>' +
              '<button type="button" class="btn btn-success hide" id="futurecrmagent-sales-doc-create-open" data-target-status="open">Tạo Open</button>' +
              '<button type="button" class="btn btn-warning hide" id="futurecrmagent-sales-doc-submit-approval"><i class="fa fa-paper-plane"></i> Gửi phê duyệt</button>' +
            '</div>' +
            '<div class="futurecrmagent-sales-doc-input">' +
              '<div class="futurecrmagent-sales-doc-command-chips">' +
                '<button type="button" class="futurecrmagent-sales-doc-chip" data-preview-mode="summary" data-prompt="/summary Tóm tắt phiếu này và các điểm cần chú ý"><i class="fa fa-history"></i> /summary</button>' +
                '<button type="button" class="futurecrmagent-sales-doc-chip" data-preview-mode="qa" data-prompt="/missing_fields Còn thiếu gì trước khi gửi khách?"><i class="fa fa-list-check"></i> /missing_fields</button>' +
                '<button type="button" class="futurecrmagent-sales-doc-chip" data-preview-mode="action" data-prompt="/action_note Tạo note nội bộ tóm tắt phiếu này để gửi phê duyệt"><i class="fa fa-bolt"></i> /action_note</button>' +
                '<button type="button" class="futurecrmagent-sales-doc-chip" data-preview-mode="qa" data-prompt="/tools Liệt kê tool catalog đang được phép"><i class="fa fa-wrench"></i> /tools</button>' +
              '</div>' +
              '<div class="futurecrmagent-sales-doc-message" id="futurecrmagent-sales-doc-status"></div>' +
              '<div class="futurecrmagent-sales-doc-composer">' +
                '<button type="button" class="futurecrmagent-sales-doc-tool" id="futurecrmagent-sales-doc-attach" title="Attach file"><i class="fa fa-paperclip"></i></button>' +
                '<textarea class="form-control" id="futurecrmagent-sales-doc-message" rows="1" placeholder="Ask anything or use / for commands..."></textarea>' +
                '<button type="button" class="futurecrmagent-sales-doc-tool" id="futurecrmagent-sales-doc-voice" title="Voice input"><i class="fa fa-microphone"></i></button>' +
                '<button type="button" class="futurecrmagent-sales-doc-send" id="futurecrmagent-sales-doc-run" aria-label="Send"><i class="fa fa-arrow-up"></i></button>' +
              '</div>' +
              '<div class="text-center text-muted" style="font-size:11px;margin-top:8px">FutureCRM AI can make mistakes. Verify important financial data.</div>' +
            '</div>' +
          '</div>' +
          '<aside class="futurecrmagent-sales-doc-context">' +
            '<section class="futurecrmagent-sales-doc-context-section">' +
              '<h4 class="futurecrmagent-sales-doc-context-title"><i class="fa fa-eye"></i> Current Context</h4>' +
              '<div class="futurecrmagent-sales-doc-context-card">' +
                '<div class="futurecrmagent-sales-doc-context-kicker">Active record</div>' +
                '<div class="futurecrmagent-sales-doc-context-value" id="futurecrmagent-sales-doc-context-record">' + escapeHtml(contextTitle()) + '</div>' +
                '<div class="futurecrmagent-sales-doc-context-sub" id="futurecrmagent-sales-doc-context-record-line">' + escapeHtml(contextRecordLine()) + '</div>' +
              '</div>' +
              '<div class="futurecrmagent-sales-doc-context-card">' +
                '<div class="futurecrmagent-sales-doc-context-kicker">Active capability</div>' +
                '<div class="futurecrmagent-sales-doc-context-value" id="futurecrmagent-sales-doc-context-capability">' + escapeHtml(contextCapability()) + '</div>' +
                '<div class="futurecrmagent-sales-doc-context-sub">LLM proxy qua FutureAgent settings</div>' +
              '</div>' +
            '</section>' +
            '<section class="futurecrmagent-sales-doc-context-section">' +
              '<h4 class="futurecrmagent-sales-doc-context-title"><i class="fa fa-bolt"></i> Quick Actions</h4>' +
              '<button type="button" class="futurecrmagent-sales-doc-quick" data-quick-action="export"><i class="fa fa-file-pdf-o"></i><span>Export Chat</span></button>' +
              '<button type="button" class="futurecrmagent-sales-doc-quick" data-quick-action="share"><i class="fa fa-link"></i><span>Share Thread Link</span></button>' +
              '<button type="button" class="futurecrmagent-sales-doc-quick" data-quick-action="pin-summary"><i class="fa fa-file-text-o"></i><span>Pin Summary</span></button>' +
            '</section>' +
            '<div class="futurecrmagent-sales-doc-secure"><i class="fa fa-shield"></i><span>Secure Session Active</span></div>' +
          '</aside>' +
        '</div>' +
      '</div>'
    );
  }

  $("body").on("click", "#futurecrmagent-sales-doc-fab", function () {
    syncContextFromLocation();
    openAssistant();
  });

  $(window).on("hashchange", function () {
    syncContextFromLocation();
  });

  $("body").on("click", "#futurecrmagent-sales-doc-close,#futurecrmagent-sales-doc-cancel,#futurecrmagent-sales-doc-overlay", function () {
    closeAssistant();
  });

  $("body").on("keydown", function (event) {
    if (event.key === "Escape" && $("#futurecrmagent-sales-doc-panel").hasClass("is-open")) {
      closeAssistant();
    }
  });

  $("body").on("keydown", "#futurecrmagent-sales-doc-message", function (event) {
    if (event.key === "Enter" && !event.shiftKey) {
      event.preventDefault();
      $("#futurecrmagent-sales-doc-run").trigger("click");
    }
  });

  $("body").on("input", "#futurecrmagent-sales-doc-message", function () {
    this.style.height = "auto";
    this.style.height = Math.min(this.scrollHeight, 120) + "px";
  });

  $("body").on("click", ".futurecrmagent-sales-doc-model", function () {
    $(".futurecrmagent-sales-doc-model").removeClass("active");
    $(this).addClass("active");
  });

  $("body").on("click", ".futurecrmagent-sales-doc-chip", function () {
    var mode = $(this).data("preview-mode");
    var prompt = $(this).data("prompt") || "";
    if (context.mode === "preview" && mode) {
      $('.futurecrmagent-sales-doc-mode[data-preview-mode="' + mode + '"]').trigger("click");
    }
    $("#futurecrmagent-sales-doc-message").val(prompt).trigger("input").focus();
  });

  $("body").on("click", ".futurecrmagent-sales-doc-quick", function () {
    var action = $(this).data("quick-action");
    if (action === "export") {
      exportTranscript();
      return;
    }
    if (action === "share") {
      copyThreadLink();
      return;
    }
    if (action === "pin-summary") {
      $("#futurecrmagent-sales-doc-message").val("/summary Tóm tắt nội dung trao đổi hiện tại và phiếu đang mở").trigger("input").focus();
    }
  });

  $("body").on("click", ".futurecrmagent-sales-doc-mode", function () {
    previewMode = $(this).data("preview-mode") || "qa";
    $(".futurecrmagent-sales-doc-mode").removeClass("active");
    $(this).addClass("active");
    if (previewMode === "action") {
      setStatus("Write actions from preview will create an approval only; nothing is executed immediately.", "info");
    } else {
      setStatus("", "info");
      $("#futurecrmagent-sales-doc-status").removeClass("info success danger").text("");
    }
  });

  function openAssistant() {
    $("#futurecrmagent-sales-doc-panel,#futurecrmagent-sales-doc-overlay").addClass("is-open");
    $("body").addClass("futurecrmagent-sales-doc-modal-open");
    setTimeout(function () {
      $("#futurecrmagent-sales-doc-message").focus();
    }, 60);
  }

  function closeAssistant() {
    $("#futurecrmagent-sales-doc-panel,#futurecrmagent-sales-doc-overlay").removeClass("is-open");
    $("body").removeClass("futurecrmagent-sales-doc-modal-open");
  }

  function syncContextFromLocation() {
    var nextContext = detectContext();
    if (!nextContext.type) {
      return;
    }

    var changed = nextContext.mode !== context.mode || nextContext.type !== context.type || nextContext.id !== context.id;
    context = nextContext;
    if (!changed || !$("#futurecrmagent-sales-doc-panel").length) {
      return;
    }

    $("#futurecrmagent-sales-doc-panel").attr("data-mode", context.mode);
    $("#futurecrmagent-sales-doc-subtitle").text(contextSubtitle());
    $("#futurecrmagent-sales-doc-context-record").text(contextTitle());
    $("#futurecrmagent-sales-doc-context-record-line").text(contextRecordLine());
    $("#futurecrmagent-sales-doc-context-capability").text(contextCapability());
    if ($("#futurecrmagent-sales-doc-thread .futurecrmagent-sales-doc-bubble").length === 1) {
      $("#futurecrmagent-sales-doc-thread .futurecrmagent-sales-doc-bubble").html('<strong>FutureCRM Agent</strong><br>' + escapeHtml(contextIntro()));
    }
  }

  $("body").on("click", "#futurecrmagent-sales-doc-run", function () {
    var message = $.trim($("#futurecrmagent-sales-doc-message").val());
    if (!message) {
      setStatus("Bạn cần nhập nội dung yêu cầu.", "danger");
      return;
    }

    lastMessage = message;
    currentPlan = null;
    currentPreview = null;
    resetConfirmButtons();
    appendUserMessage(message);
    $("#futurecrmagent-sales-doc-message").val("");
    var button = $(this);
    button.prop("disabled", true);

    if (context.mode === "preview") {
      runPreviewAssistant(button, message);
      return;
    }

    setStatus("Đang gửi ngữ cảnh tới LLM proxy của FutureAgent...", "info");

    $.post(admin_url + "futurecrmagent/sales_doc_assist", {
      doc_type: context.type,
      draft_id: context.mode === "edit" ? context.id : 0,
      message: message,
    }).done(function (response) {
      response = normalizeResponse(response);
      if (!response.success) {
        appendAgentMessage('<strong>Không tạo được preview.</strong><br>' + escapeHtml(response.message || "AI chưa tạo được dữ liệu hợp lệ."));
        setStatus(response.message || "AI chưa tạo được dữ liệu hợp lệ.", "danger");
        return;
      }

      currentPlan = response.plan || {};
      appendAgentMessage(renderPreview(currentPlan, response.validation || {}));
      setStatus("Preview đã sẵn sàng. Kiểm tra kỹ trước khi xác nhận.", "success");
      $("#futurecrmagent-sales-doc-actions").addClass("is-visible");
      if (context.mode === "edit") {
        $("#futurecrmagent-sales-doc-apply").removeClass("hide");
      } else {
        $("#futurecrmagent-sales-doc-create-draft,#futurecrmagent-sales-doc-create-open").removeClass("hide");
      }
    }).fail(function () {
      appendAgentMessage("<strong>FutureCRM Agent request failed.</strong>");
      setStatus("FutureCRM Agent request failed.", "danger");
    }).always(function () {
      button.prop("disabled", false);
    });
  });

  function runPreviewAssistant(button, message) {
    var endpoint = endpoints.previewAssist;
    setStatus(previewMode === "action" ? "Đang tạo action plan để gửi phê duyệt..." : "Đang tra cứu ngữ cảnh phiếu...", "info");

    $.post(admin_url + endpoint, {
      doc_type: context.type,
      doc_id: context.id,
      mode: previewMode,
      message: message,
    }).done(function (response) {
      response = normalizeResponse(response);
      if (!response.success) {
        appendAgentMessage('<strong>Không xử lý được yêu cầu.</strong><br>' + escapeHtml(response.message || "FutureCRM Agent chưa tạo được phản hồi hợp lệ."));
        setStatus(response.message || "FutureCRM Agent chưa tạo được phản hồi hợp lệ.", "danger");
        return;
      }

      currentPreview = response.preview || {};
      currentPreview.tool_trace = currentPreview.tool_trace || response.tool_trace || [];
      appendAgentMessage(renderPreviewAssistantResponse(currentPreview, response.validation || {}));
      if (previewMode === "action" && currentPreview.action_plan && currentPreview.action_plan.action_key) {
        $("#futurecrmagent-sales-doc-actions").addClass("is-visible");
        $("#futurecrmagent-sales-doc-submit-approval").removeClass("hide");
        setStatus("Action plan đã sẵn sàng. Bấm Gửi phê duyệt để đưa vào approval queue.", "success");
      } else {
        setStatus("Đã trả lời theo ngữ cảnh phiếu hiện tại.", "success");
      }
    }).fail(function () {
      appendAgentMessage("<strong>FutureCRM Agent preview request failed.</strong>");
      setStatus("FutureCRM Agent preview request failed.", "danger");
    }).always(function () {
      button.prop("disabled", false);
    });
  }

  $("body").on("click", "#futurecrmagent-sales-doc-apply", function () {
    if (!currentPlan) {
      setStatus("Chưa có preview để apply.", "danger");
      return;
    }

    var button = $(this);
    button.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Đang lưu...');
    $.post(admin_url + "futurecrmagent/apply_sales_doc_draft", {
      doc_type: context.type,
      draft_id: context.id,
      plan: JSON.stringify(currentPlan),
    }).done(function (response) {
      response = normalizeResponse(response);
      if (!response.success) {
        setStatus(response.message || "Không lưu được draft.", "danger");
        return;
      }
      setStatus("Draft đã được cập nhật. Trang sẽ reload để lấy dữ liệu mới.", "success");
      setTimeout(function () {
        window.location.reload();
      }, 700);
    }).fail(function () {
      setStatus("Không lưu được draft.", "danger");
    }).always(function () {
      button.prop("disabled", false).html('<i class="fa fa-check"></i> Cập nhật phiếu hiện tại');
    });
  });

  $("body").on("click", "#futurecrmagent-sales-doc-create-draft,#futurecrmagent-sales-doc-create-open", function () {
    if (!currentPlan) {
      setStatus("Chưa có preview để tạo phiếu.", "danger");
      return;
    }

    var button = $(this);
    var targetStatus = button.data("target-status");
    button.prop("disabled", true);
    setStatus(targetStatus === "open" ? "Đang tạo phiếu Open..." : "Đang tạo nháp...", "info");

    $.post(admin_url + "futurecrmagent/create_sales_doc_from_plan", {
      doc_type: context.type,
      draft_id: 0,
      target_status: targetStatus,
      message: lastMessage,
      plan: JSON.stringify(currentPlan),
    }).done(function (response) {
      response = normalizeResponse(response);
      if (!response.success) {
        setStatus(response.message || "Không tạo được phiếu.", "danger");
        return;
      }
      setStatus("Đã tạo phiếu. Đang mở trang chỉnh sửa...", "success");
      window.location.href = response.edit_url;
    }).fail(function () {
      setStatus("Không tạo được phiếu.", "danger");
    }).always(function () {
      button.prop("disabled", false);
    });
  });

  $("body").on("click", "#futurecrmagent-sales-doc-submit-approval", function () {
    if (!currentPreview || !currentPreview.action_plan) {
      setStatus("Chưa có action plan để gửi phê duyệt.", "danger");
      return;
    }

    var button = $(this);
    button.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Đang gửi...');
    $.post(admin_url + endpoints.submitToolActionApproval, {
      doc_type: context.type,
      doc_id: context.id,
      message: lastMessage,
      action_plan: JSON.stringify(currentPreview.action_plan),
    }).done(function (response) {
      response = normalizeResponse(response);
      if (!response.success) {
        setStatus(response.message || "Không tạo được phê duyệt.", "danger");
        return;
      }
      var approvalId = response.approval && response.approval.id ? response.approval.id : "";
      appendAgentMessage('<strong>Đã tạo yêu cầu phê duyệt.</strong><br>Không có dữ liệu CRM nào được ghi ngay từ preview.' + (approvalId ? '<br><span class="text-muted">Approval ID: ' + escapeHtml(approvalId) + '</span>' : ''));
      setStatus("Đã gửi vào approval queue.", "success");
      resetConfirmButtons();
    }).fail(function () {
      setStatus("Không tạo được phê duyệt.", "danger");
    }).always(function () {
      button.prop("disabled", false).html('<i class="fa fa-paper-plane"></i> Gửi phê duyệt');
    });
  });

  function appendUserMessage(message) {
    $("#futurecrmagent-sales-doc-thread").append('<div class="futurecrmagent-sales-doc-bubble user">' + escapeHtml(message) + '</div>');
    scrollThread();
  }

  function appendAgentMessage(html) {
    $("#futurecrmagent-sales-doc-thread").append('<div class="futurecrmagent-sales-doc-bubble agent">' + html + '</div>');
    scrollThread();
  }

  function contextIntro() {
    if (context.mode === "manage") {
      return "Nhập yêu cầu để tạo phiếu mới. Tôi sẽ hiển thị preview trước khi tạo nháp hoặc tạo Open.";
    }
    if (context.mode === "preview") {
      return "Tôi đang đọc ngữ cảnh phiếu preview này. Bạn có thể hỏi/tra cứu, tổng hợp, hoặc đề xuất hành động. Mọi hành động ghi dữ liệu sẽ chỉ tạo phê duyệt.";
    }

    return "Nhập yêu cầu để cập nhật phiếu hiện tại. Tôi sẽ preview trước khi ghi vào draft.";
  }

  function contextSubtitle() {
    if (context.mode === "preview") {
      return "Preview Gateway";
    }
    if (context.mode === "manage") {
      return "Sales Document Creator";
    }

    return "Draft Builder";
  }

  function contextTitle() {
    var names = {
      estimate: "Estimate",
      invoice: "Invoice",
      proposal: "Proposal",
    };
    var label = names[context.type] || "Sales document";
    return context.id ? label + " #" + context.id : label + " workspace";
  }

  function contextRecordLine() {
    if (context.mode === "preview") {
      return "Read-only Q&A plus approval-gated actions";
    }
    if (context.mode === "manage") {
      return "Create Draft or Open after preview confirmation";
    }

    return "Current editable draft context";
  }

  function contextCapability() {
    if (context.mode === "preview") {
      return "Q&A, summary, action approval";
    }
    if (context.mode === "manage") {
      return "Create sales document from text";
    }

    return "Apply AI plan to current draft";
  }

  function scrollThread() {
    var thread = $("#futurecrmagent-sales-doc-thread");
    thread.scrollTop(thread[0].scrollHeight);
  }

  function exportTranscript() {
    var text = $("#futurecrmagent-sales-doc-thread").text().replace(/\n{3,}/g, "\n\n").trim();
    var blob = new Blob([text], { type: "text/plain;charset=utf-8" });
    var url = URL.createObjectURL(blob);
    var link = document.createElement("a");
    link.href = url;
    link.download = "futurecrmagent-chat-" + context.type + (context.id ? "-" + context.id : "") + ".txt";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    setStatus("Transcript exported.", "success");
  }

  function copyThreadLink() {
    var value = window.location.href;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(value).then(function () {
        setStatus("Đã copy link phiên làm việc.", "success");
      }).catch(function () {
        setStatus(value, "info");
      });
      return;
    }
    setStatus(value, "info");
  }

  function renderPreviewAssistantResponse(preview, validation) {
    var html = "";
    if (preview.answer) {
      html += '<div class="futurecrmagent-sales-doc-markdown">' + renderMarkdown(preview.answer) + '</div>';
    }
    if (isTechnicalMode()) {
      html += renderList("Nguồn dữ liệu đã dùng", preview.context_used);
      html += renderList("Trích dẫn", preview.citations);
    }
    html += renderInsightList("Thiếu thông tin", preview.missing_info, "missing", "fa-list-check");
    html += renderInsightList("Rủi ro", preview.risks, "risk", "fa-warning");
    if (preview.analysis_summary && isTechnicalMode()) {
      html += '<div class="futurecrmagent-sales-doc-muted-section"><strong>Analysis summary</strong><div class="futurecrmagent-sales-doc-markdown" style="margin-top:4px">' + renderMarkdown(preview.analysis_summary) + '</div></div>';
    }
    html += renderToolTrace(preview.tool_trace);

    if (preview.action_plan && preview.action_plan.action_key) {
      html += renderActionPlan(preview.action_plan, validation);
    }
    if (!html) {
      html = "Không có phản hồi phù hợp từ FutureCRM Agent.";
    }

    return html;
  }

  function renderToolTrace(trace) {
    trace = trace || [];
    if (!trace.length || !isTechnicalMode()) {
      return "";
    }

    var html = '<div style="margin-top:12px"><strong>Tool trace</strong><div class="futurecrmagent-sales-doc-preview-card">';
    $.each(trace, function (_, entry) {
      html += '<div class="futurecrmagent-sales-doc-preview-row"><span><code>' + escapeHtml(entry.tool || "") + '</code><br><small class="text-muted">' + escapeHtml(entry.mode || "read") + ' · ' + escapeHtml(entry.duration_ms || 0) + 'ms</small></span><span class="text-right">';
      if (entry.summary) {
        html += escapeHtml(JSON.stringify(entry.summary));
      }
      html += '</span></div>';
    });
    html += '</div></div>';
    return html;
  }

  function isTechnicalMode() {
    return $('.futurecrmagent-sales-doc-model[data-agent-profile="technical"]').hasClass("active");
  }

  function renderActionPlan(plan, validation) {
    var html = '<div class="futurecrmagent-sales-doc-action-card">';
    html += '<span class="futurecrmagent-sales-doc-action-key">' + escapeHtml(plan.action_key || "") + '</span>';
    html += '<h5 style="margin:0 0 6px">' + escapeHtml(plan.title || "Action plan") + '</h5>';
    if (plan.description) {
      html += '<p style="margin-bottom:8px">' + escapeHtml(plan.description) + '</p>';
    }
    html += '<div class="text-muted" style="font-size:12px;margin-bottom:8px"><i class="fa fa-lock"></i> Approval required. No CRM write action will run from preview.</div>';
    html += '<pre style="white-space:pre-wrap;max-height:180px;overflow:auto;background:#111827;color:#e5e7eb;border-radius:8px;padding:10px;border:0">' + escapeHtml(JSON.stringify(plan.payload || {}, null, 2)) + '</pre>';
    if (validation.errors && validation.errors.length) {
      html += renderList("Lỗi validation", validation.errors, "text-danger");
    }
    html += '</div>';

    return html;
  }

  function renderPreview(plan, validation) {
    var header = plan.header || {};
    var items = plan.items || [];
    var html = '<p style="margin-bottom:8px;font-weight:600"><i class="fa fa-check-circle text-success"></i> Đã phân tích yêu cầu. Vui lòng kiểm tra dữ liệu:</p>';
    html += '<div class="futurecrmagent-sales-doc-preview-card">';
    html += previewRow("Khách hàng", header.customer_name || (header.clientid ? "Customer #" + header.clientid : "-"));
    html += previewRow("Dự án", header.project_id ? "Project #" + header.project_id : "-");
    html += previewRow("Ngày", header.date || "-");
    html += previewRow("Chiết khấu", formatDiscount(header));
    html += '<div style="padding-top:8px"><span class="text-muted">Mục báo giá:</span>';
    $.each(items, function (index, item) {
      html += '<div class="futurecrmagent-sales-doc-item"><div>' + (index + 1) + ". " + escapeHtml(item.description || "") + " (x" + escapeHtml(item.qty || 1) + ")";
      if (item.long_description) {
        html += '<small>' + escapeHtml(item.long_description) + '</small>';
      }
      html += '</div><strong>' + escapeHtml(formatMoney((item.qty || 1) * (item.rate || 0))) + '</strong></div>';
    });
    html += '</div></div>';
    html += renderList("Thiếu thông tin", plan.missing_fields);
    html += renderList("Cảnh báo", plan.warnings);
    if (validation.errors && validation.errors.length) {
      html += renderList("Lỗi validation", validation.errors, "text-danger");
    }

    return html;
  }

  function previewRow(label, value) {
    return '<div class="futurecrmagent-sales-doc-preview-row"><span class="text-muted">' + escapeHtml(label) + ':</span><strong>' + escapeHtml(value) + '</strong></div>';
  }

  function formatMoney(value) {
    value = Number(value || 0);
    return value.toLocaleString("vi-VN") + " đ";
  }

  function formatDiscount(header) {
    if (header.discount_percent) {
      return header.discount_percent + "% " + (header.discount_type || "");
    }
    if (header.discount_total) {
      return formatMoney(header.discount_total) + " " + (header.discount_type || "");
    }
    return "-";
  }

  function renderList(title, values, klass) {
    values = values || [];
    if (!values.length) {
      return "";
    }
    var html = '<div class="' + (klass || "") + '" style="margin-top:10px"><strong>' + escapeHtml(title) + '</strong><ul class="futurecrmagent-sales-doc-list">';
    $.each(values, function (_, value) {
      var label = formatListValue(value);
      if (label) {
        html += '<li>' + escapeHtml(label) + '</li>';
      }
    });
    html += '</ul></div>';
    return html;
  }

  function renderInsightList(title, values, klass, icon) {
    values = values || [];
    if (!values.length) {
      return "";
    }
    var html = '<div class="futurecrmagent-sales-doc-insight ' + escapeHtml(klass || "") + '">';
    html += '<div class="futurecrmagent-sales-doc-insight-title"><i class="fa ' + escapeHtml(icon || "fa-info-circle") + '"></i><span>' + escapeHtml(title) + '</span></div>';
    html += '<ul>';
    $.each(values, function (_, value) {
      var label = formatListValue(value);
      if (label) {
        html += '<li>' + renderMarkdownInline(label) + '</li>';
      }
    });
    html += '</ul></div>';
    return html;
  }

  function renderMarkdown(value) {
    var text = String(value || "").replace(/\r\n/g, "\n").trim();
    if (!text) {
      return "";
    }

    var html = "";
    var paragraph = [];
    var listType = null;
    var codeBlock = [];
    var inCodeBlock = false;
    var lines = text.split("\n");

    function flushParagraph() {
      if (!paragraph.length) {
        return;
      }
      html += "<p>" + renderMarkdownInline(paragraph.join("\n")).replace(/\n/g, "<br>") + "</p>";
      paragraph = [];
    }

    function flushList() {
      if (!listType) {
        return;
      }
      html += "</" + listType + ">";
      listType = null;
    }

    function flushCodeBlock() {
      if (!inCodeBlock) {
        return;
      }
      html += "<pre><code>" + escapeHtml(codeBlock.join("\n")) + "</code></pre>";
      codeBlock = [];
      inCodeBlock = false;
    }

    $.each(lines, function (_, line) {
      var trimmed = line.trim();

      if (trimmed.indexOf("```") === 0) {
        if (inCodeBlock) {
          flushCodeBlock();
        } else {
          flushParagraph();
          flushList();
          inCodeBlock = true;
          codeBlock = [];
        }
        return;
      }

      if (inCodeBlock) {
        codeBlock.push(line);
        return;
      }

      if (!trimmed) {
        flushParagraph();
        flushList();
        return;
      }

      var heading = trimmed.match(/^(#{1,3})\s+(.+)$/);
      if (heading) {
        flushParagraph();
        flushList();
        html += (heading[1].length > 1 ? "<h5>" : "<h4>") + renderMarkdownInline(heading[2]) + (heading[1].length > 1 ? "</h5>" : "</h4>");
        return;
      }

      var ordered = trimmed.match(/^\d+\.\s+(.+)$/);
      var unordered = trimmed.match(/^[-*]\s+(.+)$/);
      if (ordered || unordered) {
        flushParagraph();
        var nextType = ordered ? "ol" : "ul";
        if (listType && listType !== nextType) {
          flushList();
        }
        if (!listType) {
          html += "<" + nextType + ">";
          listType = nextType;
        }
        html += "<li>" + renderMarkdownInline((ordered || unordered)[1]) + "</li>";
        return;
      }

      if (trimmed.indexOf("> ") === 0) {
        flushParagraph();
        flushList();
        html += "<blockquote>" + renderMarkdownInline(trimmed.substring(2)) + "</blockquote>";
        return;
      }

      flushList();
      paragraph.push(line);
    });

    flushParagraph();
    flushList();
    flushCodeBlock();

    return html;
  }

  function renderMarkdownInline(value) {
    var html = escapeHtml(value);
    html = html.replace(/`([^`]+)`/g, "<code>$1</code>");
    html = html.replace(/\*\*([^*]+)\*\*/g, "<strong>$1</strong>");
    html = html.replace(/__([^_]+)__/g, "<strong>$1</strong>");
    html = html.replace(/(^|[\s(])\*([^*\n]+)\*/g, "$1<em>$2</em>");
    html = html.replace(/(^|[\s(])_([^_\n]+)_/g, "$1<em>$2</em>");
    return html;
  }

  function formatListValue(value) {
    if (typeof value === "boolean" || value === null || typeof value === "undefined") {
      return "";
    }
    if (typeof value === "string" || typeof value === "number") {
      return String(value);
    }
    if ($.isArray(value)) {
      return value.map(formatListValue).filter(Boolean).join(", ");
    }
    if (typeof value === "object") {
      return value.label || value.title || value.source || value.name || value.type || value.table || value.field || "";
    }
    return "";
  }

  function resetConfirmButtons() {
    $("#futurecrmagent-sales-doc-actions").removeClass("is-visible");
    $("#futurecrmagent-sales-doc-apply,#futurecrmagent-sales-doc-create-draft,#futurecrmagent-sales-doc-create-open,#futurecrmagent-sales-doc-submit-approval").addClass("hide");
  }

  function setStatus(message, type) {
    $("#futurecrmagent-sales-doc-status")
      .removeClass("info success danger")
      .addClass(type || "info")
      .text(message);
  }

  function normalizeResponse(response) {
    if (typeof response === "string") {
      try {
        return JSON.parse(response);
      } catch (e) {
        return { success: false, message: response };
      }
    }
    return response || {};
  }

  function escapeHtml(value) {
    return $("<div>").text(value === null || typeof value === "undefined" ? "" : String(value)).html();
  }

  // Kept for compatibility with Perfex transaction helpers loaded on edit pages.
  function applyPlanToForm(plan) {
    if (typeof add_item_to_table === "function" && plan && plan.items) {
      return true;
    }
    return false;
  }
})(jQuery);
