<?php
/**
 * Test script for Gemini API error handling
 * This file can be used to test the comprehensive error handling system
 */

require_once __DIR__ . '/src/GeminiProvider.php';

use Perfexcrm\Geminiai\GeminiProvider;

// Create instance
$provider = new GeminiProvider();

echo "=== Gemini API Error Handling Test ===\n\n";

// Test 1: Quota exceeded error
echo "Test 1: Quota exceeded error (429)\n";
$errorInfo = $provider->getErrorInfo(429, 'rateLimitExceeded');
echo "Code: {$errorInfo['code']}\n";
echo "Reason: {$errorInfo['reason']}\n";
echo "Message: {$errorInfo['message']}\n";
echo "Is Quota Related: " . ($errorInfo['isQuotaRelated'] ? 'Yes' : 'No') . "\n";
echo "Is Auth Related: " . ($errorInfo['isAuthRelated'] ? 'Yes' : 'No') . "\n";
echo "Is Config Related: " . ($errorInfo['isConfigRelated'] ? 'Yes' : 'No') . "\n\n";

// Test 2: Authentication error
echo "Test 2: Authentication error (401)\n";
$errorInfo = $provider->getErrorInfo(401, 'keyInvalid');
echo "Code: {$errorInfo['code']}\n";
echo "Reason: {$errorInfo['reason']}\n";
echo "Message: {$errorInfo['message']}\n";
echo "Is Quota Related: " . ($errorInfo['isQuotaRelated'] ? 'Yes' : 'No') . "\n";
echo "Is Auth Related: " . ($errorInfo['isAuthRelated'] ? 'Yes' : 'No') . "\n";
echo "Is Config Related: " . ($errorInfo['isConfigRelated'] ? 'Yes' : 'No') . "\n\n";

// Test 3: Configuration error
echo "Test 3: Configuration error (400)\n";
$errorInfo = $provider->getErrorInfo(400, 'invalidParameter');
echo "Code: {$errorInfo['code']}\n";
echo "Reason: {$errorInfo['reason']}\n";
echo "Message: {$errorInfo['message']}\n";
echo "Is Quota Related: " . ($errorInfo['isQuotaRelated'] ? 'Yes' : 'No') . "\n";
echo "Is Auth Related: " . ($errorInfo['isAuthRelated'] ? 'Yes' : 'No') . "\n";
echo "Is Config Related: " . ($errorInfo['isConfigRelated'] ? 'Yes' : 'No') . "\n\n";

// Test 4: Unknown error
echo "Test 4: Unknown error (999)\n";
$errorInfo = $provider->getErrorInfo(999, 'unknownError');
echo "Code: {$errorInfo['code']}\n";
echo "Reason: {$errorInfo['reason']}\n";
echo "Message: {$errorInfo['message']}\n";
echo "Is Quota Related: " . ($errorInfo['isQuotaRelated'] ? 'Yes' : 'No') . "\n";
echo "Is Auth Related: " . ($errorInfo['isAuthRelated'] ? 'Yes' : 'No') . "\n";
echo "Is Config Related: " . ($errorInfo['isConfigRelated'] ? 'Yes' : 'No') . "\n\n";

// Test 5: Simulate error handling
echo "Test 5: Simulating error handling\n";
$testError = [
    'code' => 403,
    'message' => 'The requested operation requires more resources than the quota allows.',
    'errors' => [
        [
            'domain' => 'global',
            'reason' => 'quotaExceeded',
            'message' => 'The requested operation requires more resources than the quota allows.'
        ]
    ]
];

// Use reflection to access private method for testing
$reflection = new ReflectionClass($provider);
$handleApiErrorMethod = $reflection->getMethod('handleApiError');
$handleApiErrorMethod->setAccessible(true);

$result = $handleApiErrorMethod->invoke($provider, $testError);
echo "Error handling result: " . ($result === '' ? 'Empty string (as expected)' : $result) . "\n";
echo "Quota exceeded flag: " . ($provider->isQuotaExceeded() ? 'Yes' : 'No') . "\n\n";

echo "=== Test completed ===\n";
