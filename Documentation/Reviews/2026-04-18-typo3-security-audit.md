# TYPO3 Security Audit — Remaining Gaps

_Date: 2026-04-18_
_Skill: typo3-security_

Prior work (commits `5587d1b`, `eff9727`, `d709cca`) already landed the
backend admin guard, scope-to-authorized-roots moderation, honeypot
field on comments, URL scheme whitelist, and reCAPTCHA validator
de-globalization + timeout. This audit lists only the remaining
v14-specific gaps.

Out of scope: project-level items (`trustedHostsPattern`, install tool,
backend user policies, `fileDenyPattern`, HTTP security headers at the
web-server tier) — these live in the consuming project's
`config/system/settings.php`, not inside an extension.

## Findings

### 1. Standalone + ModernTailwind Sets load CDN assets (HIGH for CSP-hardened sites)

`Configuration/Sets/Standalone/setup.typoscript:38,43` pulls Bootstrap
5.3-alpha3 CSS + JS from `cdn.jsdelivr.net`, and
`Configuration/Sets/ModernTailwind/setup.typoscript:39` pulls Tailwind
from `cdn.tailwindcss.com`:

```typoscript
bootstrap = https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css
bootstrap.external = 1

…

bootstrap = https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js
```

Loading third-party runtime assets from a CDN works on default CSP but
breaks every CSP-hardened deployment (default-src 'self'). Three issues:

1. **Supply chain.** Any compromise of jsdelivr / cdn.tailwindcss.com
   executes in the blog's frontend origin. Subresource Integrity (SRI)
   is not set.
2. **Alpha pin.** `bootstrap@5.3.0-alpha3` is a pre-release; a stable
   `5.3` / `5.4` release is out.
3. **CSP guidance missing.** The README/docs do not tell operators to
   relax CSP or self-host.

**Fix (scope of this cycle):**

- Upgrade the pin to the latest Bootstrap 5.3.x stable and add an SRI
  hash (`integrity=`, `crossorigin="anonymous"`) via
  `includeCSS.<key>.external = 1` + `includeCSS.<key>.integrity = ...`.
- Document the CSP implication in the README (so operators know to
  either allow `cdn.jsdelivr.net` in `script-src` / `style-src` or
  self-host).
- Tailwind's `cdn.tailwindcss.com` is an in-browser runtime compiler
  explicitly marked "not for production" upstream — replace with a
  compiled stylesheet shipped via the extension's Vite pipeline or
  document "use only for local testing."

### 2. `f:format.raw()` on copyright + Meta partials (LOW — editor-controlled)

14 `f:format.raw` call sites, all on:

- `Resources/Private/Layouts/Page/Default.html:75` and its variants
  (`ModernBootstrap`, `ModernTailwind`, `Pages/Default`, …) rendering
  `{copyright}` from site-set TypoScript constants.
- `Resources/Private/Partials/Meta/Rendering/Group.html:2`,
  `Item.html:5,9,10` rendering `icon`/`prefix`/`content` from backend
  editor data passed into a render context.

All values are editor-controlled (TypoScript constants, TCA fields)
and the rendered contexts are intentionally HTML-accepting. Low risk in
the current codebase, but explicit escape-intent comments would help
future contributors.

**Fix (optional):** Add `<!-- editor-controlled HTML -->` comments at
the `f:format.raw` sites. Nothing blocking.

### 3. Comment submission has no per-IP rate limit (MEDIUM — noted earlier, deferred)

Reiterated here from the initial review. With reCAPTCHA disabled (the
default), a spammer can flood `CommentFormFinisher` and:

- bloat the `tx_blog_domain_model_comment` table,
- amplify outgoing mail via `CommentAddedNotification`.

The honeypot catches dumb bots; a rate limit catches scripted
submissions with cleared honeypot state.

**Fix (deferred to its own cycle):** Introduce a `symfony/rate-limiter`
integration (v14 core pulls it in) or a simple
`Context`-based per-IP throttle keyed by `Cache::VariableFrontend`.
Deferred because it touches request lifecycle and needs functional test
coverage.

### 4. No Subresource Integrity on backend JavaScript modules (INFO)

`Configuration/JavaScriptModules.php` registers `@t3g/blog/*` via the
TYPO3 importmap. The modules are served from the extension directory
(`Resources/Public/JavaScript/…`) so they share the backend origin.
No SRI is needed for first-party scripts; the importmap handles
integrity.

### 5. Comment moderation uses route tokens (OK)

`Resources/Private/Templates/Backend/Comments.html` uses `f:link.action`
which TYPO3 backend decorates with a per-route token. CSRF covered.

### 6. CSP for the extension (INFO)

No `Configuration/ContentSecurityPolicies.php` is shipped. The backend
module has no inline `<script>` / `<style>` (verified by reading
`Resources/Private/Templates/Backend/*`). Default backend CSP holds.
Adding a scoped policy becomes necessary if someone inlines styles in
the future — flagged for the conformance cycle.

## Planned changes (this cycle)

1. Bump Bootstrap pin (`5.3.0-alpha3` → latest stable) and add SRI to
   the Standalone Set's `includeCSS` / `includeJSFooterlibs`.
2. Replace `cdn.tailwindcss.com` in the ModernTailwind Set with a
   documentation note ("dev-only; ship your compiled CSS for
   production") and leave the include disabled by default.
3. Update the README with a "CSP and external assets" note.

Deferred:

- Per-IP rate limit on comment submissions (its own cycle).
- `Configuration/ContentSecurityPolicies.php` (will be re-evaluated in
  the conformance cycle if and when the backend gains inline assets).
