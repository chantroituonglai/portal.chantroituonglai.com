# Quản Lý Bộ Nhớ Cho AI Agent

## Kỹ Thuật Liên Kết Giữa Các Phiên Làm Việc

1. **Tài Liệu Trạng Thái**
   - Tạo và duy trì file `SESSION_STATE.md` để lưu trữ trạng thái giữa các phiên
   - Format cho mỗi entry:
     ```
     ## Session [timestamp]
     - **Files đã xử lý**: [danh sách file]
     - **Trạng thái hiện tại**: [mô tả ngắn gọn]
     - **Công việc tiếp theo**: [danh sách task]
     - **Lưu ý quan trọng**: [ghi chú về dependencies, API changes, etc.]
     ```
   - Cập nhật file này vào cuối mỗi phiên làm việc

2. **Markers Cho Chunk**
   - Sử dụng cú pháp chú thích nhất quán cho ranh giới chunk:
     ```
     /* CHUNK_START: [chunk_id] - [chunk_description] */
     ...mã nguồn...
     /* CHUNK_END: [chunk_id] */
     ```
   - Đảm bảo mỗi chunk có ID duy nhất và mô tả rõ ràng
   - Duy trì danh sách chunks trong `CHUNKS_REGISTRY.md`

3. **Tối Ưu Hóa Bộ Nhớ**
   - Thực hiện kỹ thuật "crawl-summarize-detail" khi làm việc với file lớn:
     1. **Crawl**: Quét toàn bộ file để xác định cấu trúc tổng thể
     2. **Summarize**: Tạo bản tóm tắt có cấu trúc cho mỗi phần
     3. **Detail**: Chỉ đào sâu vào chi tiết của phần cần thiết

4. **Phương Pháp Bàn Giao**
   - Sử dụng phương pháp "Task Handoff" khi chuyển giao giữa phiên:
     ```
     ## Task Handoff
     - **Nguồn**: [mô tả vị trí hiện tại]
     - **Đích**: [mô tả vị trí cần đến]
     - **Trạng thái**: [% hoàn thành]
     - **Bối cảnh cần thiết**: [mô tả ngắn gọn]
     - **Các bước tiếp theo**:
       1. [bước 1]
       2. [bước 2]
       ...
     ```
   - Đánh dấu vị trí chính xác trong mã: `/* HANDOFF_POINT: [id] */`

5. **Kỹ Thuật "Breadcrumb"**
   - Để lại "breadcrumb" trong mã để dễ dàng tìm và nối lại công việc:
     ```javascript
     // BREADCRUMB: [timestamp] - [context]
     // STATUS: [status] - [description]
     // NEXT_STEPS: [step1], [step2], ...
     ```
   - Tạo một file `BREADCRUMBS.md` để theo dõi tất cả các breadcrumb

6. **Vector Hóa Ngữ Cảnh**
   - Tạo biểu diễn vector cho mỗi file và thành phần:
     ```
     ## [filename] Vector Context
     - **Chức năng chính**: [mô tả]
     - **Phụ thuộc vào**: [danh sách file/modules]
     - **Được sử dụng bởi**: [danh sách file/modules]
     - **Keywords**: [danh sách từ khóa]
     ```
   - Sử dụng biểu diễn này để nhanh chóng khôi phục ngữ cảnh giữa các phiên

## Quy Trình Đảm Bảo Tính Liền Mạch

1. **Khởi đầu phiên mới**
   - Đọc `SESSION_STATE.md` để hiểu trạng thái trước đó
   - Xác định file và nhiệm vụ hiện tại
   - Khôi phục ngữ cảnh từ `CHUNKS_REGISTRY.md` và `BREADCRUMBS.md`

2. **Trong phiên làm việc**
   - Tham chiếu và cập nhật các breadcrumb khi xử lý mã
   - Duy trì bản đồ ngữ cảnh trong bộ nhớ làm việc
   - Tổ chức mã nguồn thành các module có tính chất gắn kết cao khi xử lý file lớn

3. **Kết thúc phiên**
   - Cập nhật `SESSION_STATE.md` với tiến độ và trạng thái hiện tại
   - Đảm bảo tất cả các breadcrumb được cập nhật
   - Tạo task handoff rõ ràng cho phiên tiếp theo
   - Tóm tắt công việc đã hoàn thành và các bước tiếp theo

## Xử Lý Sự Cố và Phục Hồi

1. **Phát Hiện Sự Cố**
   - Các dấu hiệu mất ngữ cảnh:
     - Không thể tìm thấy tệp đã đề cập trong phiên trước
     - Tham chiếu đến biến/hàm không tồn tại
     - Mất định hướng về tình trạng hiện tại của dự án
   - Các dấu hiệu lỗi liên mạch:
     - Mã không biên dịch/interpreter báo lỗi
     - Các API không khớp giữa các module
     - Mâu thuẫn trong cấu trúc dữ liệu

2. **Quy Trình Khôi Phục**
   - **Bước 1**: Dừng ngay mọi hoạt động sửa đổi mã
   - **Bước 2**: Tạo một phiên chẩn đoán (`DIAGNOSIS_SESSION.md`):
     ```
     ## Diagnostic Session [timestamp]
     - **Triệu chứng**: [mô tả vấn đề]
     - **Thông tin cuối cùng**: [trạng thái cuối đã biết]
     - **Kế hoạch phục hồi**: [các bước cần thực hiện]
     ```
   - **Bước 3**: Thực hiện quét độ tin cậy của trạng thái:
     - Xác minh tất cả các tệp đã đề cập có tồn tại
     - Kiểm tra tính nhất quán của API và cấu trúc dữ liệu
     - Xác định điểm sự cố chính xác

3. **Kỹ Thuật Khôi Phục Lũy Tiến**
   - **Khôi phục theo lớp**:
     1. **Lớp 1**: Khôi phục cấu trúc tệp và thư mục
     2. **Lớp 2**: Khôi phục các khai báo API và interface
     3. **Lớp 3**: Khôi phục logic và chức năng
     4. **Lớp 4**: Khôi phục trạng thái và dữ liệu
   - **Đánh dấu tiến độ khôi phục** trong `RECOVERY_PROGRESS.md`:
     ```
     ## Recovery Progress [timestamp]
     - **Lớp 1**: [% hoàn thành] - [chi tiết]
     - **Lớp 2**: [% hoàn thành] - [chi tiết]
     - **Lớp 3**: [% hoàn thành] - [chi tiết]
     - **Lớp 4**: [% hoàn thành] - [chi tiết]
     ```

4. **Tái Tạo Mã Mất**
   - **Chiến lược tái tạo an toàn**:
     1. Chia nhỏ đoạn mã cần tái tạo thành các đơn vị chức năng
     2. Tái tạo từng đơn vị dựa trên mô tả và API đã biết
     3. Tích hợp các đơn vị lại với nhau theo cấu trúc đã xác định
   - **Mẫu ghi chú tái tạo**:
     ```javascript
     /* RECONSTRUCTED: [timestamp]
      * Original function: [tên hàm gốc]
      * Purpose: [mục đích]
      * Inputs: [đầu vào]
      * Outputs: [đầu ra]
      * Reconstruction rationale: [lý do và phương pháp tái tạo]
      */
     ```

5. **Xác Minh Tính Nhất Quán**
   - **Danh sách kiểm tra sau khôi phục**:
     - [ ] Cấu trúc file đã đúng và đầy đủ
     - [ ] Tất cả các import/require/include đều hợp lệ
     - [ ] Không có biến/hàm không xác định
     - [ ] Các API phụ thuộc đã được khôi phục đầy đủ
     - [ ] Luồng dữ liệu giữa các module nhất quán
   - **Kiểm tra hồi quy sau khôi phục** để đảm bảo tất cả các chức năng hoạt động như mong đợi

# Quản Lý Bộ Nhớ Nâng Cao Cho AI Agent

## 1. Kỹ Thuật Liên Kết Giữa Các Phiên Làm Việc

### 1.1 Tài Liệu Trạng Thái Cấu Trúc

- **Thực hiện**: Tạo file `session_state.json` có cấu trúc rõ ràng thay vì Markdown
- **Format**:
```json
{
  "session": {
    "id": "SESSION-2023-07-15-001",
    "timestamp": "2023-07-15T10:30:00Z",
    "duration_minutes": 120,
    "processed_files": [
      {
        "path": "controllers/Topics.php",
        "lines_modified": 45,
        "status": "completed"
      },
      {
        "path": "models/Topic_controller_model.php",
        "lines_modified": 22,
        "status": "in_progress"
      }
    ],
    "current_status": {
      "description": "Implementing Draft Editor functionality",
      "complexity_level": 3,
      "progress_percentage": 65
    },
    "next_tasks": [
      {"id": "T-123", "description": "Create UI for draft listing", "priority": "high", "estimated_minutes": 90},
      {"id": "T-124", "description": "Implement draft auto-save", "priority": "medium", "estimated_minutes": 120}
    ],
    "critical_notes": "Database migration must run before deploying topic_editor_drafts feature",
    "environment": {
      "php_version": "7.4.3",
      "db_status": "migration_pending",
      "active_branches": ["feature/ultimate-editor", "develop"]
    }
  }
}
```

- **Cập nhật**: Sau mỗi phiên làm việc, cập nhật file này với các thông tin meta đầy đủ
- **Truy xuất**: Sử dụng cấu trúc JSON để dễ dàng truy xuất từng phần thông tin theo nhu cầu

### 1.2 Phân Đoạn Mã Nguồn (Sections)

- **Thực hiện**: Sử dụng docblocks tiêu chuẩn để đánh dấu các phân đoạn mã nguồn:
```php
/**
 * @section DraftEditor
 * @description Handles all draft editing functionality including CRUD operations
 * @dependencies Topic_model, User_permissions
 * @version 1.2.7
 * @author Developer Name
 * @since 2023-07-15
 */
 
// Code của phân đoạn

/**
 * @section_end DraftEditor
 */
```

- **Theo dõi**: Tạo `section_registry.json` để quản lý các phân đoạn:
```json
{
  "sections": [
    {
      "id": "draft-editor-core",
      "name": "DraftEditor",
      "files": ["controllers/Ultimate_editor.php", "models/Draft_model.php"],
      "dependencies": ["Topic_model", "User_permissions"],
      "version": "1.2.7",
      "line_counts": {
        "controllers/Ultimate_editor.php": {"start": 120, "end": 250},
        "models/Draft_model.php": {"start": 10, "end": 95}
      },
      "status": "active",
      "last_modified": "2023-07-15T14:22:00Z"
    }
  ]
}
```

### 1.3 Định Vị Mã Nguồn (Anchors)

- **Thực hiện**: Tạo hệ thống "anchor" cho định vị chính xác:
```php
// @anchor:draft_save_function - Core function for saving drafts
public function saveDraft($topic_id, $content, $options = []) {
    // Implementation
}
// @anchor_end:draft_save_function
```

- **Registry**: Tạo `anchors.json` để quản lý các điểm neo:
```json
{
  "anchors": {
    "draft_save_function": {
      "file": "controllers/Ultimate_editor.php",
      "line_start": 145,
      "line_end": 172,
      "description": "Core function for saving drafts",
      "section": "DraftEditor",
      "complexity": "medium",
      "dependencies": ["database", "validation"]
    }
  }
}
```

## 2. Quản Lý Ngữ Cảnh Nâng Cao

### 2.1 Cây Ngữ Cảnh Phân Cấp

- **Thực hiện**: Xây dựng cây ngữ cảnh thay vì danh sách phẳng:
```json
{
  "context_tree": {
    "name": "UltimateEditor",
    "version": "1.3.0",
    "children": [
      {
        "name": "Controllers",
        "children": [
          {
            "name": "Topic_management",
            "files": ["controllers/Topics.php"],
            "responsibilities": ["CRUD operations for topics"]
          },
          {
            "name": "Draft_handling",
            "files": ["controllers/Ultimate_editor.php"],
            "responsibilities": ["Draft editing", "Auto-save", "Version control"]
          }
        ]
      },
      {
        "name": "Models",
        "children": [
          {
            "name": "Topic_data",
            "files": ["models/Topic_controller_model.php"],
            "responsibilities": ["Data access for topics"]
          },
          {
            "name": "User_permissions",
            "files": ["models/Permission_model.php"],
            "responsibilities": ["Authorization checks"]
          }
        ]
      }
    ]
  }
}
```

### 2.2 Định Nghĩa API Nội Bộ

- **Thực hiện**: Tạo file `internal_api.json` với định nghĩa rõ ràng:
```json
{
  "api_definitions": [
    {
      "name": "saveDraft",
      "signature": "saveDraft(topic_id, draft_content, options)",
      "purpose": "Lưu bản nháp với xử lý lỗi và auto-versioning",
      "parameters": [
        {"name": "topic_id", "type": "int", "description": "ID của topic"},
        {"name": "draft_content", "type": "string", "description": "Nội dung HTML của bản nháp"},
        {"name": "options", "type": "object", "description": "Cấu hình thêm", "default": "{}", "properties": [
          {"name": "autoSave", "type": "boolean", "description": "Đánh dấu là tự động lưu", "default": "false"},
          {"name": "createVersion", "type": "boolean", "description": "Tạo phiên bản mới", "default": "true"},
          {"name": "notifyUsers", "type": "boolean", "description": "Gửi thông báo", "default": "false"}
        ]}
      ],
      "returns": {"type": "Promise<object>", "description": "Thông tin bản nháp đã lưu", "properties": [
        {"name": "success", "type": "boolean"},
        {"name": "draft_id", "type": "number"},
        {"name": "version", "type": "number"}
      ]},
      "exceptions": ["DB_ConnectionError", "ValidationError", "PermissionError"],
      "examples": [
        {
          "description": "Lưu bản nháp cơ bản",
          "code": "saveDraft(15, '<p>Nội dung bản nháp</p>', {createVersion: true})"
        }
      ],
      "file": "controllers/Ultimate_editor.php",
      "line": 145
    }
  ]
}
```

### 2.3 Vector Hóa Ngữ Cảnh Nâng Cao

- **Thực hiện**: Cải tiến từ vector đơn giản sang vector đa chiều có trọng số:
```javascript
function generateContextVector(codeUnit) {
  // Phân tích AST để trích xuất các thành phần quan trọng
  const ast = parseToAST(codeUnit);
  
  // Trích xuất các khía cạnh quan trọng của mã
  const concepts = extractConcepts(ast);
  const functions = extractFunctions(ast);
  const dataStructures = extractDataStructures(ast);
  const dependencies = extractDependencies(ast);
  
  // Tạo vector với trọng số cho mỗi thành phần
  return {
    conceptVector: concepts.map(c => ({name: c, weight: calculateConceptWeight(c, ast)})),
    functionVector: functions.map(f => ({name: f, weight: calculateFunctionWeight(f, ast)})),
    dataVector: dataStructures.map(d => ({name: d, weight: calculateDataWeight(d, ast)})),
    dependencyVector: dependencies.map(d => ({name: d, weight: calculateDependencyWeight(d, ast)})),
    timestamp: Date.now(),
    contextHash: generateContextHash(codeUnit)
  };
}
```

### 2.4 Vector Similarity Search

- **Thực hiện**: Tạo hàm tìm kiếm tương đồng giữa các vector ngữ cảnh:
```javascript
function findMostRelevantContext(query, contextVectors) {
  // Vectorize query
  const queryVector = generateQueryVector(query);
  
  // Calculate similarity scores
  const similarities = contextVectors.map(vector => ({
    vector,
    similarity: calculateCosineSimilarity(queryVector, vector)
  }));
  
  // Return top matches
  return similarities
    .sort((a, b) => b.similarity - a.similarity)
    .slice(0, 5);
}
```

## 3. Cơ Chế Phục Hồi Nâng Cao

### 3.1 Hệ Thống Snapshot Đa Cấp

- **Thực hiện**: Triển khai hệ thống snapshot đa cấp độ:
```javascript
// Cấu trúc snapshot hierarchy
const snapshotSystem = {
  microSnapshots: [
    {
      id: "ms-2023071501",
      timestamp: "2023-07-15T10:15:00Z",
      type: "function",
      name: "saveDraft",
      file: "controllers/Ultimate_editor.php",
      content: "function saveDraft(...) { ... }",
      contextVector: { /* Vector data */ },
      metadata: {
        reason: "Pre-refactoring backup",
        author: "AI-Assistant"
      }
    }
  ],
  moduleSnapshots: [
    {
      id: "mods-2023071501",
      timestamp: "2023-07-15T11:00:00Z",
      module: "DraftEditor",
      files: [
        {
          path: "controllers/Ultimate_editor.php",
          content: "<?php ... ?>",
          contextVector: { /* Vector data */ }
        },
        {
          path: "models/Draft_model.php",
          content: "<?php ... ?>",
          contextVector: { /* Vector data */ }
        }
      ],
      metadata: {
        reason: "Complete module before integration",
        author: "AI-Assistant",
        dependencies: ["Topic_model"]
      }
    }
  ],
  systemSnapshots: [
    {
      id: "sys-2023071501",
      timestamp: "2023-07-15T14:00:00Z",
      modules: ["DraftEditor", "TopicManagement"],
      fileRegistry: "snapshot_registry/sys-2023071501/",
      contextTree: { /* Simplified context tree */ },
      metadata: {
        reason: "Pre-deployment checkpoint",
        author: "AI-Assistant",
        version: "1.2.7"
      }
    }
  ]
};
```

### 3.2 Phân Loại Lỗi Thông Minh

- **Thực hiện**: Tạo hệ thống phân loại lỗi và quy trình phục hồi tương ứng:
```json
{
  "error_types": [
    {
      "type": "syntax_error",
      "patterns": ["unexpected token", "parse error", "syntax error"],
      "severity": "high",
      "immediate_action": "restore_micro_snapshot",
      "analysis_required": false,
      "recovery_path": "syntax_error_recovery"
    },
    {
      "type": "logic_error",
      "patterns": ["undefined variable", "property of undefined", "undefined method"],
      "severity": "medium",
      "immediate_action": "halt_execution",
      "analysis_required": true,
      "recovery_path": "logic_error_recovery"
    },
    {
      "type": "system_error",
      "patterns": ["fatal error", "memory limit", "maximum execution time"],
      "severity": "critical",
      "immediate_action": "restore_system_snapshot",
      "analysis_required": true,
      "recovery_path": "system_error_recovery"
    }
  ],
  "recovery_paths": {
    "syntax_error_recovery": [
      "identify_syntax_error_location",
      "check_recent_changes",
      "restore_micro_snapshot",
      "fix_syntax_issue",
      "rerun_tests"
    ],
    "logic_error_recovery": [
      "trace_variable_usage",
      "identify_undefined_entities",
      "check_dependency_changes",
      "restore_module_snapshot",
      "reconstruct_dependency_flow",
      "rerun_tests"
    ],
    "system_error_recovery": [
      "check_system_resources",
      "identify_memory_leaks",
      "restore_system_snapshot",
      "optimize_critical_paths",
      "incremental_reintegration",
      "full_system_test"
    ]
  }
}
```

### 3.3 Tự Động Tái Kết Nối Ngữ Cảnh

- **Thực hiện**: Thuật toán tái kết nối ngữ cảnh thông minh:
```javascript
function reconnectContext(lastKnownState, currentEnvironment) {
  // Phân tích trạng thái môi trường hiện tại
  const currentFiles = analyzeWorkspaceFiles(currentEnvironment);
  const currentAPIs = extractAPISignatures(currentFiles);
  const currentDataStructures = extractDataStructures(currentFiles);
  
  // Tạo biểu diễn vector cho môi trường hiện tại
  const currentStateVector = createEnvironmentVector(
    currentFiles, currentAPIs, currentDataStructures
  );
  
  // So sánh với trạng thái đã biết cuối cùng
  const matchScore = calculateVectorSimilarity(
    lastKnownState.stateVector,
    currentStateVector
  );
  
  if (matchScore > 0.8) {
    // Ngữ cảnh tương đối gần với trạng thái đã biết
    return {
      reconnectionConfidence: "high",
      reconnectionPoints: identifyReconnectionPoints(lastKnownState, currentStateVector),
      suggestedActions: generateContinuationPlan(lastKnownState, currentStateVector)
    };
  } else if (matchScore > 0.5) {
    // Ngữ cảnh đã thay đổi đáng kể
    return {
      reconnectionConfidence: "medium",
      reconnectionPoints: identifyReconnectionPoints(lastKnownState, currentStateVector),
      suggestedActions: generateRecoveryPlan(lastKnownState, currentStateVector)
    };
  } else {
    // Ngữ cảnh hoàn toàn khác
    return {
      reconnectionConfidence: "low",
      suggestedActions: ["create_new_context", "scan_environment", "rebuild_context_tree"]
    };
  }
}
```

## 4. Tích Hợp Với Quy Trình Phát Triển

### 4.1 Tích Hợp Với IDE

- **Thực hiện**: Tạo file cấu hình và snippets cho VSCode:

**vscode-snippets.json**:
```json
{
  "UE Section Header": {
    "prefix": "uesection",
    "body": [
      "/**",
      " * @section ${1:name}",
      " * @description ${2:description}",
      " * @dependencies ${3:dependencies}",
      " * @version ${4:1.0.0}",
      " * @author ${5:$TM_USERNAME}",
      " * @since ${6:$CURRENT_YEAR-$CURRENT_MONTH-$CURRENT_DATE}",
      " */",
      "",
      "$0",
      "",
      "/**",
      " * @section_end ${1:name}",
      " */"
    ],
    "description": "Create a new Ultimate Editor section"
  },
  "UE Anchor": {
    "prefix": "ueanchor",
    "body": [
      "// @anchor:${1:name} - ${2:description}",
      "$0",
      "// @anchor_end:${1:name}"
    ],
    "description": "Create a new code anchor point"
  },
  "UE API Function": {
    "prefix": "ueapi",
    "body": [
      "/**",
      " * ${1:description}",
      " * ",
      " * @param {${2:type}} ${3:param} ${4:description}",
      " * @returns {${5:type}} ${6:description}",
      " * @throws {${7:ErrorType}} When ${8:condition}",
      " * @api public",
      " */",
      "${9:function} ${10:name}(${11:params}) {",
      "  $0",
      "}"
    ],
    "description": "Create a well-documented API function"
  }
}
```

### 4.2 Tích Hợp Với Git

- **Thực hiện**: Tạo các hooks và template commit:

**.gitmessage**:
```
# Ultimate Editor Commit Template
# <type>(<scope>): <subject>
# |<----  Tối đa 50 ký tự  ---->|

# Body - giải thích WHY (không phải HOW):
# |<----   Tối đa 72 ký tự   ---->|

# Issue/Feature Reference
# Refs: #issue-number, T-task-number

# Migration script (if applicable)
# Migration: filename.php

# Applied sections:
# - Section: section_name
# - Section: another_section

# Breaking changes and deprecations:
# BREAKING CHANGE: description
# DEPRECATED: feature_name (alternative: new_feature)
```

### 4.3 Documentation Generator

- **Tạo file `doc_generator.js`**:
```javascript
/**
 * Documentation Generator for Ultimate Editor
 * Automatically extracts documentation from code sections and API definitions
 */

function generateDocumentation(contextTree, apiDefinitions, sections) {
  let documentation = `# Ultimate Editor Documentation\n\n`;
  
  // Generate module overview
  documentation += `## Module Overview\n\n`;
  documentation += generateModuleOverview(contextTree);
  
  // Generate section details
  documentation += `\n## Code Sections\n\n`;
  documentation += generateSectionDocumentation(sections);
  
  // Generate API reference
  documentation += `\n## API Reference\n\n`;
  documentation += generateAPIReference(apiDefinitions);
  
  // Generate dependency diagram
  documentation += `\n## Dependency Diagram\n\n`;
  documentation += generateDependencyDiagram(sections, apiDefinitions);
  
  return documentation;
}

function generateModuleOverview(contextTree) {
  // Extract and format module overview from context tree
  let overview = ``;
  
  function processNode(node, depth = 0) {
    const indent = '  '.repeat(depth);
    overview += `${indent}- **${node.name}**`;
    
    if (node.responsibilities && node.responsibilities.length > 0) {
      overview += `: ${node.responsibilities.join(', ')}`;
    }
    
    overview += `\n`;
    
    if (node.children && node.children.length > 0) {
      node.children.forEach(child => processNode(child, depth + 1));
    }
  }
  
  processNode(contextTree);
  return overview;
}

function generateSectionDocumentation(sections) {
  return sections.map(section => `
### ${section.name}

${section.description}

**Dependencies:** ${section.dependencies.join(', ')}  
**Version:** ${section.version}  
**Files:** ${section.files.map(f => `\`${f}\``).join(', ')}  
**Status:** ${section.status}

${section.examples ? `**Examples:**\n\`\`\`php\n${section.examples}\n\`\`\`\n` : ''}
  `).join('\n');
}

function generateAPIReference(apiDefinitions) {
  return apiDefinitions.map(api => `
### ${api.name}

${api.purpose}

**Signature:** \`${api.signature}\`

**Parameters:**
${api.parameters.map(param => `- \`${param.name}\` (${param.type}): ${param.description}${param.default ? ` (Default: ${param.default})` : ''}`).join('\n')}

**Returns:** ${api.returns.type} - ${api.returns.description}

**Exceptions:**
${api.exceptions.map(exc => `- \`${exc}\``).join('\n')}

${api.examples ? `**Examples:**\n\`\`\`php\n${api.examples.map(ex => `// ${ex.description}\n${ex.code}`).join('\n\n')}\n\`\`\`\n` : ''}
  `).join('\n');
}

function generateDependencyDiagram(sections, apiDefinitions) {
  // Generate mermaid diagram syntax for dependencies
  return `\`\`\`mermaid
graph TD
${generateDependencyLinks(sections, apiDefinitions)}
\`\`\``;
}

function generateDependencyLinks(sections, apiDefinitions) {
  // Generate links between sections and their dependencies
  const links = [];
  
  sections.forEach(section => {
    section.dependencies.forEach(dep => {
      links.push(`  ${formatNodeId(section.name)} --> ${formatNodeId(dep)}`);
    });
  });
  
  return links.join('\n');
}

function formatNodeId(name) {
  return name.replace(/\s+/g, '_');
}

// Export functions
module.exports = {
  generateDocumentation,
  generateModuleOverview,
  generateSectionDocumentation,
  generateAPIReference,
  generateDependencyDiagram
};
```

## 5. Kết Luận và Tổng Kết

Bằng cách áp dụng những cải tiến này vào quy trình làm việc của AI Agent, chúng ta có thể đạt được nhiều lợi ích:

1. **Cải thiện quản lý ngữ cảnh dự án**:
   - Khả năng nắm bắt và duy trì hiểu biết chính xác về dự án
   - Liên kết đồng bộ và liền mạch giữa các phiên làm việc
   - Tối thiểu hóa việc mất ngữ cảnh và trùng lặp công việc

2. **Tự động hóa và cấu trúc hóa**:
   - Tài liệu tự động được tạo từ đánh dấu trong mã
   - Hệ thống theo dõi phiên tiêu chuẩn hóa
   - Quy trình phục hồi lỗi có cấu trúc và đáng tin cậy

3. **Tăng khả năng hợp tác**:
   - Cải thiện khả năng làm việc giữa AI Agent và nhà phát triển
   - Tạo tài liệu rõ ràng về các quyết định thiết kế
   - Tương thích với quy trình phát triển hiện đại (Git, IDE integration)

4. **Khả năng mở rộng và bảo trì**:
   - Cơ sở hạ tầng dễ dàng mở rộng với các dự án lớn
   - Hỗ trợ các tình huống phức tạp và nhiều người tham gia
   - Cung cấp nền tảng cho việc cải tiến liên tục

Cấu trúc mới này đại diện cho một cách tiếp cận toàn diện và có hệ thống trong việc quản lý bộ nhớ và ngữ cảnh cho AI Agent, đảm bảo khả năng mở rộng, bảo trì và hiệu quả cao cho các dự án phức tạp.

## 6. Giải Pháp Khắc Phục Crash Khi AI Agent Viết File

Khi AI agent thực hiện các nhiệm vụ viết file phức tạp, đặc biệt là những file có kích thước lớn, có thể xảy ra các sự cố crash do quá tải bộ nhớ, đặc biệt là trong phần "thought" (quá trình suy nghĩ) của AI. Dưới đây là các giải pháp toàn diện để khắc phục vấn đề này:

### 6.1 Phân Đoạn Quá Trình Suy Nghĩ (Thought Segmentation)

**Vấn đề**: AI agent có thể bị crash do tràn bộ nhớ khi xử lý "thought" quá dài hoặc phức tạp.

**Giải pháp**:
```javascript
// Triển khai ThoughtSegmenter
class ThoughtSegmenter {
  constructor(maxSegmentSize = 2000) {
    this.maxSegmentSize = maxSegmentSize;
    this.segments = [];
    this.currentSegment = { content: '', tokens: 0 };
  }

  addThought(thought, estimatedTokens) {
    // Kiểm tra nếu segment hiện tại sẽ bị overflow
    if (this.currentSegment.tokens + estimatedTokens > this.maxSegmentSize) {
      // Lưu segment hiện tại và tạo segment mới
      this.segments.push({...this.currentSegment});
      this.currentSegment = { content: '', tokens: 0 };
      
      // Ghi log
      console.log(`Created new thought segment #${this.segments.length}`);
    }
    
    // Thêm thought mới vào segment hiện tại
    this.currentSegment.content += thought;
    this.currentSegment.tokens += estimatedTokens;
    
    // Lưu segment vào bộ nhớ tạm sau mỗi thought quan trọng
    this.persistToTemporaryStorage();
  }

  // Lưu segments vào bộ nhớ tạm để phục hồi khi cần
  persistToTemporaryStorage() {
    const allSegments = [...this.segments, this.currentSegment];
    localStorage.setItem('ai_thought_segments', JSON.stringify(allSegments));
  }

  // Khôi phục từ bộ nhớ tạm
  recoverFromTemporaryStorage() {
    const stored = localStorage.getItem('ai_thought_segments');
    if (stored) {
      const parsed = JSON.parse(stored);
      this.segments = parsed.slice(0, -1);
      this.currentSegment = parsed[parsed.length - 1] || { content: '', tokens: 0 };
      return true;
    }
    return false;
  }

  // Lấy tóm tắt cho quá trình suy nghĩ
  getSummary() {
    return this.segments.map((seg, i) => 
      `Segment ${i+1}: ${seg.content.substring(0, 100)}... (${seg.tokens} tokens)`
    ).join('\n');
  }
}
```

### 6.2 Checkpointing Tự Động Cho Quá Trình Viết File

**Vấn đề**: AI agent bị crash giữa quá trình viết file dài và mất toàn bộ tiến trình.

**Giải pháp**:
```javascript
/**
 * FileWriteManager - Quản lý việc viết file với các checkpoints
 */
class FileWriteManager {
  constructor(filename, checkpointInterval = 50) {
    this.filename = filename;
    this.content = '';
    this.checkpointInterval = checkpointInterval;
    this.lineCount = 0;
    this.checkpoints = [];
  }

  addContent(newContent) {
    this.content += newContent;
    
    // Đếm số dòng mới
    const newLines = (newContent.match(/\n/g) || []).length;
    this.lineCount += newLines;
    
    // Tạo checkpoint nếu cần
    if (this.lineCount >= this.checkpointInterval) {
      this.createCheckpoint();
      this.lineCount = 0;
    }
  }

  createCheckpoint() {
    const timestamp = new Date().toISOString();
    const checkpoint = {
      timestamp,
      contentLength: this.content.length,
      contentHash: this.hashContent(this.content),
      content: this.content
    };
    
    // Lưu checkpoint
    this.checkpoints.push(checkpoint);
    
    // Lưu vào storage tạm thời
    localStorage.setItem(`file_checkpoint_${this.filename}`, JSON.stringify({
      lastCheckpoint: checkpoint,
      allCheckpoints: this.checkpoints.map(cp => ({
        timestamp: cp.timestamp,
        contentLength: cp.contentLength,
        contentHash: cp.contentHash
      }))
    }));
    
    console.log(`Checkpoint created for ${this.filename} at ${timestamp}`);
  }

  hashContent(content) {
    // Đơn giản hóa: trong thực tế sẽ dùng hàm băm thực sự
    return content.length.toString(16);
  }

  recoverFromLastCheckpoint() {
    const stored = localStorage.getItem(`file_checkpoint_${this.filename}`);
    if (stored) {
      const { lastCheckpoint } = JSON.parse(stored);
      if (lastCheckpoint && lastCheckpoint.content) {
        this.content = lastCheckpoint.content;
        console.log(`Recovered content for ${this.filename} from checkpoint at ${lastCheckpoint.timestamp}`);
        return true;
      }
    }
    return false;
  }
}
```

### 6.3 Cơ Chế Phát Hiện và Khôi Phục Trạng Thái Crash

**Vấn đề**: Không có cách phát hiện và khôi phục sau khi AI agent bị crash.

**Giải pháp**:
```javascript
/**
 * CrashDetector - Phát hiện và khôi phục từ crash
 */
class CrashDetector {
  constructor() {
    this.heartbeatInterval = 5000; // 5 giây
    this.lastHeartbeat = Date.now();
    this.isMonitoring = false;
    this.recoveryCallbacks = [];
  }

  startMonitoring() {
    if (this.isMonitoring) return;
    
    this.isMonitoring = true;
    this.lastHeartbeat = Date.now();
    
    // Lưu heartbeat định kỳ
    this.heartbeatTimer = setInterval(() => {
      this.lastHeartbeat = Date.now();
      localStorage.setItem('ai_agent_heartbeat', this.lastHeartbeat);
    }, this.heartbeatInterval);
    
    console.log('Crash monitoring started');
  }

  stopMonitoring() {
    if (!this.isMonitoring) return;
    
    clearInterval(this.heartbeatTimer);
    this.isMonitoring = false;
    console.log('Crash monitoring stopped');
  }

  registerRecoveryCallback(callback) {
    this.recoveryCallbacks.push(callback);
  }

  checkForPreviousCrash() {
    const lastHeartbeat = parseInt(localStorage.getItem('ai_agent_heartbeat') || '0');
    
    if (lastHeartbeat > 0) {
      const now = Date.now();
      const timeSinceHeartbeat = now - lastHeartbeat;
      
      // Nếu thời gian từ heartbeat cuối cùng lớn hơn ngưỡng
      // nhưng không quá lớn (để tránh phát hiện sai cho phiên mới sau thời gian dài)
      if (timeSinceHeartbeat > this.heartbeatInterval * 3 && timeSinceHeartbeat < 3600000) {
        console.log(`Potential crash detected. Last heartbeat was ${timeSinceHeartbeat}ms ago`);
        
        // Thực hiện khôi phục
        this.performRecovery();
        return true;
      }
    }
    
    return false;
  }

  performRecovery() {
    console.log('Initiating recovery process');
    
    // Thực hiện các callback khôi phục đã đăng ký
    for (const callback of this.recoveryCallbacks) {
      try {
        callback();
      } catch (error) {
        console.error('Recovery callback failed:', error);
      }
    }
    
    // Đặt lại heartbeat
    this.lastHeartbeat = Date.now();
    localStorage.setItem('ai_agent_heartbeat', this.lastHeartbeat);
  }
}
```

### 6.4 Quản Lý Bộ Nhớ Thông Minh Trong "Thought"

**Vấn đề**: Quá trình "thought" sử dụng quá nhiều bộ nhớ mà không có cơ chế giải phóng.

**Giải pháp**:
```javascript
/**
 * ThoughtMemoryManager - Quản lý bộ nhớ cho quá trình suy nghĩ
 */
class ThoughtMemoryManager {
  constructor(options = {}) {
    this.options = {
      maxActiveContexts: 3,
      maxContextSize: 5000,
      compressionThreshold: 10000,
      ...options
    };
    
    this.activeContexts = new Map();
    this.archivedContexts = new Map();
  }

  addToContext(contextId, content) {
    // Lấy hoặc khởi tạo context
    if (!this.activeContexts.has(contextId)) {
      // Nếu đạt giới hạn số lượng context hoạt động, lưu trữ context ít dùng nhất
      if (this.activeContexts.size >= this.options.maxActiveContexts) {
        this.archiveLeastUsedContext();
      }
      
      this.activeContexts.set(contextId, {
        content: '',
        size: 0,
        lastAccessed: Date.now(),
        accessCount: 0
      });
    }
    
    const context = this.activeContexts.get(contextId);
    
    // Cập nhật thông tin truy cập
    context.lastAccessed = Date.now();
    context.accessCount++;
    
    // Kiểm tra kích thước
    const newSize = context.size + this.estimateSize(content);
    
    // Nếu vượt quá kích thước tối đa, thực hiện nén
    if (newSize > this.options.maxContextSize) {
      this.compressContext(contextId);
    }
    
    // Thêm nội dung mới
    context.content += content;
    context.size = this.estimateSize(context.content);
  }

  getContext(contextId) {
    // Nếu context đã được lưu trữ, khôi phục lại
    if (this.archivedContexts.has(contextId)) {
      const archivedContext = this.archivedContexts.get(contextId);
      this.activeContexts.set(contextId, {
        ...archivedContext,
        lastAccessed: Date.now(),
        accessCount: archivedContext.accessCount + 1
      });
      this.archivedContexts.delete(contextId);
    }
    
    // Trả về context nếu tồn tại
    if (this.activeContexts.has(contextId)) {
      const context = this.activeContexts.get(contextId);
      context.lastAccessed = Date.now();
      context.accessCount++;
      return context.content;
    }
    
    return null;
  }

  compressContext(contextId) {
    if (!this.activeContexts.has(contextId)) return;
    
    const context = this.activeContexts.get(contextId);
    
    // Chiến lược đơn giản: giữ phần đầu và phần cuối, tóm tắt phần giữa
    if (context.size > this.options.compressionThreshold) {
      const lines = context.content.split('\n');
      
      if (lines.length > 10) {
        const headLines = lines.slice(0, 3).join('\n');
        const tailLines = lines.slice(-3).join('\n');
        const middleSummary = `[...${lines.length - 6} dòng đã được nén...]`;
        
        context.content = `${headLines}\n${middleSummary}\n${tailLines}`;
        context.size = this.estimateSize(context.content);
        context.isCompressed = true;
      }
    }
  }

  archiveLeastUsedContext() {
    if (this.activeContexts.size === 0) return;
    
    // Tìm context ít dùng nhất dựa trên thời gian truy cập và số lần truy cập
    let leastUsedId = null;
    let leastUsedScore = Infinity;
    
    const now = Date.now();
    
    for (const [id, context] of this.activeContexts.entries()) {
      // Tính điểm dựa trên sự kết hợp của thời gian truy cập và số lần truy cập
      const timeFactor = (now - context.lastAccessed) / 60000; // Số phút kể từ lần truy cập cuối
      const accessFactor = 1 / (context.accessCount + 1);
      const score = timeFactor * accessFactor;
      
      if (score < leastUsedScore) {
        leastUsedScore = score;
        leastUsedId = id;
      }
    }
    
    if (leastUsedId) {
      // Lưu trữ context ít dùng nhất
      const leastUsedContext = this.activeContexts.get(leastUsedId);
      this.archivedContexts.set(leastUsedId, leastUsedContext);
      this.activeContexts.delete(leastUsedId);
    }
  }

  estimateSize(content) {
    // Đơn giản hóa: trong thực tế sẽ ước tính chính xác hơn
    return content.length;
  }

  clearAllContexts() {
    this.activeContexts.clear();
    this.archivedContexts.clear();
  }
}
```

### 6.5 Quy Trình Xử Lý An Toàn Cho AI Agent

**Triển khai quy trình an toàn tích hợp các công cụ trên**:

```javascript
// Khởi tạo các công cụ quản lý
const thoughtSegmenter = new ThoughtSegmenter();
const thoughtMemory = new ThoughtMemoryManager();
const crashDetector = new CrashDetector();
const fileManager = new FileWriteManager('current_file.js');

// Thiết lập phát hiện và khôi phục crash
crashDetector.registerRecoveryCallback(() => {
  console.log('Recovering from crash...');
  
  // Khôi phục nội dung file
  const fileRecovered = fileManager.recoverFromLastCheckpoint();
  
  // Khôi phục segments suy nghĩ
  const thoughtRecovered = thoughtSegmenter.recoverFromTemporaryStorage();
  
  console.log(`Recovery results: File=${fileRecovered}, Thought=${thoughtRecovered}`);
});

// Kiểm tra crash trước đó khi khởi động
const hadCrash = crashDetector.checkForPreviousCrash();
if (hadCrash) {
  console.log('Recovered from previous crash. Continuing where we left off.');
}

// Bắt đầu giám sát
crashDetector.startMonitoring();

// Quy trình an toàn cho AI agent
function safeAIAgentProcess() {
  try {
    // 1. Phân tích yêu cầu
    thoughtSegmenter.addThought('Analyzing requirements...', 100);
    thoughtMemory.addToContext('requirements', 'Need to create a file with...');
    
    // 2. Tạo kế hoạch
    thoughtSegmenter.addThought('Creating plan...', 150);
    
    // 3. Viết file (thực hiện theo các checkpoints)
    fileManager.addContent('// Begin file\n');
    fileManager.addContent('function main() {\n');
    // ... thêm nội dung
    fileManager.createCheckpoint(); // Tạo checkpoint thủ công tại điểm quan trọng
    
    // 4. Hoàn thành
    thoughtSegmenter.addThought('Finalizing file...', 80);
    fileManager.addContent('}\n');
    fileManager.addContent('// End file\n');
    
    // 5. Kết thúc quy trình
    crashDetector.stopMonitoring();
    return fileManager.content;
  } catch (error) {
    console.error('Error in AI agent process:', error);
    
    // Tạo checkpoint cuối cùng trước khi xử lý lỗi
    fileManager.createCheckpoint();
    
    // Ghi log lỗi và trạng thái
    localStorage.setItem('ai_agent_error', JSON.stringify({
      timestamp: new Date().toISOString(),
      error: error.message,
      stack: error.stack,
      thoughtSummary: thoughtSegmenter.getSummary()
    }));
    
    throw error;
  }
}
```

### 6.6 Cấu Hình Tối Ưu Cho Không Gian "Thought"

**Vấn đề**: Thiếu cấu hình rõ ràng cho quá trình suy nghĩ dẫn đến sử dụng không hiệu quả.

**Giải pháp**:
```json
{
  "thought_config": {
    "max_depth": 3,
    "max_tokens_per_thought": 1000,
    "prune_threshold": 0.7,
    "retention_policy": {
      "critical": "keep_full",
      "important": "keep_summary_and_key_details",
      "context": "keep_summary",
      "tangential": "discard"
    },
    "auto_checkpoint_interval": 50,
    "compression_strategy": "summarize_middle"
  }
}
```

### 6.7 Giám Sát Hiệu Suất Thời Gian Thực

**Vấn đề**: Không có khả năng phát hiện sớm vấn đề trước khi crash xảy ra.

**Giải pháp**:
```javascript
class AIAgentMonitor {
  constructor() {
    this.metrics = {
      memoryUsage: [],
      responseTime: [],
      thoughtComplexity: []
    };
    this.warningThresholds = {
      memoryUsage: 0.85, // 85% sử dụng bộ nhớ
      responseTime: 5000, // 5 giây
      thoughtComplexity: 0.8 // 80% độ phức tạp tối đa
    };
    this.monitoringInterval = null;
  }

  startMonitoring() {
    this.monitoringInterval = setInterval(() => this.collectMetrics(), 1000);
  }

  stopMonitoring() {
    clearInterval(this.monitoringInterval);
  }

  collectMetrics() {
    // Mô phỏng thu thập metrics (trong thực tế sẽ đo lường chính xác)
    const memoryUsage = this.estimateMemoryUsage();
    const responseTime = this.measureResponseTime();
    const thoughtComplexity = this.estimateThoughtComplexity();
    
    // Lưu metrics
    this.metrics.memoryUsage.push(memoryUsage);
    this.metrics.responseTime.push(responseTime);
    this.metrics.thoughtComplexity.push(thoughtComplexity);
    
    // Giới hạn lịch sử lưu trữ
    this.pruneMetricsHistory();
    
    // Kiểm tra các cảnh báo
    this.checkWarnings();
  }

  estimateMemoryUsage() {
    // Mô phỏng - trong thực tế sẽ đo lường thực sự
    return Math.random() * 0.5 + 0.3; // 30-80%
  }

  measureResponseTime() {
    // Mô phỏng
    return Math.random() * 3000 + 1000; // 1-4s
  }

  estimateThoughtComplexity() {
    // Mô phỏng
    return Math.random() * 0.6 + 0.2; // 20-80%
  }

  pruneMetricsHistory() {
    // Giữ tối đa 60 mẫu cho mỗi loại
    if (this.metrics.memoryUsage.length > 60) {
      this.metrics.memoryUsage = this.metrics.memoryUsage.slice(-60);
    }
    if (this.metrics.responseTime.length > 60) {
      this.metrics.responseTime = this.metrics.responseTime.slice(-60);
    }
    if (this.metrics.thoughtComplexity.length > 60) {
      this.metrics.thoughtComplexity = this.metrics.thoughtComplexity.slice(-60);
    }
  }

  checkWarnings() {
    // Kiểm tra các cảnh báo dựa trên các ngưỡng
    const lastMemory = this.metrics.memoryUsage[this.metrics.memoryUsage.length - 1];
    const lastResponse = this.metrics.responseTime[this.metrics.responseTime.length - 1];
    const lastComplexity = this.metrics.thoughtComplexity[this.metrics.thoughtComplexity.length - 1];
    
    if (lastMemory > this.warningThresholds.memoryUsage) {
      this.triggerWarning('memory', lastMemory);
    }
    
    if (lastResponse > this.warningThresholds.responseTime) {
      this.triggerWarning('response', lastResponse);
    }
    
    if (lastComplexity > this.warningThresholds.thoughtComplexity) {
      this.triggerWarning('complexity', lastComplexity);
    }
  }

  triggerWarning(type, value) {
    console.warn(`Warning: ${type} threshold exceeded (${value})`);
    
    // Thực hiện hành động dựa trên loại cảnh báo
    switch (type) {
      case 'memory':
        // Thực hiện dọn dẹp bộ nhớ
        this.triggerMemoryCleanup();
        break;
      case 'response':
        // Giảm độ phức tạp xử lý
        this.reduceProcessingComplexity();
        break;
      case 'complexity':
        // Phân đoạn suy nghĩ phức tạp
        this.segmentComplexThought();
        break;
    }
  }

  triggerMemoryCleanup() {
    console.log('Performing memory cleanup');
    // Thực hiện logic dọn dẹp bộ nhớ
  }

  reduceProcessingComplexity() {
    console.log('Reducing processing complexity');
    // Điều chỉnh tham số để giảm độ phức tạp
  }

  segmentComplexThought() {
    console.log('Segmenting complex thought');
    // Chia nhỏ quá trình suy nghĩ hiện tại
  }
}
```

### 6.8 Tóm Tắt Các Giải Pháp

Bằng cách kết hợp các giải pháp trên, chúng ta có thể khắc phục tình trạng crash khi AI agent viết file theo những cách sau:

1. **Chia nhỏ quá trình suy nghĩ** thành các phân đoạn có thể quản lý
2. **Tạo checkpoint tự động** trong quá trình viết file
3. **Phát hiện và khôi phục từ crash** một cách chủ động
4. **Quản lý bộ nhớ thông minh** để tránh tràn bộ nhớ
5. **Thiết lập quy trình làm việc an toàn** với xử lý lỗi toàn diện
6. **Cấu hình không gian thought tối ưu** để giảm thiểu rủi ro
7. **Giám sát hiệu suất thời gian thực** để can thiệp trước khi xảy ra sự cố

Các giải pháp này không chỉ giúp ngăn ngừa crash mà còn đảm bảo rằng nếu crash vẫn xảy ra, AI agent có thể khôi phục công việc và tiếp tục từ điểm gần nhất có thể, giảm thiểu tổn thất thời gian và công sức.
