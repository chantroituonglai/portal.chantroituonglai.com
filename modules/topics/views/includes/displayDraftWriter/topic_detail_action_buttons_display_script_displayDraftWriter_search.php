<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
/**
 * Search functionality for Draft Writer
 */

// Search result template
function generateSearchResultTemplate(result) {
    return `
        <div class="search-result" data-content="${result.snippet}">
            <h4>${result.title}</h4>
            <p>${result.snippet}</p>
            <small class="text-muted">
                <i class="fa fa-external-link"></i> ${result.url}
                <span class="pull-right">
                    <i class="fa fa-star"></i> ${result.relevance_score}%
                </span>
            </small>
        </div>
    `;
}

// Format search results
function formatSearchResults(results) {
    if (!results || results.length === 0) {
        return `<div class="alert alert-info"><?php echo _l('no_results_found'); ?></div>`;
    }
    
    let html = '<div class="search-results-container">';
    results.forEach(result => {
        html += generateSearchResultTemplate(result);
    });
    html += '</div>';
    
    return html;
}

// Handle search result selection
function handleSearchResultSelection() {
    $('.search-result').on('click', function() {
        $('.search-result').removeClass('selected');
        $(this).addClass('selected');
    });
}

// Insert search result into editor
function insertSearchResult(content) {
    if (window.DraftWriter.editor) {
        window.DraftWriter.editor.selection.setContent(content);
        window.DraftWriter.hasChanges = true;
    }
}

// Search styles
const searchStyles = `
    <style>
        .search-results-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #eee;
            border-radius: 4px;
        }
        
        .search-result {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .search-result:last-child {
            border-bottom: none;
        }
        
        .search-result:hover {
            background-color: #f9f9f9;
        }
        
        .search-result.selected {
            background-color: #e3f2fd;
            border-left: 3px solid #2196F3;
        }
        
        .search-result h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #2196F3;
        }
        
        .search-result p {
            margin-bottom: 10px;
            color: #333;
        }
        
        .search-result small {
            color: #777;
        }
    </style>
`;

// Append search styles to head
$('head').append(searchStyles); 
</script> 