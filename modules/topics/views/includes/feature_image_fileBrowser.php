<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=2" />
    <title>Feature Image Selector</title>
    <!-- jQuery and jQuery UI -->
    <script src="<?php echo base_url('assets/plugins/jquery/jquery.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/plugins/jquery-ui/jquery-ui.min.js'); ?>"></script>
    <!-- elFinder JS -->
    <script src="<?php echo base_url('assets/plugins/elFinder/js/elfinder.min.js?v=' . get_app_version()); ?>"></script>
    <?php 
    $mediaLocale = get_media_locale();
    if ($mediaLocale != 'en' && file_exists(FCPATH . 'assets/plugins/elFinder/js/i18n/elfinder.' . $mediaLocale . '.js')) { ?>
    <script src="<?php echo base_url('assets/plugins/elFinder/js/i18n/elfinder.' . $mediaLocale . '.js?v=' . get_app_version()); ?>"></script>
    <?php } ?>
    
    <script>
        var site_url = '<?php echo site_url(); ?>';
        
        // Tạo một đối tượng custom để xử lý callback khi chọn file
        var FeatureImageBrowser = {
            init: function() {
                // Khởi tạo
            },
            // Hàm này sẽ được gọi khi người dùng chọn một file
            selectFile: function(file) {
                // Gửi dữ liệu về trang chính thông qua postMessage API
                window.opener.postMessage({
                    messageType: 'fileSelected',
                    file: file
                }, window.location.origin);
                
                // Đóng cửa sổ popup sau khi chọn xong
                setTimeout(function() {
                    window.close();
                }, 300);
            }
        };
    </script>
</head>

<body>
    <div id="elfinder"></div>
    
    <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/plugins/elFinder/themes/Material/css/theme-gray.css?v=' . get_app_version()); ?>">
    <script src="//cdnjs.cloudflare.com/ajax/libs/require.js/2.3.2/require.min.js"></script>
    <script>
        define('elFinderConfig', {
            // elFinder options
            defaultOpts: {
                onlyMimes: ['image'], // Chỉ cho phép chọn hình ảnh
                url: '<?php echo admin_url('utilities/media_connector') . '?editor=true'; ?>', // connector URL
                commandsOptions: {
                    edit: {
                        extraOptions: {
                            creativeCloudApiKey: '',
                            managerUrl: ''
                        }
                    },
                    quicklook: {
                        googleDocsMimes: ['application/pdf', 'image/tiff', 'application/vnd.ms-office']
                    }
                },
                // bootCallback thực thi trước khi elFinder được khởi động
                bootCallback: function(fm, extraObj) {
                    // Binding events
                    fm.bind('init', function() {
                        // Khởi tạo
                    });
                }
            },
            managers: {
                'elfinder': {}
            }
        });
        
        define('returnVoid', void 0);
        
        (function() {
            var elver = '<?php echo elFinder::getApiFullVersion(); ?>',
                jqver = '3.2.1',
                uiver = '1.12.1',
                
                // Khởi động elFinder
                start = function(elFinder, editors, config) {
                    // Tải CSS của jQueryUI
                    elFinder.prototype.loadCss('//cdnjs.cloudflare.com/ajax/libs/jqueryui/' + uiver + '/themes/smoothness/jquery-ui.css');
                    
                    $(function() {
                        var elfEditorCustomData = {};
                        if (typeof(csrfData) !== 'undefined') {
                            elfEditorCustomData[csrfData['token_name']] = csrfData['hash'];
                        }
                        
                        var optEditors = {
                            commandsOptions: {
                                edit: {
                                    editors: Array.isArray(editors) ? editors : []
                                }
                            }
                        },
                        opts = {
                            height: 700,
                            customData: elfEditorCustomData,
                            // Đây là hàm quan trọng nhất - được gọi khi người dùng chọn một file
                            getFileCallback: function(file, fm) {
                                // Gọi hàm selectFile từ FeatureImageBrowser của chúng ta
                                FeatureImageBrowser.selectFile(file);
                            },
                            contextmenu: {
                                files: [
                                    'getfile', '|', 'open', 'quicklook', '|', 'download', '|',
                                    'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'edit', 'rename'
                                ]
                            },
                            ui: ['toolbar', 'tree', 'path', 'stat'],
                            uiOptions: {
                                // Cấu hình toolbar
                                toolbar: [
                                    ['back', 'forward'],
                                    ['mkdir', 'mkfile', 'upload'],
                                    ['open', 'download', 'getfile'],
                                    ['quicklook'],
                                    ['copy', 'paste'],
                                    ['rm'],
                                    ['duplicate', 'rename', 'edit'],
                                    ['search'],
                                    ['view'],
                                    ['info'],
                                ]
                            }
                        };
                        
                        // Xử lý cấu hình elFinder
                        if (config && config.managers) {
                            $.each(config.managers, function(id, mOpts) {
                                opts = Object.assign(opts, config.defaultOpts || {});
                                
                                try {
                                    mOpts.commandsOptions.edit.editors = mOpts.commandsOptions.edit.editors.concat(editors || []);
                                } catch (e) {
                                    Object.assign(mOpts, optEditors);
                                }
                                
                                // Khởi tạo elFinder
                                $('#' + id).elfinder(
                                    $.extend(true, {
                                        lang: '<?php echo $mediaLocale; ?>'
                                    }, opts, mOpts || {}),
                                    function(fm, extraObj) {
                                        // Callback khi init
                                        fm.bind('init', function() {
                                            // Init code
                                        });
                                    }
                                );
                            });
                        } else {
                            console.error('"elFinderConfig" object is wrong.');
                        }
                    });
                },
                
                // JavaScript loader
                load = function() {
                    require(
                        [
                            'elfinder', 
                            'extras/editors.default',
                            'elFinderConfig'
                        ],
                        start,
                        function(error) {
                            alert(error.message);
                        }
                    );
                },
                
                // Check IE8
                ie8 = (typeof window.addEventListener === 'undefined' && typeof document.getElementsByClassName === 'undefined');
            
            // RequireJS config
            require.config({
                baseUrl: site_url + 'assets/plugins/elFinder/js',
                paths: {
                    'jquery': '//cdnjs.cloudflare.com/ajax/libs/jquery/' + (ie8 ? '1.12.4' : jqver) + '/jquery.min',
                    'jquery-ui': '//cdnjs.cloudflare.com/ajax/libs/jqueryui/' + uiver + '/jquery-ui.min',
                    'elfinder': 'elfinder.min',
                },
                waitSeconds: 10
            });
            
            // Tải JavaScript
            load();
        })();
    </script>
</body>
</html> 