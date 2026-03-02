<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Render debug panel for logs
 */
function render_topics_debug_panel() {
    // Only render if debug panel is enabled
    if (get_option('topics_debug_panel_enabled') != 1) {
        return;
    }

    echo '<div id="topics-debug-panel" class="topics-debug-panel minimized">
        <div class="panel-header">
            <span class="panel-title">Debug Logs</span>
            <div class="panel-controls">
                <button class="btn-refresh" title="Refresh"><i class="fa fa-refresh"></i></button>
                <button class="btn-fullscreen" title="Toggle Fullscreen"><i class="fa fa-expand"></i></button>
                <button class="btn-minimize" title="Minimize"><i class="fa fa-minus"></i></button>
            </div>
        </div>
        <div class="panel-content">
            <div class="log-list active">
                <div class="log-search">
                    <input type="text" class="form-control" placeholder="Search logs...">
                </div>
                <div class="log-files"></div>
            </div>
            <div class="log-viewer">
                <div class="log-viewer-header">
                    <div class="log-search">
                        <input type="text" class="form-control" placeholder="Search in log...">
                        <span class="search-count"></span>
                    </div>
                    <div class="log-viewer-controls">
                        <div class="control-row">
                            <button class="btn-back"><i class="fa fa-arrow-left"></i> Back</button>
                            <button class="btn-refresh-log"><i class="fa fa-refresh"></i> Refresh</button>
                            <button class="btn-clean-log"><i class="fa fa-eraser"></i> Clean</button>
                        </div>
                        <div class="btn-collapse-all-row">
                            <button class="btn-collapse-all"><i class="fa fa-compress"></i> Collapse All</button>
                            <button class="btn-expand-all"><i class="fa fa-expand"></i> Expand All</button>
                        </div>
                    </div>
                </div>
                <pre class="log-content"></pre>
            </div>
        </div>
    </div>';

    // Add debug panel styles
    echo '<style>
        .topics-debug-panel {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 400px;
            height: 500px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }
        .topics-debug-panel.minimized {
            height: 40px;
        }
        .topics-debug-panel .panel-header {
            padding: 8px 12px;
            background: #fff4c9;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .topics-debug-panel .panel-title {
            font-weight: 600;
            color: #333;
        }
        .topics-debug-panel .panel-controls button {
            background: none;
            border: none;
            padding: 4px 8px;
            cursor: pointer;
            color: #666;
        }
        .topics-debug-panel .panel-controls button:hover {
            color: #333;
        }
        .topics-debug-panel .panel-content {
            flex: 1;
            overflow: hidden;
            position: relative;
        }
        .topics-debug-panel .log-list,
        .topics-debug-panel .log-viewer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            padding: 12px;
            display: none;
        }
        .topics-debug-panel .log-list.active,
        .topics-debug-panel .log-viewer.active {
            display: block;
        }
        .topics-debug-panel .log-files {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .topics-debug-panel .log-file-item {
            padding: 8px 12px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .topics-debug-panel .log-file-item:hover {
            background: #e9ecef;
        }
        .topics-debug-panel .btn-back,
        .topics-debug-panel .btn-refresh-log,
        .topics-debug-panel .btn-clean-log {
            // margin-bottom: 12px;
            margin-right: 8px;
            padding: 4px 12px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        .topics-debug-panel .log-content {
            // background: #f8f9fa;
            padding: 12px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .topics-debug-panel .btn-delete-log {
            background: none;
            border: none;
            padding: 4px 8px;
            cursor: pointer;
            opacity: 0.7;
            position: absolute;
            right: 10px;
            top: -25px;
        }
        .topics-debug-panel .btn-delete-log:hover {
            opacity: 1;
        }
        .topics-debug-panel .log-file-actions {
            display: none;
            position: relative;
        }
        .topics-debug-panel .log-file-item:hover .log-file-actions {
            display: block;
        }
        .topics-debug-panel.fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            z-index: 99999;
        }
        .topics-debug-panel .log-search {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .topics-debug-panel .log-viewer-header {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            background: #f8f9fa;
        }
        .topics-debug-panel .log-search {
            margin-bottom: 12px;
        }
        .topics-debug-panel .log-search input {
            width: 100%;
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .topics-debug-panel .log-viewer-controls {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .topics-debug-panel .control-row {
            display: flex;
            gap: 8px;
            justify-content: flex-start;
        }
        .topics-debug-panel .control-row button {
            padding: 6px 12px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            color: #333;
        }
        .topics-debug-panel .control-row button:hover {
            background: #f0f0f0;
        }
        .topics-debug-panel .control-row button i {
            font-size: 12px;
        }
        .topics-debug-panel .btn-back { color: #666; }
        .topics-debug-panel .btn-refresh-log { color: #28a745; }
        .topics-debug-panel .btn-clean-log { color: #dc3545; }
        .topics-debug-panel .btn-collapse-all { color: #0056b3; }
        .topics-debug-panel .btn-expand-all { color: #0056b3; }
        .topics-debug-panel .search-count {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 12px;
        }
        .topics-debug-panel .log-search {
            position: relative;
        }
        .topics-debug-panel .search-highlight {
            background-color: yellow;
            padding: 2px;
            border-radius: 2px;
        }
        .topics-debug-panel .search-current {
            background-color: orange;
        }
        .topics-debug-panel .log-group {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        .topics-debug-panel .log-group.error {
            border-color: #dc3545;
        }
        .topics-debug-panel .log-group.debug {
            border-color: #17a2b8;
        }
        .topics-debug-panel .log-group.info {
            border-color: #28a745;
        }
        .topics-debug-panel .log-group.warning {
            border-color: #ffc107;
            margin-bottom: 15px;
        }
        .topics-debug-panel .warning .log-level {
            background: #ffc107;
            color: #000;
        }
        .topics-debug-panel .warning-header {
            cursor: pointer;
            user-select: none;
        }
        .topics-debug-panel .warning-header:hover {
            background: #f8f9fa;
        }
        .topics-debug-panel .collapse-icon {
            margin-left: auto;
            padding-left: 10px;
        }
        .topics-debug-panel .log-group.collapsed .warning-content {
            display: none;
        }
        .topics-debug-panel .log-group.collapsed .collapse-icon i {
            transform: rotate(-90deg);
        }
        .topics-debug-panel .collapse-icon i {
            transition: transform 0.2s ease;
        }
        .topics-debug-panel .log-header {
            padding: 8px 12px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .topics-debug-panel .log-level {
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .topics-debug-panel .error .log-level {
            background: #dc3545;
            color: white;
        }
        .topics-debug-panel .debug .log-level {
            background: #17a2b8;
            color: white;
        }
        .topics-debug-panel .info .log-level {
            background: #28a745;
            color: white;
        }
        .topics-debug-panel .log-timestamp {
            color: #666;
            font-size: 0.9em;
            padding-left: 15px;
        }
        .topics-debug-panel .log-messages {
            padding: 10px;
        }
        .topics-debug-panel .log-message {
            margin-bottom: 8px;
            line-height: 1.4;
        }
        .topics-debug-panel .log-message:last-child {
            margin-bottom: 0;
        }
        .topics-debug-panel .json {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin: 5px 0;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .topics-debug-panel .log-content {
            padding: 15px;
        }
        .topics-debug-panel .log-raw {
            padding: 15px;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: monospace;
            font-size: 12px;
        }
        .topics-debug-panel .btn-copy-log {
            background: none;
            border: none;
            padding: 4px 8px;
            cursor: pointer;
            opacity: 0.7;
            margin-left: 8px;
            color: #666;
        }
        .topics-debug-panel .btn-copy-log:hover {
            opacity: 1;
            color: #333;
        }
        .topics-debug-panel .log-header {
            position: relative;
        }
        .topics-debug-panel .btn-copy-log {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
        }
        .topics-debug-panel .warning-header .btn-copy-log {
            right: 32px; /* Make space for collapse icon */
        }
        .topics-debug-panel .collapsible-header {
            cursor: pointer;
            user-select: none;
        }
        .topics-debug-panel .collapsible-header:hover {
            background: #f8f9fa;
        }
        .topics-debug-panel .collapse-icon {
            margin-left: auto;
            padding-left: 10px;
            margin-right: 30px;
        }
        .topics-debug-panel .log-group.collapsed .collapsible-content,
        .topics-debug-panel .log-group.collapsed .warning-content {
            display: none;
        }
        .topics-debug-panel .log-group.collapsed .collapse-icon i {
            transform: rotate(-90deg);
        }
        .topics-debug-panel .collapse-icon i {
            transition: transform 0.2s ease;
        }
        .topics-debug-panel .log-header {
            position: relative;
        }
        .topics-debug-panel .btn-copy-log {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
        }
        .topics-debug-panel .btn-collapse-all,
        .topics-debug-panel .btn-expand-all {
            // margin-left: 8px;
            padding: 4px 12px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        .topics-debug-panel .btn-collapse-all:hover,
        .topics-debug-panel .btn-expand-all:hover {
            background: #e9ecef;
        }
        .topics-debug-panel .btn-collapse-all-row {
            display: flex;
            gap: 8px;
            justify-content: flex-start;
        }
        .topics-debug-panel .btn-collapse-all-row button {
            padding: 6px 12px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            color: #333;
        }
        .topics-debug-panel .btn-collapse-all-row button:hover {
            background: #f0f0f0;
        }
    </style>';

    // Add debug panel scripts
    echo '<script>
        $(function() {
            const panel = $("#topics-debug-panel");
            const logList = panel.find(".log-list");
            const logViewer = panel.find(".log-viewer");
            const logFiles = panel.find(".log-files");
            const logContent = panel.find(".log-content");
            let currentLogFile = "";

            // Minimize/Maximize panel
            panel.find(".btn-minimize").click(function() {
                panel.toggleClass("minimized");
                $(this).find("i").toggleClass("fa-minus fa-plus");
            });

            // Refresh log files list
            function refreshLogFiles() {
                $.ajax({
                    url: admin_url + "topics/get_log_files",
                    type: "GET",
                    success: function(response) {
                        try {
                            const files = JSON.parse(response);
                            logFiles.empty();
                            
                            files.forEach(file => {
                                logFiles.append(`
                                    <div class="log-file-item" data-file="${file.name}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fa fa-file-text-o"></i>
                                                ${file.name}
                                                <small class="text-muted">${file.size}</small>
                                            </div>
                                            <div class="log-file-actions">
                                                <button class="btn-delete-log" data-file="${file.name}" title="Delete log">
                                                    <i class="fa fa-trash text-danger"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `);
                            });
                        } catch (e) {
                            console.error("Error parsing log files:", e);
                        }
                    }
                });
            }

            // Refresh button click
            panel.find(".btn-refresh").click(refreshLogFiles);

            // View log file
            logFiles.on("click", ".log-file-item", function() {
                currentLogFile = $(this).data("file");
                $.ajax({
                    url: admin_url + "topics/get_log_content",
                    type: "GET",
                    data: { file: currentLogFile },
                    success: function(response) {
                        logContent.html(response);
                        logList.removeClass("active");
                        logViewer.addClass("active");
                        // Restore collapse states
                        restoreCollapseStates();
                    }
                });
            });

            // Back button click
            panel.find(".btn-back").click(function() {
                logViewer.removeClass("active");
                logList.addClass("active");
            });

            // Refresh log content
            panel.find(".btn-refresh-log").click(function() {
                if (!currentLogFile) return;
                
                $.ajax({
                    url: admin_url + "topics/get_log_content",
                    type: "GET",
                    data: { file: currentLogFile },
                    success: function(response) {
                        logContent.html(response);
                    }
                });
            });

            // Delete log file
            logFiles.on("click", ".btn-delete-log", function(e) {
                e.stopPropagation();
                const file = $(this).data("file");
                
                if (confirm("Are you sure you want to delete this log file?")) {
                    $.ajax({
                        url: admin_url + "topics/delete_log_file",
                        type: "POST",
                        data: { file: file },
                        success: function(response) {
                            try {
                                const result = JSON.parse(response);
                                if (result.success) {
                                    alert_float("success", result.message);
                                    refreshLogFiles();
                                } else {
                                    alert_float("danger", result.message);
                                }
                            } catch (e) {
                                console.error("Error parsing response:", e);
                                alert_float("danger", "Error deleting log file");
                            }
                        },
                        error: function() {
                            alert_float("danger", "Error deleting log file");
                        }
                    });
                }
            });

            // Fullscreen toggle
            panel.find(".btn-fullscreen").click(function() {
                panel.toggleClass("fullscreen");
                $(this).find("i").toggleClass("fa-expand fa-compress");
            });

            // Search in log files list
            const logSearchInput = panel.find(".log-list .log-search input");
            logSearchInput.on("input", function() {
                const searchTerm = $(this).val().toLowerCase();
                panel.find(".log-file-item").each(function() {
                    const fileName = $(this).data("file").toLowerCase();
                    $(this).toggle(fileName.includes(searchTerm));
                });
            });

            // Search in log content
            const logContentSearchInput = panel.find(".log-viewer .log-search input");
            let currentHighlightIndex = -1;
            let highlights = [];

            function highlightSearchTerms(searchTerm) {
                if (!searchTerm) {
                    // Lấy lại nội dung gốc từ server
                    if (currentLogFile) {
                        $.ajax({
                            url: admin_url + "topics/get_log_content", 
                            type: "GET",
                            data: { file: currentLogFile },
                            success: function(response) {
                                logContent.html(response);
                                panel.find(".search-count").text("");
                                highlights = [];
                                currentHighlightIndex = -1;
                            }
                        });
                    }
                    return;
                }

                // Clone nội dung hiện tại để tìm kiếm
                const $content = logContent.clone();
                
                // Tìm text nodes trong log messages
                const walker = document.createTreeWalker(
                    $content[0],
                    NodeFilter.SHOW_TEXT,
                    {
                        acceptNode: function(node) {
                            // Chỉ tìm trong các text nodes không phải là JSON
                            if ($(node).closest(\'pre.json\').length === 0) {
                                return NodeFilter.FILTER_ACCEPT;
                            }
                            return NodeFilter.FILTER_REJECT;
                        }
                    }
                );

                const matches = [];
                const regex = new RegExp(searchTerm, \'gi\');
                
                // Tìm và highlight text
                let node;
                while (node = walker.nextNode()) {
                    let match;
                    while ((match = regex.exec(node.textContent)) !== null) {
                        const range = document.createRange();
                        range.setStart(node, match.index);
                        range.setEnd(node, match.index + match[0].length);
                        
                        const span = document.createElement(\'span\');
                        span.className = \'search-highlight\';
                        span.setAttribute(\'data-index\', matches.length);
                        range.surroundContents(span);
                        
                        matches.push(span);
                        
                        // Update walker to continue from new position
                        walker.currentNode = span;
                    }
                }

                // Update DOM with highlighted content
                logContent.html($content.html());
                highlights = panel.find(".search-highlight");
                currentHighlightIndex = highlights.length > 0 ? 0 : -1;

                // Update count
                panel.find(".search-count").text(
                    highlights.length > 0 ? 
                    `${currentHighlightIndex + 1}/${highlights.length} matches` : 
                    "No matches"
                );

                // Scroll to first match
                if (currentHighlightIndex >= 0) {
                    highlights.removeClass("search-current");
                    const current = highlights.eq(currentHighlightIndex);
                    current.addClass("search-current");
                    current[0].scrollIntoView({
                        behavior: "smooth",
                        block: "center"
                    });
                }
            }

            // Handle search input
            logContentSearchInput.on("input", function() {
                highlightSearchTerms($(this).val());
            });

            // Handle enter key to move to next match
            logContentSearchInput.on("keydown", function(e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    if (highlights.length > 0) {
                        highlights.removeClass("search-current");
                        currentHighlightIndex = (currentHighlightIndex + 1) % highlights.length;
                        const current = highlights.eq(currentHighlightIndex);
                        current.addClass("search-current");
                        current[0].scrollIntoView({
                            behavior: "smooth",
                            block: "center"
                        });
                        panel.find(".search-count").text(
                            `${currentHighlightIndex + 1}/${highlights.length} matches`
                        );
                    }
                }
            });

            // Clear search when switching files
            panel.find(".btn-back").click(function() {
                logContentSearchInput.val("");
                highlightSearchTerms("");
            });

            // Clean log file
            panel.find(".btn-clean-log").click(function() {
                if (!currentLogFile) return;
                
                if (confirm("Are you sure you want to clean this log file? A backup will be created.")) {
                    $.ajax({
                        url: admin_url + "topics/clean_log_file",
                        type: "POST",
                        data: { file: currentLogFile },
                        success: function(response) {
                            try {
                                const result = JSON.parse(response);
                                if (result.success) {
                                    alert_float("success", result.message);
                                    // Refresh log content
                                    $.ajax({
                                        url: admin_url + "topics/get_log_content",
                                        type: "GET",
                                        data: { file: currentLogFile },
                                        success: function(response) {
                                            logContent.html(response);
                                            // Clear search if any
                                            panel.find(".log-viewer .log-search input").val("");
                                            panel.find(".search-count").text("");
                                        }
                                    });
                                } else {
                                    alert_float("danger", result.message);
                                }
                            } catch (e) {
                                console.error("Error parsing response:", e);
                                alert_float("danger", "Error cleaning log file");
                            }
                        },
                        error: function() {
                            alert_float("danger", "Error cleaning log file");
                        }
                    });
                }
            });

            // Handle collapse/expand for all log groups
            logContent.on("click", ".collapsible-header, .warning-header", function(e) {
                if ($(e.target).closest(\'.btn-copy-log\').length) {
                    return;
                }
                
                const group = $(this).closest(\'.log-group\');
                group.toggleClass(\'collapsed\');
                
                // Save state to localStorage
                const fileKey = currentLogFile + \'_collapsed\';
                const states = JSON.parse(localStorage.getItem(fileKey) || \'{}\');
                const stateKey = group.find(\'.log-timestamp\').text() + \'_\' + group.find(\'.log-level\').text();
                states[stateKey] = group.hasClass(\'collapsed\');
                localStorage.setItem(fileKey, JSON.stringify(states));
            });

            // Restore collapse states when viewing log
            function restoreCollapseStates() {
                if (!currentLogFile) return;
                
                const fileKey = currentLogFile + \'_collapsed\';
                const states = JSON.parse(localStorage.getItem(fileKey) || \'{}\');
                
                panel.find(\'.log-group.collapsible\').each(function() {
                    const group = $(this);
                    const stateKey = group.find(\'.log-timestamp\').text() + \'_\' + group.find(\'.log-level\').text();
                    if (states[stateKey]) {
                        group.addClass(\'collapsed\');
                    } else {
                        group.removeClass(\'collapsed\');
                    }
                });
            }

            // Handle copy log content
            logContent.on("click", ".btn-copy-log", function(e) {
                e.stopPropagation(); // Prevent triggering collapse for warnings
                
                const group = $(this).closest(\'.log-group\');
                const rawContent = group.find(\'.log-raw-content\').text();
                
                // Create temporary textarea
                const textarea = document.createElement(\'textarea\');
                textarea.value = rawContent;
                document.body.appendChild(textarea);
                
                // Select and copy
                textarea.select();
                try {
                    document.execCommand(\'copy\');
                    alert_float(\'success\', \'Log content copied to clipboard\');
                } catch (err) {
                    alert_float(\'danger\', \'Failed to copy log content\');
                    console.error(\'Failed to copy:\', err);
                }
                
                // Cleanup
                document.body.removeChild(textarea);
            });

            // Handle collapse/expand all buttons
            panel.find(\'.btn-collapse-all\').click(function() {
                const groups = panel.find(\'.log-group.collapsible\');
                groups.addClass(\'collapsed\');
                
                // Save all states
                const states = {};
                groups.each(function() {
                    const group = $(this);
                    const stateKey = group.find(\'.log-timestamp\').text() + \'_\' + group.find(\'.log-level\').text();
                    states[stateKey] = true;
                });
                localStorage.setItem(currentLogFile + \'_collapsed\', JSON.stringify(states));
            });

            panel.find(\'.btn-expand-all\').click(function() {
                const groups = panel.find(\'.log-group.collapsible\');
                groups.removeClass(\'collapsed\');
                
                // Save all states
                const states = {};
                groups.each(function() {
                    const group = $(this);
                    const stateKey = group.find(\'.log-timestamp\').text() + \'_\' + group.find(\'.log-level\').text();
                    states[stateKey] = false;
                });
                localStorage.setItem(currentLogFile + \'_collapsed\', JSON.stringify(states));
            });

            // Initial load
            refreshLogFiles();
        });
    </script>';
}