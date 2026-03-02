<!-- SEO Analysis Tab -->
<div role="tabpanel" class="tab-pane" id="tab_seo_analysis">
    <div id="seo-analysis-container" class="mt-3">
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin compact-heading">
                    <i class="fa fa-line-chart"></i> <?= _l('seo_analysis') ?>
                </h4>
                <hr class="hr-panel-separator" />

                <!-- Target Keyword Input -->
                <div class="form-group">
                    <label for="seo-target-keyword"><?= _l('target_keyword') ?></label>
                    <div class="input-group">
                        <input type="text" id="seo-target-keyword" class="form-control" placeholder="<?= _l('enter_target_keyword') ?>">
                        <span class="input-group-btn">
                            <button class="btn btn-info" id="analyze-seo-btn" type="button">
                                <i class="fa fa-search"></i> <?= _l('analyze') ?>
                            </button>
                        </span>
                    </div>
                    <small class="text-muted"><?= _l('enter_main_keyword_for_analysis') ?> <?= _l('or_first_tag_will_be_used') ?></small>
                </div>

                <!-- SEO Score -->
                <div class="seo-score-container mb-4">
                    <div class="d-flex align-items-center">
                        <div class="score-indicator">
                            <div class="score-number">0</div>
                            <div class="score-label"><?= _l('score') ?></div>
                        </div>
                        <div class="score-progress flex-1 ml-3">
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar" role="progressbar" style="width: 0%;" 
                                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="score-description mt-1">
                                <small class="text-muted"><?= _l('seo_score_description') ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO Stats -->
                <div class="seo-stats mb-4">
                    <h5 class="bold"><?= _l('content_statistics') ?></h5>
                    <div class="stat-list">
                        <div class="stat-item mb-2">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon mr-2">
                                    <i class="fa fa-file-text-o text-info"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-label"><?= _l('words') ?></div>
                                    <div class="stat-number" id="content-length">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="stat-item mb-2">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon mr-2">
                                    <i class="fa fa-header text-info"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-label"><?= _l('headings') ?></div>
                                    <div class="stat-number" id="headings-count">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon mr-2">
                                    <i class="fa fa-image text-info"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-label"><?= _l('images') ?></div>
                                    <div class="stat-number" id="images-count">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO Checklist -->
                <div class="seo-checklist">
                    <h5 class="bold"><?= _l('seo_checklist') ?></h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <tbody id="seo-checklist-items">
                                <!-- Title -->
                                <tr id="check-title">
                                    <td width="30"><i class="fa fa-spinner fa-spin"></i></td>
                                    <td><?= _l('title_tag') ?></td>
                                    <td class="text-right" width="100"><span class="status"></span></td>
                                </tr>
                                <!-- Meta Description -->
                                <tr id="check-description">
                                    <td><i class="fa fa-spinner fa-spin"></i></td>
                                    <td><?= _l('meta_description') ?></td>
                                    <td class="text-right"><span class="status"></span></td>
                                </tr>
                                <!-- Content Length -->
                                <tr id="check-content-length">
                                    <td><i class="fa fa-spinner fa-spin"></i></td>
                                    <td><?= _l('content_length') ?></td>
                                    <td class="text-right"><span class="status"></span></td>
                                </tr>
                                <!-- Headings -->
                                <tr id="check-headings">
                                    <td><i class="fa fa-spinner fa-spin"></i></td>
                                    <td><?= _l('heading_structure') ?></td>
                                    <td class="text-right"><span class="status"></span></td>
                                </tr>
                                <!-- Images -->
                                <tr id="check-images">
                                    <td><i class="fa fa-spinner fa-spin"></i></td>
                                    <td><?= _l('image_optimization') ?></td>
                                    <td class="text-right"><span class="status"></span></td>
                                </tr>
                                <!-- Links -->
                                <tr id="check-links">
                                    <td><i class="fa fa-spinner fa-spin"></i></td>
                                    <td><?= _l('internal_links') ?></td>
                                    <td class="text-right"><span class="status"></span></td>
                                </tr>
                                <!-- Keyword Usage -->
                                <tr id="check-keyword">
                                    <td><i class="fa fa-spinner fa-spin"></i></td>
                                    <td><?= _l('keyword_usage') ?></td>
                                    <td class="text-right"><span class="status"></span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SEO Suggestions -->
                <div id="seo-suggestions" class="mt-4">
                    <h5 class="bold"><?= _l('suggestions_for_improvement') ?></h5>
                    <div class="suggestions-list">
                        <!-- Suggestions will be added here -->
                    </div>
                </div>

                <!-- Loading State -->
                <div id="seo-analysis-loading" class="text-center hide">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only"><?= _l('loading') ?></span>
                    </div>
                    <p class="mt-2"><?= _l('analyzing_seo') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.score-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 10px solid #eee;
    margin: 0 auto;
    position: relative;
}

.score-circle .score-number {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 24px;
    font-weight: bold;
}

.score-circle .score-label {
    position: absolute;
    bottom: -30px;
    left: 50%;
    transform: translateX(-50%);
    white-space: nowrap;
}

/* Score Indicator Styles */
.score-indicator {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 5px solid #eee;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    background: #fff;
}

.score-indicator .score-number {
    font-size: 18px;
    font-weight: bold;
    line-height: 1;
    margin-bottom: 2px;
}

.score-indicator .score-label {
    font-size: 10px;
    color: #777;
    text-transform: uppercase;
    line-height: 1;
}

.score-progress {
    width: 100%;
}

.flex-1 {
    flex: 1;
}

.ml-3 {
    margin-left: 15px;
}

.mt-1 {
    margin-top: 5px;
}

.stat-box {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.stat-box i {
    font-size: 24px;
    color: #03a9f4;
}

.stat-box .stat-number {
    font-size: 20px;
    font-weight: bold;
    margin: 10px 0;
}

/* Stat List Styles */
.stat-list {
    margin-top: 10px;
}

.stat-item {
    padding: 10px;
    border-left: 3px solid #03a9f4;
    background-color: #f9f9f9;
    margin-bottom: 10px;
}

.stat-icon {
    font-size: 24px;
    width: 30px;
    text-align: center;
}

.stat-content {
    flex: 1;
}

.stat-content .stat-label {
    font-size: 12px;
    color: #777;
}

.stat-content .stat-number {
    font-size: 16px;
    font-weight: bold;
}

.d-flex {
    display: flex;
}

.align-items-center {
    align-items: center;
}

.mr-2 {
    margin-right: 10px;
}

.mb-2 {
    margin-bottom: 10px;
}

.suggestions-list .suggestion-item {
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 4px;
}

.suggestions-list .suggestion-item.good {
    background-color: #dff0d8;
    border-left: 4px solid #3c763d;
}

.suggestions-list .suggestion-item.warning {
    background-color: #fcf8e3;
    border-left: 4px solid #8a6d3b;
}

.suggestions-list .suggestion-item.error {
    background-color: #f2dede;
    border-left: 4px solid #a94442;
}

/* Compact headings for tabs */
.compact-heading {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 10px;
}

.tab-pane h5.bold {
    font-size: 14px;
    margin-top: 15px;
    margin-bottom: 10px;
}

/* Additional spacing utilities */
.tab-pane .form-group:last-child {
    margin-bottom: 5px;
}

@media (max-width: 767px) {
    .compact-heading {
        font-size: 14px;
    }
    
    .tab-pane h5.bold {
        font-size: 13px;
    }
    
    .form-group {
        margin-bottom: 10px;
    }
}
</style> 