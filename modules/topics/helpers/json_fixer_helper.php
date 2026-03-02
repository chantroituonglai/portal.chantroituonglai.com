<?php

class JsonFixer {
    private $silent = false;

    public function fix($json) {
        // Trim whitespace
        $json = trim($json);

        // Remove invalid sections
        $json = $this->removeInvalidSections($json);

        // Return early if empty or already valid
        if (empty($json) || $this->isValid($json)) {
            return $json;
        }

        // Attempt to fix common issues
        $json = $this->fixUnescapedCharacters($json);
        $json = $this->fixMalformedStrings($json);
        $json = $this->fixMissingQuotesOnKeys($json);
        $json = $this->fixMissingCommas($json);
        $json = $this->removeTrailingCommas($json);
        $json = $this->balanceBrackets($json);

        // Final validation
        if ($this->isValid($json)) {
            return $json;
        } else {
            // Truncate from the end until valid
            $json = $this->truncateToValidJson($json);
            if ($json !== null) {
                return $json;
            }
        }

        // If still invalid and silent mode is off, throw exception
        if (!$this->silent) {
            throw new Exception('Could not fix JSON structure');
        }

        return $json;
    }

    private function isValid($json) {
        json_decode($json);
        return JSON_ERROR_NONE === json_last_error();
    }

    private function removeInvalidSections($json) {
        // Remove comments
        $json = preg_replace('/<!--.*?-->/s', '', $json);

        // Remove [removed] placeholders
        $json = preg_replace('/\[removed\]/s', '', $json);

        // Remove any content outside of JSON (e.g., HTML tags)
        $json = preg_replace('/<[^>]*>/', '', $json);

        return $json;
    }

    private function fixUnescapedCharacters($json) {
        // Escape backslashes and quotes within strings
        $json = preg_replace_callback('/"(?:\\\\.|[^"\\\\])*"/s', function ($matches) {
            $str = $matches[0];
            // Remove starting and ending quotes
            $inner = substr($str, 1, -1);
            
            // Đặc biệt xử lý cho các ký tự đặc biệt trong HTML
            $inner = str_replace(
                ['<br/>', '<br />', '<br>', '\n'],
                ['<br\/>', '<br\/>', '<br\/>', '\\n'],
                $inner
            );
            
            // Escape backslashes and quotes
            $inner = addcslashes($inner, "\\\"\n\r\t/");
            
            // Đảm bảo giữ nguyên các ký tự đã được escape
            $inner = str_replace(
                ['\\\\n', '\\\\"', '\\\\/', '\\\\\\\\'],
                ['\\n', '\\"', '\\/', '\\\\'],
                $inner
            );
            
            return '"' . $inner . '"';
        }, $json);
        return $json;
    }

    private function fixMalformedStrings($json) {
        // Ensure all string values are properly enclosed in double quotes
        $json = preg_replace_callback('/(:\s*)([^\s,"\[\]\{\}]+)(\s*,?)/', function ($matches) {
            if (is_numeric($matches[2]) || in_array(strtolower($matches[2]), ['true', 'false', 'null'])) {
                return $matches[0];
            }
            
            // Xử lý đặc biệt cho các chuỗi có chứa ký tự đặc biệt
            $value = $matches[2];
            $value = str_replace(
                ['<br/>', '<br />', '<br>', '\n'],
                ['<br\/>', '<br\/>', '<br\/>', '\\n'],
                $value
            );
            
            // Encode string value
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            // Đảm bảo giữ nguyên các ký tự đã được escape
            $encoded = str_replace(
                ['\\\\n', '\\\\"', '\\\\/', '\\\\\\\\'],
                ['\\n', '\\"', '\\/', '\\\\'],
                $encoded
            );
            
            return $matches[1] . $encoded . $matches[3];
        }, $json);
        return $json;
    }

    private function fixMissingQuotesOnKeys($json) {
        // Add missing quotes around object keys
        $json = preg_replace('/([{\s,])([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1"$2":', $json);
        return $json;
    }

    private function fixMissingCommas($json) {
        // Add missing commas between elements
        $json = preg_replace('/}\s*{/', '},{', $json);
        $json = preg_replace('/"\s*"/', '","', $json);
        $json = preg_replace('/"([^\"]*)"\s*"([^\"]*)"/', '"$1","$2"', $json);
        return $json;
    }

    private function removeTrailingCommas($json) {
        // Remove trailing commas before closing brackets/braces
        $json = preg_replace('/,(?=\s*[\]}])/', '', $json);
        return $json;
    }

    private function balanceBrackets($json) {
        // Balance brackets and braces
        $openBrackets = substr_count($json, '[');
        $closeBrackets = substr_count($json, ']');
        $openBraces = substr_count($json, '{');
        $closeBraces = substr_count($json, '}');

        // Chỉ thêm dấu đóng nếu có nhiều dấu mở hơn
        if ($openBrackets > $closeBrackets) {
            $json .= str_repeat(']', $openBrackets - $closeBrackets);
        }
        if ($openBraces > $closeBraces) {
            $json .= str_repeat('}', $openBraces - $closeBraces);
        }

        // Nếu có nhiều dấu đóng hơn, cắt bớt từ cuối
        if ($closeBrackets > $openBrackets) {
            $json = preg_replace('/\]{' . ($closeBrackets - $openBrackets) . '}$/', '', $json);
        }
        if ($closeBraces > $openBraces) {
            $json = preg_replace('/\}{' . ($closeBraces - $openBraces) . '}$/', '', $json);
        }

        return $json;
    }

    private function truncateToValidJson($json) {
        // Truncate the string from the end until it's valid JSON
        for ($i = strlen($json); $i > 0; $i--) {
            $truncatedJson = substr($json, 0, $i);
            if ($this->isValid($truncatedJson)) {
                return $truncatedJson;
            }
        }
        return null;
    }

    public function setSilent($silent = true) {
        $this->silent = (bool) $silent;
        return $this;
    }
}

// Helper function for easy access
if (!function_exists('fix_json')) {
    function fix_json($json, $silent = true) {
        $fixer = new JsonFixer();
        return $fixer->setSilent($silent)->fix($json);
    }
}


if (!function_exists('get_guid_raw_from_upload_media_response')) {
    /**
     * Lấy URL ảnh từ phản hồi upload media của WordPress API
     * 
     * @param string $json Phản hồi JSON từ WordPress API
     * @return string|null URL của ảnh hoặc null nếu không tìm thấy
     */
    function get_guid_raw_from_upload_media_response($json) {
        // Load helper nếu cần
        if (!class_exists('JsonFixer')) {
            log_message('debug', 'Loading JsonFixer helper');
            require_once(__DIR__ . '/json_fixer_helper.php');
        }

        try {
            // Cố gắng fix và parse JSON
            $fixer = new JsonFixer();
            $fixedJson = $fixer->setSilent(true)->fix($json);
            $data = json_decode($fixedJson, true);

            // Nếu parse thành công, thử các phương pháp khác nhau để lấy URL
            if ($data) {
                // Phương pháp 1: Cấu trúc guid.raw
                if (!empty($data['guid']['raw'])) {
                    return $data['guid']['raw'];
                }

                // Phương pháp 2: Cấu trúc guid.rendered
                if (!empty($data['guid']['rendered'])) {
                    return $data['guid']['rendered'];
                }

                // Phương pháp 3: Cấu trúc upload_media.guid.raw
                if (!empty($data['upload_media']['guid']['raw'])) {
                    return $data['upload_media']['guid']['raw'];
                }

                // Phương pháp 4: Tìm URL trong source_url
                if (!empty($data['source_url'])) {
                    return $data['source_url'];
                }

                // Phương pháp 5: Tìm URL trong link
                if (!empty($data['link']) && filter_var($data['link'], FILTER_VALIDATE_URL)) {
                    return $data['link'];
                }

                // Phương pháp 6: Tìm URL trong url
                if (!empty($data['url']) && filter_var($data['url'], FILTER_VALIDATE_URL)) {
                    return $data['url'];
                }

                // Phương pháp 7: Tìm kiếm đệ quy trong mảng
                $url = find_url_in_array($data);
                if ($url) {
                    return $url;
                }
            }

            // Phương pháp 8: Tìm URL bằng regex nếu các cách trên thất bại
            $urlPattern = '/https?:\/\/[^\s<>"]+?\/wp-content\/uploads\/[^\s<>"]+?\.(jpg|jpeg|png|gif|webp)/i';
            if (preg_match($urlPattern, $json, $matches)) {
                return $matches[0];
            }

            log_message('debug', 'Failed to extract image URL from JSON: ' . substr($json, 0, 500));
            return null;

        } catch (Exception $e) {
            log_message('error', 'Error extracting image URL: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('find_url_in_array')) {
    /**
     * Tìm kiếm đệ quy URL trong mảng
     * 
     * @param array $array Mảng cần tìm kiếm
     * @return string|null URL đầu tiên tìm thấy hoặc null
     */
    function find_url_in_array($array) {
        foreach ($array as $key => $value) {
            // Kiểm tra nếu là URL hợp lệ
            if (is_string($value) && 
                filter_var($value, FILTER_VALIDATE_URL) && 
                preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $value)) {
                return $value;
            }
            
            // Tìm kiếm đệ quy nếu là mảng
            if (is_array($value)) {
                $result = find_url_in_array($value);
                if ($result) {
                    return $result;
                }
            }
        }
        return null;
    }
}


class JsonRepairError extends Exception {
    public $position;
    
    public function __construct($message, $position) {
        parent::__construct("$message at position $position");
        $this->position = $position;
    }
}

class JsonRepair {
    private $text;
    private $index = 0;
    private $output = '';
    private $CONTROL_CHARACTERS = [
        "\b" => "\\b",
        "\f" => "\\f", 
        "\n" => "\\n",
        "\r" => "\\r",
        "\t" => "\\t"
    ];
    
    private $ESCAPE_CHARACTERS = [
        '"' => '"',
        '\\' => '\\',
        '/' => '/',
        'b' => "\b",
        'f' => "\f",
        'n' => "\n",
        'r' => "\r",
        't' => "\t"
    ];

    public function repair($text) {
        $this->text = $text;
        $this->index = 0;
        $this->output = '';

        try {
            $this->parseValue();
            $this->skipWhitespace();
            
            // Nếu còn ký tự sau khi parse xong
            if ($this->index < strlen($this->text)) {
                throw new JsonRepairError("Unexpected characters", $this->index);
            }
            
            return $this->output;
        } catch (JsonRepairError $e) {
            // Thử phương án sửa chữa
            return $this->attemptRepair();
        }
    }

    private function attemptRepair() {
        try {
            // Reset
            $this->index = 0;
            $this->output = '';
            
            // Bỏ qua whitespace đầu
            $this->skipWhitespace();
            
            // Parse giá trị chính
            if ($this->currentChar() === '{') {
                $this->parseObject();
            } elseif ($this->currentChar() === '[') {
                $this->parseArray();
            } else {
                $this->parseValue();
            }
            
            // Cân bằng dấu ngoặc
            $this->balanceBrackets();
            
            return $this->output;
        } catch (Exception $e) {
            // Nếu vẫn lỗi, trả về JSON gốc
            return $this->text;
        }
    }

    private function parseValue() {
        $this->skipWhitespace();
        
        $char = $this->currentChar();
        
        switch ($char) {
            case '{':
                $this->parseObject();
                break;
                
            case '[':
                $this->parseArray();
                break;
                
            case '"':
                $this->parseString();
                break;
                
            case 't':
                $this->parseKeyword('true');
                break;
                
            case 'f':
                $this->parseKeyword('false');
                break;
                
            case 'n':
                $this->parseKeyword('null');
                break;
                
            default:
                if ($this->isDigit($char) || $char === '-') {
                    $this->parseNumber();
                } else {
                    throw new JsonRepairError("Unexpected character", $this->index);
                }
        }
    }

    private function parseObject() {
        $this->output .= '{';
        $this->index++;
        
        $isFirst = true;
        
        while ($this->index < strlen($this->text)) {
            $this->skipWhitespace();
            
            if ($this->currentChar() === '}') {
                $this->output .= '}';
                $this->index++;
                return;
            }
            
            if (!$isFirst) {
                if ($this->currentChar() !== ',') {
                    $this->output .= ',';
                } else {
                    $this->output .= ',';
                    $this->index++;
                }
            }
            
            $this->skipWhitespace();
            
            // Parse key
            if ($this->currentChar() === '"') {
                $this->parseString();
            } else {
                $this->parseNonQuotedKey();
            }
            
            $this->skipWhitespace();
            
            // Expect colon
            if ($this->currentChar() !== ':') {
                $this->output .= ':';
            } else {
                $this->output .= ':';
                $this->index++;
            }
            
            // Parse value
            $this->parseValue();
            
            $isFirst = false;
        }
        
        $this->output .= '}';
    }

    private function parseArray() {
        $this->output .= '[';
        $this->index++;
        
        $isFirst = true;
        
        while ($this->index < strlen($this->text)) {
            $this->skipWhitespace();
            
            if ($this->currentChar() === ']') {
                $this->output .= ']';
                $this->index++;
                return;
            }
            
            if (!$isFirst) {
                if ($this->currentChar() !== ',') {
                    $this->output .= ',';
                } else {
                    $this->output .= ',';
                    $this->index++;
                }
            }
            
            $this->parseValue();
            
            $isFirst = false;
        }
        
        $this->output .= ']';
    }

    private function parseString() {
        $this->output .= '"';
        $this->index++;
        
        while ($this->index < strlen($this->text)) {
            $char = $this->currentChar();
            
            if ($char === '"' && $this->text[$this->index - 1] !== '\\') {
                $this->output .= '"';
                $this->index++;
                return;
            }
            
            if ($char === '\\') {
                $this->index++;
                $nextChar = $this->currentChar();
                
                if (isset($this->ESCAPE_CHARACTERS[$nextChar])) {
                    $this->output .= '\\' . $nextChar;
                } else {
                    $this->output .= '\\' . $nextChar;
                }
            } else {
                $this->output .= $this->escapeControlCharacter($char);
            }
            
            $this->index++;
        }
        
        $this->output .= '"';
    }

    private function parseNumber() {
        $start = $this->index;
        
        // Optional minus sign
        if ($this->currentChar() === '-') {
            $this->index++;
        }
        
        // Integer part
        while ($this->index < strlen($this->text) && $this->isDigit($this->currentChar())) {
            $this->index++;
        }
        
        // Fractional part
        if ($this->currentChar() === '.') {
            $this->index++;
            while ($this->index < strlen($this->text) && $this->isDigit($this->currentChar())) {
                $this->index++;
            }
        }
        
        // Exponential part
        if (strtolower($this->currentChar()) === 'e') {
            $this->index++;
            if (in_array($this->currentChar(), ['+', '-'])) {
                $this->index++;
            }
            while ($this->index < strlen($this->text) && $this->isDigit($this->currentChar())) {
                $this->index++;
            }
        }
        
        $this->output .= substr($this->text, $start, $this->index - $start);
    }

    private function parseKeyword($keyword) {
        if (substr($this->text, $this->index, strlen($keyword)) === $keyword) {
            $this->output .= $keyword;
            $this->index += strlen($keyword);
        } else {
            throw new JsonRepairError("Invalid keyword", $this->index);
        }
    }

    private function parseNonQuotedKey() {
        $start = $this->index;
        while ($this->index < strlen($this->text) && 
               !in_array($this->currentChar(), [' ', '\t', '\n', '\r', ':', ',', '}'])) {
            $this->index++;
        }
        
        $key = substr($this->text, $start, $this->index - $start);
        $this->output .= '"' . $key . '"';
    }

    private function skipWhitespace() {
        while ($this->index < strlen($this->text) && 
               in_array($this->currentChar(), [' ', '\t', '\n', '\r'])) {
            $this->index++;
        }
    }

    private function currentChar() {
        return $this->index < strlen($this->text) ? $this->text[$this->index] : null;
    }

    private function isDigit($char) {
        return $char !== null && preg_match('/[0-9]/', $char);
    }

    private function escapeControlCharacter($char) {
        return isset($this->CONTROL_CHARACTERS[$char]) ? $this->CONTROL_CHARACTERS[$char] : $char;
    }

    private function balanceBrackets() {
        $openBraces = substr_count($this->output, '{');
        $closeBraces = substr_count($this->output, '}');
        $openBrackets = substr_count($this->output, '[');
        $closeBrackets = substr_count($this->output, ']');
        
        while ($openBraces > $closeBraces) {
            $this->output .= '}';
            $closeBraces++;
        }
        
        while ($openBrackets > $closeBrackets) {
            $this->output .= ']';
            $closeBrackets++;
        }
    }
}

// Helper function
if (!function_exists('repair_json')) {
    function repair_json($json) {
        $repairer = new JsonRepair();
        return $repairer->repair($json);
    }
}