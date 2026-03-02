/**
 * ULTIMATE EDITOR HELPER FUNCTIONS
 * File này chứa các hàm hỗ trợ cho Ultimate Editor
 * 
 * CÁC NHÓM HÀM:
 * - FUNCTIONAL FUNCTIONS: Hàm xử lý logic, tính toán và xử lý dữ liệu
 * - UI/PRESENTATION FUNCTIONS: Hàm hiển thị, cập nhật UI và xử lý hiệu ứng
 */

/**
 * @FUNCTIONAL_FUNCTION: Lấy nội dung từ trình soạn thảo an toàn
 * Đảm bảo truy cập an toàn đến nội dung trình soạn thảo ngay cả khi trình soạn thảo chưa được khởi tạo
 * @returns {string} Nội dung từ trình soạn thảo hoặc HTML nội dung từ DOM
 */
function safeGetEditorContent() {
    // Check for tinymce global
    if (typeof tinymce !== 'undefined') {
        // Try tinymce.activeEditor first
        if (tinymce.activeEditor && tinymce.activeEditor.initialized) {
            return tinymce.activeEditor.getContent();
        }

        // Try get editor instance by ID
        if (tinymce.get('editor-content') && tinymce.get('editor-content').initialized) {
            return tinymce.get('editor-content').getContent();
        }
    }

    // Check if editor global variable exists
    if (typeof editor !== 'undefined' && editor) {
        return editor.getContent();
    }

    // Fallback to HTML content
    return $('#editor-content').html() || '';
}

/**
 * @FUNCTIONAL_FUNCTION: Set nội dung cho trình soạn thảo an toàn
 * Đảm bảo truy cập an toàn để đặt nội dung cho trình soạn thảo ngay cả khi trình soạn thảo chưa được khởi tạo
 * @param {string} content - Nội dung HTML cần đặt vào trình soạn thảo
 * @returns {boolean} - True nếu thành công, False nếu không thể đặt nội dung
 */
function safeSetEditorContent(content) {
    console.log('Attempting to set editor content safely');

    // Process content if it's a JSON string
    if (typeof content === 'string' && content.startsWith('{') && content.includes('"content":[')) {
        try {
            const parsedContent = JSON.parse(content);
            if (parsedContent.content && Array.isArray(parsedContent.content)) {
                // Extract the text from the JSON structure
                let htmlContent = '';
                parsedContent.content.forEach(item => {
                    if (item.type === 'text' && item.text) {
                        htmlContent += item.text;
                    }
                });
                if (htmlContent) {
                    console.log('Converted JSON content to HTML for editor');
                    content = htmlContent;
                }
            }
        } catch (e) {
            console.warn('Content appears to be JSON but couldn\'t be parsed, using as-is:', e);
        }
    }

    // Check for tinymce global
    if (typeof tinymce !== 'undefined') {
        console.log('TinyMCE is defined, checking for editor instances');

        // Try tinymce.activeEditor first
        if (tinymce.activeEditor) {
            console.log('Active editor found:', tinymce.activeEditor.id);
            if (tinymce.activeEditor.initialized) {
                console.log('Active editor is initialized, setting content');
                tinymce.activeEditor.setContent(content || '');
                return true;
            } else {
                console.warn('Active editor found but not initialized');
            }
        } else {
            console.warn('No active editor found');
        }

        // Try get editor instance by ID
        if (tinymce.get('editor-content')) {
            console.log('Editor instance found by ID: editor-content');
            if (tinymce.get('editor-content').initialized) {
                console.log('Editor instance is initialized, setting content');
                tinymce.get('editor-content').setContent(content || '');
                return true;
            } else {
                console.warn('Editor instance found but not initialized');
            }
        } else {
            console.warn('No editor instance found with ID: editor-content');

            // Log all available editor instances for debugging
            const editors = tinymce.editors;
            if (editors && editors.length > 0) {
                console.log('Available editor instances:');
                editors.forEach(ed => {
                    console.log(`- Editor ID: ${ed.id}, Initialized: ${ed.initialized}`);
                });
            } else {
                console.log('No editor instances available in tinymce.editors');
            }
        }
    } else {
        console.warn('TinyMCE global not defined');
    }

    // Check if editor global variable exists
    if (typeof editor !== 'undefined' && editor) {
        console.log('Global editor variable found, attempting to use it');
        try {
            editor.setContent(content || '');
            return true;
        } catch (e) {
            console.error('Error using global editor variable:', e);
        }
    } else {
        console.warn('Global editor variable not defined or null');
    }

    // Fallback to HTML content
    console.warn('All editor checks failed, falling back to setting HTML directly');
    $('#editor-content').html(content || '');
    return false;
}

/**
 * @FUNCTIONAL_FUNCTION: Tính toán và cập nhật số đếm từ, ký tự và độ đọc
 * Phân tích nội dung văn bản để tính toán số lượng từ, ký tự và độ đọc
 */
function updateWordCount() {
    try {
        const content = safeGetEditorContent();
        const textContent = content ? content.replace(/<[^>]*>/g, ' ') : '';
        const wordCount = textContent.trim() ? textContent.trim().split(/\s+/).length : 0;
        const charCount = textContent.length;

        $('#word-count').text(wordCount);
        $('#char-count').text(charCount);

        // Calculate estimated reading time (average 200 words per minute)
        const readingTimeMinutes = Math.ceil(wordCount / 200);
        $('#reading-time').text(readingTimeMinutes + ' min');

        // Simple readability score
        const sentences = content.split(/[.!?]+/).filter(s => s.trim().length > 0);
        const sentenceCount = sentences.length;

        if (sentenceCount > 0 && wordCount > 0) {
            // Very basic Flesch-Kincaid inspired score
            const wordsPerSentence = wordCount / sentenceCount;
            const readabilityScore = Math.max(0, Math.min(100, 100 - (wordsPerSentence - 10) * 5));
            $('#readability-score').text(Math.round(readabilityScore) + '/100');
        } else {
            $('#readability-score').text('0/100');
        }
    } catch (e) {
        console.error('Error updating word count:', e);
    }
}

/**
 * CHỨC NĂNG (FUNCTIONAL FUNCTION)
 * Phân tích nội dung để đánh giá chất lượng
 */
function analyzeContent(content, title, description, keywords) {
    // Đếm số từ
    const wordCount = countWords(stripHtml(content));

    // Hiển thị phân tích đơn giản
    const analysis = {
        wordCount: wordCount,
        readingTime: Math.ceil(wordCount / 200), // ~200 từ/phút
        titleLength: title ? title.length : 0,
        descriptionLength: description ? description.length : 0
    };

    // Hiển thị phân tích
    displayAnalysis(analysis);

    // Phân tích SEO nếu có từ khóa
    if (keywords && $('#seo-analysis-container').length) {
        analyzeSEO(content, title, description, keywords);
    }
}


/**
 * Lấy văn bản khuyến nghị cho từ khóa
 * Chuyển đổi mã khuyến nghị thành văn bản có thể đọc được
 * 
 * @FUNCTIONAL_FUNCTION: Xử lý dữ liệu để chuyển đổi mã khuyến nghị thành văn bản
 */
function getKeywordRecommendationText(recommendations) {
    if (!recommendations || !recommendations.length) {
        return 'No recommendations';
    }

    const recommendationTexts = {
        'keyword_density_too_low': 'Increase keyword density (aim for 1-3%)',
        'keyword_density_too_high': 'Reduce keyword density (aim for 1-3%)',
        'add_keyword_to_title': 'Add keyword to your title',
        'add_keyword_to_first_paragraph': 'Add keyword to first paragraph',
        'add_keyword_to_headings': 'Add keyword to headings (H1, H2, H3)',
        'add_keyword_to_beginning': 'Add keyword to beginning of content',
        'improve_keyword_distribution': 'Distribute keyword more evenly'
    };

    const result = recommendations.map(code => recommendationTexts[code] || code).join('<br>');
    return result || 'No specific recommendations';
}



/**
 * CHỨC NĂNG (FUNCTIONAL FUNCTION)
 * Phân tích SEO của nội dung
 */
function analyzeSEO(content, title, description, keywords) {
    // Tách từ khóa
    const keywordList = keywords.split(',').map(k => k.trim()).filter(k => k.length > 0);

    // Phân tích SEO đơn giản
    const seoAnalysis = {
        score: 0,
        suggestions: []
    };

    // Kiểm tra độ dài tiêu đề
    if (title.length < 10) {
        seoAnalysis.suggestions.push({
            type: 'error',
            text: 'Title is too short (minimum 10 characters)'
        });
    } else if (title.length > 60) {
        seoAnalysis.suggestions.push({
            type: 'warning',
            text: 'Title is too long (maximum 60 characters recommended)'
        });
    } else {
        seoAnalysis.suggestions.push({
            type: 'good',
            text: 'Title length is good'
        });
        seoAnalysis.score += 20;
    }

    // Kiểm tra độ dài mô tả
    if (description.length < 50) {
        seoAnalysis.suggestions.push({
            type: 'error',
            text: 'Description is too short (minimum 50 characters)'
        });
    } else if (description.length > 160) {
        seoAnalysis.suggestions.push({
            type: 'warning',
            text: 'Description is too long (maximum 160 characters recommended)'
        });
    } else {
        seoAnalysis.suggestions.push({
            type: 'good',
            text: 'Description length is good'
        });
        seoAnalysis.score += 20;
    }

    // Kiểm tra từ khóa trong tiêu đề
    if (keywordList.length > 0) {
        const mainKeyword = keywordList[0];
        if (title.toLowerCase().includes(mainKeyword.toLowerCase())) {
            seoAnalysis.suggestions.push({
                type: 'good',
                text: 'Main keyword found in title'
            });
            seoAnalysis.score += 20;
        } else {
            seoAnalysis.suggestions.push({
                type: 'warning',
                text: 'Main keyword not found in title'
            });
        }
    }

    // Kiểm tra từ khóa trong mô tả
    if (keywordList.length > 0) {
        const mainKeyword = keywordList[0];
        if (description.toLowerCase().includes(mainKeyword.toLowerCase())) {
            seoAnalysis.suggestions.push({
                type: 'good',
                text: 'Main keyword found in description'
            });
            seoAnalysis.score += 20;
        } else {
            seoAnalysis.suggestions.push({
                type: 'warning',
                text: 'Main keyword not found in description'
            });
        }
    }

    // Kiểm tra từ khóa trong nội dung
    if (keywordList.length > 0) {
        const mainKeyword = keywordList[0];
        const contentText = stripHtml(content).toLowerCase();
        const keywordCount = (contentText.match(new RegExp(mainKeyword.toLowerCase(), 'g')) || []).length;
        const keywordDensity = (keywordCount / countWords(contentText)) * 100;

        if (keywordCount === 0) {
            seoAnalysis.suggestions.push({
                type: 'error',
                text: 'Main keyword not found in content'
            });
        } else if (keywordDensity < 0.5) {
            seoAnalysis.suggestions.push({
                type: 'warning',
                text: `Keyword density is too low (${keywordDensity.toFixed(2)}%)`
            });
            seoAnalysis.score += 10;
        } else if (keywordDensity > 3) {
            seoAnalysis.suggestions.push({
                type: 'warning',
                text: `Keyword density is too high (${keywordDensity.toFixed(2)}%) - may be seen as keyword stuffing`
            });
            seoAnalysis.score += 10;
        } else {
            seoAnalysis.suggestions.push({
                type: 'good',
                text: `Keyword density is good (${keywordDensity.toFixed(2)}%)`
            });
            seoAnalysis.score += 20;
        }
    }

    // Hiển thị kết quả phân tích SEO
    displaySEOAnalysis(seoAnalysis);
}

/**
 * CHỨC NĂNG (FUNCTIONAL FUNCTION)
 * Đếm số từ trong văn bản
 */
function countWords(text) {
    return text.split(/\s+/).filter(Boolean).length;
}

/**
 * CHỨC NĂNG (FUNCTIONAL FUNCTION)
 * Loại bỏ các thẻ HTML từ chuỗi văn bản
 */
function stripHtml(html) {
    const temp = document.createElement('div');
    temp.innerHTML = html;
    return temp.textContent || temp.innerText || '';
}

/**
 * CHỨC NĂNG (FUNCTIONAL FUNCTION)
 * Giải mã các ký tự HTML đặc biệt
 */
function decodeHtmlEntities(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.innerHTML = text;
    return div.textContent || div.innerText || '';
}


/**
 * CHỨC NĂNG (FUNCTIONAL FUNCTION)
 * Thực hiện phân tích SEO cục bộ
 */
function performLocalSEOAnalysis(content, title, description, targetKeyword, tags) {
    const contentText = stripHtml(content);
    const wordCount = countWords(contentText);
    const headingsMatch = content.match(/<h[1-6][^>]*>(.*?)<\/h[1-6]>/gi) || [];
    const imagesMatch = content.match(/<img[^>]*>/gi) || [];
    const linksMatch = content.match(/<a[^>]*>(.*?)<\/a>/gi) || [];

    // Initialize the analysis object
    const analysis = {
        score: 0,
        stats: {
            wordCount: wordCount,
            headingsCount: headingsMatch.length,
            imagesCount: imagesMatch.length,
            linksCount: linksMatch.length
        },
        suggestions: []
    };

    // Check if target keyword exists
    if (!targetKeyword) {
        analysis.suggestions.push({
            type: 'error',
            text: 'No target keyword specified'
        });
    } else {
        // Check title
        if (!title) {
            analysis.suggestions.push({
                type: 'error',
                text: 'Title is missing'
            });
        } else {
            if (title.length < 10) {
                analysis.suggestions.push({
                    type: 'error',
                    text: 'Title is too short (minimum 10 characters)'
                });
            } else if (title.length > 60) {
                analysis.suggestions.push({
                    type: 'warning',
                    text: 'Title is too long (maximum 60 characters recommended)'
                });
            } else {
                analysis.suggestions.push({
                    type: 'good',
                    text: 'Title length is good'
                });
                analysis.score += 10;
            }

            if (title.toLowerCase().includes(targetKeyword.toLowerCase())) {
                analysis.suggestions.push({
                    type: 'good',
                    text: 'Target keyword found in title'
                });
                analysis.score += 15;
            } else {
                analysis.suggestions.push({
                    type: 'warning',
                    text: 'Target keyword not found in title'
                });
            }
        }

        // Check description
        if (!description) {
            analysis.suggestions.push({
                type: 'error',
                text: 'Description is missing'
            });
        } else {
            if (description.length < 50) {
                analysis.suggestions.push({
                    type: 'error',
                    text: 'Description is too short (minimum 50 characters)'
                });
            } else if (description.length > 160) {
                analysis.suggestions.push({
                    type: 'warning',
                    text: 'Description is too long (maximum 160 characters recommended)'
                });
            } else {
                analysis.suggestions.push({
                    type: 'good',
                    text: 'Description length is good'
                });
                analysis.score += 10;
            }

            if (description.toLowerCase().includes(targetKeyword.toLowerCase())) {
                analysis.suggestions.push({
                    type: 'good',
                    text: 'Target keyword found in description'
                });
                analysis.score += 10;
            } else {
                analysis.suggestions.push({
                    type: 'warning',
                    text: 'Target keyword not found in description'
                });
            }
        }

        // Check content length
        if (wordCount < 300) {
            analysis.suggestions.push({
                type: 'error',
                text: 'Content is too short (minimum 300 words recommended)'
            });
        } else if (wordCount >= 300 && wordCount < 600) {
            analysis.suggestions.push({
                type: 'warning',
                text: 'Content length is acceptable but could be improved (currently ' + wordCount + ' words)'
            });
            analysis.score += 5;
        } else {
            analysis.suggestions.push({
                type: 'good',
                text: 'Content length is good (' + wordCount + ' words)'
            });
            analysis.score += 15;
        }

        // Check headings
        if (headingsMatch.length === 0) {
            analysis.suggestions.push({
                type: 'error',
                text: 'No headings found in content'
            });
        } else {
            const h1Count = content.match(/<h1[^>]*>(.*?)<\/h1>/gi) || [];
            const h2Count = content.match(/<h2[^>]*>(.*?)<\/h2>/gi) || [];

            if (h1Count.length > 1) {
                analysis.suggestions.push({
                    type: 'warning',
                    text: 'Multiple H1 headings found - consider using only one H1'
                });
            } else if (h1Count.length === 0) {
                analysis.suggestions.push({
                    type: 'warning',
                    text: 'No H1 heading found - consider adding one'
                });
            } else {
                analysis.suggestions.push({
                    type: 'good',
                    text: 'H1 heading usage is good'
                });
                analysis.score += 5;
            }

            if (h2Count.length === 0) {
                analysis.suggestions.push({
                    type: 'warning',
                    text: 'No H2 headings found - consider structuring content with H2 headings'
                });
            } else {
                analysis.suggestions.push({
                    type: 'good',
                    text: 'H2 headings are used (' + h2Count.length + ' found)'
                });
                analysis.score += 5;
            }

            // Check if target keyword is in any heading
            let keywordInHeading = false;
            for (let i = 0; i < headingsMatch.length; i++) {
                const headingText = headingsMatch[i].replace(/<\/?h[1-6][^>]*>/gi, '');
                if (headingText.toLowerCase().includes(targetKeyword.toLowerCase())) {
                    keywordInHeading = true;
                    break;
                }
            }

            if (keywordInHeading) {
                analysis.suggestions.push({
                    type: 'good',
                    text: 'Target keyword found in at least one heading'
                });
                analysis.score += 10;
            } else {
                analysis.suggestions.push({
                    type: 'warning',
                    text: 'Target keyword not found in any heading'
                });
            }
        }

        // Check keyword density
        const keywordRegex = new RegExp('\\b' + escapeRegExp(targetKeyword.toLowerCase()) + '\\b', 'g');
        const keywordCount = (contentText.toLowerCase().match(keywordRegex) || []).length;

        if (keywordCount === 0) {
            analysis.suggestions.push({
                type: 'error',
                text: 'Target keyword not found in content body'
            });
        } else {
            const keywordDensity = (keywordCount / wordCount) * 100;

            if (keywordDensity < 0.5) {
                analysis.suggestions.push({
                    type: 'warning',
                    text: 'Keyword density is too low (' + keywordDensity.toFixed(2) + '%)'
                });
                analysis.score += 5;
            } else if (keywordDensity > 3) {
                analysis.suggestions.push({
                    type: 'warning',
                    text: 'Keyword density is too high (' + keywordDensity.toFixed(2) + '%) - may be seen as keyword stuffing'
                });
                analysis.score += 5;
            } else {
                analysis.suggestions.push({
                    type: 'good',
                    text: 'Keyword density is good (' + keywordDensity.toFixed(2) + '%)'
                });
                analysis.score += 10;
            }
        }

        // Check tags usage
        if (!tags || tags.trim() === '') {
            analysis.suggestions.push({
                type: 'warning',
                text: 'No tags specified - tags help with categorization and SEO'
            });
        } else {
            const tagsList = tags.split(',').map(tag => tag.trim()).filter(Boolean);

            if (tagsList.length === 0) {
                analysis.suggestions.push({
                    type: 'warning',
                    text: 'No valid tags found - add relevant tags for better SEO'
                });
            } else if (tagsList.length < 3) {
                analysis.suggestions.push({
                    type: 'warning',
                    text: 'Only ' + tagsList.length + ' tags found - consider adding more relevant tags'
                });
                analysis.score += 5;
            } else {
                analysis.suggestions.push({
                    type: 'good',
                    text: 'Good number of tags used (' + tagsList.length + ' tags)'
                });
                analysis.score += 10;
            }

            // Check if target keyword is in tags
            if (tagsList.some(tag => tag.toLowerCase() === targetKeyword.toLowerCase())) {
                analysis.suggestions.push({
                    type: 'good',
                    text: 'Target keyword found in tags'
                });
                analysis.score += 5;
            }
        }
    }

    // Cap the score at 100
    analysis.score = Math.min(analysis.score, 100);

    return analysis;
}

/**
 * CHỨC NĂNG (FUNCTIONAL FUNCTION)
 * Escape các ký tự đặc biệt trong biểu thức chính quy
 */
function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}


/**
 * CHỨC NĂNG (FUNCTIONAL FUNCTION)
 * Thực thi lệnh định dạng trong trình soạn thảo
 */
function executeFormatCommand(editor, format) {
    if (!editor || !format) {
        console.error('Editor hoặc format không hợp lệ', { editor, format });
        return;
    }

    console.log('Executing format command:', format, 'on editor:', editor.id);

    try {
        // Đảm bảo editor đã focus
        editor.focus();

        // Xử lý các command khác nhau
        switch (format) {
            case 'bold':
                editor.execCommand('Bold');
                break;
            case 'italic':
                editor.execCommand('Italic');
                break;
            case 'underline':
                editor.execCommand('Underline');
                break;
            case 'alignleft':
                editor.execCommand('JustifyLeft');
                break;
            case 'aligncenter':
                editor.execCommand('JustifyCenter');
                break;
            case 'alignright':
                editor.execCommand('JustifyRight');
                break;
            case 'bullist':
                editor.execCommand('InsertUnorderedList');
                break;
            case 'numlist':
                editor.execCommand('InsertOrderedList');
                break;
            case 'link':
                editor.execCommand('mceLink');
                break;
            case 'image':
                editor.execCommand('mceImage');
                break;
            default:
                console.log('Thử lệnh tiêu chuẩn cho:', format);
                // Thử lệnh TinyMCE tiêu chuẩn
                const standardCommand = 'mce' + format.charAt(0).toUpperCase() + format.slice(1);
                editor.execCommand(standardCommand);
        }

        console.log('Đã thực hiện lệnh:', format);
    } catch (e) {
        console.error('Lỗi khi thực hiện lệnh định dạng:', e);
        alert_float('error', 'Không thể thực hiện lệnh định dạng: ' + format);
    }
}

/**
 * Hàm dịch ngôn ngữ cho JavaScript
 * @param {string} key - Khóa ngôn ngữ cần dịch
 * @returns {string} - Chuỗi đã dịch hoặc khóa gốc nếu không tìm thấy bản dịch
 */
function _l(key) {
    console.log('_l called with key:', key);

    // Kiểm tra xem app.lang có tồn tại và chứa khóa này không
    if (typeof app !== 'undefined' && app.lang && app.lang[key]) {
        return app.lang[key];
    }

    // Trả về khóa nếu không tìm thấy bản dịch
    return key;
}

/**
 * Kiểm tra trình duyệt có hỗ trợ localStorage không
 * @returns {boolean} - True nếu trình duyệt hỗ trợ localStorage
 */
function supportsLocalStorage() {
    try {
        return 'localStorage' in window && window['localStorage'] !== null;
    } catch (e) {
        return false;
    }
}

/**
 * Lưu trạng thái vào localStorage
 * @param {string} key - Khóa để lưu
 * @param {*} value - Giá trị cần lưu (sẽ được chuyển đổi thành JSON)
 * @returns {boolean} - True nếu lưu thành công
 */
function saveToLocalStorage(key, value) {
    if (!supportsLocalStorage()) {
        console.warn('localStorage not supported');
        return false;
    }

    try {
        localStorage.setItem(key, JSON.stringify(value));
        return true;
    } catch (e) {
        console.error('Error saving to localStorage:', e);
        return false;
    }
}

/**
 * Đọc trạng thái từ localStorage
 * @param {string} key - Khóa để đọc
 * @param {*} defaultValue - Giá trị mặc định nếu không tìm thấy
 * @returns {*} - Giá trị đã được phân tích cú pháp hoặc giá trị mặc định
 */
function getFromLocalStorage(key, defaultValue = null) {
    if (!supportsLocalStorage()) {
        console.warn('localStorage not supported');
        return defaultValue;
    }

    try {
        const item = localStorage.getItem(key);
        if (item === null) {
            return defaultValue;
        }
        return JSON.parse(item);
    } catch (e) {
        console.error('Error reading from localStorage:', e);
        return defaultValue;
    }
}

/**
 * Xóa trạng thái từ localStorage
 * @param {string} key - Khóa để xóa
 * @returns {boolean} - True nếu xóa thành công
 */
function removeFromLocalStorage(key) {
    if (!supportsLocalStorage()) {
        console.warn('localStorage not supported');
        return false;
    }

    try {
        localStorage.removeItem(key);
        return true;
    } catch (e) {
        console.error('Error removing from localStorage:', e);
        return false;
    }
}

/**
 * Tạo ID duy nhất
 * @returns {string} - ID duy nhất
 */
function generateUniqueId() {
    return 'id_' + Math.random().toString(36).substring(2, 9) + '_' + Date.now();
}

/**
 * Làm sạch chuỗi HTML
 * @param {string} html - Chuỗi HTML cần làm sạch
 * @returns {string} - Chuỗi HTML đã làm sạch
 */
function sanitizeHtml(html) {
    // Tạo một div tạm thời để xử lý HTML
    const div = document.createElement('div');
    div.innerHTML = html;

    // Loại bỏ các script
    const scripts = div.getElementsByTagName('script');
    for (let i = scripts.length - 1; i >= 0; i--) {
        scripts[i].parentNode.removeChild(scripts[i]);
    }

    // Loại bỏ các thuộc tính không an toàn
    const allElements = div.getElementsByTagName('*');
    for (let i = 0; i < allElements.length; i++) {
        const element = allElements[i];
        const attributes = element.attributes;
        for (let j = attributes.length - 1; j >= 0; j--) {
            const attributeName = attributes[j].name;
            if (attributeName.startsWith('on') || attributeName === 'href' && attributes[j].value.startsWith('javascript:')) {
                element.removeAttribute(attributeName);
            }
        }
    }

    return div.innerHTML;
}

/**
 * Chuyển đổi sang slug
 * @param {string} text - Chuỗi cần chuyển đổi
 * @returns {string} - Slug đã tạo
 */
function slugify(text) {
    return text
        .toString()
        .toLowerCase()
        .normalize('NFD') // Chuẩn hóa Unicode
        .replace(/[\u0300-\u036f]/g, '') // Xóa dấu
        .replace(/[đĐ]/g, 'd') // Xử lý đặc biệt cho chữ đ
        .replace(/\s+/g, '-') // Thay khoảng trắng bằng gạch ngang
        .replace(/[^\w\-]+/g, '') // Loại bỏ ký tự không phải chữ cái, số hoặc gạch ngang
        .replace(/\-\-+/g, '-') // Thay nhiều gạch ngang liên tiếp bằng một gạch ngang
        .replace(/^-+/, '') // Cắt gạch ngang ở đầu
        .replace(/-+$/, ''); // Cắt gạch ngang ở cuối
}

/**
 * CHỨC NĂNG (FUNCTIONAL FUNCTION)
 * Lấy danh sách ảnh từ Topic Composer
 * 
 * @returns {Array} Mảng các đối tượng ảnh từ Topic Composer
 */
function getTopicComposerImages() {
    // Kiểm tra xem TopicComposer có tồn tại không
    if (typeof window.TopicComposer === 'undefined' || !window.TopicComposer.items) {
        console.log('Topic Composer data not available');
        return [];
    }

    const images = [];
    const items = window.TopicComposer.items || [];

    // Duyệt qua tất cả các items để tìm ảnh
    items.forEach(item => {
        // Kiểm tra item_Pictures_Full
        if (item.item_Pictures_Full && Array.isArray(item.item_Pictures_Full)) {
            item.item_Pictures_Full.forEach(imgObj => {
                if (imgObj && typeof imgObj === 'object') {
                    if (imgObj['item_Pictures_Full-large_src']) {
                        images.push({
                            url: imgObj['item_Pictures_Full-large_src'],
                            type: 'full',
                            title: item.Item_Title || 'Image'
                        });
                    }
                }
            });
        }

        // Kiểm tra item_Pictures
        if (item.item_Pictures && Array.isArray(item.item_Pictures)) {
            item.item_Pictures.forEach(imgObj => {
                if (imgObj && typeof imgObj === 'object') {
                    if (imgObj['item_Pictures-src']) {
                        // Kiểm tra xem URL này đã tồn tại trong mảng images chưa
                        const exists = images.some(img => img.url === imgObj['item_Pictures-src']);
                        if (!exists) {
                            images.push({
                                url: imgObj['item_Pictures-src'],
                                type: 'thumbnail',
                                title: item.Item_Title || 'Image'
                            });
                        }
                    }
                }
            });
        }
    });

    console.log('Found ' + images.length + ' images from Topic Composer');
    return images;
}

/**
 * CHỨC NĂNG (FUNCTIONAL FUNCTION)
 * Tạo mã hash MD5 cho chuỗi
 * 
 * @param {string} string - Chuỗi cần tạo hash
 * @returns {string} Chuỗi hash MD5
 */
function md5(string) {
    function RotateLeft(lValue, iShiftBits) {
        return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
    }

    function AddUnsigned(lX, lY) {
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
    }

    function F(x, y, z) { return (x & y) | ((~x) & z); }
    function G(x, y, z) { return (x & z) | (y & (~z)); }
    function H(x, y, z) { return (x ^ y ^ z); }
    function I(x, y, z) { return (y ^ (x | (~z))); }

    function FF(a, b, c, d, x, s, ac) {
        a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b, c, d), x), ac));
        return AddUnsigned(RotateLeft(a, s), b);
    }

    function GG(a, b, c, d, x, s, ac) {
        a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b, c, d), x), ac));
        return AddUnsigned(RotateLeft(a, s), b);
    }

    function HH(a, b, c, d, x, s, ac) {
        a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b, c, d), x), ac));
        return AddUnsigned(RotateLeft(a, s), b);
    }

    function II(a, b, c, d, x, s, ac) {
        a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b, c, d), x), ac));
        return AddUnsigned(RotateLeft(a, s), b);
    }

    function ConvertToWordArray(string) {
        var lWordCount;
        var lMessageLength = string.length;
        var lNumberOfWords_temp1 = lMessageLength + 8;
        var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
        var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
        var lWordArray = Array(lNumberOfWords - 1);
        var lBytePosition = 0;
        var lByteCount = 0;
        while (lByteCount < lMessageLength) {
            lWordCount = (lByteCount - (lByteCount % 4)) / 4;
            lBytePosition = (lByteCount % 4) * 8;
            lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount) << lBytePosition));
            lByteCount++;
        }
        lWordCount = (lByteCount - (lByteCount % 4)) / 4;
        lBytePosition = (lByteCount % 4) * 8;
        lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
        lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
        lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
        return lWordArray;
    }

    function WordToHex(lValue) {
        var WordToHexValue = "", WordToHexValue_temp = "", lByte, lCount;
        for (lCount = 0; lCount <= 3; lCount++) {
            lByte = (lValue >>> (lCount * 8)) & 255;
            WordToHexValue_temp = "0" + lByte.toString(16);
            WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length - 2, 2);
        }
        return WordToHexValue;
    }

    function Utf8Encode(string) {
        string = string.replace(/\r\n/g, "\n");
        var utftext = "";

        for (var n = 0; n < string.length; n++) {
            var c = string.charCodeAt(n);
            if (c < 128) {
                utftext += String.fromCharCode(c);
            } else if ((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            } else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }
        }
        return utftext;
    }

    var x = Array();
    var k, AA, BB, CC, DD, a, b, c, d;
    var S11 = 7, S12 = 12, S13 = 17, S14 = 22;
    var S21 = 5, S22 = 9, S23 = 14, S24 = 20;
    var S31 = 4, S32 = 11, S33 = 16, S34 = 23;
    var S41 = 6, S42 = 10, S43 = 15, S44 = 21;

    string = Utf8Encode(string);
    x = ConvertToWordArray(string);
    a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;

    for (k = 0; k < x.length; k += 16) {
        AA = a; BB = b; CC = c; DD = d;
        a = FF(a, b, c, d, x[k + 0], S11, 0xD76AA478);
        d = FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
        c = FF(c, d, a, b, x[k + 2], S13, 0x242070DB);
        b = FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
        a = FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);
        d = FF(d, a, b, c, x[k + 5], S12, 0x4787C62A);
        c = FF(c, d, a, b, x[k + 6], S13, 0xA8304613);
        b = FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
        a = FF(a, b, c, d, x[k + 8], S11, 0x698098D8);
        d = FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);
        c = FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
        b = FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
        a = FF(a, b, c, d, x[k + 12], S11, 0x6B901122);
        d = FF(d, a, b, c, x[k + 13], S12, 0xFD987193);
        c = FF(c, d, a, b, x[k + 14], S13, 0xA679438E);
        b = FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
        a = GG(a, b, c, d, x[k + 1], S21, 0xF61E2562);
        d = GG(d, a, b, c, x[k + 6], S22, 0xC040B340);
        c = GG(c, d, a, b, x[k + 11], S23, 0x265E5A51);
        b = GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);
        a = GG(a, b, c, d, x[k + 5], S21, 0xD62F105D);
        d = GG(d, a, b, c, x[k + 10], S22, 0x2441453);
        c = GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
        b = GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
        a = GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);
        d = GG(d, a, b, c, x[k + 14], S22, 0xC33707D6);
        c = GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
        b = GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
        a = GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
        d = GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8);
        c = GG(c, d, a, b, x[k + 7], S23, 0x676F02D9);
        b = GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
        a = HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942);
        d = HH(d, a, b, c, x[k + 8], S32, 0x8771F681);
        c = HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122);
        b = HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
        a = HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44);
        d = HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9);
        c = HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60);
        b = HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
        a = HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6);
        d = HH(d, a, b, c, x[k + 0], S32, 0xEAA127FA);
        c = HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085);
        b = HH(b, c, d, a, x[k + 6], S34, 0x4881D05);
        a = HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039);
        d = HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5);
        c = HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8);
        b = HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
        a = II(a, b, c, d, x[k + 0], S41, 0xF4292244);
        d = II(d, a, b, c, x[k + 7], S42, 0x432AFF97);
        c = II(c, d, a, b, x[k + 14], S43, 0xAB9423A7);
        b = II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
        a = II(a, b, c, d, x[k + 12], S41, 0x655B59C3);
        d = II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92);
        c = II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D);
        b = II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
        a = II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F);
        d = II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0);
        c = II(c, d, a, b, x[k + 6], S43, 0xA3014314);
        b = II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
        a = II(a, b, c, d, x[k + 4], S41, 0xF7537E82);
        d = II(d, a, b, c, x[k + 11], S42, 0xBD3AF235);
        c = II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB);
        b = II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
        a = AddUnsigned(a, AA);
        b = AddUnsigned(b, BB);
        c = AddUnsigned(c, CC);
        d = AddUnsigned(d, DD);
    }
    var temp = WordToHex(a) + WordToHex(b) + WordToHex(c) + WordToHex(d);
    return temp.toLowerCase();
}

/**
 * CHỨC NĂNG (FUNCTIONAL FUNCTION)
 * Hiển thị biểu đồ phân phối từ khóa
 * 
 * @param {Object} analysis - Dữ liệu phân tích từ khóa
 */
function renderKeywordChart(analysis) {
    // Kiểm tra xem phần tử canvas có tồn tại không
    const chartCanvas = document.getElementById('keyword-distribution-chart');
    if (!chartCanvas) {
        console.error('Chart canvas element not found');
        return;
    }

    // Kiểm tra xem Chart.js có được load không
    if (typeof Chart === 'undefined') {
        console.error('Chart.js library not loaded');
        return;
    }

    // Lấy các từ khóa và điểm số
    const keywords = Object.keys(analysis.keywords);
    const scores = [];
    const densities = [];
    const colors = [];
    const borderColors = [];

    // Xử lý dữ liệu cho biểu đồ
    keywords.forEach(keyword => {
        const data = analysis.keywords[keyword];
        scores.push(data.score);
        densities.push(parseFloat(data.density));

        // Xác định màu dựa trên điểm số
        if (data.score >= 80) {
            colors.push('rgba(40, 167, 69, 0.7)');
            borderColors.push('rgb(40, 167, 69)');
        } else if (data.score >= 50) {
            colors.push('rgba(23, 162, 184, 0.7)');
            borderColors.push('rgb(23, 162, 184)');
        } else if (data.score >= 30) {
            colors.push('rgba(255, 193, 7, 0.7)');
            borderColors.push('rgb(255, 193, 7)');
        } else {
            colors.push('rgba(220, 53, 69, 0.7)');
            borderColors.push('rgb(220, 53, 69)');
        }
    });

    // Xóa biểu đồ cũ nếu có
    if (window.keywordChart instanceof Chart) {
        window.keywordChart.destroy();
    }

    // Tạo biểu đồ mới
    window.keywordChart = new Chart(chartCanvas, {
        type: 'bar',
        data: {
            labels: keywords,
            datasets: [
                {
                    label: 'Keyword Score',
                    data: scores,
                    backgroundColor: colors,
                    borderColor: borderColors,
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: 'Density (%)',
                    data: densities,
                    type: 'line',
                    fill: false,
                    borderColor: 'rgba(54, 162, 235, 0.8)',
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Score (0-100)'
                    },
                    max: 100
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Density (%)'
                    },
                    grid: {
                        drawOnChartArea: false
                    },
                    max: Math.max(...densities) * 1.5 || 5
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const label = context.dataset.label || '';
                            const value = context.parsed.y;
                            if (label === 'Keyword Score') {
                                return `Score: ${value}/100`;
                            } else if (label === 'Density (%)') {
                                return `Density: ${value.toFixed(2)}%`;
                            }
                            return `${label}: ${value}`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * @FUNCTIONAL_FUNCTION: Lấy nội dung được chọn từ trình soạn thảo an toàn
 * Đảm bảo truy cập an toàn để lấy nội dung được chọn từ trình soạn thảo ngay cả khi trình soạn thảo chưa được khởi tạo
 * @returns {string} - Nội dung được chọn hoặc chuỗi rỗng nếu không lấy được
 */
function safeGetEditorSelection() {
    try {
        // Check for tinymce global
        if (typeof tinymce !== 'undefined') {
            // Try tinymce.activeEditor first
            if (tinymce.activeEditor && tinymce.activeEditor.initialized) {
                return tinymce.activeEditor.selection.getContent() || '';
            }

            // Try get editor instance by ID
            if (tinymce.get('editor-content') && tinymce.get('editor-content').initialized) {
                return tinymce.get('editor-content').selection.getContent() || '';
            }
        }

        // Check if editor global variable exists
        if (typeof editor !== 'undefined' && editor) {
            return editor.selection.getContent() || '';
        }

        // No selection possible
        return '';
    } catch (e) {
        console.error('Error getting editor selection:', e);
        return '';
    }
}
function downloadPopularTags(controllerId, callback, errorCallback) {
    // AJAX request to get tags
    $.ajax({
        url: admin_url + 'topics/ultimate_editor/get_platform_tags/' + controllerId,
        type: 'GET',
        data: { popular_only: 'true' }, // Request popular tags
        dataType: 'json',
        responseType: 'json',
        cache: false,
        success: function (response) {
            if (typeof callback === 'function') {
                callback(response);
            }
        },
        error: function (xhr, status, error) {
            if (typeof errorCallback === 'function') {
                errorCallback(error);
            }
        }
    });
}

/**
 * @FUNCTIONAL_FUNCTION: Lấy các action buttons từ controller
 * @param {number} controllerId - ID của controller
 */ 
function downloadActionButtons(controllerId, callback, errorCallback) {
    // AJAX request to get action buttons
    $.ajax({
        url: admin_url + 'topics/controllers/get_action_buttons/' + controllerId,
        type: 'GET',
        responseType: 'json',
        dataType: 'json',
        success: (response) => {
            if (typeof callback === 'function') {
                callback(response);
            }
        },
        error: () => {
            if (typeof errorCallback === 'function') {
                errorCallback();
            }
        }
    });
}