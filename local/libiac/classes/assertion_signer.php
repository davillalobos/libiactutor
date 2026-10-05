<?php
namespace local_libiac;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds and signs a Moodle assertion in the EXACT format the Libiac
 * backend verifier expects (FASE 1B, app/auth/moodle_assertion.py on the
 * Libiac Core side).
 *
 * The canonical signing string produced here MUST match
 * `app.auth.moodle_assertion._canonical_signing_string()` byte for byte, or
 * every assertion this plugin issues fails signature verification:
 *
 *   - every field value is a plain string (never a bare int/bool/null) —
 *     `100` and `"100"` must serialize identically regardless of language;
 *   - keys sorted with `ksort($fields, SORT_STRING)` — field names are
 *     ASCII-only, so this agrees with Python's default string ordering;
 *   - `json_encode` with no extra whitespace (PHP's default already matches
 *     Python's `separators=(",", ":")`);
 *   - non-ASCII characters left to escape as `\uXXXX` — PHP's `json_encode`
 *     default already matches Python's `ensure_ascii=True` default, no
 *     extra flag needed;
 *   - forward slashes left UNESCAPED — PHP's `json_encode` escapes `/` to
 *     `\/` by default, so `JSON_UNESCAPED_SLASHES` is REQUIRED here to
 *     reproduce the same string Python produces (Python's `json.dumps`
 *     never escapes `/`).
 *
 * See `integrations/moodle/ci_reference/assertion_reference.py` for the
 * Python mirror of this exact algorithm, verified end-to-end against the
 * real backend verifier in
 * `tests/test_moodle_plugin_assertion_compatibility.py` (CI cannot execute
 * PHP, so that Python mirror is what CI actually runs).
 */
class assertion_signer {

    /** @return array<string, string> */
    public static function build_fields(
        string $issuer,
        string $externaluserid,
        string $externalcourseid,
        string $issuedat,
        string $expiresat,
        string $userfullname = ''
    ): array {
        $fields = [
            'issuer' => $issuer,
            'external_user_id' => $externaluserid,
            'external_course_id' => $externalcourseid,
            'issued_at' => $issuedat,
            'expires_at' => $expiresat,
        ];
        // Optional signed field: the student's display name, so the backend can address them.
        if ($userfullname !== '') {
            $fields['user_fullname'] = $userfullname;
        }
        return $fields;
    }

    /** @param array<string, string> $fields */
    public static function canonical_signing_string(array $fields): string {
        ksort($fields, SORT_STRING);
        return json_encode($fields, JSON_UNESCAPED_SLASHES);
    }

    /** @param array<string, string> $fields */
    public static function sign(array $fields, string $secret): string {
        return hash_hmac('sha256', self::canonical_signing_string($fields), $secret);
    }

    /**
     * Convenience used by local_libiac (classes/widget.php, ajax.php): builds the full fields +
     * signature for "right now", with the given lifetime in seconds.
     *
     * Callers MUST keep $lifetimeseconds within the backend's
     * MOODLE_ASSERTION_MAX_LIFETIME (300 seconds / 5 minutes as of FASE 1B)
     * or the backend rejects the assertion outright regardless of a valid
     * signature.
     *
     * @return array{payload: array<string, string>, signature: string}
     */
    public static function build_and_sign(
        string $issuer,
        string $secret,
        string $externaluserid,
        string $externalcourseid,
        int $lifetimeseconds,
        string $userfullname = ''
    ): array {
        $now = time();
        $fields = self::build_fields(
            $issuer,
            $externaluserid,
            $externalcourseid,
            (string) $now,
            (string) ($now + $lifetimeseconds),
            $userfullname
        );
        return [
            'payload' => $fields,
            'signature' => self::sign($fields, $secret),
        ];
    }
}
