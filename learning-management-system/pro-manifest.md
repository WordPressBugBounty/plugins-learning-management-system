# Pro Manifest

This file is used by the `/sync-to-pro` and `/sync-to-free` Claude Code slash commands
to determine which files and folders are pro-only and should not be replicated to the free version.

Keep this file updated as the repos evolve.

---

## Pro-Only Addon Folders

These folders exist only in `learning-management-system-pro/addons/` and must never be replicated to free:

```
addons/advanced-quiz
addons/assignment
addons/content-drip
addons/coupons
addons/course-attachments
addons/course-bundle
addons/course-faq
addons/course-preview
addons/edd-integration
addons/gradebook
addons/hubspot-integration
addons/mailchimp-integration
addons/mailerlite-integration
addons/manual-enrollment
addons/multiple-instructors
addons/pmpro-integration
addons/prerequisites
addons/public-profile
addons/razorpay
addons/rcp-integration
addons/social-login
addons/two-factor-authentication
addons/zapier
addons/zoom
```

---

## Pro-Only Files (outside addons)

These individual files exist only in pro:

```
includes/Tax.php
includes/Models/PaymentRetry.php
includes/PostType/Subscription.php
packages/zoom
pro/Controllers
pro/Enums
pro/Helper
pro/Jobs
pro/License.php
pro/Models
pro/PluginUpdater.php
pro/Repository
pro/core-features
```

---

## Shared Files with Pro-Only Code Blocks

Files that exist in both repos but may contain pro-only code blocks.
When syncing pro→free, the agent will ask you per-file whether to replicate or skip.

Use `// @pro-only:start` and `// @pro-only:end` markers (or JS/CSS equivalents)
to annotate pro-only code blocks inside shared files so the agent can auto-skip them.

```
# Add shared files with known pro-only blocks here as you discover them
# Example:
# includes/Helper.php  (contains subscription-related methods)
```

---

## Notes

- All files not listed above are considered **shared** and will trigger a "replicate or skip?" prompt during sync.
- If you add a new pro-only addon, add its path to this manifest immediately.
- This file should be kept in sync (identical) across both repos.
