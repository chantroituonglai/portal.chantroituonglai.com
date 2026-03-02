# Draft Writing Functionality Overview

## Architecture Overview

The Draft Writing feature is designed as a modular component within the Perfex CRM Topics module. It follows a client-server architecture with:

1. **Frontend UI Components**: Modal interface with rich text editor, analysis panels, and storage controls
2. **Backend Processing**: PHP-based processors that handle requests and integrate with N8N workflows
3. **Integration Layer**: Connections to AI services, storage mechanisms, and content analysis tools

## File Structure and Responsibilities

### Frontend Files (JavaScript & HTML)

1. **Main Modal Display**:
   - `views/includes/displayDraftWriter/topic_detail_action_buttons_display_script_displayDraftWriter_modal.php`: Contains the modal structure and initialization logic

2. **Result Display**:
   - `views/includes/topic_detail_action_buttons_display_script_displayDraftWritingResult.php`: Handles displaying, formatting, and processing workflow responses

3. **Functional Components**:
   - `views/includes/displayDraftWriter/topic_detail_action_buttons_display_script_displayDraftWriter_ai.php`: AI integration functionality
   - `views/includes/displayDraftWriter/topic_detail_action_buttons_display_script_displayDraftWriter_storage.php`: Local storage implementation
   - `views/includes/displayDraftWriter/topic_detail_action_buttons_display_script_displayDraftWriter_search.php`: Search functionality
   - `views/includes/displayDraftWriter/topic_detail_action_buttons_display_script_displayDraftWriter_analysis.php`: Content analysis tools

4. **Styling**:
   - `assets/css/draft_writer.css`: Specific styles for the Draft Writer UI

### Backend Files (PHP)

1. **Controller**:
   - `controllers/Topics.php`: Contains endpoint handlers like `process_data()` and integration with topic management

2. **Processor**:
   - `helpers/topic_action_processor_DraftWritingProcessor_helper.php`: Specialized processor that extends BaseTopicActionProcessor for draft writing functionality

3. **Supporting Files**:
   - Various model files that interact with the database
   - Integration helpers for external services

## Key Functions and Their Purposes

### Frontend JavaScript Functions

#### Modal and UI Management
- `window.DraftWriter.loadDraftWriterCSS()`: Dynamically loads CSS for the Draft Writer
- `window.DraftWriter.addFixedFooterStyles()`: Adds styles for the fixed footer in the modal
- `displayDraftWritingResult(data, workflowData)`: Main function to display workflow results

#### Content Processing
- `decodeHtmlEntities(encodedText)`: Decodes HTML entities in received content
- `cleanHtmlContent(content)`: Cleans HTML content for security and formatting
- `loadContentFromWorkflowResponse(data)`: Extracts and processes content from N8N response

#### Storage Management
- `window.DraftWriter.storage.saveDraft(content)`: Saves draft to local storage
- `window.DraftWriter.storage.loadDraft()`: Loads draft from local storage
- `window.DraftWriter.storage.startAutoSave()`: Starts the auto-save functionality

#### Analysis Tools
- `extractKeywords(text)`: Extracts keywords from content for analysis
- `calculateKeywordDensity(text, keyword)`: Calculates keyword density for SEO
- `updateContentAnalysis()`: Updates all content analysis panels

#### AI Integration
- `window.DraftWriter.ai.improve(content, type, style, tone)`: Improves content using AI
- `window.DraftWriter.ai.factCheck(content, checkType)`: Performs fact-checking on content
- `window.DraftWriter.ai.search(query, searchType, limit)`: Searches for relevant content

### Backend PHP Methods

#### Processing
- `DraftWritingProcessor->process($topic_id, $action_data)`: Main processing method
- `DraftWritingProcessor->processStep1($topic_id, $action_data)`: Processes step 1 of draft writing
- `DraftWritingProcessor->prepareN8nData($topic, $action_data)`: Prepares data for N8N workflow

#### Controller Methods
- `Topics->process_data($id, $action_type_code)`: Handles processing requests
- `Topics->get_data_processor($action_type_code)`: Gets the appropriate processor

## Data Flow and Processing Logic

1. **Initialization Flow**:
   - User clicks on Draft Writing action button
   - `process_data()` controller method is called
   - DraftWritingProcessor is instantiated
   - `process()` method is called with topic ID and action data

2. **N8N Workflow Integration**:
   - `prepareN8nData()` formats data for N8N
   - `send_to_n8n()` sends data to the configured workflow
   - N8N processes the request and returns structured content

3. **Result Processing**:
   - `displayDraftWritingResult()` receives the workflow response
   - Content is extracted, cleaned, and formatted
   - UI is updated with the processed content

4. **Editing and Storage Flow**:
   - User edits content in the rich text editor
   - Changes trigger analysis updates via event listeners
   - Auto-save functionality periodically saves to local storage

## UI Components and Their Interactions

1. **Main Modal**: Full-screen editor with two-column layout
   - Left: Content editor with rich text capabilities
   - Right: Analysis panels and tools

2. **Rich Text Editor**: TinyMCE integration with custom toolbar
   - Formatting tools
   - Media insertion
   - Table management
   - Special characters

3. **Analysis Panels**: Collapsible sections for different analysis tools
   - Keyword analysis
   - SEO suggestions
   - Readability metrics
   - Content structure analysis

4. **Storage Controls**: UI elements for managing drafts
   - Auto-save toggle
   - Manual save button
   - Load from storage option
   - Clear storage button

## Integration Points

### N8N Workflow Integration
- Configured in admin settings
- Requires valid workflow ID
- Communicates via HTTP webhooks
- Structured JSON data format

### AI Services Integration
- Content improvement
- Fact checking
- Relevant search
- Keyword extraction and analysis

### External Storage Options
- Browser local storage (primary)
- Future extensibility for server-side storage

## Extension Guidelines

When adding new features to the Draft Writer, follow these guidelines:

1. **Use Existing Components**: 
   - Extend the `window.DraftWriter` namespace
   - Add methods to appropriate sub-objects (ai, storage, analysis)

2. **Follow UI Patterns**:
   - Use the existing modal structure
   - Add panels to the appropriate column
   - Maintain responsive design principles

3. **Processing Logic**:
   - Extend the DraftWritingProcessor for new backend functionality
   - Add new step methods following the existing pattern
   - Validate inputs thoroughly

4. **Event Handling**:
   - Use debounced functions for performance-intensive operations
   - Follow the event delegation pattern for dynamic elements
   - Maintain consistent error handling

5. **Integration**:
   - Use the established API patterns for external services
   - Ensure proper error handling and fallbacks
   - Document integration requirements

## Common Development Tasks

### Adding a New Analysis Tool
1. Add UI panel to the analysis column in `topic_detail_action_buttons_display_script_displayDraftWriter_modal.php`
2. Add related functions to `topic_detail_action_buttons_display_script_displayDraftWriter_analysis.php`
3. Connect to event listeners for content changes

### Adding AI Features
1. Add new methods to `window.DraftWriter.ai` in `topic_detail_action_buttons_display_script_displayDraftWriter_ai.php`
2. Create UI controls in the modal
3. Add event handlers for the new features

### Modifying Content Processing
1. Update `loadContentFromWorkflowResponse()` in `topic_detail_action_buttons_display_script_displayDraftWritingResult.php`
2. Ensure proper HTML handling and entity decoding
3. Update the content structure building logic

### Extending Backend Processing
1. Add new methods to `DraftWritingProcessor` in `topic_action_processor_DraftWritingProcessor_helper.php`
2. Update `process()` to handle new steps or actions
3. Add validation for new inputs in `validate()`

## Best Practices

1. **Security First**:
   - Always sanitize user inputs
   - Validate data from external sources
   - Use prepared statements for database operations

2. **Performance Optimization**:
   - Debounce UI update functions
   - Optimize DOM manipulations
   - Cache frequently used data

3. **Error Handling**:
   - Provide clear error messages
   - Log errors for debugging
   - Fail gracefully with user feedback

4. **Code Style**:
   - Follow existing naming conventions
   - Method names: camelCase
   - Variable names: snake_case
   - Comment complex logic

5. **Testing**:
   - Test with various data sets
   - Verify N8N integration
   - Check local storage functionality
   - Validate content processing

## Troubleshooting Common Issues

1. **N8N Connection Issues**:
   - Verify N8N host settings
   - Check workflow ID
   - Inspect error responses in console

2. **Content Formatting Problems**:
   - Check HTML entity handling
   - Verify TinyMCE configuration
   - Inspect raw workflow response

3. **Storage Issues**:
   - Clear browser cache
   - Check local storage limits
   - Verify topic ID consistency

4. **UI Rendering Problems**:
   - Verify CSS loading
   - Check browser compatibility
   - Inspect DOM structure
