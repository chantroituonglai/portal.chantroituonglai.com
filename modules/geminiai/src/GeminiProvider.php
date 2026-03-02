<?php

namespace Perfexcrm\Geminiai;

use app\services\ai\Contracts\AiProviderInterface;
use Exception;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\HtmlConverter;

defined('BASEPATH') or exit('No direct script access allowed');

class GeminiProvider implements AiProviderInterface
{
    private string $model;
    private string $systemPrompt;
    private int $maxToken;
    private bool $quotaExceeded = false;
    
    // Google API Error Codes Mapping
    private array $errorCodes = [
        // 301 - MOVED_PERMANENTLY
        301 => [
            'movedPermanently' => 'This request and future requests for the same operation have to be sent to the URL specified in the Location header of this response instead of to the URL to which this request was sent.'
        ],
        
        // 303 - SEE_OTHER
        303 => [
            'seeOther' => 'Your request was processed successfully. To obtain your response, send a GET request to the URL specified in the Location header.',
            'mediaDownloadRedirect' => 'Your request was processed successfully. To obtain your response, send a GET request to the URL specified in the Location header.'
        ],
        
        // 304 - NOT_MODIFIED
        304 => [
            'notModified' => 'The condition set for an If-None-Match header was not met. This response indicates that the requested document has not been modified and that a cached response should be retrieved.'
        ],
        
        // 307 - TEMPORARY_REDIRECT
        307 => [
            'temporaryRedirect' => 'To have your request processed, resend it to the URL specified in the Location header of this response.'
        ],
        
        // 400 - BAD_REQUEST
        400 => [
            'badRequest' => 'The API request is invalid or improperly formed. Consequently, the API server could not understand the request.',
            'badBinaryDomainRequest' => 'The binary domain request is invalid.',
            'badContent' => 'The content type of the request data or the content type of a part of a multipart request is not supported.',
            'badLockedDomainRequest' => 'The locked domain request is invalid.',
            'corsRequestWithXOrigin' => 'The CORS request contains an XD3 X-Origin header, which is indicative of a bad CORS request.',
            'endpointConstraintMismatch' => 'The request failed because it did not match the specified API. Check the value of the URL path to make sure it is correct.',
            'invalid' => 'The request failed because it contained an invalid value. The value could be a parameter value, a header value, or a property value.',
            'invalidAltValue' => 'The alt parameter value specifies an unknown output format.',
            'invalidHeader' => 'The request failed because it contained an invalid header.',
            'invalidParameter' => 'The request failed because it contained an invalid parameter or parameter value. Review the API documentation to determine which parameters are valid for your request.',
            'invalidQuery' => 'The request is invalid. Check the API documentation to determine what parameters are supported for the request and to see if the request contains an invalid combination of parameters or an invalid parameter value.',
            'keyExpired' => 'The API key provided in the request expired, which means the API server is unable to check the quota limit for the application making the request.',
            'keyInvalid' => 'The API key provided in the request is invalid, which means the API server is unable to check the quota limit for the application making the request.',
            'lockedDomainCreationFailure' => 'The OAuth token was received in the query string, which this API forbids for response formats other than JSON or XML.',
            'notDownload' => 'Only media downloads requests can be sent to /download/* URL paths. Resend the request to the same path, but without the /download prefix.',
            'notUpload' => 'The request failed because it is not an upload request, and only upload requests can be sent to /upload/* URIs.',
            'parseError' => 'The API server cannot parse the request body.',
            'required' => 'The API request is missing required information. The required information could be a parameter or resource property.',
            'tooManyParts' => 'The multipart request failed because it contains too many parts',
            'unknownApi' => 'The API that the request is calling is not recognized.',
            'unsupportedMediaProtocol' => 'The client is using an unsupported media protocol.',
            'unsupportedOutputFormat' => 'The alt parameter value specifies an output format that is not supported for this service.',
            'wrongUrlForUpload' => 'The request is an upload request, but it failed because it was not sent to the proper URI.'
        ],
        
        // 401 - UNAUTHORIZED
        401 => [
            'unauthorized' => 'The user is not authorized to make the request.',
            'authError' => 'The authorization credentials provided for the request are invalid. Check the value of the Authorization HTTP request header.',
            'expired' => 'Session Expired. Check the value of the Authorization HTTP request header.',
            'lockedDomainExpired' => 'The request failed because a previously valid locked domain has expired.',
            'required' => 'The user must be logged in to make this API request. Check the value of the Authorization HTTP request header.'
        ],
        
        // 402 - PAYMENT_REQUIRED
        402 => [
            'dailyLimitExceeded402' => 'A daily budget limit set by the developer has been reached.',
            'quotaExceeded402' => 'The requested operation requires more resources than the quota allows. Payment is required to complete the operation.',
            'user402' => 'The requested operation requires some kind of payment from the authenticated user.'
        ],
        
        // 403 - FORBIDDEN
        403 => [
            'forbidden' => 'The requested operation is forbidden and cannot be completed.',
            'accessNotConfigured' => 'Your project is not configured to access this API. Please use the Google Developers Console to activate the API for your project.',
            'accountDeleted' => 'The user account associated with the request\'s authorization credentials has been deleted.',
            'accountDisabled' => 'The user account associated with the request\'s authorization credentials has been disabled.',
            'accountUnverified' => 'The email address for the user making the request has not been verified.',
            'concurrentLimitExceeded' => 'The request failed because a concurrent usage limit has been reached.',
            'dailyLimitExceeded' => 'A daily quota limit for the API has been reached.',
            'dailyLimitExceededUnreg' => 'The request failed because a daily limit for unauthenticated API use has been hit.',
            'downloadServiceForbidden' => 'The API does not support a download service.',
            'insufficientAudience' => 'The request cannot be completed for this audience.',
            'insufficientAuthorizedParty' => 'The request cannot be completed for this application.',
            'insufficientPermissions' => 'The authenticated user does not have sufficient permissions to execute this request.',
            'limitExceeded' => 'The request cannot be completed due to access or rate limitations.',
            'lockedDomainForbidden' => 'This API does not support locked domains.',
            'quotaExceeded' => 'The requested operation requires more resources than the quota allows.',
            'rateLimitExceeded' => 'Too many requests have been sent within a given time span.',
            'rateLimitExceededUnreg' => 'A rate limit has been exceeded and you must register your application to be able to continue calling the API.',
            'responseTooLarge' => 'The requested resource is too large to return.',
            'servingLimitExceeded' => 'The overall rate limit specified for the API has already been reached.',
            'sslRequired' => 'SSL is required to perform this operation.',
            'unknownAuth' => 'The API server does not recognize the authorization scheme used for the request.',
            'userRateLimitExceeded' => 'The request failed because a per-user rate limit has been reached.',
            'userRateLimitExceededUnreg' => 'The request failed because a per-user rate limit has been reached, and the client developer was not identified in the request.',
            'variableTermExpiredDailyExceeded' => 'The request failed because a variable term quota expired and a daily limit was reached.',
            'variableTermLimitExceeded' => 'The request failed because a variable term quota limit was reached.'
        ],
        
        // 404 - NOT_FOUND
        404 => [
            'notFound' => 'The requested operation failed because a resource associated with the request could not be found.',
            'unsupportedProtocol' => 'The protocol used in the request is not supported.'
        ],
        
        // 405 - METHOD_NOT_ALLOWED
        405 => [
            'httpMethodNotAllowed' => 'The HTTP method associated with the request is not supported.'
        ],
        
        // 409 - CONFLICT
        409 => [
            'conflict' => 'The API request cannot be completed because the requested operation would conflict with an existing item.',
            'duplicate' => 'The requested operation failed because it tried to create a resource that already exists.'
        ],
        
        // 410 - GONE
        410 => [
            'deleted' => 'The request failed because the resource associated with the request has been deleted'
        ],
        
        // 412 - PRECONDITION_FAILED
        412 => [
            'conditionNotMet' => 'The condition set in the request\'s If-Match or If-None-Match HTTP request header was not met.'
        ],
        
        // 413 - REQUEST_ENTITY_TOO_LARGE
        413 => [
            'backendRequestTooLarge' => 'The request is too large.',
            'batchSizeTooLarge' => 'The batch request contains too many elements.',
            'uploadTooLarge' => 'The request failed because the data sent in the request is too large.'
        ],
        
        // 416 - REQUESTED_RANGE_NOT_SATISFIABLE
        416 => [
            'requestedRangeNotSatisfiable' => 'The request specified a range that cannot be satisfied.'
        ],
        
        // 417 - EXPECTATION_FAILED
        417 => [
            'expectationFailed' => 'A client expectation cannot be met by the server.'
        ],
        
        // 428 - PRECONDITION_REQUIRED
        428 => [
            'preconditionRequired' => 'The request requires a precondition that is not provided. For this request to succeed, you need to provide either an If-Match or If-None-Match header with the request.'
        ],
        
        // 429 - TOO_MANY_REQUESTS
        429 => [
            'rateLimitExceeded' => 'Too many requests have been sent within a given time span.'
        ],
        
        // 500 - INTERNAL_SERVER_ERROR
        500 => [
            'internalError' => 'The request failed due to an internal error.'
        ],
        
        // 501 - NOT_IMPLEMENTED
        501 => [
            'notImplemented' => 'The requested operation has not been implemented.',
            'unsupportedMethod' => 'The request failed because it is trying to execute an unknown method or operation.'
        ],
        
        // 503 - SERVICE_UNAVAILABLE
        503 => [
            'backendError' => 'A backend error occurred.',
            'backendNotConnected' => 'The request failed due to a connection error.',
            'notReady' => 'The API server is not ready to accept requests.'
        ]
    ];
    private array $apiKeys = [];
    private int $activeKeyIndex = 0;

    public function __construct()
    {
        $this->model        = get_option('geminiai_model') ?: 'gemini-1.5-flash';
        $this->systemPrompt = get_option('ai_system_prompt') ?: '';
        $this->maxToken     = intval(get_option('geminiai_max_token')) ?: 1024;

        // Load API keys (multi-key support) with backward compatibility
        $this->apiKeys        = $this->loadApiKeys();
        $this->activeKeyIndex = $this->loadActiveKeyIndex();
    }

    public function getName(): string
    {
        return 'Gemini';
    }
    
    public function setQuotaExceeded(bool $exceeded): void
    {
        $this->quotaExceeded = $exceeded;
    }
    
    public function isQuotaExceeded(): bool
    {
        return $this->quotaExceeded;
    }
    
    /**
     * Get error message for a specific error code and reason
     */
    private function getErrorMessage(int $code, string $reason = ''): string
    {
        if (isset($this->errorCodes[$code])) {
            if (!empty($reason) && isset($this->errorCodes[$code][$reason])) {
                return $this->errorCodes[$code][$reason];
            }
            // Return first available message for this code if specific reason not found
            return reset($this->errorCodes[$code]);
        }
        return 'Unknown error occurred';
    }
    
    /**
     * Check if error code indicates quota/rate limit exceeded
     */
    private function isQuotaRelatedError(int $code, string $reason = ''): bool
    {
        $quotaRelatedCodes = [402, 403, 429];
        $quotaRelatedReasons = [
            'quotaExceeded', 'quotaExceeded402', 'dailyLimitExceeded', 'dailyLimitExceeded402',
            'rateLimitExceeded', 'rateLimitExceededUnreg', 'userRateLimitExceeded',
            'userRateLimitExceededUnreg', 'concurrentLimitExceeded', 'servingLimitExceeded',
            'variableTermExpiredDailyExceeded', 'variableTermLimitExceeded'
        ];
        
        return in_array($code, $quotaRelatedCodes) || in_array($reason, $quotaRelatedReasons);
    }
    
    /**
     * Check if error code indicates authentication issues
     */
    private function isAuthRelatedError(int $code, string $reason = ''): bool
    {
        $authRelatedCodes = [401, 403];
        $authRelatedReasons = [
            'unauthorized', 'authError', 'expired', 'keyExpired', 'keyInvalid',
            'accountDeleted', 'accountDisabled', 'accountUnverified', 'unknownAuth',
            'insufficientPermissions', 'insufficientAudience', 'insufficientAuthorizedParty'
        ];
        
        return in_array($code, $authRelatedCodes) || in_array($reason, $authRelatedReasons);
    }
    
    /**
     * Check if error code indicates configuration issues
     */
    private function isConfigRelatedError(int $code, string $reason = ''): bool
    {
        $configRelatedReasons = [
            'accessNotConfigured', 'invalidParameter', 'invalidQuery', 'invalidHeader',
            'endpointConstraintMismatch', 'unknownApi', 'unsupportedMethod'
        ];
        
        return in_array($reason, $configRelatedReasons);
    }
    
    /**
     * Handle API error response
     */
    private function handleApiError(array $error): string
    {
        $code = $error['code'] ?? 0;
        $message = $error['message'] ?? 'Unknown error';
        $errors = $error['errors'] ?? [];
        
        // Get specific error reason if available
        $reason = '';
        if (!empty($errors) && isset($errors[0]['reason'])) {
            $reason = $errors[0]['reason'];
        }
        
        // Get detailed error message
        $detailedMessage = $this->getErrorMessage($code, $reason);
        
        // Log the error with context
        $logMessage = "Gemini API Error {$code}";
        if (!empty($reason)) {
            $logMessage .= " ({$reason})";
        }
        $logMessage .= ": {$message}";
        if ($detailedMessage !== $message) {
            $logMessage .= " | Details: {$detailedMessage}";
        }
        
        log_message('error', $logMessage);
        
        // Handle specific error types
        if ($this->isQuotaRelatedError($code, $reason)) {
            $this->setQuotaExceeded(true);
            return '';
        }
        
        if ($this->isAuthRelatedError($code, $reason)) {
            log_message('error', 'Gemini API: Authentication/Authorization issue - ' . $detailedMessage);
            return '';
        }
        
        if ($this->isConfigRelatedError($code, $reason)) {
            log_message('error', 'Gemini API: Configuration issue - ' . $detailedMessage);
            return '';
        }
        
        // For other errors, return empty string but log the details
        log_message('error', 'Gemini API: ' . $detailedMessage);
        return '';
    }
    
    /**
     * Get all available error codes and their descriptions
     */
    public function getErrorCodes(): array
    {
        return $this->errorCodes;
    }
    
    /**
     * Get error information for a specific code and reason
     */
    public function getErrorInfo(int $code, string $reason = ''): array
    {
        return [
            'code' => $code,
            'reason' => $reason,
            'message' => $this->getErrorMessage($code, $reason),
            'isQuotaRelated' => $this->isQuotaRelatedError($code, $reason),
            'isAuthRelated' => $this->isAuthRelatedError($code, $reason),
            'isConfigRelated' => $this->isConfigRelatedError($code, $reason)
        ];
    }

    public static function getModels(): array
    {
        return hooks()->apply_filters('geminiai_models', [
            ['id' => 'gemini-1.5-flash', 'name' => 'Gemini 1.5 Flash'],
            ['id' => 'gemini-2.0-flash', 'name' => 'Gemini 2.0 Flash'],
            ['id' => 'gemini-2.5-flash', 'name' => 'Gemini 2.5 Flash'],
        ]);
    }

    public function chat($prompt): string
    {
        // Resolve API keys (multi or single)
        if (empty($this->apiKeys)) {
            log_message('error', 'Gemini API: No API key(s) configured');
            return '';
        }

        // Reset quota flag for this call
        $this->setQuotaExceeded(false);

        log_message('debug', 'Gemini API: Starting chat with prompt length: ' . strlen($prompt));

        $model  = $this->model;
        $system = $this->systemPrompt;

        $parts = [];
        if (!empty($system)) {
            $parts[] = ['text' => $system];
        }
        $parts[] = ['text' => (string) $prompt];

        $payload = [
            'contents' => [
                ['parts' => $parts],
            ],
            'generationConfig' => [
                'maxOutputTokens' => $this->maxToken,
            ],
        ];

        // Try current key; rotate on 429; stop when success or all tried
        $attempted = 0;
        $maxAttempts = count($this->apiKeys);
        $startIndex = $this->activeKeyIndex;

        while ($attempted < $maxAttempts) {
            $currentKey = $this->apiKeys[$this->activeKeyIndex] ?? '';
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($currentKey);

            log_message('debug', 'Gemini API: Using key index ' . $this->activeKeyIndex . ', URL: ' . $url);
            $response = $this->httpPostJson($url, $payload);

            log_message('debug', 'Gemini API Response: ' . json_encode($response));

            if (!$response) {
                log_message('error', 'Gemini API: No response received');
                return '';
            }

            if (isset($response['error'])) {
                $errorCode = $response['error']['code'] ?? 0;
                $errorMessage = $response['error']['message'] ?? 'Unknown error';
                $errors = $response['error']['errors'] ?? [];
                
                // Get specific error reason if available
                $reason = '';
                if (!empty($errors) && isset($errors[0]['reason'])) {
                    $reason = $errors[0]['reason'];
                }

                // Handle quota/rate limit errors with key rotation
                if ($this->isQuotaRelatedError($errorCode, $reason)) {
                    $detailedMessage = $this->getErrorMessage($errorCode, $reason);
                    log_message('error', 'Gemini API: Quota exceeded for key index ' . $this->activeKeyIndex . ' - ' . $errorMessage . ' | Details: ' . $detailedMessage);
                    $this->setQuotaExceeded(true);
                    
                    // Rotate to next key and persist, then retry
                    $this->rotateToNextKey();
                    $attempted++;
                    // If we wrapped all the way and returned to start without success, stop
                    if ($attempted >= $maxAttempts) {
                        log_message('error', 'Gemini API: All keys exhausted due to quota (attempts=' . $attempted . ')');
                        return '';
                    }
                    continue;
                }

                // For other errors, use comprehensive error handling and stop (do not rotate)
                return $this->handleApiError($response['error']);
            }

            if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                log_message('error', 'Gemini API: Invalid response structure - ' . json_encode($response));
                return '';
            }

            $text = (string) $response['candidates'][0]['content']['parts'][0]['text'];
            return rtrim(ltrim($text, '```html'), '```');
        }

        return '';
    }

    public function createFineTuningJob(array $trainingData): ?string
    {
        // Gemini fine-tuning is not implemented here.
        return null;
    }

    public function retrieveFineTuningJob(string $jobId): array
    {
        return ['status' => 'not_supported'];
    }

    public function checkFineTuningStatus(string $jobId): array
    {
        return ['status' => 'not_supported'];
    }

    public function getFineTunedModels(): array
    {
        return [];
    }

    public function getOurFineTunedModels(): array
    {
        return [];
    }

    public function deleteFineTunedModel(string $modelId): bool
    {
        return false;
    }

    public function enhanceText(string $text, string $enhancementType): string
    {
        $converter = new HtmlConverter();
        $converter->getConfig()->setOption('strip_tags', true);
        $converter->getEnvironment()->addConverter(new TableConverter());

        $prompt = <<<TICKET
                    Enhance the following text to be more {$enhancementType}. Only return the enhanced text without any explanations or introductions, the text should be TinyMCE 6 compatible HTML format:\n\n
                    {$converter->convert($text)}
            TICKET;

        $result = $this->chat(
            hooks()->apply_filters('before_ai_tickets_enhance_text', $prompt, $text, $enhancementType)
        );

        if (function_exists('startsWith') && function_exists('endsWith')) {
            if (startsWith($result, '<p>') && endsWith($result, '</p>') && substr_count($result, '<p>') === 1) {
                $result = strip_tags($result, '<strong><em><u><span><a><b><i>');
            }
        }

        return $result;
    }

    private function httpPostJson(string $url, array $payload): ?array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $result = curl_exec($ch);
        if ($result === false) {
            log_message('error', 'Gemini API: cURL error - ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        log_message('debug', 'Gemini API: HTTP Code - ' . $httpCode);
        log_message('debug', 'Gemini API: Raw Response - ' . substr($result, 0, 500));
        
        curl_close($ch);

        $data = json_decode($result, true);
        if (!is_array($data)) {
            log_message('error', 'Gemini API: JSON decode failed - ' . json_last_error_msg());
            return null;
        }
        return $data;
    }

    private function loadApiKeys(): array
    {
        $keysRaw = (string) get_option('geminiai_api_keys');
        $keys = [];
        if ($keysRaw !== '') {
            foreach (preg_split('/\r\n|\r|\n/', $keysRaw) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $keys[] = $line;
                }
            }
        }
        // Backward compatibility: fall back to single key option if no multi-keys provided
        if (empty($keys)) {
            $single = (string) get_option('geminiai_api_key');
            if ($single !== '') {
                $keys = [$single];
            }
        }
        return $keys;
    }

    private function loadActiveKeyIndex(): int
    {
        $idx = (int) get_option('geminiai_active_key_index');
        if ($idx < 0 || $idx >= count($this->apiKeys)) {
            $idx = 0;
        }
        // Persist normalized index
        update_option('geminiai_active_key_index', $idx);
        return $idx;
    }

    private function rotateToNextKey(): void
    {
        if (count($this->apiKeys) <= 1) {
            return;
        }
        $this->activeKeyIndex = ($this->activeKeyIndex + 1) % count($this->apiKeys);
        update_option('geminiai_active_key_index', $this->activeKeyIndex);
        log_message('error', 'Gemini API: Rotated to key index ' . $this->activeKeyIndex);
    }
}
