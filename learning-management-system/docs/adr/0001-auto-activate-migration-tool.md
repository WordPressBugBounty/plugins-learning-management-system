# 1. Auto-activate the Migration Tool when a competing LMS is present

Date: 2026-08-11
Status: Accepted
Issue: [#603](https://github.com/Codeinwp/learning-management-system-pro/issues/603)
Milestone: 3.4

## Context

A site that runs a competing LMS alongside Masteriyo is a site mid-migration, or a
site that has not discovered it can migrate. The Migration Tool addon already
carries a working migrator for each of the five platforms it supports, but it
ships inactive, and its UI tab is hidden until it is activated. The people who
most need it are the people least likely to find it.

Planning confirmed the behaviour and named Tutor LMS the first target: activate
the addon when a competing LMS is detected, and tell the user it happened.

Facts about the tree that constrain the design:

- `addons/migration-tool/` is a `Plan: Free` addon under `addons/`, and
  `bootstrap/plugin.php:116` calls `Addons::load_all()` unconditionally. The
  addon therefore loads in **both** products.
- Migrator classes are PSR-4 autoloaded (`Masteriyo\Addons\MigrationTool\`)
  independently of whether the addon is active, so detection can read
  `TutorLMSMigrator::get_plugin_file()` without the addon being on.
- `main.php` returns early when the addon is inactive, before it adds anything
  to the `masteriyo_service_providers` filter.
- The autoloader is required at `bootstrap/plugin.php:79`, before `load_all()`.
- `migration-tool` has no `setup.php`, so `Addons::set_active()` requires only
  `main.php`.
- Masteriyo admin screens call `remove_all_actions( 'admin_notices' )`
  (`includes/Masteriyo.php:930`), so notices must use `masteriyo_admin_notices`.

## Decision

### Detection runs on `admin_init`, hooked above the early return

Turning an addon on is a change to site state, so it needs an authenticated user
who is allowed to make it: `current_user_can( 'manage_masteriyo_settings' )`.
That decides the timing, because the capability is not knowable earlier.
`current_user_can()` reaches `wp_get_current_user()`, which is pluggable and so
undefined while plugins load.

`is_admin()` is not a substitute. It is true for an unauthenticated request to
`/wp-admin/`, which WordPress serves through the whole plugin load before
`auth_redirect()` sends it to the login screen, and true for `admin-ajax.php`,
which serves `nopriv` actions. Either would let a visitor trigger the write.

The hook is registered **above** the `is_active()` guard, because an inactive
addon is exactly the one that has to detect.

The cost is one page load. `masteriyo_service_providers` is applied from
`config/app.php` while plugins load, so an addon activated at `admin_init` has
missed it and registers nothing more that request. Nothing misreports in the
meantime: the notice is hooked *after* the same guard, so the request that
performs the activation renders no notice at all. The next admin page load sees
an active addon and brings up the providers, the Migration tab and the notice
together.

An earlier revision of this ADR justified activating during plugin load by
claiming the alternative would show a notice saying "enabled" while the tab was
absent. That was wrong — the notice cannot render on that request either.

### Every migrator's source plugin, keyed by migrator slug

All five migrators are watched — Tutor LMS, LearnDash, LearnPress, LifterLMS and
MasterStudy. Each already declares its own basename through `get_plugin_file()`,
so `Helper::source_migrators()` is the whole inventory and a new migrator joins
by being added to that one list.

Tutor LMS was the first target and shipped alone at first. Widening cost only
that list, because the marker stores migrator **slugs** rather than a boolean.

**A site may be leaving more than one platform**, so detection returns a list,
not a first match, and the notice names all of them. Detection order follows
`source_migrators()` rather than plugin activation order, so the same site
always reports the same way.

**Every platform found is recorded in one pass**, not only the one that
triggered activation. Otherwise a second platform that was on the site all along
would switch the addon back on later, against an admin who deliberately turned
it off — the marker exists precisely to stop that. A platform installed *after*
that point is genuinely new and is meant to trigger again.

**Recorded and announced are separate lists.** `masteriyo_migration_tool_announced`
holds only the platforms whose detection actually turned the addon on, and the
notice reads that one. Finding a platform while the addon is already active —
because an admin enabled it by hand — still records it in the first list, so it
cannot switch the addon back on later, but announcing it would claim we enabled
something we did not.

### A one-shot marker per source LMS

The option `masteriyo_migration_tool_auto_activated` holds the slugs already
acted on, e.g. `array( 'tutor' )`.

Without it the addon cannot be turned off: an admin deactivates it, and the next
admin page load turns it straight back on, which reads as a bug rather than a
feature. A slug is written the first time that platform is seen, whether or not
the sighting activated anything, and is never cleared. We therefore act at most
once per source LMS per site.

### Two notices: WordPress outside the app, React inside it

Masteriyo screens render their own notices as Chakra components inside the app
(`ReviewNotice.tsx`, `AllowUsageNotice.tsx`) and core suppresses the PHP
equivalent there — see the early return in `Masteriyo::add_review_notice()`. A
plain WordPress notice on those screens sits above the app chrome and reads as
foreign, so this follows the same split: the PHP notice bails on
`masteriyo_is_admin_page()`, and `MigrationNotice.tsx` covers the app.

Both read one predicate, `Helper::should_display_activation_notice()`, so they
cannot disagree about when to appear. The React side gets its state through the
`masteriyo_localized_admin_scripts` filter rather than an edit to
`ScriptStyle.php`, so core keeps no reference to this addon.

`MigrationNotice` is mounted twice — in `components/common/Header` and in
`screens/dashboard/Index` — because the two layouts are disjoint and neither
covers the whole app. `ReviewNotice` uses only the first and is therefore absent
from Tools, Settings and Add-ons.

Two mounts still miss one case. A registered route whose `layout` is not
`dashboard-header` is rendered bare by `Router.tsx`, so a pro screen such as the
student course report shows no notice. Mounting once at the router root would
close that, at the cost of putting the card above the Masteriyo menu bar on
every screen, which is the placement this decision set out to avoid. The gap
costs a user nothing: any other screen in the app shows the notice, and it is
not dismissed by failing to be seen.

### Only administrators are told

The notice requires `manage_masteriyo_settings` **and** the administrator role.
The second test looks redundant and is not: the Migration tab in `Tools.tsx`
renders behind `isCurrentUserAdmin`, whereas `manage_masteriyo_settings` also
belongs to the manager role (`Capabilities::get_manager_capabilities()`). Gating
on the capability alone invites a manager to a tab that does not exist for them.

The deep link defends itself as well — `resolveMigrationTabIndex()` returns null
unless the tab is really there, because selecting an absent tab index leaves the
Tools panel area blank.

### The notice is dismissed permanently, per user

Dismissal writes user meta `masteriyo_dismissed_migration_notice`, following
`includes/Masteriyo.php:1493`. Per user rather than per site because on a
multi-admin site one admin's dismissal should not hide the news from another.

The marker and the dismissal are separate state. The marker answers "have we
already acted", the dismissal answers "has this person seen it". Collapsing them
would either re-activate a deliberately-disabled addon or nag forever.

### Dismissal is a REST route

Dismissal is `POST masteriyo/v1/migration-notice/dismiss`, on its own
`MigrationNoticeController`, which is the rule in `CLAUDE.md` and the shape core
already uses for `SingleCourseLayoutNoticeController`. React reaches it through
the `API` wrapper, which injects the nonce; the WordPress notice outside the app
sends the same request with an `X-WP-Nonce` header.

It is a separate controller from `LMSMigrationController` because that one
authorises against course-import permissions, which is not what dismissing a
notice means. This one asks for `manage_masteriyo_settings`, the capability that
shows the notice.

An earlier revision used a `wp_ajax_*` handler, for consistency with
`ReviewNoticeAjaxHandler` and `UsageTrackingNoticeHandler`. Those are legacy, not
a precedent to extend.

### The call to action deep-links to the Migration tab

The notice links to `admin.php?page=masteriyo#/tools?migration`. The search has
to sit **inside** the hash: the admin app is a `HashRouter`, so everything after
`#` is the route, and a `migration` placed before the `#` never reaches
`location.search`. `Tools.tsx` reads it through `resolveMigrationTabIndex()`.

That effect depends on `location.search`, not only on mount. Following the notice
while already on Tools changes the hash alone, so the screen stays mounted and a
mount-only effect would never open the tab.

## Consequences

- A free-product user with a competing LMS installed gets the Migration Tool
  switched on and pointed at, which is the conversion path the issue is about.
- The whole feature lives under `addons/migration-tool/`, outside `pro/`, so it
  is free-safe by construction. No PHPStan guard is needed.
- The addon and its notice appear on the admin page load **after** the one that
  detected the platform. Nothing claims otherwise in between.
- Detection runs on every `admin_init` for a user with the capability. Once the
  slugs are recorded it is one `get_option()`, five `is_plugin_active()` reads
  and an `in_array()`.
- The Tools tab index is positional and conditional on `isUserAdmin`, so
  `MIGRATION_TAB_INDEX` has to track the JSX by hand. Anything inserted before
  Migration in `Tools.tsx` moves it. The `?shortcodes` and `?logs` links in the
  same file still set unguarded indexes and remain able to select a tab a
  non-admin does not have.
- An admin who deactivates the addon keeps it deactivated. A site that installs
  Tutor LMS *after* Masteriyo is still caught, because detection runs per
  request rather than at plugin activation.
