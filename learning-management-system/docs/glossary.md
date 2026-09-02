# Glossary

Terms that carry a specific meaning in this codebase, where the everyday reading
of the word is not enough to work with the code.

## Addon

A self-contained feature module discovered under a root returned by
`Addons::get_addon_roots()`. Core contributes `addons/`; pro appends
`pro/addons/` from its own bootstrap. Every addon has a `main.php` that declares
its metadata in a header comment and returns early unless the addon is active.

An addon being **present on disk** and an addon being **active** are different
things. `load_all()` requires every `main.php` it finds on every request; the
early return is what keeps an inactive addon from doing anything.

One addon adds a hook **above** that early return: the Migration Tool registers
an `admin_init` callback there, because an inactive addon is the one that has to
detect a competing LMS. Only the registration is early. The callback itself runs
later, where an authenticated user exists to authorise switching the addon on,
and the providers load on the next request. It is the exception, not the
pattern — see [ADR 0001](adr/0001-auto-activate-migration-tool.md).

## Active addon

An addon whose slug is a key of the `masteriyo_active_addons` option. Read with
`Addons::is_active( $slug )`, written with `set_active()` / `set_inactive()`.

Activation is a stored option, not a file operation. It is unrelated to whether
WordPress considers the *plugin* active.

## Source LMS

A competing LMS plugin that the Migration Tool can read data out of — Tutor LMS,
LearnDash, LearnPress, LifterLMS, MasterStudy. Always the origin, never the
destination; Masteriyo is the destination in every migration.

## Migrator

The adapter that knows how to read one source LMS, implementing
`MigratorInterface`. Identified by a **migrator slug** (`'tutor'`), which is
distinct from the source plugin's basename (`'tutor/tutor.php'`) returned by
`get_plugin_file()`. Migrators are registered into `MigratorRegistry` by the
addon's service provider, but their classes are autoloaded regardless of whether
the addon is active.

## Auto-activation

Switching the Migration Tool addon on without the user asking, because a source
LMS was detected on the site. Distinct from ordinary activation through the
add-ons screen: auto-activation happens at most once per source LMS per site,
and records itself in the one-shot marker.

See [ADR 0001](adr/0001-auto-activate-migration-tool.md).

## One-shot marker

The `masteriyo_migration_tool_auto_activated` option: a list of migrator slugs
already auto-activated on this site. It exists so that an admin who deactivates
an auto-activated addon is obeyed rather than overridden on the next page load.

Never cleared. It records what we *did*, not what is currently true.

## `masteriyo_admin_notices`

The action every Masteriyo admin notice must hook, instead of WordPress's
`admin_notices`. Masteriyo screens call `remove_all_actions( 'admin_notices' )`
to suppress third-party noise, then fire this action, so a notice on the core
hook is invisible on exactly the screens where it matters most.

## Free-safe

A file or reference that cannot break the free product. Pro-ness is path
encoded: anything under a `pro/` path segment is excluded from the free build,
so a new file under `pro/` is free-safe by construction, and a reference from
outside `pro/` to a pro symbol is free-safe only when a guard covers it.

See `CLAUDE.md` → Free↔Pro Separation.
