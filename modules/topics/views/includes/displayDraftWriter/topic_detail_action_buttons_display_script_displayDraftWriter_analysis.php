<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
/**
 * Extract keywords from text
 */
function extractKeywords(text) {
    // Remove common words and count occurrences
    const commonWords = ['a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'with', 'by', 'about', 'as', 'of', 'from'];
    const words = text.toLowerCase()
        .replace(/[^\w\s]/g, '')
        .split(/\s+/)
        .filter(word => word.length > 3 && !commonWords.includes(word));
    
    const keywords = {};
    words.forEach(word => {
        keywords[word] = (keywords[word] || 0) + 1;
    });
    
    return keywords;
}

/**
 * Debounce function to limit rapid function calls
 */
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(context, args);
        }, wait);
    };
}

/**
 * Calculate keyword density
 */
function calculateKeywordDensity(text, keyword) {
    if (!keyword || !text) return 0;
    
    const words = text.toLowerCase().split(/\s+/).filter(Boolean);
    const keywordCount = words.filter(word => word === keyword.toLowerCase()).length;
    
    return (keywordCount / words.length) * 100;
}

/**
 * Generate SEO suggestions
 */
function generateSEOSuggestions(content, title, description, targetKeyword) {
    const suggestions = [];
    const textContent = stripHtml(content);
    const wordCount = countWords(textContent);
    
    // Check title length
    if (!title) {
        suggestions.push({
            type: 'error',
            text: '<?php echo _l('title_missing'); ?>'
        });
    } else if (title.length < 30) {
        suggestions.push({
            type: 'warning',
            text: '<?php echo _l('title_too_short'); ?>'
        });
    } else if (title.length > 60) {
        suggestions.push({
            type: 'warning',
            text: '<?php echo _l('title_too_long'); ?>'
        });
    } else {
        suggestions.push({
            type: 'good',
            text: '<?php echo _l('title_good_length'); ?>'
        });
    }
    
    // Check description length
    if (!description) {
        suggestions.push({
            type: 'error',
            text: '<?php echo _l('description_missing'); ?>'
        });
    } else if (description.length < 120) {
        suggestions.push({
            type: 'warning',
            text: '<?php echo _l('description_too_short'); ?>'
        });
    } else if (description.length > 160) {
        suggestions.push({
            type: 'warning',
            text: '<?php echo _l('description_too_long'); ?>'
        });
    } else {
        suggestions.push({
            type: 'good',
            text: '<?php echo _l('description_good_length'); ?>'
        });
    }
    
    // Check content length
    if (wordCount < 300) {
        suggestions.push({
            type: 'warning',
            text: '<?php echo _l('content_too_short'); ?>'
        });
    } else {
        suggestions.push({
            type: 'good',
            text: '<?php echo _l('content_good_length'); ?>'
        });
    }
    
    // Check keyword usage
    if (targetKeyword) {
        const keywordDensity = calculateKeywordDensity(textContent, targetKeyword);
        
        if (keywordDensity < 0.5) {
            suggestions.push({
                type: 'warning',
                text: '<?php echo _l('keyword_density_too_low'); ?>'
            });
        } else if (keywordDensity > 3) {
            suggestions.push({
                type: 'warning',
                text: '<?php echo _l('keyword_density_too_high'); ?>'
            });
        } else {
            suggestions.push({
                type: 'good',
                text: '<?php echo _l('keyword_density_good'); ?>'
            });
        }
        
        // Check keyword in title
        if (title && title.toLowerCase().includes(targetKeyword.toLowerCase())) {
            suggestions.push({
                type: 'good',
                text: '<?php echo _l('keyword_in_title'); ?>'
            });
        } else {
            suggestions.push({
                type: 'warning',
                text: '<?php echo _l('keyword_not_in_title'); ?>'
            });
        }
        
        // Check keyword in description
        if (description && description.toLowerCase().includes(targetKeyword.toLowerCase())) {
            suggestions.push({
                type: 'good',
                text: '<?php echo _l('keyword_in_description'); ?>'
            });
        } else {
            suggestions.push({
                type: 'warning',
                text: '<?php echo _l('keyword_not_in_description'); ?>'
            });
        }
    } else {
        suggestions.push({
            type: 'warning',
            text: '<?php echo _l('no_target_keyword'); ?>'
        });
    }
    
    return suggestions;
} 
</script> 