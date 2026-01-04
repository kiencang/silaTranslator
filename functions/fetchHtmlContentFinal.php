<?php
// Đưa sản phẩm cho người dùng cuối thì để về 0
// Test sản phẩm thì xóa nó đi để lỗi hiển thị rõ hơn
/**
 * Custom Exception for Fetching Content Errors
 */
class FetchContentException extends \RuntimeException {}
class FetchContentCurlException extends FetchContentException {}
class FetchContentHttpException extends FetchContentException {}

/**
 * Fetches HTML content from a URL, optimized for reliability and browser mimicry.
 * Focuses on retrieving English content by default.
 *
 * @param string $url The URL to fetch content from.
 * @param array $options An array of options to customize the request. Available options:
 *     - `cookieFilePath` (string|null): Path to the file for reading/writing cookies. Default: null.
 *     - `userAgent` (string|null): Specific User-Agent string. If null, a random modern browser UA is chosen. Default: null.
 *     - `referer` (string|false|null): Referer URL. Set to false to omit the header. Default: 'https://www.google.com/'.
 *     - `timeout` (int): Maximum execution time in seconds. Default: 30.
 *     - `connectTimeout` (int): Connection timeout in seconds. Default: 10.
 *     - `maxRedirects` (int): Maximum number of redirects to follow. Default: 10.
 *     - `customHeaders` (array): Associative array of custom headers ('Header-Name' => 'Value'). These merge with/override defaults. Default: [].
 *     - `proxy` (string|null): Proxy server string (e.g., 'http://proxy.example.com:8080', 'socks5://user:pass@host:port'). Default: null.
 *     - `proxyType` (int): Proxy type (CURLPROXY_HTTP, CURLPROXY_SOCKS5, etc.). Default: CURLPROXY_HTTP.
 *     - `proxyUserPwd` (string|null): Proxy authentication ('user:password'). Default: null.
 *     - `maxRetries` (int): Maximum number of retries on transient errors (timeout, 429, 5xx). Default: 3.
 *     - `retryDelay` (int): Delay in seconds between retries. Default: 2.
 *     - `sslVerifyPeer` (bool): Verify the peer's SSL certificate. Default: true. (Disabling is insecure).
 *     - `sslVerifyHost` (int): Verify the certificate's name against host (0, 1, 2). Default: 2. (Disabling is insecure).
 *     - `caInfo` (string|null): Path to Certificate Authority (CA) bundle. Default: null (uses system default).
 *     - `forceEnglish` (bool): Strongly prefer English content via Accept-Language header. Default: true.
 *     - `returnTransferInfo` (bool): If true, returns an array with 'content', 'http_code', 'content_type', 'effective_url'. Default: false.
 *
 * @return string|array The HTML content as a string, or an array if `returnTransferInfo` is true.
 * @throws FetchContentCurlException If a cURL error occurs that cannot be resolved by retries.
 * @throws FetchContentHttpException If an HTTP error (>= 400) occurs that cannot be resolved by retries.
 * @throws FetchContentException For other issues like empty content or cookie file problems.
 */

function fetchHtmlContentFinal(string $url, array $options = []): string|array
{
    $defaultOptions = [
        'cookieFilePath' => null,
        'userAgent' => null,
        'referer' => 'https://www.google.com/', // Default to Google Referer
        'timeout' => 90,
        'connectTimeout' => 30,
        'maxRedirects' => 10,
        'customHeaders' => [],
        'proxy' => null,
        'proxyType' => CURLPROXY_HTTP,
        'proxyUserPwd' => null,
        'maxRetries' => 3,
        'retryDelay' => 2,
        'sslVerifyPeer' => true,
        'sslVerifyHost' => 2,
        'caInfo' => null,
        'forceEnglish' => true,
        'returnTransferInfo' => false,
    ];
    // Merge provided options with defaults
    $opt = array_merge($defaultOptions, $options);

    // --- User Agent Selection ---
    if (empty($opt['userAgent'])) {
        $userAgents = [
            // Keep these relatively updated
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36', // Chrome Win10
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:124.0) Gecko/20100101 Firefox/124.0', // Firefox Win10
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36 Edg/123.0.0.0' // Edge Win10
        ];
        $opt['userAgent'] = $userAgents[array_rand($userAgents)];
    }

    // --- Header Construction ---
    $headers = [
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'Accept-Encoding' => 'gzip, deflate', // Request compression
        'Connection' => 'keep-alive',
        'Upgrade-Insecure-Requests' => '1',
    ];
    // Set Accept-Language based on option
    $headers['Accept-Language'] = $opt['forceEnglish'] ? 'en-US,en;q=0.9' : 'en-US,en'; // Prioritize English strongly if forced

    // Add Referer if specified
    if ($opt['referer'] !== false && !empty($opt['referer'])) {
        $headers['Referer'] = $opt['referer'];
    }

    // Merge custom headers (custom ones take precedence)
    // Note: Case-insensitive merge for header names might be needed for perfect RFC compliance, but this usually works.
    $finalHeaders = array_merge($headers, $opt['customHeaders']);

    // Format headers for cURL
    $curlHeaders = [];
    foreach ($finalHeaders as $key => $value) {
        $curlHeaders[] = $key . ': ' . $value;
    }

    // --- Retry Logic ---
    $attempts = 0;
    while ($attempts < $opt['maxRetries']) {
        $attempts++;
        $ch = curl_init();

        try {
            // --- Basic cURL Options ---
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $opt['maxRedirects']);
            curl_setopt($ch, CURLOPT_TIMEOUT, $opt['timeout']);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $opt['connectTimeout']);
            curl_setopt($ch, CURLOPT_USERAGENT, $opt['userAgent']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

            // --- Compression Handling ---
            curl_setopt($ch, CURLOPT_ENCODING, ""); // Handle gzip, deflate automatically

            // --- Cookie Handling ---
            if ($opt['cookieFilePath']) {
                $cookieDir = dirname($opt['cookieFilePath']);
                if (!is_dir($cookieDir)) {
                     // Optionally try to create dir: if (!@mkdir($cookieDir, 0755, true) && !is_dir($cookieDir)) ...
                     trigger_error("Cookie directory not found: " . $cookieDir, E_USER_WARNING);
                } elseif (!file_exists($opt['cookieFilePath'])) {
                    if (!touch($opt['cookieFilePath'])) { // Avoid @ if possible, check return value
                        trigger_error("Could not create cookie file: " . $opt['cookieFilePath'] . ". Check permissions.", E_USER_WARNING);
                    }
                }

                // Check writability/readability before setting options
                 if (is_readable($opt['cookieFilePath']) && is_writable($opt['cookieFilePath'])) {
                     curl_setopt($ch, CURLOPT_COOKIEJAR, $opt['cookieFilePath']); // Save cookies
                     curl_setopt($ch, CURLOPT_COOKIEFILE, $opt['cookieFilePath']); // Load cookies
                 } else {
                     // Log or warn if still not accessible after checks/creation attempt
                     trigger_error("Cookie file is not readable/writable: " . $opt['cookieFilePath'], E_USER_WARNING);
                 }
            }

            // --- Proxy Settings ---
            if ($opt['proxy']) {
                curl_setopt($ch, CURLOPT_PROXY, $opt['proxy']);
                curl_setopt($ch, CURLOPT_PROXYTYPE, $opt['proxyType']);
                if ($opt['proxyUserPwd']) {
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $opt['proxyUserPwd']);
                }
            }

            // --- SSL Settings ---
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $opt['sslVerifyPeer']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $opt['sslVerifyHost']);
            if ($opt['caInfo']) {
                curl_setopt($ch, CURLOPT_CAINFO, $opt['caInfo']);
            } elseif (!$opt['sslVerifyPeer'] || $opt['sslVerifyHost'] === 0) {
                // trigger_error sẽ hiển thị lỗi ra màn hình, sử dụng ini_set('display_errors', 0); để chặn hiển thị này
                 trigger_error("SSL verification disabled. This is insecure.", E_USER_WARNING);
            }


            // --- Execute Request ---
            $htmlContent = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); // Get final URL after redirects
            $error = curl_error($ch);
            $errno = curl_errno($ch);


            // --- cURL Error Check ---
            if ($errno) {
                 // Define transient cURL errors that might be resolved by retrying
                 $retryableCurlErrors = [
                     CURLE_OPERATION_TIMEDOUT,
                     CURLE_COULDNT_RESOLVE_HOST, // Can be temporary DNS issues
                     CURLE_COULDNT_CONNECT, // Can be temporary network issues
                     CURLE_GOT_NOTHING, // Server closed connection unexpectedly
                     // Add more if needed based on experience
                 ];
                 if (in_array($errno, $retryableCurlErrors) && $attempts < $opt['maxRetries']) {
                     trigger_error("cURL error (Attempt $attempts/{$opt['maxRetries']}) for '$url': [$errno] $error. Retrying in {$opt['retryDelay']}s...", E_USER_WARNING);
                     sleep($opt['retryDelay']);
                     curl_close($ch); // Close handle before retrying
                     continue; // Go to next iteration of the while loop
                 } else {
                     // Non-retryable cURL error or max retries reached
                     $errorMessage = "cURL error after $attempts attempt(s) for '$url': [$errno] $error";
                     if ($errno === CURLE_SSL_CACERT || $errno === CURLE_SSL_PEER_CERTIFICATE) {
                          $errorMessage .= " - SSL certificate issue. Check system CA bundle or 'caInfo' option.";
                     }
                     throw new FetchContentCurlException($errorMessage, $errno);
                 }
            }

            // --- HTTP Status Code Check ---
            if ($httpCode >= 400) {
                 // Define transient HTTP errors
                 $retryableHttpCodes = [429]; // Too Many Requests
                 if (($httpCode >= 500 || in_array($httpCode, $retryableHttpCodes)) && $attempts < $opt['maxRetries']) {
                     trigger_error("HTTP error (Attempt $attempts/{$opt['maxRetries']}) for '$url': Code $httpCode. Retrying in {$opt['retryDelay']}s...", E_USER_WARNING);
                     // Log response body snippet for debugging server errors on retry attempts
                     if ($htmlContent && $httpCode >= 500) {
                          error_log("Server error $httpCode response snippet from $url: " . substr($htmlContent, 0, 500));
                     }
                     sleep($opt['retryDelay']);
                     curl_close($ch); // Close handle before retrying
                     continue; // Go to next iteration of the while loop
                 } else {
                     // Non-retryable HTTP error or max retries reached
                     $errorMessage = "HTTP error $httpCode after $attempts attempt(s) for URL: $url";
                     if ($httpCode == 403) {
                         $errorMessage .= " - Forbidden. Check User-Agent, Headers, Cookies, Referer, or potential IP block. Lỗi này có thể do website chặn ứng dụng, bạn nên dùng cách *Dịch HTML* ở button dưới cùng.";
                     } elseif ($httpCode == 404) {
                         $errorMessage .= " - Not Found.";
                     } elseif ($httpCode == 429) {
                          $errorMessage .= " - Too Many Requests (Rate Limit Exceeded).";
                     } elseif ($httpCode >= 500) {
                         $errorMessage .= " - Server Error.";
                     }
                     // Log response body snippet for final failure
                     if ($htmlContent) {
                          error_log("Failed request ($httpCode) response snippet from $url: " . substr($htmlContent, 0, 500));
                     }
                     throw new FetchContentHttpException($errorMessage, $httpCode);
                 }
            }

            // --- Content Check ---
            // Check Content-Type (Optional, as non-HTML might still be useful sometimes)
             if ($contentType && !preg_match('/^(text\/html|application\/xhtml\+xml|application\/xml)/i', $contentType)) {
                  trigger_error("Warning: Content-Type received is not typical HTML/XML ('$contentType') for URL: $url. Content snippet: " . substr($htmlContent ?: '', 0, 200), E_USER_WARNING);
                  // Decide whether to proceed or throw based on requirements. Proceeding for now.
             }

            // Check for empty content after successful HTTP code (2xx)
             if ($htmlContent === false || $htmlContent === '') {
                  // This case might happen if curl_exec succeeded but returned nothing despite a 2xx code.
                  // Check errno again just in case, though it should have been caught above.
                  if (curl_errno($ch) === 0) {
                       throw new FetchContentException("Received empty content despite HTTP $httpCode for URL: " . $url);
                  } else {
                       // Should have been caught by the errno check, but handle defensively.
                       throw new FetchContentCurlException("cURL error after $attempts attempt(s) for '$url': [" . curl_errno($ch) . "] " . curl_error($ch), curl_errno($ch));
                  }
             }


            // --- Success ---
            curl_close($ch); // Close handle on success

            if ($opt['returnTransferInfo']) {
                 return [
                     'content' => $htmlContent,
                     'http_code' => $httpCode,
                     'content_type' => $contentType,
                     'effective_url' => $effectiveUrl,
                 ];
            } else {
                 return $htmlContent;
            }

        } catch (Exception $e) {
            // Ensure cURL handle is closed if an exception occurred within the try block
            if (isset($ch) && is_resource($ch)) {
                curl_close($ch);
            }
            // If it's the last attempt, re-throw the caught exception
            if ($attempts >= $opt['maxRetries']) {
                 throw $e; // Re-throw the specific exception (FetchContentCurlException, FetchContentHttpException, etc.)
            }
            // Log the exception before retrying (already handled by trigger_error within checks, but could add more here if needed)
             // Ensure delay before next attempt (already handled by sleep() within checks)
             // continue; // Loop will continue naturally if error was retryable and not max attempts
        }
    } // End while loop

    // Should not be reached if logic is correct, but as a fallback:
    throw new FetchContentException("Failed to fetch content for '$url' after {$opt['maxRetries']} attempts (unexpected state).");
}