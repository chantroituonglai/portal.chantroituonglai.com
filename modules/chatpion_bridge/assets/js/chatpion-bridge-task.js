(function ($) {
    "use strict";

    var lang = (window.ChatpionBridge && ChatpionBridge.lang) || {};
    var debounce = (typeof _ !== 'undefined' && typeof _.debounce === 'function') ? _.debounce : function (fn) { return fn; };
    var escapeHtml = (typeof _ !== 'undefined' && typeof _.escape === 'function') ? _.escape : function (str) {
        return $('<div>').text(str || '').html();
    };

    function t(key) {
        return lang[key] || key;
    }

    function parseJson(value) {
        if (!value) {
            return null;
        }
        if (typeof value === 'object') {
            return value;
        }
        try {
            return JSON.parse(value);
        } catch (err) {
            return null;
        }
    }

    function formatStatusLabel(link) {
        if (!link || !link.status) {
            return t('status_pending');
        }
        var key = link.status.key || '';
        var translationKey = 'status_' + key;
        if (lang[translationKey]) {
            return lang[translationKey];
        }
        if (link.status.label) {
            return link.status.label;
        }
        return key || t('status_pending');
    }

    function formatDateTime(value) {
        if (!value) {
            return t('not_synced');
        }
        return value;
    }

    var CampaignModal = {
        $modal: null,
        context: null,
        currentPage: 1,
        totalPages: 1,
        mediaType: 'all',
        init: function () {
            console.log('[ChatpionBridge] CampaignModal.init called');
            if (this.$modal) {
                return;
            }
            this.$modal = $('#chatpionCampaignModal');
            if (!this.$modal.length) {
                return;
            }
            var self = this;
            this.$modal.on('shown.bs.modal', function () {
                console.log('[ChatpionBridge] CampaignModal shown');
                init_selectpicker(self.$modal.find('.selectpicker'));
                self.currentPage = 1;
                self.load();
                // Ensure z-index is a
                // bove other modals (e.g., task modal)
                try {
                    var topZ = 10002;
                    self.$modal.css('z-index', topZ);
                    // Adjust the latest backdrop created for this modal
                    $('.modal-backdrop').last().css('z-index', topZ - 1).addClass('chatpion-campaign-backdrop');
                } catch (e) { /* noop */ }
            });
            this.$modal.on('hidden.bs.modal', function () {
                console.log('[ChatpionBridge] CampaignModal hidden');
                self.context = null;
            });
            this.$modal.find('#chatpion-campaign-search').on('keyup', debounce(function () {
                self.currentPage = 1;
                self.load();
            }, 300));
            this.$modal.find('#chatpion-campaign-status').on('changed.bs.select', function () {
                self.currentPage = 1;
                self.load();
            });
            this.$modal.find('#chatpion-campaign-account').on('changed.bs.select', function () {
                self.currentPage = 1;
                self.load();
            });
            this.$modal.find('#chatpion-campaign-reset').on('click', function () {
                self.$modal.find('#chatpion-campaign-search').val('');
                self.$modal.find('#chatpion-campaign-status').selectpicker('val', '');
                self.$modal.find('#chatpion-campaign-account').selectpicker('val', '');
                self.currentPage = 1;
                self.load();
            });
            this.$modal.find('#chatpion-campaign-pagination button').on('click', function () {
                var dir = $(this).attr('data-page');
                if (dir === 'prev' && self.currentPage > 1) {
                    self.currentPage--;
                }
                if (dir === 'next' && self.currentPage < self.totalPages) {
                    self.currentPage++;
                }
                self.load();
            });
            this.$modal.on('click', '.chatpion-select-campaign', function () {
                var campaign = $(this).data('campaign');
                console.log('[ChatpionBridge] Campaign selected', campaign && campaign.id);
                if (self.context && typeof self.context.onSelect === 'function') {
                    self.context.onSelect(campaign);
                }
                self.$modal.modal('hide');
            });
        },
        updateAccountOptions: function (accounts, currentValue) {
            var $account = this.$modal.find('#chatpion-campaign-account');
            if (!$account.length) {
                return;
            }
            var current = typeof currentValue !== 'undefined' && currentValue !== null ? String(currentValue) : ($account.val() || '');
            var optionsHtml = ['<option value="">' + escapeHtml(t('all_accounts')) + '</option>'];
            var seen = {};
            if (Array.isArray(accounts)) {
                accounts.forEach(function (account) {
                    if (!account) {
                        return;
                    }
                    var id = account.id !== null && account.id !== undefined ? String(account.id) : '';
                    var name = account.name || '';
                    if (id === '' && name === '') {
                        return;
                    }
                    var key = id || name;
                    if (seen[key]) {
                        return;
                    }
                    seen[key] = true;
                    optionsHtml.push('<option value="' + escapeHtml(id) + '">' + escapeHtml(name || ('#' + id)) + '</option>');
                });
            }
            if (current && current !== '' && !seen[current]) {
                optionsHtml.push('<option value="' + escapeHtml(current) + '">' + escapeHtml(current) + '</option>');
            }
            if ($account.data('selectpicker')) {
                $account.selectpicker('destroy');
            }
            $account.html(optionsHtml.join(''));
            init_selectpicker($account);
            $account.selectpicker('val', current || '');
        },
        open: function (context) {
            console.log('[ChatpionBridge] CampaignModal.open', context);
            this.init();
            if (!this.$modal) {
                return;
            }
            this.mediaType = (context && context.media_type) ? context.media_type : 'all';
            this.context = context || {};
            this.$modal.modal('show');
        },
        load: function () {
            console.log('[ChatpionBridge] CampaignModal.load page', this.currentPage);
            if (!this.$modal) {
                return;
            }
            var self = this;
            var params = {
                page: this.currentPage,
                limit: 10,
                search: $.trim(this.$modal.find('#chatpion-campaign-search').val()),
                status: this.$modal.find('#chatpion-campaign-status').val(),
                account_id: this.$modal.find('#chatpion-campaign-account').val(),
                media_type: this.mediaType || 'all',
            };

            var $tbody = this.$modal.find('#chatpion-campaign-results');
        $tbody.html('<tr><td colspan="5" class="text-center text-muted">' + t('loading') + '</td></tr>');

        $.getJSON(admin_url + 'chatpion_bridge/campaigns', params).done(function (response) {
            if (!response || response.status !== 'success') {
                $tbody.html('<tr><td colspan="5" class="text-center text-danger">' + t('unexpected_error') + '</td></tr>');
                return;
            }
            var items = response.data || [];
            var accountsMeta = (response.meta && Array.isArray(response.meta.accounts)) ? response.meta.accounts : [];
            if ((!accountsMeta || !accountsMeta.length) && items.length) {
                accountsMeta = items.map(function (item) {
                    return item.account || null;
                });
            }
            self.updateAccountOptions(accountsMeta, params.account_id);
            var selectedCampaignId = null;
            if (self.context && typeof self.context.getSelectedId === 'function') {
                selectedCampaignId = self.context.getSelectedId();
            }
            if (!items.length) {
                $tbody.html('<tr><td colspan="5" class="text-center text-muted">' + t('no_results') + '</td></tr>');
            } else {
                var rows = items.map(function (item) {
                        var isSelected = selectedCampaignId && String(selectedCampaignId) === String(item.id);
                        var schedule = item.schedule && item.schedule.time_iso ? item.schedule.time_iso : '';
                        var statusKey = (item.status && item.status.key) ? item.status.key : 'pending';
                        var statusText = lang['status_' + statusKey] || (item.status && item.status.label) || statusKey;
                        var ownerInfo = '';
                        if (item.owner && (item.owner.name || item.owner.id)) {
                            var ownerBits = [];
                            if (item.owner.name) {
                                ownerBits.push(item.owner.name);
                            }
                            if (item.owner.id) {
                                ownerBits.push('#' + item.owner.id);
                            }
                            ownerInfo = '<div class="tw-text-xs tw-text-neutral-500">' + escapeHtml(t('owner') || 'Owner') + ': ' + escapeHtml(ownerBits.join(' / ')) + '</div>';
                        }
                        var mediaType = (item.media_type || '').toLowerCase();
                        var mediaLabel = '';
                        if (mediaType) {
                            var mediaKey = 'media_type_' + mediaType;
                            mediaLabel = lang[mediaKey] || mediaType.toUpperCase();
                        }
                        var mediaBadge = mediaLabel ? '<span class="label label-default tw-uppercase tw-mr-1">' + escapeHtml(mediaLabel) + '</span>' : '';
                        var campaignLabel = mediaBadge + escapeHtml(item.name || ('#' + item.id)) + ownerInfo;
                        var serialized = encodeURIComponent(JSON.stringify(item));
                        var btnClass = 'btn btn-primary btn-sm chatpion-select-campaign';
                        var btnText = t('choose');
                        var disabledAttr = '';
                        if (isSelected) {
                            btnClass = 'btn btn-success btn-sm disabled';
                            btnText = t('linked');
                            disabledAttr = ' disabled';
                        }
                        var btn = '<button type="button" class="' + btnClass + '" data-campaign="' + serialized + '"' + disabledAttr + '>' + btnText + '</button>';
                        var rowClass = isSelected ? ' class="info chatpion-selected-row"' : '';
                        if (isSelected) {
                            campaignLabel = '<div class="tw-flex tw-items-start tw-space-x-1"><i class="fa fa-check-circle tw-text-success tw-mt-1"></i><div>' + campaignLabel + '</div></div>';
                        }
                        return '<tr' + rowClass + '>' +
                            '<td>' + campaignLabel + '</td>' +
                            '<td>' + escapeHtml(item.account && item.account.name ? item.account.name : '') + '</td>' +
                            '<td>' + escapeHtml(statusText) + '</td>' +
                            '<td>' + escapeHtml(schedule || '') + '</td>' +
                            '<td class="text-right">' + btn + '</td>' +
                            '</tr>';
                    });
                    $tbody.html(rows.join(''));
                    $tbody.find('.chatpion-select-campaign').each(function () {
                        var $btn = $(this);
                        var raw = $btn.attr('data-campaign');
                        try {
                            $btn.data('campaign', JSON.parse(decodeURIComponent(raw)));
                        } catch (err) {
                            $btn.data('campaign', null);
                        }
                    });
                }
                var pagination = response.pagination || {};
                var total = parseInt(pagination.total, 10) || 0;
                self.totalPages = parseInt(pagination.total_pages, 10) || 1;
                var infoText = '';
                if (pagination.page) {
                    infoText = pagination.page + ' / ' + self.totalPages;
                }
                infoText += infoText ? ' | ' + total : total;
                self.$modal.find('#chatpion-campaign-pagination [data-role="pagination-info"]').text(infoText);
            }).fail(function () {
                console.log('[ChatpionBridge] CampaignModal.load failed');
                $tbody.html('<tr><td colspan="5" class="text-center text-danger">' + t('unexpected_error') + '</td></tr>');
            });
        }
    };

    function initTaskForm($modal) {
        console.log('[ChatpionBridge] initTaskForm called', $modal && $modal.attr('id'));
        if (!$modal.length) {
            return;
        }
        var $group = $modal.find('.chatpion-bridge-campaign-group');
        if (!$group.length || $group.data('bridgeInit')) {
            return;
        }
        $group.data('bridgeInit', true);

        var $display = $group.find('#chatpion_campaign_display');
        var $campaignId = $group.find('#chatpion_campaign_id');
        var $accountId = $group.find('#chatpion_account_id');
        var $mediaTypeInput = $group.find('#chatpion_media_type');
        var $remove = $group.find('#chatpion_remove_link');
        var $clear = $group.find('#chatpion-clear-campaign');
        var currentLink = parseJson($group.attr('data-current-link')) || {};

        function formatDisplay(link) {
            var id = link && typeof link === 'object' ? link.campaign_id : link;
            if (!id) {
                return '';
            }
            var baseLabel = lang.campaign_display ? lang.campaign_display.replace('%s', id) : ('Campaign #' + id);
            var mediaType = link && typeof link === 'object' ? link.media_type : null;
            if (mediaType) {
                var mediaKey = 'media_type_' + mediaType.toLowerCase();
                var mediaLabel = lang[mediaKey] || mediaType;
                baseLabel += ' · ' + mediaLabel;
            }
            return baseLabel;
        }

        function renderDisplay(link) {
            if (link && link.campaign_id) {
                $display.val(formatDisplay(link));
                $clear.removeClass('hide');
                $campaignId.val(link.campaign_id);
                $accountId.val(link.account_id || '');
                if ($mediaTypeInput.length) {
                    $mediaTypeInput.val(link.media_type || '');
                }
                $remove.val('0');
            } else {
                $display.val('');
                $campaignId.val('');
                $accountId.val('');
                if ($mediaTypeInput.length) {
                    $mediaTypeInput.val('');
                }
                $remove.val('0');
                $clear.addClass('hide');
            }
            currentLink = link || {};
            $group.data('selectedCampaign', link && link.campaign_id ? link.campaign_id : '');
            $group.attr('data-current-link', JSON.stringify(link || {}));
        }

        renderDisplay(currentLink);
        console.log('[ChatpionBridge] initTaskForm currentLink', currentLink);

        $group.on('click', '#chatpion-select-campaign', function () {
            console.log('[ChatpionBridge] task form choose campaign clicked');
            var existingLink = parseJson($group.attr('data-current-link')) || {};
            var preferredMediaType = existingLink.media_type || ($mediaTypeInput.length ? $mediaTypeInput.val() : '') || 'all';
            CampaignModal.open({
                context: 'form',
                taskId: $group.data('taskId'),
                media_type: preferredMediaType,
                getSelectedId: function () {
                    return $group.data('selectedCampaign') || $campaignId.val();
                },
                onSelect: function (campaign) {
                    console.log('[ChatpionBridge] task form onSelect', campaign && campaign.id);
                    var link = {
                        campaign_id: campaign.id,
                        account_id: campaign.account ? campaign.account.id : '',
                        media_type: campaign.media_type || ''
                    };
                    renderDisplay(link);
                }
            });
        });

        $group.on('click', '#chatpion-clear-campaign', function () {
            console.log('[ChatpionBridge] task form clear campaign clicked');
            $display.val('');
            $campaignId.val('');
            $accountId.val('');
            $remove.val('1');
            $group.attr('data-current-link', JSON.stringify({}));
            $clear.addClass('hide');
        });
    }

    function initWorkspaceSection() {
        console.log('[ChatpionBridge] initWorkspaceSection called');
        var $section = $('#chatpion-workspace-section');
        if (!$section.length || $section.data('bridgeInit')) {
            return;
        }
        $section.data('bridgeInit', true);

        var canEdit = $section.data('canEdit') === 1 || $section.data('canEdit') === '1';
        var $textarea = $('#chatpion-workspace-editor');
        var initialData = $section.attr('data-workspace');
        var initialContent = '';
        if (initialData) {
            try {
                initialContent = JSON.parse(initialData);
            } catch (err) {
                initialContent = initialData;
            }
        }
        if (initialContent && typeof initialContent === 'object' && initialContent.caption) {
            initialContent = initialContent.caption;
        }
        if (initialContent) {
            $textarea.val(initialContent);
        }

        if (canEdit && typeof init_editor !== 'undefined') {
            init_editor('#chatpion-workspace-editor', {
                height: 220,
                setup: function (editor) {
                    editor.on('init', function () {
                        if (initialContent) {
                            editor.setContent(initialContent);
                        }
                    });
                }
            });
        }

        $('#chatpion-workspace-save').on('click', function () {
            console.log('[ChatpionBridge] workspace save clicked');
            if (!canEdit) {
                return;
            }
            var taskId = $section.data('taskId');
            var content = getWorkspaceContent();
            $.ajax({
                url: admin_url + 'chatpion_bridge/workspace/' + taskId,
                type: 'POST',
                dataType: 'json',
                data: JSON.stringify({workspace: {caption: content}}),
                contentType: 'application/json',
            }).done(function (res) {
                var $alert = $('#chatpion-workspace-alert');
                if (res && res.success) {
                    $alert.removeClass('hide alert-danger').addClass('alert alert-success').text(t('workspace_saved'));
                    if (res.data) {
                        updatePanel(res.data);
                    }
                } else {
                    $alert.removeClass('hide alert-success').addClass('alert alert-danger').text(t('workspace_error'));
                }
            }).fail(function () {
                console.log('[ChatpionBridge] workspace save failed');
                $('#chatpion-workspace-alert').removeClass('hide alert-success').addClass('alert alert-danger').text(t('workspace_error'));
            });
        });

        $section.on('click', '.chatpion-link-action[data-action="link"]', function (e) {
            console.log('[ChatpionBridge] workspace link button clicked');
            e.preventDefault();
            var taskId = $section.data('taskId');
            var currentLink = $('#chatpion-bridge-panel').data('linkData') || {};
            var preferredMediaType = currentLink.media_type || 'all';
            CampaignModal.open({
                context: 'workspace',
                taskId: taskId,
                media_type: preferredMediaType,
                getSelectedId: function () {
                    var current = $('#chatpion-bridge-panel').data('linkData') || {};
                    return current.campaign_id || null;
                },
                onSelect: function (campaign) {
                    console.log('[ChatpionBridge] workspace onSelect', campaign && campaign.id);
                    linkTask(taskId, campaign);
                }
            });
        });
    }

    function getWorkspaceContent() {
        var editor = window.tinymce && tinymce.get('chatpion-workspace-editor');
        if (editor) {
            return editor.getContent();
        }
        return $('#chatpion-workspace-editor').val();
    }

    function initPanel() {
        console.log('[ChatpionBridge] initPanel called');
        var $panel = $('#chatpion-bridge-panel');
        if (!$panel.length || $panel.data('bridgeInit')) {
            return;
        }
        $panel.data('bridgeInit', true);

        var linkData = parseJson($panel.attr('data-link')) || {};
        updatePanel(linkData);

        $panel.on('click', '.chatpion-link-action', function (e) {
            console.log('[ChatpionBridge] panel action clicked');
            e.preventDefault();
            var action = $(this).data('action');
            var taskId = $panel.data('taskId');
            console.log('[ChatpionBridge] panel action', action, 'taskId', taskId);
            if (!action) {
                return;
            }
        if (action === 'link') {
            console.log('[ChatpionBridge] opening CampaignModal from panel');
            var currentLink = $panel.data('linkData') || linkData || {};
            var preferredMediaType = currentLink.media_type || 'all';
            CampaignModal.open({
                context: 'panel',
                taskId: taskId,
                media_type: preferredMediaType,
                getSelectedId: function () {
                    var current = $panel.data('linkData') || {};
                    return current.campaign_id || null;
                },
                onSelect: function (campaign) {
                    console.log('[ChatpionBridge] panel onSelect', campaign && campaign.id);
                    linkTask(taskId, campaign);
                }
            });
                return;
            }
            if (action === 'refresh') {
                console.log('[ChatpionBridge] refresh action');
                refreshTask(taskId);
                return;
            }
            if (action === 'unlink') {
                console.log('[ChatpionBridge] unlink action');
                if (confirm(t('unlink_confirm'))) {
                    unlinkTask(taskId);
                }
                return;
            }
            if (action === 'open') {
                console.log('[ChatpionBridge] open action');
                var link = $panel.data('linkData') || {};
                var target = link.post_url || ($panel.data('frontUrl') ? ($panel.data('frontUrl') + '/instagram_poster') : '#');
                if (target === '#') {
                    return;
                }
                window.open(target, '_blank');
            }
        });
    }

    function updatePanel(link) {
        console.log('[ChatpionBridge] updatePanel called, hasLink=', !!(link && link.campaign_id));
        var $panel = $('#chatpion-bridge-panel');
        if (!$panel.length) {
            return;
        }
        $panel.data('linkData', link || {});
        $panel.attr('data-link', JSON.stringify(link || {}));

        var hasLink = link && link.campaign_id;
        var $summary = $panel.find('.chatpion-bridge-summary');
        var $empty = $summary.find('[data-role="empty-state"]');
        var $details = $summary.find('[data-role="summary-details"]');
        var $campaign = $summary.find('[data-role="campaign-label"]');
        var $channel = $summary.find('[data-role="channel"]');
        var $status = $summary.find('[data-role="status-label"]');
        var $sync = $summary.find('[data-role="last-synced"]');
        var $post = $summary.find('[data-role="post-url"]');
        var $preview = $panel.find('[data-role="post-preview"]');
        var $openBtn = $panel.find('.chatpion-link-action[data-action="open"]');
        var $refreshBtn = $panel.find('.chatpion-link-action[data-action="refresh"]');
        var $unlinkBtn = $panel.find('.chatpion-link-action[data-action="unlink"]');

        if (hasLink) {
            if ($empty.length) {
                $empty.addClass('hide');
            }
            if ($details.length) {
                $details.removeClass('hide');
            }
            if ($campaign.length) {
                var campaignLabel = link.name || ('#' + link.campaign_id);
                $campaign.text(campaignLabel);
            }
            if ($channel.length) {
                var mediaType = (link.media_type || '').toLowerCase();
                var channelLabel = mediaType ? t('media_type_' + mediaType) : t('not_available');
                if (!channelLabel || channelLabel === ('media_type_' + mediaType)) {
                    channelLabel = link.media_type || t('not_available');
                }
                $channel.text(channelLabel);
            }
            if ($status.length) {
                $status.text(formatStatusLabel(link));
            }
            if ($sync.length) {
                $sync.text(formatDateTime(link.last_synced_at));
            }
            if ($post.length) {
                if (link.post_url) {
                    $post.html('<a href="' + escapeHtml(link.post_url) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(link.post_url) + '</a>');
                } else {
                    $post.html('<span class="text-muted">' + t('not_available') + '</span>');
                }
            }
            if ($refreshBtn.length) {
                $refreshBtn.removeClass('hide');
            }
            if ($unlinkBtn.length) {
                $unlinkBtn.removeClass('hide');
            }
            if ($openBtn.length) {
                if (link.post_url) {
                    $openBtn.removeClass('hide').attr('href', link.post_url);
                } else {
                    $openBtn.addClass('hide').attr('href', '#');
                }
            }
            if ($preview.length) {
                if (link.post_url) {
                    if ((link.media_type || '').toLowerCase() === 'facebook') {
                        var src = 'https://www.facebook.com/plugins/post.php?href=' + encodeURIComponent(link.post_url) + '&show_text=true&width=500';
                        $preview.html('<iframe src="' + src + '" width="100%" height="580" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowfullscreen="true"></iframe>');
                    } else {
                        $preview.html('<a href="' + escapeHtml(link.post_url) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(link.post_url) + '</a>');
                    }
                } else {
                    $preview.html('<span class="text-muted">' + t('not_available') + '</span>');
                }
            }
        } else {
            if ($details.length) {
                $details.addClass('hide');
            }
            if ($empty.length) {
                $empty.removeClass('hide').text(t('chatpion_bridge_task_no_campaign'));
            }
            if ($post.length) {
                $post.html('<span class="text-muted">' + t('not_available') + '</span>');
            }
            if ($preview.length) {
                $preview.html('<span class="text-muted">' + t('not_available') + '</span>');
            }
            if ($refreshBtn.length) {
                $refreshBtn.addClass('hide');
            }
            if ($unlinkBtn.length) {
                $unlinkBtn.addClass('hide');
            }
            if ($openBtn.length) {
                $openBtn.addClass('hide').attr('href', '#');
            }
        }
    }

    function linkTask(taskId, campaign) {
        console.log('[ChatpionBridge] linkTask called', { taskId: taskId, campaignId: campaign && campaign.id });
        var data = {
            task_id: taskId,
            campaign_id: campaign.id,
            account_id: campaign.account ? campaign.account.id : '',
            media_type: campaign.media_type || ''
        };
        $.post(admin_url + 'chatpion_bridge/link_task', data, function (res) {
            console.log('[ChatpionBridge] linkTask success', res);
            if (!res || !res.success) {
                alert_float('danger', (res && res.message) || t('unexpected_error'));
                return;
            }
            alert_float('success', t('link'));
            updatePanel(res.data || {});
            if (res.data && res.data.workspace) {
                applyWorkspace(res.data.workspace);
            }
            // If we're on the task view modal, refresh its content to reflect the new link
            try {
                var $taskIdInput = $('#taskid');
                var currentTaskId = $taskIdInput.length ? $taskIdInput.val() : null;
                if (currentTaskId && typeof init_task_modal === 'function') {
                    console.log('[ChatpionBridge] refreshing task modal after link', currentTaskId);
                    init_task_modal(currentTaskId);
                }
            } catch (e) {
                // noop
            }
        }, 'json').fail(function () {
            console.log('[ChatpionBridge] linkTask failed');
            alert_float('danger', t('unexpected_error'));
        });
    }

    function unlinkTask(taskId) {
        console.log('[ChatpionBridge] unlinkTask called', taskId);
        $.post(admin_url + 'chatpion_bridge/unlink_task/' + taskId, function (res) {
            console.log('[ChatpionBridge] unlinkTask success', res);
            if (!res || !res.success) {
                alert_float('danger', (res && res.message) || t('unexpected_error'));
                return;
            }
            alert_float('success', t('clear'));
            updatePanel(null);
        }, 'json').fail(function () {
            console.log('[ChatpionBridge] unlinkTask failed');
            alert_float('danger', t('unexpected_error'));
        });
    }

    function refreshTask(taskId) {
        console.log('[ChatpionBridge] refreshTask called', taskId);
        $.post(admin_url + 'chatpion_bridge/refresh_task/' + taskId, function (res) {
            console.log('[ChatpionBridge] refreshTask success', res);
            if (!res || !res.success) {
                alert_float('danger', (res && res.message) || t('unexpected_error'));
                return;
            }
            updatePanel(res.data || {});
        }, 'json').fail(function () {
            console.log('[ChatpionBridge] refreshTask failed');
            alert_float('danger', t('unexpected_error'));
        });
    }

    function applyWorkspace(workspace) {
        console.log('[ChatpionBridge] applyWorkspace called');
        if (!workspace) {
            return;
        }
        var content = workspace;
        if (typeof workspace === 'object' && workspace.caption) {
            content = workspace.caption;
        }
        var editor = window.tinymce && tinymce.get('chatpion-workspace-editor');
        if (editor) {
            editor.setContent(content || '');
        } else {
            $('#chatpion-workspace-editor').val(content || '');
        }
    }

    $(document).on('shown.bs.modal', '#_task_modal', function () {
        console.log('[ChatpionBridge] #_task_modal shown');
        initTaskForm($(this));
    });

    $(function () {
        console.log('[ChatpionBridge] document ready');
        initTaskForm($('#_task_modal'));
        initPanel();
        initWorkspaceSection();
        // Support task view buttons that use class "chatpion-bridge-action"
        $(document).on('click', '.chatpion-bridge-action[data-action="link"]', function (e) {
            console.log('[ChatpionBridge] task view link button clicked');
            e.preventDefault();
            var taskId = $('#taskid').val() || ($('#chatpion-bridge-panel').data('taskId')) || '';
            if (!taskId) { return; }
            CampaignModal.open({
                context: 'task-view',
                taskId: taskId,
                media_type: 'all',
                getSelectedId: function () {
                    var current = $('#chatpion-bridge-panel').data('linkData') || {};
                    return current.campaign_id || null;
                },
                onSelect: function (campaign) {
                    console.log('[ChatpionBridge] task view onSelect', campaign && campaign.id);
                    linkTask(taskId, campaign);
                }
            });
        });
        $(document).ajaxComplete(function (event, xhr, settings) {
            if (!settings || !settings.url) {
                return;
            }
            if (settings.url.indexOf('tasks/get_task_data') !== -1 || settings.url.indexOf('tasks/task/') !== -1) {
                setTimeout(function () {
                    console.log('[ChatpionBridge] tasks ajaxComplete -> re-init panel & workspace');
                    initPanel();
                    initWorkspaceSection();
                }, 150);
            }
        });
    });

})(jQuery);
