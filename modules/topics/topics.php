<?php
defined('BASEPATH') or exit('No direct script access allowed');


/*
Module Name: Topics Management
Description: Module for managing topics with action types and states tracking
Version: 1.3.3
Author: FHC
Author URI: https://chantroituonglai.com
Requires at least: 2.3.*

Note about CSS:
- The module uses a common CSS file (assets/css/topics.css) for shared styles
- This file contains reusable styles for tables, buttons, labels, etc.
- When adding new views or components, please add related styles to this file
- The file is automatically loaded on all module views via register_topics_module_assets()
- Group related styles and add comments for better maintainability
*/

define('TOPICS_MODULE_NAME', 'topics');

// Register language files early to ensure keys like 'topics' are available when menus/hooks run
register_language_files(TOPICS_MODULE_NAME, [
    TOPICS_MODULE_NAME,
    'draft_writer',
    'controllers',
    'ultimate_editor',
]);

// Load project sync helper
require_once(__DIR__ . '/helpers/topics_project_sync_helper.php');

/**
 * Register module assets
 */
function register_topics_module_assets() {
    $CI = &get_instance();
    
    // Only load assets on topics module pages
    if (!is_topics_module_page()) {
        return;
    }
    
    // Move Tailwind from head to footer
    hooks()->add_action('app_admin_head', function(){
        // Add module's common CSS file with cache-busting version parameter
        $version = '?v=' . uniqid();
        echo '<link href="' . module_dir_url(TOPICS_MODULE_NAME, 'assets/css/topics.css') . $version . '" rel="stylesheet" type="text/css" />';
        
        // Add online status dot CSS
        echo '<style>
            .online-status-dot {
                position: absolute;
                bottom: 2px;
                right: 2px;
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background-color: #28a745;
                border: 2px solid #fff;
                display: none;
            }
            .is-online .online-status-dot {
                display: block;
            }
            .staff-profile-image-small {
                position: relative;
            }
        </style>';
    });

    // Add jsonrepair.min.js and json_fixer to footer
    hooks()->add_action('app_admin_footer', function(){
        $version = '?v=' . uniqid();
        echo '<script src="' . module_dir_url(TOPICS_MODULE_NAME, 'assets/js/jsonrepair.min.js') . $version . '"></script>';
        // echo '<script src="' . module_dir_url(TOPICS_MODULE_NAME, 'assets/js/topics.js') . $version . '"></script>';
        
        
        
        // Add JSON Fixer helper script
        echo '<script>
            window.onerror = function(message, source, lineno, colno, error) {
                if (typeof message.includes === "function" && message.includes("circleProgress is not a function")) {
                    // console.warn("Ignoring circleProgress error:", message);
                    return true; // Prevents the default error handling.
                }
                return false;
            };
            function fixJson(json, silent = true) {
                try {
                    // First try using JsonFixer helper
                    const fixedJson = window.jsonrepair(json);
                    return fixedJson;
                } catch (e) {
                    if (!silent) {
                        console.error("JSON fix failed:", e);
                        throw e;
                    }
                    return json;
                }
            }
            // Xử lý lỗi circleProgress
            (function($) {
                // Kiểm tra nếu circleProgress không tồn tại, tạo một hàm giả để tránh lỗi
                if (typeof $.fn.circleProgress !== "function") {
                    $.fn.circleProgress = function(options) {
                        // console.warn("circleProgress plugin not loaded, using fallback");
                        return this; // Trả về đối tượng jQuery để có thể chain
                    };
                }
            })(jQuery);
        </script>';

        // Load all language strings into JavaScript
        load_topics_language_js();

        // Add online tracking JavaScript
        if (get_option('topics_online_tracking_enabled')) {
            echo '<script>
                function updateTopicOnlineStatus() {
                    const topicid = $("#topicid").val();
                    if (!topicid) return;
                    
                    $.ajax({
                        url: admin_url + "topics/update_online_status",
                        type: "POST",
                        data: {
                            topicid: topicid,
                        },
                        success: function(response) {
                            try {
                                const data = JSON.parse(response);
                                if (data.success && data.online_staff) {
                                    updateOnlineStatusDots(data.online_staff);
                                }
                            } catch (e) {
                                console.error("Error parsing response:", e);
                            }
                        }
                    });
                }

                function updateOnlineStatusDots(onlineStaff) {
                    // Reset all status dots first
                    $(".staff-profile-image-small").removeClass("is-online");
                    
                    // Update status for online staff
                    onlineStaff.forEach(function(staff) {
                        $(`.staff-profile-image-small[data-staff-id="${staff.staffid}"]`)
                            .addClass("is-online");
                    });
                }

                // Add status dot to all profile images
                $(".staff-profile-image-small").each(function() {
                    if (!$(this).find(".online-status-dot").length) {
                        $(this).append("<span class=\"online-status-dot\"></span>");
                    }
                });

                // Update every 30 seconds if enabled
                if ($("#topicid").length) {
                    setInterval(updateTopicOnlineStatus, 30000);
                    updateTopicOnlineStatus(); // Initial update
                    
                    // Update when leaving page
                    $(window).on("beforeunload", function() {
                        $.ajax({
                            url: admin_url + "topics/remove_online_status",
                            type: "POST",
                            async: false,
                            data: {
                                topicid: $("#topicid").val(),
                            }
                        });
                    });
                }
            </script>';
        }

        // Add global notification handler for topics module
        if (get_option('pusher_realtime_notifications') == 1) {
            // Get Pusher options first
            $pusher_options = hooks()->apply_filters('pusher_options', [['disableStats' => true]]);
            if (!isset($pusher_options['cluster']) && get_option('pusher_cluster') != '') {
                $pusher_options['cluster'] = get_option('pusher_cluster');
            }
            
            echo '<script>
            $(function() {
                // Language variables
                var langTopicUpdatedNotification = "' . _l('topic_updated_notification') . '";
                
                // Global notification handler for topics module
                function initTopicsNotificationHandler() {
                
                    var channel = pusher.subscribe("notifications-channel-' . get_staff_user_id() . '");
                    
                    channel.bind("notification", function(data) {
                        console.log("Topics Module - Received notification:", data);
                        
                        try {
                            if (data && data.additional_data) {
                                // Format base message
                                var baseMessage = `${langTopicUpdatedNotification} ${data.description.split("[")[0].trim()} [Type: ${
                                    data.additional_data.action_type_name || data.additional_data.action_type_code || ""
                                }, State: ${
                                    data.additional_data.action_state_name || data.additional_data.action_state_code || ""
                                }]`;

                                // Check if we are on a page with refresh functionality
                                var hasRefreshButton = $("#refresh-history").length > 0;
                                var hasProgressSteps = typeof refreshProgressSteps === "function";
                                
                                // Build absolute URL for topic
                                var absoluteUrl = admin_url + data.link;
                                
                                // If we have refresh functionality and this is current topic
                                if ((hasRefreshButton || hasProgressSteps) && 
                                    data.additional_data.topicid === $("#topicid").val()) {
                                    console.log("Found refresh functionality for current topic");
                                    
                                    // Refresh available components
                                    if (hasRefreshButton) {
                                        console.log("Triggering history refresh");
                                        $("#refresh-history").trigger("click");
                                    }
                                    if (hasProgressSteps) {
                                        console.log("Refreshing progress steps");
                                        refreshProgressSteps();
                                    }
                                    
                                    // Update last update time if element exists
                                    var lastUpdateElement = document.getElementById("last-update-time");
                                    if (lastUpdateElement) {
                                        lastUpdateElement.textContent = moment(data.date).format("DD/MM/YYYY HH:mm:ss");
                                    }
                                    
                                    // Update connection status if element exists
                                    var statusIcon = document.getElementById("connection-status-icon");
                                    if (statusIcon) {
                                        statusIcon.style.color = "green";
                                        statusIcon.title = "Connected - Last update: " + moment(data.date).format("DD/MM/YYYY HH:mm:ss");
                                    }
                                    
                                    // Show notification without link
                                    //alert_float("info", baseMessage, 5000);
                                } else {
                                    // Show notification with absolute URL link
                                    console.log("Notification for different topic:", absoluteUrl);
                                    var messageWithLink = `${baseMessage} - <a href="${absoluteUrl}" class="tw-text-primary hover:tw-text-primary-600">Click to view</a>`;
                                    console.log("Showing notification with absolute URL:", absoluteUrl);
                                    alert_float("info", messageWithLink, 7000);
                                }
                            }
                        } catch (e) {
                            console.error("Error processing topics notification:", e);
                            console.error("Raw notification data:", data);
                        }
                    });

                    // Log connection status if debugging
                    pusher.connection.bind("state_change", function(states) {
                        console.log("Topics Module - Pusher connection state changed:", states);
                    });

                    pusher.connection.bind("error", function(err) {
                        console.error("Topics Module - Pusher connection error:", err);
                    });
                }

                // Initialize handler
                if (typeof pusher === "undefined") {
                    console.log("Pusher not available");
                    var pusher_options = ' . json_encode($pusher_options) . ';
                    var pusher = new Pusher("' . get_option('pusher_app_key') . '", pusher_options);
                }
                if (pusher.connection.state !== "connected") {
                    pusher.connect();
                }
                if (!window.location.href.includes("topics/detail")) {
                    initTopicsNotificationHandler();
                }
            });
            </script>';
        }

        // Add AlertManager utility
        echo '<script>
            const AlertManager = {
                queue: [],
                isProcessing: false,
                timeout: 5000,
                offset: 15,
                maxVisibleAlerts: 3, // Maximum number of visible alerts

                createAlertElement: function(type, message) {
                    const alertDiv = $(`<div class="float-alert alert alert-${type} alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        ${message}
                    </div>`);
                    
                    $("body").append(alertDiv);
                    return alertDiv;
                },

                addAlert: function(type, message, customTimeout) {
                    this.queue.push({
                        type: type,
                        message: message,
                        timeout: customTimeout || this.timeout
                    });
                    
                    if (!this.isProcessing) {
                        this.processQueue();
                    }
                },

                processQueue: function() {
                    if (this.queue.length === 0) {
                        this.isProcessing = false;
                        return;
                    }

                    this.isProcessing = true;
                    const alert = this.queue.shift();
                    const visibleAlerts = $(".float-alert:visible").length;

                    if (visibleAlerts < this.maxVisibleAlerts) {
                        const $alertElement = this.createAlertElement(alert.type, alert.message);
                        
                        // Set position at the bottom
                        $alertElement.css({
                            "position": "fixed",
                            "bottom": `${visibleAlerts * (this.offset + $alertElement.outerHeight()) + 20}px`,
                            "right": "25px",
                            "z-index": "999999",
                            "transition": "all 0.3s ease"
                        });

                        // Auto remove after timeout
                        setTimeout(() => {
                            $alertElement.fadeOut(400, function() {
                                $(this).remove();
                                AlertManager.processQueue(); // Process next alert when one is removed
                            });
                        }, alert.timeout);
                    } else {
                        // Re-add to queue if max visible alerts are reached
                        this.queue.unshift(alert);
                    }

                    // Continue processing if there are more alerts
                    if (this.queue.length > 0) {
                        setTimeout(() => {
                            this.processQueue();
                        }, 1000); // Check every second
                    } else {
                        this.isProcessing = false;
                    }
                }
            };

            // Override default alert_float to use AlertManager
            window._originalAlertFloat = window.alert_float;
            window.alert_float = function(type, message, timeout) {
                AlertManager.addAlert(type, message, timeout);
            };
        </script>';

        // Add float-alert styling
        echo '<style>
            .float-alert {
                position: fixed !important;
                right: 25px;
                z-index: 999999;
                transition: all 0.3s ease;
                max-width: 24rem;
                display: inline-block;
                top: unset !important;
            }
        </style>';

        // Add Tailwind CSS at the very end (highest priority)
        // echo '<script src="https://cdn.tailwindcss.com"></script>';
        // echo '<script>
        // tailwind.config = {
        //     prefix: "tw-",
        //     important: "#tailwind-scope",
        //     corePlugins: {
        //         preflight: false,
        //     },
        //     theme: {
        //         extend: {
        //             colors: {
        //                 primary: "#28a745",
        //                 secondary: "#6c757d",
        //                 success: "#28a745",
        //                 danger: "#dc3545",
        //                 warning: "#ffc107",
        //                 info: "#17a2b8"
        //             }
        //         }
        //     }
        // }
        // </script>';
        render_topics_debug_panel();
    }, PHP_INT_MAX);
}

/**
 * Check if current page is topics module page
 */
function is_topics_module_page() {
    $CI = &get_instance();
    $class = $CI->router->fetch_class();
    $module_pages = ['topics', 'action_types', 'action_states', 'topic_master', 'ultimate_editor'];
    return in_array($class, $module_pages);
}

/**
 * Kiểm tra và khởi tạo module
 */
function init_topics_module() {
    $CI = &get_instance();
    
    // Load hooks file trước
    require_once(__DIR__ . '/hooks.php');
    
    // Load helpers
    require_once(__DIR__ . '/helpers/topics_setup_helper.php');
    require_once(__DIR__ . '/helpers/json_fixer_helper.php'); // Add JSON Fixer helper
    require_once(__DIR__ . '/helpers/topics_display_processor_helper.php');
    require_once(__DIR__ . '/helpers/api_helper.php');
    require_once(__DIR__ . '/helpers/topic_action_processor_helper.php');
    require_once(__DIR__ . '/helpers/topic_action_processor_SocialMediaPostActionProcessor_helper.php');
    require_once(__DIR__ . '/helpers/topic_action_processor_WordPressPostActionProcessor_helper.php');
    require_once(__DIR__ . '/helpers/topic_action_processor_WordPressPostSelectionProcessor_helper.php');
    require_once(__DIR__ . '/helpers/topic_action_processor_InitGooglesheetRawItemProcessor_helper.php');
    require_once(__DIR__ . '/helpers/topic_action_processor_ImageGenerateToggleProcessor_helper.php');
    require_once(__DIR__ . '/helpers/topic_action_processor_TopicComposerProcessor_helper.php');
    require_once(__DIR__ . '/helpers/topic_action_processor_DraftWritingProcessor_helper.php');
    require_once(__DIR__ . '/helpers/ultimate_editor_action_processor_helper.php');
    require_once(__DIR__ . '/helpers/ultimate_editor_action_processor_GenericProcessor_helper.php');
    require_once(__DIR__ . '/helpers/ultimate_editor_action_processor_ImageGenerateToggle_helper.php');
    require_once(__DIR__ . '/helpers/topic_platform_helper.php'); // Add Platform Helper
    // Register assets
    register_topics_module_assets();
    
    // Kiểm tra các bảng cần thiết vi prefix
    $required_tables = [
        db_prefix() . 'topics',
        db_prefix() . 'topic_action_types', 
        db_prefix() . 'topic_action_states'
    ];
    
    $tables_exist = true;
    
    foreach ($required_tables as $table) {
        if (!$CI->db->table_exists($table)) {
            $tables_exist = false;
            log_message('debug', 'Topics Module: Table ' . $table . ' does not exist');
            break;
        }
    }
    
    // Nếu chưa có bảng, tạo mới
    if (!$tables_exist) {
        log_message('info', 'Topics Module: Installing required tables...');
        require_once(__DIR__ . '/install.php');
    }

    // Đăng ký ngôn ngữ
    register_language_files(TOPICS_MODULE_NAME, [
        TOPICS_MODULE_NAME,
        'draft_writer',
        'controllers',
        // 'categories',
        // 'blogs',
        // 'tags',
        'ultimate_editor'
    ]);

    // Ensure language keys available immediately for early hooks/permissions
    $language = $CI->session->userdata('language');
    if (!$language) { $language = 'english'; }
    $CI->lang->load('topics/topics', $language);
    $CI->lang->load('topics/controllers', $language);
    $CI->lang->load('topics/draft_writer', $language);
    $CI->lang->load('topics/ultimate_editor', $language);
    if ($language !== 'english') {
        $CI->lang->load('topics/topics', 'english');
        $CI->lang->load('topics/controllers', 'english');
        $CI->lang->load('topics/draft_writer', 'english');
        $CI->lang->load('topics/ultimate_editor', 'english');
    }
    
    // Đăng ký các hooks
    register_topics_hooks();
    
    // Đăng ký permissions
    $capabilities = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];
    
    register_staff_capabilities(TOPICS_MODULE_NAME, $capabilities, _l('topics'));
    
    // Đăng ký API endpoints nếu có

    // Add online tracking tables to required tables
    $required_tables[] = db_prefix() . 'staff_online_status';

}

/**
 * Đăng ký các hooks cần thiết
 */
function register_topics_hooks() {
    // Remove any existing hooks first to prevent duplicates
    hooks()->remove_action('admin_init', 'topics_admin_init_menu');
    
    // Hook cho menu admin
    hooks()->add_action('admin_init', 'topics_admin_init_menu', 5);
    
    // Hook cho cron job
    hooks()->add_action('after_cron_run', 'topics_module_cron_job');
}

/**
 * Hook xử lý cron job
 */
function topics_module_cron_job() {
    $CI = &get_instance();
    
    // Thực hiện các tác vụ cron
    // ...
}

/**
 * Loads all language strings from the language files and outputs them as JavaScript variables
 * This makes all language strings available in JavaScript through the app.lang object
 */
function load_topics_language_js() {
    $CI = &get_instance();
    $language_files = [
        'topics_lang',
        'draft_writer_lang',
        'controllers_lang',
        'ultimate_editor_lang'
    ];
    
    // Start the JavaScript output
    echo '<script>
    // Initialize app.lang if it doesn\'t exist
    console.log("Loading language strings...");
    if (typeof app === "undefined") {
        window.app = {};
    }
    if (!app.lang) {
        app.lang = {};
    }
    
    // Load all language strings into app.lang
';
    
    // Loop through each language file
    foreach ($language_files as $file) {
        $CI = &get_instance();
        $language = $CI->session->userdata('language'); 
        // Get the language file path
        $lang_path = FCPATH . 'modules/' . TOPICS_MODULE_NAME . '/language/' . $language . '/' . $file . '.php';
        $en_lang_path = FCPATH . 'modules/' . TOPICS_MODULE_NAME . '/language/english/' . $file . '.php';
        
        // Initialize the $lang array to store language strings
        $lang = [];
        
        // Try to load the current language file, fall back to English if not found
        if (file_exists($lang_path)) {
            include $lang_path;
        } elseif (file_exists($en_lang_path)) {
            include $en_lang_path;
        }
        
        // Output each language string as a JavaScript variable
        if (!empty($lang)) {
            foreach ($lang as $key => $value) {
                // Escape any quotes to avoid JavaScript errors
                $escaped_value = addslashes($value);
                echo '    app.lang.' . $key . ' = "' . $escaped_value . '";' . PHP_EOL;
            }
        }
    }
    
    // Close the JavaScript tag
    echo '</script>';
}

// Khởi tạo module
init_topics_module();

// Register settings
add_option('topics_online_tracking_enabled', 1);
add_option('topics_online_timeout', 900);
add_option('topics_debug_panel_enabled', 0); // 0 = disabled by default

require_once(__DIR__ . '/helpers/logs_viewers_helper.php');