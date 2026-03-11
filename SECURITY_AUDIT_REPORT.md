# Security Audit Report — Recheck

**Extension:** t3g/blog v14.0.0  
**Date:** 2026-03-11  
**Framework:** OWASP Top 10 (2021), CVSS v3.1

## Recheck Outcome

Most previously identified issues are now remediated:

- comment text escaping in `Nl2pViewHelper` is in place
- Gravatar file-type handling is restricted
- SQL parameterization in `CommentRepository` is present
- reCAPTCHA validator logic and request handling are corrected and hardened
- model-level comment URL scheme enforcement now limits links to `http/https`

## Remaining Findings

### Medium — External link opener isolation not fully enforced

**File:** `Resources/Private/Partials/Comments/Comment.html`  
**OWASP:** A05 Security Misconfiguration

Comment author links open with `target="_blank"` and currently use
`rel="external nofollow"`. Add `noopener` and `noreferrer` to prevent reverse
tabnabbing and opener leakage.

**Recommended fix:** `rel="noopener noreferrer nofollow ugc"`.

## Residual Risk Summary

| Severity | Count |
|----------|-------|
| Critical | 0 |
| High | 0 |
| Medium | 1 |
| Low | 0 |
| Total | 1 |

## Planned Final Audit Remediation

1. Harden external link `rel` attributes in comment template
2. Re-run PHPStan and targeted tests to ensure no regression
