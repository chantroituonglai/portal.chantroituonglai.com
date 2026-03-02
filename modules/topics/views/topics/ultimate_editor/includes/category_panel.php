<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="panel panel-default" data-panel="categories-panel">
    <div class="panel-heading">
        <h4 class="panel-title"><i class="fa fa-folder-open"></i> <?= _l('categories'); ?></h4>
    </div>
    <div class="panel-body" style="position: relative;">
        <!-- Category Controls -->
        <div class="row mbot10">
            <div class="col-md-12">
                <div class="category-filter-search">
                    <i class="fa fa-search"></i>
                    <input type="text" class="form-control" id="category_search" placeholder="<?= _l('search_categories'); ?>">
                </div>
            </div>
        </div>
        
        <div class="category-tree-controls">
            <div>
                <button type="button" class="btn btn-xs btn-default" id="expand_all_categories">
                    <i class="fa fa-plus-square"></i> <?= _l('expand_all'); ?>
                </button>
                <button type="button" class="btn btn-xs btn-default" id="collapse_all_categories">
                    <i class="fa fa-minus-square"></i> <?= _l('collapse_all'); ?>
                </button>
            </div>
            <div>
                <span class="text-muted small" id="categories_count"></span>
            </div>
        </div>
        
        <!-- Categories Tree Container -->
        <div class="categories-container">
            <div id="categories-tree" class="category-tree">
                <!-- Categories will be loaded here dynamically -->
                <div class="loading-categories hide">
                    <i class="fa fa-spinner fa-spin"></i> <?= _l('loading_categories'); ?>
                </div>
            </div>
        </div>
        
        <!-- Selected Category Info -->
        <div id="selected_category_info" class="selected-category-info">
            <div class="selected-category-header">
                <i class="fa fa-check-circle text-success"></i> 
                <strong><?= _l('selected_category'); ?>:</strong> 
                <span id="selected_category_name"></span>
            </div>
        </div>
    </div>
</div>

<style>
/* Category Styling */
.categories-container {
    max-height: 350px;
    overflow-y: auto;
    border: 1px solid #ddd;
    padding: 5px;
    background-color: #fff;
    margin-top: 10px;
}

.category-tree {
    padding: 0;
    margin: 0;
    font-family: Arial, sans-serif;
}

.category-tree ul {
    list-style: none;
    padding-left: 20px;
    margin: 0;
}

.category-tree li {
    margin: 3px 0;
    position: relative;
}

.category-tree .expander {
    display: inline-block;
    width: 16px;
    height: 16px;
    text-align: center;
    line-height: 16px;
    cursor: pointer;
    margin-right: 3px;
    font-weight: bold;
    color: #555;
}

.category-tree .category-node {
    display: flex;
    align-items: center;
    margin: 2px 0;
}

.category-tree .category-icon {
    margin-right: 5px;
    color: #555;
}

.category-tree .category-checkbox {
    margin-right: 5px;
}

.category-tree .category-label {
    cursor: pointer;
    font-size: 13px;
    color: #333;
}

.category-tree .category-label.disabled {
    color: #999;
    font-style: italic;
}

.category-tree .child-categories {
    display: none;
}

.category-tree .child-categories.expanded {
    display: block;
}

.category-filter-search {
    position: relative;
    margin-bottom: 10px;
}

.category-filter-search input {
    padding: 5px 5px 5px 25px;
    border: 1px solid #ddd;
    width: 100%;
}

.category-filter-search i {
    position: absolute;
    left: 7px;
    top: 8px;
    color: #777;
}

.category-tree-controls {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.selected-category-info {
    display: none;
    padding: 8px;
    margin-top: 10px;
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
}

.selected-category-info.active {
    display: block;
}
</style> 