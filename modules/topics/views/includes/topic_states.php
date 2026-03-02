<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="topic-states-wrapper">
    <div class="row">
        <!-- Total Topics -->
        <div class="col-lg-3 col-md-6 col-xs-6">
            <div class="topic-state-card topics-total" data-filter="total">
                <div class="inner">
                    <h3 class="topics-number"><?php echo $total_topics; ?></h3>
                    <p><?php echo _l('total_topic_masters'); ?></p>
                </div>
                <div class="icon">
                    <i class="fa fa-book"></i>
                </div>
                <a href="<?php echo admin_url('topics'); ?>" class="small-box-footer" data-filter="total">
                    <?php echo _l('view_all'); ?> <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Active Topics -->
        <div class="col-lg-3 col-md-6 col-xs-6">
            <div class="topic-state-card active-topics" data-filter="active">
                <div class="inner">
                    <h3 class="topics-number"><?php echo $active_topics; ?></h3>
                    <p><?php echo _l('active_topic_masters'); ?></p>
                </div>
                <div class="icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <a href="#" class="small-box-footer" data-filter="active">
                    <?php echo _l('filter_by_state'); ?> <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Writing Topics -->
        <div class="col-lg-3 col-md-6 col-xs-6">
            <div class="topic-state-card writing" data-filter="writing">
                <div class="inner">
                    <h3 class="format-money"><?php echo $writing_topics; ?></h3>
                    <p><?php echo _l('writing_topics'); ?></p>
                </div>
                <div class="icon">
                    <i class="fa fa-pencil"></i>
                </div>
                <a href="#" class="small-box-footer" data-filter="writing">
                    <?php echo _l('filter_by_state'); ?> <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Social Audit Topics -->
        <div class="col-lg-3 col-md-6 col-xs-6">
            <div class="topic-state-card social-audit" data-filter="social_audit">
                <div class="inner">
                    <h3 class="format-money"><?php echo $social_audit_topics; ?></h3>
                    <p><?php echo _l('social_audit_topics'); ?></p>
                </div>
                <div class="icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <a href="#" class="small-box-footer" data-filter="social_audit">
                    <?php echo _l('filter_by_state'); ?> <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Scheduled Social Topics -->
        <div class="col-lg-3 col-md-6 col-xs-6">
            <div class="topic-state-card scheduled" data-filter="scheduled_social">
                <div class="inner">
                    <h3 class="format-money"><?php echo $scheduled_social_topics; ?></h3>
                    <p><?php echo _l('scheduled_social_topics'); ?></p>
                </div>
                <div class="icon">
                    <i class="fa fa-calendar"></i>
                </div>
                <a href="#" class="small-box-footer" data-filter="scheduled_social">
                    <?php echo _l('filter_by_state'); ?> <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Post Audit Gallery Topics -->
        <div class="col-lg-3 col-md-6 col-xs-6">
            <div class="topic-state-card post-audit" data-filter="post_audit_gallery">
                <div class="inner">
                    <h3 class="format-money"><?php echo $post_audit_gallery_topics; ?></h3>
                    <p><?php echo _l('post_audit_gallery_topics'); ?></p>
                </div>
                <div class="icon">
                    <i class="fa fa-image"></i>
                </div>
                <a href="#" class="small-box-footer" data-filter="post_audit_gallery">
                    <?php echo _l('filter_by_state'); ?> <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Fail Topics -->
        <div class="col-lg-3 col-md-6 col-xs-6">
            <div class="topic-state-card fail-topics" data-filter="fail">
                <div class="inner">
                    <h3 class="topics-number"><?php echo $fail_topics; ?></h3>
                    <p><?php echo _l('fail_topics'); ?></p>
                </div>
                <div class="icon">
                    <i class="fa fa-times-circle"></i>
                </div>
                <a href="#" class="small-box-footer" data-filter="fail">
                    <?php echo _l('filter_by_state'); ?> <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.topic-states-wrapper {
    margin-bottom: 30px;
}

.topic-state-card {
    position: relative;
    display: block;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    min-height: 230px;
}

.topic-state-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.topic-state-card .inner {
    padding: 25px 20px;
    color: #fff;
}

.topic-state-card .inner h3 {
    font-size: 38px;
    font-weight: 600;
    margin: 0 0 10px 0;
    white-space: nowrap;
    padding: 0;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
}

.topic-state-card .inner p {
    font-size: 16px;
    margin: 0;
    opacity: 0.9;
    font-weight: 500;
}

.topic-state-card .icon {
    position: absolute;
    top: 20px;
    right: 15px;
    font-size: 70px;
    color: rgba(255,255,255,0.15);
    transition: all 0.3s ease;
}

.topic-state-card:hover .icon {
    transform: scale(1.1);
    color: rgba(255,255,255,0.2);
}

.topic-state-card .small-box-footer {
    position: absolute;
    left: 0;
    bottom: 0;
    width: 100%;
    text-align: center;
    padding: 12px 0;
    color: #fff;
    display: block;
    background: rgba(0,0,0,0.1);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.topic-state-card .small-box-footer:hover {
    background: rgba(0,0,0,0.15);
    padding-right: 10px;
}

.topic-state-card .small-box-footer i {
    margin-left: 5px;
    transition: transform 0.3s ease;
}

.topic-state-card .small-box-footer:hover i {
    transform: translateX(4px);
}

/* Modern Color Scheme */
.topic-state-card.topics-total {
    background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
}

.topic-state-card.writing {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
}

.topic-state-card.social-audit {
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
}

.topic-state-card.scheduled {
    background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
}

.topic-state-card.post-audit {
    background: linear-gradient(135deg, #EC4899 0%, #DB2777 100%);
}

.topic-state-card.active-topics {
    background: linear-gradient(135deg, #14B8A6 0%, #0D9488 100%);
}

.topic-state-card.fail-topics {
    background: linear-gradient(135deg, #DC3545 0%, #C82333 100%);
}

/* Responsive */
@media (max-width: 767px) {
    .topic-state-card .inner {
        padding: 15px;
    }
    
    .topic-state-card .inner h3 {
        font-size: 28px;
    }
    
    .topic-state-card .icon {
        font-size: 50px;
    }
}

.topic-state-card.active {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

.topic-state-card.topics-total.active {
    border: 2px solid #6366F1;
}
</style>
 