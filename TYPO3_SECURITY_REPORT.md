# TYPO3 Security Hardening Report — Recheck

**Extension:** t3g/blog v14.0.0  
**Date:** 2026-03-11  
**Scope:** TYPO3-oriented hardening checks for extension-level behavior

## Recheck Findings

### High — Comment profile URL accepts unsafe schemes at model level

`Classes/Domain/Model/Comment.php` stores the URL value without any scheme
allowlist. Form validation exists, but model-level hardening is missing for
non-form entry paths (imports, direct persistence, future API usage).

**Recommended:** allow only `http` and `https` and clear invalid schemes.

### Medium — External links should enforce opener isolation

`Resources/Private/Partials/Comments/Comment.html` uses `target="_blank"` and
`rel="external nofollow"` but does not include `noopener noreferrer`.

**Recommended:** enforce `rel="noopener noreferrer nofollow ugc"` for comment
author links.

### Medium — reCAPTCHA response parsing should be defensive

`Classes/Domain/Validator/GoogleCaptchaValidator.php` assumes a valid JSON
object shape from the remote API response.

**Recommended:** guard for invalid JSON payloads and treat them as validation
failures.

## Planned Hardening Change Set

1. Add model-level URL scheme allowlist in `Comment::setUrl()`
2. Normalize/trim URL values before persistence paths
3. Harden captcha response parsing and failure handling
