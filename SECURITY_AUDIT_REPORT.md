# Security Audit Report

**Extension:** t3g/blog v14.0.0
**Date:** 2026-03-11
**Framework:** OWASP Top 10 (2021), CVSS v3.1

## Critical Findings

### 1. Stored XSS via Nl2pViewHelper (CVSS 8.4)

**File:** `Classes/ViewHelpers/Format/Nl2pViewHelper.php`
**Type:** A03 Injection / XSS

The ViewHelper has `$escapeOutput = false` and outputs user-submitted comment text without HTML encoding. An attacker can inject `<script>` tags in comments that execute in other users' browsers.

**Fix:** Apply `htmlspecialchars()` to content before wrapping in `<p>` tags.

## High Findings

### 2. reCAPTCHA Validator Logic Bug (CVSS 7.5)

**File:** `Classes/Domain/Validator/GoogleCaptchaValidator.php`
**Type:** A07 Authentication Failures

The validation condition contains contradictory logic (`!(bool)$x && $x === 'value'`), meaning reCAPTCHA validation may never execute. Additionally, the validator reads from query params instead of request body for POST forms.

**Fix:** Correct the boolean logic and merge query+body params.

### 3. Unsafe File Type in Gravatar Proxy (CVSS 6.1)

**File:** `Classes/AvatarProvider/GravatarProvider.php`
**Type:** A03 Injection

The file type is derived from Content-Type header without validation. SVG files (which can contain JavaScript) could be served as avatars.

**Fix:** Whitelist allowed extensions (png, jpg, gif, webp) and reject SVG.

## Medium Findings

### 4. Comment URL Without Scheme Validation

**File:** `Resources/Private/Partials/Comments/Comment.html`
**Type:** A03 Injection

User-submitted URLs used in `f:link.external` could use `javascript:` or `data:` schemes.

**Fix:** Validate URL scheme in form validator (allow only http/https).

### 5. Missing createNamedParameter in CommentRepository

**File:** `Classes/Domain/Repository/CommentRepository.php` (line 122-124)
**Type:** A03 Injection (defense in depth)

QueryBuilder `eq()` calls without `createNamedParameter()`. Low risk since values are typed int, but should use parameterized queries consistently.

### 6. Potential TypeError in updateCommentStatusAction

**File:** `Classes/Controller/BackendController.php` (line 115)
**Type:** A09 Logging Failures

`$comments['__identity']` accessed without null check could cause TypeError with stack trace.

**Fix:** Add `?? []` fallback.

## Summary

| Severity | Count |
|----------|-------|
| Critical | 1 |
| High | 2 |
| Medium | 3 |
| Low | 2 |
| Total | 8 |
