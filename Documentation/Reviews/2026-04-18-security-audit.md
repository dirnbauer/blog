# Security Audit — OWASP/CWE Scope

_Date: 2026-04-18_
_Skill: security-audit_

Prior hardening (commits `5587d1b`, `eff9727`, `d709cca`, `1b3518e`) is
complete: admin guard on `createBlogAction`, scope-to-accessible
moderation, honeypot + URL scheme whitelist, reCAPTCHA DI + request
attribute + timeout, Bootstrap SRI, Tailwind runtime-CDN retirement.
This audit reports only exposures that remain.

## Static scans

| Check | Result |
| --- | --- |
| `composer audit` | ✅ no known CVEs against pinned dependencies |
| Dynamic `include` / `require` with variables | ✅ none |
| `unserialize` / `yaml_parse` (deserialization) | ✅ none |
| `exec` / `system` / `shell_exec` / `passthru` / `popen` / `proc_open` | ✅ none |
| `file_get_contents` / `fopen` with user input | ✅ none |
| XML parsing (XXE risk) | ✅ no `DOMDocument` / `SimpleXML` / `loadXML` |
| `var_dump` / `print_r` / `error_log` / debug helpers left in | ✅ none |
| Secrets in tracked files | ✅ none (`BEGIN PRIVATE`, `AKIA…`, api_key/password literals) |
| SQL via `createNamedParameter` with typed PDO constants | ✅ verified in original review |
| Output escaping default (Fluid) | ✅ `f:format.raw` only on editor-controlled values |

## Remaining exposures

### 1. No per-IP rate limit on comment submission (MEDIUM — carried)

`Classes/Domain/Factory/CommentFormFactory.php` +
`Classes/Domain/Finisher/CommentFormFinisher.php`. With reCAPTCHA
disabled (default) and the honeypot alone, a scripted submitter that
clears the honeypot can:

- accumulate DB rows in `tx_blog_domain_model_comment`,
- amplify outbound mail via `CommentAddedNotification` → both
  `AuthorNotificationProcessor` and `AdminNotificationProcessor` send
  an email per accepted submission.

**Fix (deferred to a dedicated cycle):** wire
`symfony/rate-limiter` (shipped with TYPO3 v14 core) into
`CommentController::formAction` or `CommentFormFinisher::executeInternal`
with a per-IP quota (e.g. 5 per minute, 30 per hour). Mapping:
`Symfony\Component\RateLimiter\RateLimiterFactory` → key is `REMOTE_ADDR`
resolved via the request. Deferred because it needs functional test
coverage for the 429 path.

### 2. `GravatarProvider` fetches external images using `md5(email)` (INFO)

`Classes/AvatarProvider/GravatarProvider.php:79` and
`Classes/Service/Avatar/Gravatar/GravatarUriBuilder.php:33` use
`md5($email)` to build the Gravatar URL. MD5 is weak cryptographically
but **required by the Gravatar public API** — the upstream service
keys on `md5(lowercase(trim(email)))`. Not ours to change; flagged only
so future reviewers don't mistake this for a vulnerability.

`hash_equals` is already used for file-integrity comparison
(`:84`) — no timing-attack exposure.

### 3. No explicit timeout on the Gravatar HTTP fetch (LOW)

`Classes/Service/Avatar/Gravatar/GravatarResourceResolver.php` (the
only other outbound HTTP call after the captcha validator) sends a
request to `https://www.gravatar.com/avatar/...` through Guzzle without
an explicit timeout option. Guzzle defaults are long. A hung remote
server can stall page rendering when the Gravatar proxy cache is cold.

**Fix (one-liner, could land this cycle):** thread a
`['timeout' => 5.0]` option into the request builder.

### 4. `SetupService` loads extension-shipped PHP via `require` (OK — path is fixed)

`Classes/Service/SetupService.php:89,139` uses
`GeneralUtility::getFileAbsFileName('EXT:blog/Configuration/DataHandler/...')`.
The path is hard-coded, `getFileAbsFileName` validates the
`EXT:<key>` prefix, and the loaded file returns an array data
structure. No user input reaches the include path. Safe.

### 5. Supply-chain hygiene (INFO)

`composer audit` is clean. Two dev-only tools have non-security minor
updates available: `friendsofphp/php-cs-fixer` 3.94.2 → 3.95.1,
`phpstan/phpstan` 2.1.46 → 2.1.50. Neither is a security concern but
keeping them current reduces friction when CI runs again. Production
deps (`typo3/cms-*` 14.2.0, `symfony/*` 7.4.8, `doctrine/dbal` 4.3.5)
are already on current patches.

### 6. Configuration / middleware (N/A — project-scope)

`trustedHostsPattern`, `fileDenyPattern`, HSTS, reverse-proxy
`X-Forwarded-*` handling, install-tool lockdown, MFA enforcement —
none are extension-configurable; all live in the consuming site's
`config/system/settings.php`. Documented in the README follow-up
rather than fixed here.

## Planned changes (this cycle)

1. Add a request timeout (`timeout: 5`) to the Gravatar request in
   `GravatarResourceResolver` (finding #3).
2. Bump `phpstan/phpstan` and `friendsofphp/php-cs-fixer` to current
   minors via `composer update --with-all-dependencies <pkg>`
   (finding #5) — only if the update does not trip any of our quality
   gates.

Deferred:

- Rate limiter on `CommentFormFinisher` (finding #1) — its own cycle.
- Nothing else actionable inside the extension. Remaining items live
  in the deployment's site configuration.
