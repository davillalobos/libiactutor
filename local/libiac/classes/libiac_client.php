<?php
namespace local_libiac;

defined('MOODLE_INTERNAL') || die();

/**
 * Minimal HTTP client from the Moodle server to Libiac Core. Deliberately thin:
 * no retries, no caching, no Libiac business logic.
 *
 * Every request carries the signed assertion (see assertion_signer) in the
 * X-Moodle-Assertion / X-Moodle-Assertion-Signature headers.
 */
class libiac_client {

    /**
     * Forwards a request (chat JSON or raw audio) to the backend. It never throws on a
     * non-2xx status: it returns the status so the caller can map it to a generic error
     * for the browser.
     *
     * @param array{payload: array<string, string>, signature: string} $assertion
     * @return array{status: int, body: array<string, mixed>}
     * @throws \moodle_exception on a transport error (no HTTP status at all)
     */
    public static function forward(
        string $backendurl,
        array $assertion,
        string $path,
        string $body,
        string $contenttype,
        int $timeout = 60
    ): array {
        $curl = new \curl();
        $curl->setHeader([
            'Content-Type: ' . $contenttype,
            // The header and the signature must describe the exact same fields.
            'X-Moodle-Assertion: ' . json_encode($assertion['payload'], JSON_UNESCAPED_SLASHES),
            'X-Moodle-Assertion-Signature: ' . $assertion['signature'],
        ]);
        $curl->setopt(['CURLOPT_TIMEOUT' => $timeout]);

        $response = $curl->post(rtrim($backendurl, '/') . $path, $body);
        $info = $curl->get_info();

        if (empty($info['http_code'])) {
            throw new \moodle_exception('connectionerror', 'local_libiac');
        }

        $decoded = json_decode((string) $response, true);
        return ['status' => (int) $info['http_code'], 'body' => is_array($decoded) ? $decoded : []];
    }
}
