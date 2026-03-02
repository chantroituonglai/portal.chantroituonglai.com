<?php defined("BASEPATH") or exit("No direct script access allowed"); ?>
<script>
 // Thêm vào window.TopicComposer.handlers
 function initTopicComposerHandlers() {
    window.TopicComposer.handlers = {   
     initAIHandlers: function() {
        // Handle AI Edit
        $('.summary-actions .ai-edit-btn').on('click', function() {
            const $btn = $(this);
            const $summary = $('#topic-summary');
            const $wrapper = $summary.closest('.summary-wrapper');
            
            // Disable button và show loading
            $btn.prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i>');
            
            // Add processing class
            $wrapper.addClass('ai-processing');
            
            // Disable summary editing
            $summary.prop('contenteditable', false);

            // Show prompt selection modal
            window.TopicComposer.handlers.showPromptSelectionModal($summary.html(), 'summary', function(output) {
                // Update content
                $summary.html(output);
                
                // Mark as changed
                window.TopicComposer.handlers.markAsChanged();
                
                // Show success feedback
                window.TopicComposer.handlers.showFieldFeedback($summary, '<?php echo _l(
                    "ai_edit_success"
                ); ?>', 'success');

                // Re-enable button và restore icon
                $btn.prop('disabled', false)
                    .html('<i class="fa fa-magic"></i>');
                    
                // Remove processing class
                $wrapper.removeClass('ai-processing');
                
                // Re-enable summary editing
                $summary.prop('contenteditable', true);
            }, $(this));
        });
     },
     initControllerHandlers: function() {
        window.TopicComposer.handlers.loadTopicComposerControllers();
        // Handle controller selection change
        $(document).on('change', '#topic-composer-controller-select', function() {
            const controllerId = $(this).val();
            
            // Clear previous controller info
            $('#topic-composer-controller-info').hide();
            
            // Remove the summary from the header
            $('.controller-selection-section .panel-title a').find('.selected-controller-summary').remove();
            
            if (!controllerId) {
                // No controller selected, clear the global state
                if (window.TopicComposer) {
                    window.TopicComposer.selectedController = null;
                }
                return;
            }
            
            // Show loading indicator
            $('#topic-composer-controller-info').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading controller information...</div>').show();
            
            $.ajax({
                url: admin_url + 'topics/get_controller_info',
                type: 'GET',
                data: { controller_id: controllerId },
                success: function(response) {
                    console.log('get_controller_info response', response);
                    
                    try {
                        // Check if response is already an object
                        if (typeof response === 'string') {
                            response = JSON.parse(response);
                        }
                        
                        if (response.success && response.data) {
                            const controllerData = response.data;
                            window.TopicComposer.handlers.showTopicComposerControllerInfo(controllerData);
                            
                            // Save selected controller to global state
                            window.TopicComposer = window.TopicComposer || {};
                            window.TopicComposer.selectedController = controllerData;
                        } else {
                            $('#topic-composer-controller-info').html('<div class="alert alert-warning">Failed to load controller information: ' + (response.message || 'Unknown error') + '</div>').show();
                            
                            $.notify({
                                message: response.message || 'Failed to load controller information'
                            }, {
                                type: 'warning',
                                delay: 3000
                            });
                        }
                    } catch (e) {
                        console.error('Error parsing controller info response:', e);
                        $('#topic-composer-controller-info').html('<div class="alert alert-danger">Error parsing server response</div>').show();
                        
                        $.notify({
                            message: 'Error parsing server response'
                        }, {
                            type: 'danger',
                            delay: 3000
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading controller info:', error);
                    console.error('Status:', status);
                    console.error('Response:', xhr.responseText);
                    
                    $('#topic-composer-controller-info').html('<div class="alert alert-danger">Error loading controller information: ' + (error || 'Unknown error') + '</div>').show();
                    
                    $.notify({
                        message: 'Error loading controller information: ' + (error || 'Unknown error')
                    }, {
                        type: 'danger',
                        delay: 3000
                    });
                }
            });
        });
     },
     quickSaveConfig: function(field) {
         let $input, value;
         let quickSaveConfig = {
             field: field
         };
         
         if (field === 'summary') {
             $input = $('#topic-summary');
             value = $input.html().trim();
         } else {
             $input = $('#topic-name');
             value = $input.val().trim();
         }

         quickSaveConfig.value = value;
         
         const $btn = field === 'summary' ? 
             $('.summary-save-btn') : 
             $input.closest('.input-group').find('.quick-save-btn');

         const $feedback = $input.siblings('.save-feedback');
         if (!$feedback.length) {
             $input.after('<div class="save-feedback"></div>');
         }

         if (!value) {
             $input.addClass('is-invalid');
             window.TopicComposer.handlers.showFieldFeedback($input, '<?php echo _l(
                 "field_required"
             ); ?>', 'error');
             return;
         }

         // Show loading state
         $btn.prop('disabled', true)
             .html('<i class="fa fa-spinner fa-spin"></i>');
         window.TopicComposer.handlers.showFieldFeedback($input, '<?php echo _l(
             "saving"
         ); ?>...', 'info');

         // Update all items with new value
         window.TopicComposer.items = window.TopicComposer.items.map(item => ({
             ...item,
             [field === 'summary' ? 'Summary' : 'Topic']: value
         }));

         // Prepare submission data
         const submissionData = {
             all_items: window.TopicComposer.items,
             quickSaveConfig: quickSaveConfig,
             updated_items: [],
             added_items: [],
             deleted_items: []
         };

         // Prepare workflow data
         const workflowData = {
             ...window.currentWorkflowData,
            audit_step: 5,
            changes_data: submissionData,
         };

         // Execute workflow
         executeWorkflow(workflowData).then(function(response) {
             try {
                 if (response.success) {
                     // Update original items
                     window.TopicComposer.originalItems = JSON.parse(JSON.stringify(window.TopicComposer.items));
                     
                     // Show success feedback
                     window.TopicComposer.handlers.showFieldFeedback($input, '<?php echo _l(
                         "saved_successfully"
                     ); ?>', 'success');
                     
                     // Remove invalid state
                     $input.removeClass('is-invalid');
                 } else {
                     window.TopicComposer.handlers.showFieldFeedback($input, response.message || '<?php echo _l(
                         "save_failed"
                     ); ?>', 'error');
                 }
             } catch (e) {
                 console.error('Error in quick save:', e);
                 window.TopicComposer.handlers.showFieldFeedback($input, '<?php echo _l(
                     "save_failed"
                 ); ?>', 'error');
                   // Restore button state
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i>');
             }
         }).catch(function(error) {
             console.error('Error executing workflow:', error);
             window.TopicComposer.handlers.showFieldFeedback($input, '<?php echo _l(
                 "save_failed"
             ); ?>', 'error');

               // Restore button state
               $btn.prop('disabled', false).html('<i class="fa fa-save"></i>');
         });
     },
     loadItemEditor: function(index) {
            originalLoadItemEditor.call(this, index);
            
            const $editor = $('#item-editor');
            if ($editor.length) {
                setTimeout(() => {
                    scrollToComposerElement($editor, 60);
                }, 100);
            }
    },
    saveItemChanges: function() {
        const result = originalSaveItemChanges.apply(this, arguments);
        
        if (result && window.TopicComposer.handlers.afterSaveItem) {
            window.TopicComposer.handlers.afterSaveItem(window.TopicComposer.currentEditingIndex);
        }
        
        return result;
    },
    afterSaveItem: function(index) {
        const $savedItem = $(`.list-group-item[data-index="${index}"]`);
        if ($savedItem.length) {
            $savedItem.addClass('save-highlight');
            setTimeout(() => {
                $savedItem.removeClass('save-highlight');
            }, 2000);

            scrollToComposerElement($savedItem, 20);
        }
    },
     /**
     * Mark as changed
     */
    markAsChanged: function() {
        window.TopicComposer.hasChanges = true;
        // Visual feedback
        $('.form-actions').addClass('has-changes');
    },
    // Helper function to show feedback
    showFieldFeedback: function($input, message, type = 'info') {
        const $feedback = $input.siblings('.save-feedback');
        const colors = {
            success: '#28a745',
            error: '#dc3545', 
            info: '#17a2b8'
        };

        $feedback
            .html(message)
            .css({
                color: colors[type],
                fontSize: '12px',
                marginTop: '5px'
            })
            .fadeIn();

        // Auto hide success/info messages after 3s
        if (type !== 'error') {
            setTimeout(() => {
                $feedback.fadeOut();
            }, 3000);
        }
    },
    quickSetPosition: function(index) {
        // Get position in list (1-based index)
        const position = index + 1;
        
        // Set position value
        const $input = $('.item-edit-form input[name="position"]');
        const newPosition = `Top ${position}`;
        
        // Update input
        $input.val(newPosition);
        
        // Mark as changed
        this.markAsChanged();
        
        // Show feedback
        this.showFieldFeedback($input, '<?php echo _l(
            "position_updated"
        ); ?>', 'success');
    },
    downloadImageToServer: function(imageUrl, $btn, callback) {
            console.log('downloadImageToServer', imageUrl, $btn);
            // Disable button and show loading if button exists
            if ($btn) {
                $btn.prop('disabled', true)
                    .html('<i class="fa fa-spinner fa-spin"></i>');
            }

            // Prepare workflow data
            const workflowData = {
                ...window.currentWorkflowData,
                audit_step: 6,
                changes_data: {
                    image_url: imageUrl
                }
            };

            // Execute workflow
            executeWorkflow(workflowData).then(function(response) {
                try {
                    if (response.success) {
                        // Update UI to show downloaded state if button exists
                        if ($btn) {
                            $btn.closest('.image-actions')
                                .html(`
                                    <div class="btn-group btn-group-xs">
                                        <button type="button" 
                                                class="btn btn-info find-similar-btn" 
                                                data-url="${imageUrl}" 
                                                title="<?php echo _l(
                                                    "find_similar_images"
                                                ); ?>">
                                            <i class="fa fa-search"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-success download-image-btn"
                                                data-url="${imageUrl}" 
                                                disabled
                                                title="<?php echo _l(
                                                    "download_to_server"
                                                ); ?>">
                                            <i class="fa fa-download"></i>
                                        </button>
                                    </div>
                                    <i class="fa fa-check text-success ml-2" 
                                       title="<?php echo _l(
                                           "image_already_downloaded"
                                       ); ?>"></i>
                                `);

                            // Add click handler for find similar images
                            $btn.closest('.image-actions').find('.find-similar-btn').click(function() {
                                if (!$(this).prop('disabled')) {
                                    findSimilarImages(imageUrl);
                                }
                            });

                            // Show success message when button exists
                            alert_float('success', response.message);
                        }
                        
                        // Find the image element that was downloaded
                        // First try to find it by looking at the parent container of the button
                        let $imgElement = $btn ? $btn.closest('.image-item').find('img') : null;
                        
                        // If not found, try to find by URL
                        if (!$imgElement || !$imgElement.length) {
                            $imgElement = $('img[src="' + imageUrl + '"]');
                        }
                        
                        // If found the image element
                        if ($imgElement && $imgElement.length) {
                            // Add the data-original-url attribute to the image
                            $imgElement.attr('data-original-url', imageUrl);
                            
                            // Make sure the image has a parent with class 'image-item'
                            if (!$imgElement.closest('.image-item').length) {
                                $imgElement.wrap('<div class="image-item"></div>');
                            }
                            
                            // Add a caption if it doesn't exist
                            if (!$imgElement.closest('.image-item').find('.caption').length) {
                                const filename = response.data && response.data.filename ? 
                                    response.data.filename : 'Downloaded image';
                                $imgElement.closest('.image-item').append(
                                    $('<div class="caption"></div>').text(filename)
                                );
                            }
                            
                            // Thêm URL ảnh vào cache để không tải lại
                            if (typeof window.downloadedImages === 'undefined') {
                                window.downloadedImages = new Set();
                            }
                            window.downloadedImages.add(imageUrl);
                            
                            // Find the item containing this image and update its badge
                            if (typeof updateImageBadgesForItem === 'function') {
                                updateImageBadgesForItem(imageUrl).catch(err => {
                                    console.error('Error updating image badges:', err);
                                });
                            } else {
                                console.warn('updateImageBadgesForItem function not found');
                            }
                            
                            // If the prompt selection modal is open, refresh the images list
                            if ($('#prompt-selection-modal').length && typeof loadUploadedImages === 'function') {
                                loadUploadedImages();
                            }
                            
                            // Refresh items list to update undownloaded images badge
                            if (typeof refreshItemsList === 'function') {
                                refreshItemsList().catch(err => console.error('Error refreshing items list:', err));
                            } else if (typeof window.TopicComposer === 'object' && 
                                      typeof window.TopicComposer.handlers === 'object' && 
                                      typeof window.TopicComposer.handlers.refreshItemsList === 'function') {
                                window.TopicComposer.handlers.refreshItemsList();
                            }
                        }
                        
                        // Call callback with success status if provided
                        if (typeof callback === 'function') {
                            callback(true);
                        }
                    } else {
                        throw new Error(response.message);
                    }
                } catch (e) {
                    console.error('Error processing download response:', e);
                    
                    // Only show error alert if button exists
                    if ($btn) {
                        alert_float('danger', '<?php echo _l(
                            "error_downloading_image"
                        ); ?>');
                        
                        // Reset button state
                        $btn.prop('disabled', false)
                            .html('<i class="fa fa-download"></i>');
                    }
                    
                    // Call callback with failure status if provided
                    if (typeof callback === 'function') {
                        callback(false);
                    }
                }
            }).catch(function(error) {
                console.error('Error executing workflow:', error);
                
                // Only show error alert if button exists
                if ($btn) {
                    alert_float('danger', '<?php echo _l(
                        "error_downloading_image"
                    ); ?>');
                    
                    // Reset button state
                    $btn.prop('disabled', false)
                        .html('<i class="fa fa-download"></i>');
                }
                
                // Call callback with failure status if provided
                if (typeof callback === 'function') {
                    callback(false);
                }
            });
        },
    md5: function(str) {
        var xl;
        var rotateLeft = function (lValue, iShiftBits) {
            return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
        };
        var addUnsigned = function (lX, lY) {
            var lX4, lY4, lX8, lY8, lResult;
            lX8 = (lX & 0x80000000);
            lY8 = (lY & 0x80000000);
            lX4 = (lX & 0x40000000);
            lY4 = (lY & 0x40000000);
            lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
            if (lX4 & lY4) {
                return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
            }
            if (lX4 | lY4) {
                if (lResult & 0x40000000) {
                    return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
                } else {
                    return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
                }
            } else {
                return (lResult ^ lX8 ^ lY8);
            }
        };
        var _F = function (x, y, z) {
            return (x & y) | ((~x) & z);
        };
        var _G = function (x, y, z) {
            return (x & z) | (y & (~z));
        };
        var _H = function (x, y, z) {
            return (x ^ y ^ z);
        };
        var _I = function (x, y, z) {
            return (y ^ (x | (~z)));
        };
        var _FF = function (a, b, c, d, x, s, ac) {
            a = addUnsigned(a, addUnsigned(addUnsigned(_F(b, c, d), x), ac));
            return addUnsigned(rotateLeft(a, s), b);
        };
        var _GG = function (a, b, c, d, x, s, ac) {
            a = addUnsigned(a, addUnsigned(addUnsigned(_G(b, c, d), x), ac));
            return addUnsigned(rotateLeft(a, s), b);
        };
        var _HH = function (a, b, c, d, x, s, ac) {
            a = addUnsigned(a, addUnsigned(addUnsigned(_H(b, c, d), x), ac));
            return addUnsigned(rotateLeft(a, s), b);
        };
        var _II = function (a, b, c, d, x, s, ac) {
            a = addUnsigned(a, addUnsigned(addUnsigned(_I(b, c, d), x), ac));
            return addUnsigned(rotateLeft(a, s), b);
        };
        var convertToWordArray = function (str) {
            var lWordCount;
            var lMessageLength = str.length;
            var lNumberOfWords_temp1 = lMessageLength + 8;
            var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
            var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
            var lWordArray = new Array(lNumberOfWords - 1);
            var lBytePosition = 0;
            var lByteCount = 0;
            while (lByteCount < lMessageLength) {
                lWordCount = (lByteCount - (lByteCount % 4)) / 4;
                lBytePosition = (lByteCount % 4) * 8;
                lWordArray[lWordCount] = (lWordArray[lWordCount] | (str.charCodeAt(lByteCount) << lBytePosition));
                lByteCount++;
            }
            lWordCount = (lByteCount - (lByteCount % 4)) / 4;
            lBytePosition = (lByteCount % 4) * 8;
            lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
            lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
            lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
            return lWordArray;
        };
        var wordToHex = function (lValue) {
            var wordToHexValue = "",
                wordToHexValue_temp = "",
                lByte, lCount;
            for (lCount = 0; lCount <= 3; lCount++) {
                lByte = (lValue >>> (lCount * 8)) & 255;
                wordToHexValue_temp = "0" + lByte.toString(16);
                wordToHexValue = wordToHexValue + wordToHexValue_temp.substr(wordToHexValue_temp.length - 2, 2);
            }
            return wordToHexValue;
        };
        var x = [],
            k, AA, BB, CC, DD, a, b, c, d, S11 = 7,
            S12 = 12,
            S13 = 17,
            S14 = 22,
            S21 = 5,
            S22 = 9,
            S23 = 14,
            S24 = 20,
            S31 = 4,
            S32 = 11,
            S33 = 16,
            S34 = 23,
            S41 = 6,
            S42 = 10,
            S43 = 15,
            S44 = 21;
        x = convertToWordArray(str);
        a = 0x67452301;
        b = 0xEFCDAB89;
        c = 0x98BADCFE;
        d = 0x10325476;
        xl = x.length;
        for (k = 0; k < xl; k += 16) {
            AA = a;
            BB = b;
            CC = c;
            DD = d;
            a = _FF(a, b, c, d, x[k + 0], S11, 0xD76AA478);
            d = _FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
            c = _FF(c, d, a, b, x[k + 2], S13, 0x242070DB);
            b = _FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
            a = _FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);
            d = _FF(d, a, b, c, x[k + 5], S12, 0x4787C62A);
            c = _FF(c, d, a, b, x[k + 6], S13, 0xA8304613);
            b = _FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
            a = _FF(a, b, c, d, x[k + 8], S11, 0x698098D8);
            d = _FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);
            c = _FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
            b = _FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
            a = _FF(a, b, c, d, x[k + 12], S11, 0x6B901122);
            d = _FF(d, a, b, c, x[k + 13], S12, 0xFD987193);
            c = _FF(c, d, a, b, x[k + 14], S13, 0xA679438E);
            b = _FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
            a = _GG(a, b, c, d, x[k + 1], S21, 0xF61E2562);
            d = _GG(d, a, b, c, x[k + 6], S22, 0xC040B340);
            c = _GG(c, d, a, b, x[k + 11], S23, 0x265E5A51);
            b = _GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);
            a = _GG(a, b, c, d, x[k + 5], S21, 0xD62F105D);
            d = _GG(d, a, b, c, x[k + 10], S22, 0x2441453);
            c = _GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
            b = _GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
            a = _GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);
            d = _GG(d, a, b, c, x[k + 14], S22, 0xC33707D6);
            c = _GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
            b = _GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
            a = _GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
            d = _GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8);
            c = _GG(c, d, a, b, x[k + 7], S23, 0x676F02D9);
            b = _GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
            a = _HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942);
            d = _HH(d, a, b, c, x[k + 8], S32, 0x8771F681);
            c = _HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122);
            b = _HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
            a = _HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44);
            d = _HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9);
            c = _HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60);
            b = _HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
            a = _HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6);
            d = _HH(d, a, b, c, x[k + 0], S32, 0xEAA127FA);
            c = _HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085);
            b = _HH(b, c, d, a, x[k + 6], S34, 0x4881D05);
            a = _HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039);
            d = _HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5);
            c = _HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8);
            b = _HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
            a = _II(a, b, c, d, x[k + 0], S41, 0xF4292244);
            d = _II(d, a, b, c, x[k + 7], S42, 0x432AFF97);
            c = _II(c, d, a, b, x[k + 14], S43, 0xAB9423A7);
            b = _II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
            a = _II(a, b, c, d, x[k + 12], S41, 0x655B59C3);
            d = _II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92);
            c = _II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D);
            b = _II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
            a = _II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F);
            d = _II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0);
            c = _II(c, d, a, b, x[k + 6], S43, 0xA3014314);
            b = _II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
            a = _II(a, b, c, d, x[k + 4], S41, 0xF7537E82);
            d = _II(d, a, b, c, x[k + 11], S42, 0xBD3AF235);
            c = _II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB);
            b = _II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
            a = addUnsigned(a, AA);
            b = addUnsigned(b, BB);
            c = addUnsigned(c, CC);
            d = addUnsigned(d, DD);
        }
        var temp = wordToHex(a) + wordToHex(b) + wordToHex(c) + wordToHex(d);
        return temp.toLowerCase();
    },
    generateBulkTitlesModal: function() {
        console.log('generateBulkTitlesModal');
        $('body').append(`
            <div class="modal fade" id="batch-titles-modal"  style="z-index: 10001;">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title"><?php echo _l(
                                "batch_generate_titles"
                            ); ?></h4>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label><?php echo _l("word_limit"); ?></label>
                                <input type="number" class="form-control" id="batch-title-word-limit" value="12" min="5" max="20">
                                <small class="text-muted"><?php echo _l(
                                    "recommended_between_5_20"
                                ); ?></small>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <input type="checkbox" id="batch-title-return-html">
                                    <label for="batch-title-return-html"><?php echo _l(
                                        "return_html"
                                    ); ?></label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><?php echo _l("language"); ?></label>
                                <select class="form-control selectpicker" id="batch-title-language" data-live-search="true">
                                    <option value="vi" data-content="<span class='flag-icon flag-icon-vn'></span> Vietnamese">Vietnamese</option>
                                    <option value="en" data-content="<span class='flag-icon flag-icon-us'></span> English">English</option>
                                    <option value="zh" data-content="<span class='flag-icon flag-icon-cn'></span> Chinese">Chinese</option>
                                    <option value="th" data-content="<span class='flag-icon flag-icon-th'></span> Thai">Thai</option>
                                    <option value="ja" data-content="<span class='flag-icon flag-icon-jp'></span> Japanese">Japanese</option>
                                    <option value="ko" data-content="<span class='flag-icon flag-icon-kr'></span> Korean">Korean</option>
                                    <option value="fr" data-content="<span class='flag-icon flag-icon-fr'></span> French">French</option>
                                    <option value="de" data-content="<span class='flag-icon flag-icon-de'></span> German">German</option>
                                    <option value="es" data-content="<span class='flag-icon flag-icon-es'></span> Spanish">Spanish</option>
                                    <option value="ru" data-content="<span class='flag-icon flag-icon-ru'></span> Russian">Russian</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo _l(
                                    "items_to_process"
                                ); ?></label>
                                <div class="radio">
                                    <input type="radio" id="process-all-items" name="items-to-process" value="all" checked>
                                    <label for="process-all-items"><?php echo _l(
                                        "all_items"
                                    ); ?></label>
                                </div>
                                <div class="radio">
                                    <input type="radio" id="process-selected-items" name="items-to-process" value="selected">
                                    <label for="process-selected-items"><?php echo _l(
                                        "selected_items"
                                    ); ?></label>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> <?php echo _l(
                                    "batch_titles_info"
                                ); ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l(
                                "close"
                            ); ?></button>
                            <button type="button" class="btn btn-primary start-batch-generation"><?php echo _l(
                                "start_generation"
                            ); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        // Xử lý khi click vào nút Batch Generate Titles
        $('.batch-generate-titles-btn').on('click', function() {
            $('#batch-titles-modal').modal('show');
        });

        // Xử lý khi click vào nút Start Generation
        $('.start-batch-generation').on('click', function() {
            // Lấy cài đặt từ modal
            const wordLimit = parseInt($('#batch-title-word-limit').val()) || 12;
            const returnHtml = $('#batch-title-return-html').is(':checked');
            const processType = $('input[name="items-to-process"]:checked').val();
            const language = $('#batch-title-language').val() || 'en';
            
            // Khởi tạo danh sách items cần xử lý
            let itemsToProcess = [];
            
            if (processType === 'all') {
                // Lấy tất cả items
                itemsToProcess = window.TopicComposer.items.map((item, index) => ({
                    item: item,
                    index: index
                }));
            } else {
                // Lấy các items đã chọn
                $('.item-checkbox:checked').each(function() {
                    const index = $(this).closest('.list-group-item').data('index');
                    itemsToProcess.push({
                        item: window.TopicComposer.items[index],
                        index: index
                    });
                });
                
                // Kiểm tra nếu không có item nào được chọn
                if (itemsToProcess.length === 0) {
                    alert_float('warning', '<?php echo _l(
                        "please_select_at_least_one_item"
                    ); ?>');
                    return;
                }
            }
            
            // Cập nhật biến toàn cục
            window.batchTitleGenerator.items = itemsToProcess;
            window.batchTitleGenerator.currentIndex = 0;
            window.batchTitleGenerator.wordLimit = wordLimit;
            window.batchTitleGenerator.returnHtml = returnHtml;
            window.batchTitleGenerator.language = language;
            window.batchTitleGenerator.results = [];
            window.batchTitleGenerator.isProcessing = true;
            
            // Đóng modal
            $('#batch-titles-modal').modal('hide');
                
                // Hiển thị progress bar
                showBatchProgressBar(itemsToProcess.length);
                
                // Bắt đầu xử lý item đầu tiên
                processNextItem();
        });

        $('#batch-title-language').selectpicker();
     },
    generateBulkContentModal: function() {
        console.log('generateBulkContentModal');
        $('body').append(
            `
        <!-- Bulk Edit Content Modal -->
        <div class="modal fade" id="bulk-content-modal" data-backdrop="false" style="z-index: 10002;">
             <div class="modal-dialog" style="z-index: 10002;">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title"><?php echo _l(
                            "bulk_edit_content"
                        ); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label><?php echo _l("content_style"); ?></label>
                            <select class="form-control" id="content-style">
                                <option value="detailed"><?php echo _l(
                                    "detailed"
                                ); ?></option>
                                <option value="concise"><?php echo _l(
                                    "concise"
                                ); ?></option>
                                <option value="creative"><?php echo _l(
                                    "creative"
                                ); ?></option>
                                <option value="professional"><?php echo _l(
                                    "professional"
                                ); ?></option>
                                <option value="conversational"><?php echo _l(
                                    "conversational"
                                ); ?></option>
                                <option value="storytelling"><?php echo _l(
                                    "storytelling"
                                ); ?></option>
                                <option value="technical"><?php echo _l(
                                    "technical"
                                ); ?></option>
                                <option value="academic"><?php echo _l(
                                    "academic"
                                ); ?></option>
                                <option value="persuasive"><?php echo _l(
                                    "persuasive"
                                ); ?></option>
                                <option value="instructional"><?php echo _l(
                                    "instructional"
                                ); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo _l("language"); ?></label>
                            <select class="form-control selectpicker" id="content-language" data-live-search="true">
                               <option value="vi" data-content="<span class='flag-icon flag-icon-vn'></span> Vietnamese">Vietnamese</option>   
                                 <option value="en" data-content="<span class='flag-icon flag-icon-us'></span>   English">English</option>
                                <option value="zh" data-content="<span class='flag-icon flag-icon-cn'></span> Chinese">Chinese</option>
                                <option value="th" data-content="<span class='flag-icon flag-icon-th'></span> Thai">Thai</option>
                                <option value="ja" data-content="<span class='flag-icon flag-icon-jp'></span> Japanese">Japanese</option>
                                <option value="ko" data-content="<span class='flag-icon flag-icon-kr'></span> Korean">Korean</option>
                                <option value="fr" data-content="<span class='flag-icon flag-icon-fr'></span> French">French</option>
                                <option value="de" data-content="<span class='flag-icon flag-icon-de'></span> German">German</option>
                                <option value="es" data-content="<span class='flag-icon flag-icon-es'></span> Spanish">Spanish</option>
                                <option value="ru" data-content="<span class='flag-icon flag-icon-ru'></span> Russian">Russian</option>
                            </select>
                        </div>
                        
                        <!-- Custom Prompt Section -->
                        <div class="form-group">
                            <label><?php echo _l(
                                "custom_instructions"
                            ); ?></label>
                            <textarea class="form-control" id="custom-content-instructions" rows="3" placeholder="<?php echo _l(
                                "enter_custom_instructions"
                            ); ?>"></textarea>
                            <small class="text-muted"><?php echo _l(
                                "custom_instructions_desc"
                            ); ?></small>
                        </div>
                        
                        <!-- Controller Information Section -->
                        <div class="form-group" id="bulk-controller-info-section" style="display: none;">
                            <label><?php echo _l("controller_information"); ?></label>
                            <div class="alert alert-info">
                                <i class="fa fa-cog"></i> <span id="bulk-controller-name">No controller selected</span>
                                <a href="#" class="pull-right" id="bulk-controller-details-toggle" data-toggle="tooltip" title="Click to view details">
                                    <i class="fa fa-info-circle"></i>
                                </a>
                            </div>
                            <div id="bulk-controller-details" class="well well-sm" style="display: none;">
                                <div id="bulk-controller-action1"></div>
                                <div id="bulk-controller-action2" class="mtop10"></div>
                            </div>
                        </div>
                        
                        <!-- Content Optimization Options -->
                        <div class="form-group">
                            <label><?php echo _l(
                                "optimization_options"
                            ); ?></label>
                            <div class="checkbox">
                                <input type="checkbox" id="opt-seo">
                                <label for="opt-seo"><?php echo _l(
                                    "optimize_for_seo"
                                ); ?></label>
                            </div>
                            <div class="checkbox">
                                <input type="checkbox" id="opt-remove-external">
                                <label for="opt-remove-external"><?php echo _l(
                                    "remove_external_links"
                                ); ?></label>
                            </div>
                            <div class="checkbox">
                                <input type="checkbox" id="opt-paragraph-length">
                                <label for="opt-paragraph-length"><?php echo _l(
                                    "optimize_paragraph_length"
                                ); ?></label>
                            </div>
                            <div class="checkbox">
                                <input type="checkbox" id="opt-subheadings">
                                <label for="opt-subheadings"><?php echo _l(
                                    "add_subheadings"
                                ); ?></label>
                            </div>
                            <div class="checkbox">
                                <input type="checkbox" id="opt-call-to-action">
                                <label for="opt-call-to-action"><?php echo _l(
                                    "add_call_to_action"
                                ); ?></label>
                            </div>
                            <div class="checkbox">
                                <input type="checkbox" id="opt-insert-images">
                                <label for="opt-insert-images"><?php echo _l(
                                    "insert_images_to_item"
                                ); ?></label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><?php echo _l("items_to_process"); ?></label>
                            <div class="radio">
                                <input type="radio" id="content-process-all-items" name="content-items-to-process" value="all" checked>
                                <label for="content-process-all-items"><?php echo _l(
                                    "all_items"
                                ); ?></label>
                            </div>
                            <div class="radio">
                                <input type="radio" id="content-process-selected-items" name="content-items-to-process" value="selected">
                                <label for="content-process-selected-items"><?php echo _l(
                                    "selected_items"
                                ); ?></label>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> <?php echo _l(
                                "content_improvement_info"
                            ); ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l(
                            "close"
                        ); ?></button>
                        <button type="button" class="btn btn-primary start-bulk-content-edit"><?php echo _l(
                            "start_generation"
                        ); ?></button>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade in" id="bulk-content-backdrop" style="z-index: 10001; display: none;" data-related-modal="bulk-content-modal"></div>`
        );
         
        // Xử lý sự kiện đóng modal
        $('#bulk-content-modal').on('hidden.bs.modal', function() {
            const backdropId = $(this).attr('id').replace('modal', 'backdrop');
            $(`#${backdropId}`).hide();
        });
        
        $('#bulk-content-modal').on('show.bs.modal', function() {
            const backdropId = $(this).attr('id').replace('modal', 'backdrop');
            $(`#${backdropId}`).show();
            
            // Kiểm tra và hiển thị thông tin controller nếu có
            if (window.TopicComposer && window.TopicComposer.selectedController) {
                const controller = window.TopicComposer.selectedController;
                
                // Hiển thị phần controller info
                $('#bulk-controller-info-section').show();
                
                // Cập nhật tên controller
                $('#bulk-controller-name').text(`Controller: ${controller.site || 'Unknown'}`);
                
                // Cập nhật tooltip cho action1 và action2
                let action1Tooltip = 'No writing requirements';
                let action2Tooltip = 'No additional instructions';
                
                if (controller.action_1) {
                    action1Tooltip = 'Writing Requirements available';
                    $('#bulk-controller-action1').html(`
                        <h5><strong><i class="fa fa-pencil"></i> Writing Requirements</strong></h5>
                        <div>${controller.action_1}</div>
                    `);
                } else if (controller.writing_style) {
                    action1Tooltip = 'Writing Style available';
                    $('#bulk-controller-action1').html(`
                        <h5><strong><i class="fa fa-pencil"></i> Writing Style</strong></h5>
                        <div>${controller.writing_style}</div>
                    `);
                } else {
                    $('#bulk-controller-action1').html('<p class="text-muted">No writing requirements available</p>');
                }
                
                if (controller.action_2) {
                    action2Tooltip = 'Additional Instructions available';
                    $('#bulk-controller-action2').html(`
                        <h5><strong><i class="fa fa-info-circle"></i> Additional Instructions</strong></h5>
                        <div>${controller.action_2}</div>
                    `);
                } else {
                    $('#bulk-controller-action2').html('<p class="text-muted">No additional instructions available</p>');
                }
                
                // Cập nhật tooltip
                $('#bulk-controller-details-toggle').attr('data-original-title', 
                    `${action1Tooltip}\n${action2Tooltip}`).tooltip('fixTitle');
            } else {
                // Ẩn phần controller info nếu không có controller
                $('#bulk-controller-info-section').hide();
            }
        });
        
        // Xử lý click vào toggle details
        $(document).on('click', '#bulk-controller-details-toggle', function(e) {
            e.preventDefault();
            $('#bulk-controller-details').slideToggle();
        });

        // Xử lý click vào nút Bulk Edit Content
        $(document).on('click', '.bulk-edit-content-btn', function() {
            $('#bulk-content-modal').modal('show');
        });
        
        // Xử lý click vào nút Start Generation trong modal
        $(document).on('click', '.start-bulk-content-edit', function() {
            // Lấy style đã chọn
            const contentStyle = $('#content-style').val();
            const processType = $('input[name="content-items-to-process"]:checked').val();
            const language = $('#content-language').val() || 'en';
            const customInstructions = $('#custom-content-instructions').val();
            
            // Lấy thông tin controller nếu có
            const controller = window.TopicComposer && window.TopicComposer.selectedController 
                ? window.TopicComposer.selectedController 
                : null;
            
            // Khởi tạo danh sách items cần xử lý
            let itemsToProcess = [];
            
            if (processType === 'all') {
                // Lấy tất cả items
                itemsToProcess = window.TopicComposer.items.map((item, index) => ({
                    item: item,
                    index: index
                }));
            } else {
                // Lấy các items đã chọn
                $('.item-checkbox:checked').each(function() {
                    const index = $(this).closest('.list-group-item').data('index');
                    itemsToProcess.push({
                        item: window.TopicComposer.items[index],
                        index: index
                    });
                });
                
                // Kiểm tra nếu không có item nào được chọn
                if (itemsToProcess.length === 0) {
                    alert_float('warning', '<?php echo _l(
                        "please_select_at_least_one_item"
                    ); ?>');
                    return;
                }
            }
            
            // Lấy các tùy chọn tối ưu
            const optimizationOptions = {
                seo: $('#opt-seo').is(':checked'),
                removeExternalLinks: $('#opt-remove-external').is(':checked'),
                optimizeParagraphLength: $('#opt-paragraph-length').is(':checked'),
                addSubheadings: $('#opt-subheadings').is(':checked'),
                addCallToAction: $('#opt-call-to-action').is(':checked'),
                insertImages: $('#opt-insert-images').is(':checked')
            };
            
            // Cập nhật biến toàn cục
            window.bulkContentEditor = {
                items: itemsToProcess,
                currentIndex: 0,
                contentStyle: contentStyle,
                language: language,
                customInstructions: customInstructions,
                optimizationOptions: optimizationOptions,
                controller: controller,
                results: [],
                isProcessing: true
            };
            
            // Đóng modal
            $('#bulk-content-modal').modal('hide');
            
            // Hiển thị progress bar
            showBulkContentProgressBar(itemsToProcess.length);
            
            // Bắt đầu xử lý item đầu tiên
            processBulkContentEditNextItem();
        });


        // Xử lý khi click vào nút Auto Reposition
        $('.auto-reposition-btn').on('click', function() {
            if (confirm('<?php echo _l("confirm_auto_reposition"); ?>')) {
                autoReposition();
            }
        });

        //init live search for selectpicker
        $('#content-language').selectpicker();
     },
     refreshItemsList: function() {
        console.log('refreshItemsList');
        const $itemsList = $('.sortable-items');
        $itemsList.html(window.TopicComposer.items.map((item, index) => {
            // Kiểm tra item có thay đổi không
            const originalItem = window.TopicComposer.originalItems.find(
                orig => orig['web-scraper-order'] === item['web-scraper-order']
            );
            const changes = window.TopicComposer.handlers.getItemChanges(item, originalItem);
            
            return `
                <div class="list-group-item ${changes ? 'has-changes' : ''}" 
                     data-index="${index}"
                     ${changes ? `data-changes="${changes.join(', ')}"` : ''}>
                    <div class="item-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="checkbox checkbox-primary" style="margin: 0 10px 0 0">
                                <input type="checkbox" class="item-checkbox" id="item-${index}" data-index="${index}">
                                <label for="item-${index}"></label>
                            </div>
                            <span class="drag-handle"><i class="fa fa-bars"></i></span>
                            <span class="item-position">${item.Item_Position}</span>
                            <div class="item-title">
                                ${htmlEntityDecode(item.Item_Title)}
                                ${changes ? `
                                    <span class="changes-indicator" title="<?php echo _l(
                                        "modified_fields"
                                    ); ?>: ${changes.join(', ')}">
                                        <i class="fa fa-check-circle text-success"></i>
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                        <div class="item-actions">
                            <button type="button" class="btn btn-xs btn-default edit-item-btn" data-index="${index}">
                                <i class="fa fa-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-xs btn-danger delete-item-btn" data-index="${index}">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    ${changes ? `
                        <div class="changes-tooltip">
                            <div class="changes-details">
                                <strong><?php echo _l(
                                    "modified_fields"
                                ); ?>:</strong>
                                ${changes.map(field => `
                                    <span class="change-field">${field}</span>
                                `).join(', ')}
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
        }).join(''));

        // Thêm styles cho đánh dấu và tooltip
        const styleHtml = `
            <style>
                .has-changes {
                    border-left: 4px solid #28a745;
                }

                .changes-indicator {
                    margin-left: 5px;
                }

                .changes-tooltip {
                    position: absolute;
                    background-color: #f9f9f9;
                    border: 1px solid #dee2e6;
                    padding: 5px;
                    border-radius: 4px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    z-index: 1000;
                    opacity: 0;
                    transition: opacity 0.3s ease-in-out;
                    pointer-events: none;
                }

                .list-group-item:hover .changes-tooltip {
                    opacity: 1;
                }

                .changes-details {
                    font-size: 12px;
                }

                .change-field {
                    background-color: #e9ecef;
                    border-radius: 4px;
                    padding: 2px 5px;
                    margin-right: 5px;
                }
            </style>
        `;

        // Add styles once
        if (!$('head').find('style[data-id="item-changes-styles"]').length) {
            $('head').append($(styleHtml).attr('data-id', 'item-changes-styles'));
        }

        // Reinitialize sortable
        new Sortable($itemsList[0], {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function() {
                updateItemsOrder();
            }
        });
     },
     getItemChanges: function(item, originalItem) {
        if (!originalItem) return null;
        
        const changes = [];
        if (item.Item_Title !== originalItem.Item_Title) changes.push('Title');
        if (item.Item_Content !== originalItem.Item_Content) changes.push('Content');
        if (item.Item_Position !== originalItem.Item_Position) changes.push('Position');
        
        return changes.length > 0 ? changes : null;
    },
    cleanupEditors: function() {
        window.TopicComposer.editors.forEach(editor => {
            if (editor && typeof editor.destroy === 'function') {
                editor.destroy();
            }
        });
        window.TopicComposer.editors.clear();
        window.TopicComposer.currentEditingIndex = -1;
        window.TopicComposer.hasChanges = false;
    },
    loadTopicComposerControllers: function() {
        // Get topic_id from global variables
        const topic_id = typeof topicCurrentId !== 'undefined' ? topicCurrentId : 
                        (window.TopicComposer && window.TopicComposer.topic_id) ? 
                        window.TopicComposer.topic_id : '';
        
        console.log('Loading controllers for topic_id:', topic_id);
        
        if (!topic_id) {
            console.error('No topic_id available for loading controllers');
            $.notify({
                message: 'Error: No topic ID available for loading controllers'
            }, {
                type: 'danger',
                delay: 3000
            });
            return;
        }
        
        // Show loading indicator
        const select = $('#topic-composer-controller-select');
        select.empty().append('<option value="">Loading controllers...</option>');
        select.prop('disabled', true);
        
        // Expand the controller section if it's collapsed
        if (!$('#controller-selection-collapse').hasClass('in')) {
            $('#controller-selection-collapse').collapse('show');
        }
        
        $.ajax({
            url: admin_url + 'topics/get_available_controllers',
            type: 'GET',
            data: { topic_id: topic_id },
            success: function(response) {
                // console.log('get_available_controllers response', response);
                try {
                    // Check if response is already an object
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }
                    console.log('get_available_controllers response', response);
                    select.empty();
                    select.append('<option value=""><?php echo _l("select_controller"); ?></option>');
                    
                    if (response.success && response.data && response.data.controllers) {
                        if (response.data.controllers.length === 0) {
                            alert_float('warning', '<?php echo _l("no_controllers_available_for_this_topic"); ?>');
                        } else {
                            response.data.controllers.forEach(controller => {
                                select.append(`<option value="${controller.id}" 
                                    data-writing-style="${escapeHtml(controller.writing_style || '')}"
                                    data-platform="${escapeHtml(controller.platform || '')}"
                                    data-slogan="${escapeHtml(controller.slogan || '')}">
                                    ${escapeHtml(controller.site)}
                                </option>`);
                            });
                            
                         
                        }
                    } else {
                        alert_float('danger', '<?php echo _l("error_loading_controllers"); ?>');        
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    alert_float('danger', '<?php echo _l("error_parsing_server_response"); ?>');
                }
                
                // Re-enable the select
                select.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                console.error('Error loading controllers:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                
                select.empty().append('<option value=""><?php echo _l("select_controller"); ?></option>');
                select.prop('disabled', false);
                
                alert_float('danger', '<?php echo _l("error_loading_controllers"); ?>');
            }
        });
    },
    showTopicComposerControllerInfo: function(data) {
        console.log('showTopicComposerControllerInfo', data);
        
        if (!data || !data.site) {
            $('#topic-composer-controller-info').hide();
            return;
        }
        
        const safeData = {
            site: escapeHtml(data.site),
            platform: escapeHtml(data.platform || 'Website'),
            writing_style: data.action_1 || data.writing_style || '',
            slogan: escapeHtml(data.slogan || ''),
            action_1: data.action_1 || '',
            action_2: data.action_2 || ''
        };
        
        // Show a summary in the collapsed header
        const controllerSummary = `<span class="selected-controller-summary">(${safeData.site} - ${safeData.platform})</span>`;
        $('.controller-selection-section .panel-title a').find('.selected-controller-summary').remove();
        $('.controller-selection-section .panel-title a').append(controllerSummary);
        
        // Update the detailed info
        $('#topic-composer-controller-info').html(`
            <div class="controller-info-content">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h4 class="panel-title">${safeData.site}</h4>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <p><strong>Platform:</strong> <span class="platform-badge platform-${safeData.platform.toLowerCase()}">${safeData.platform}</span></p>
                                ${safeData.slogan ? `<p><strong>Slogan:</strong> ${safeData.slogan}</p>` : ''}
                            </div>
                        </div>
                        
                        ${safeData.action_1 ? `
                            <div class="writing-requirements mtop15">
                                <h5><strong><i class="fa fa-pencil"></i> Writing Requirements</strong></h5>
                                <div class="well well-sm">
                                    ${safeData.action_1}
                                </div>
                            </div>
                        ` : ''}
                        
                        ${safeData.action_2 ? `
                            <div class="additional-instructions mtop15">
                                <h5><strong><i class="fa fa-info-circle"></i> Additional Instructions</strong></h5>
                                <div class="well well-sm">
                                    ${safeData.action_2}
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `).show();
        
        // Store the selected controller in the global state
        window.TopicComposer.selectedController = data;
        
        // If the panel is collapsed, show a notification
        if (!$('#controller-selection-collapse').hasClass('in')) {
            alert_float('info', '<?php echo _l("controller_selected"); ?>: ' + safeData.site);
        }
    }
}}

</script> 
