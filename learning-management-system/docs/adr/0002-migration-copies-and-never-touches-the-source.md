# 2. The migration copies, and never touches the source LMS

Date: 2026-10-05
Status: Accepted
Issue: [#1498](https://github.com/Codeinwp/learning-management-system-pro/issues/1498)

## Context

Every migrator in `addons/migration-tool/` converted and removed source data in one
irreversible step. Courses, lessons, quizzes, orders and most questions were renamed
in place: the Masteriyo record was the source post with its type flipped. Enrollments,
progress rows, quiz attempts, reviews and wishlists were inserted as new Masteriyo
rows and the source row was then deleted, in several places on skip and failure
paths as well. Users lost their source roles. A support escalation ended in a refund
after a customer ran what they believed was a copy and found their Tutor LMS courses
and enrollments gone mid-verification.

Facts that constrained the design:

- One job (`Jobs/MigrationProcessJob.php`) runs every migrator the same way: per
  step, fetch a batch of source IDs, convert each item inside a savepoint, advance.
  Batch queries were "self-cleaning", they ignored the cursor because converted rows
  vanished from the source table. Nothing linked a Masteriyo record back to its
  source except the shared post ID.
- The conversion code reads source meta through the post ID it is given, so a copy
  of the source row that carries the same meta can be converted by the same code.
- Four of five migrators already insert sections as new posts, and three insert
  questions as new posts: copying was already half the design.
- Source plugins share records with Masteriyo in places: WooCommerce products, EDD
  downloads, WordPress users, and LifterLMS's taxonomies, which have the same names
  as Masteriyo's.

## Decision

### A migration is a copy

Each source row is cloned and the clone is converted. `PostCloner::clone_post()`
copies the posts row, its postmeta and term relationships into a new post with
direct SQL, so the source plugin's save hooks never fire for a post it still owns,
and the migrator's existing conversion runs on the clone's ID. Comments go through
`PostCloner::clone_comment()`. Rows that were already new inserts stay new inserts.
Nothing renames, deletes, retypes or strips meta from a source row, and no source
role or capability is removed; Masteriyo roles are added beside them.

Writes onto shared third-party records are few and named: the course copy is linked
from the WooCommerce product, the WooCommerce order item and the EDD download through
`_masteriyo_course_id` (the download also gets `_is_masteriyo_course`), so Masteriyo's
commerce integrations find it, but the product keeps its type while the source plugin
still sells it; and a copied user gains the Masteriyo role, with the WordPress
description and website set from the source profile's bio and website when it has
them, which replaces whatever those two fields held.

### One map for every copy

`masteriyo_migration_map` (`MigrationMap`) records migrator slug, entity kind, source
ID and target ID for every copied row, with a unique key on the source side. Post
IDs are global across post types, so one entity kind (`post`) covers courses,
sections, lessons and the rest; comments and users have their own; rows from a
source plugin's own tables use the step name, and so does a post that becomes a
different kind of record (a Tutor enrollment post that becomes a user-items row, a
review post that becomes a comment), so a lookup by target ID under `post` only
ever meets posts. A target of 0 is a deliberate skip, so an unusable row is not
fetched again. A question shared between quizzes is the one source with several
copies, one per quiz copy, each found under its quiz by a marker meta. LearnDash
keeps the first copy in the map and marks copies with `_ld_question_id`; LearnPress
records every copy, so the map keeps the latest, and marks them with
`_lp_question_id`.

The map does three jobs. Dependent steps resolve the copied course, lesson or quiz a
source row points at (`require_target()` throws when it has no copy, which fails
the item rather than enrolling into nothing). Batch queries exclude mapped rows
(`unmapped_sql()`), so a second run copies only what is new and a failed item, which
nothing maps, is retried. And the counts a session starts with use the same
exclusion, so progress reflects what remains.

The table is a dedicated one rather than postmeta because enrollments, attempts and
comments are not posts, and because the batch queries need one `NOT IN` against it.
It is created on demand (`ensure_table()`) at session start, not by a core schema
migration, since only sites that migrate need it.

### Every step pages by ID

With nothing vanishing from the source, "self-cleaning" steps would loop forever
within a run. Every `get_source_ids()` now pages with `id > cursor` and the map
exclusion. Steps whose source is per-user state that grows, Tutor's lesson
completions and wishlists, re-read every run without the map: their copy is
idempotent through existing dedupe against Masteriyo tables, and that is how a
student's new completions reach the copy on the next run.

The progress recompute each migrator runs after its progress step is scoped to
the courses in the map. Before, it rewrote every course-progress row on the site
and stamped the completion date with the run's; with runs repeating, a course the
site made natively must keep its own dates.

### No cutover step, only a deactivation

The issue proposed a second, explicit cutover that converts or deletes the source.
With copies in place there is nothing left to convert, and deleting another plugin's
data is the one action that cannot be undone. The tool therefore stops at the copy.
When a run completes, the Migration tab says the source keeps its content, names the
writes it received (course links on products, downloads and order items; the
Masteriyo role, description and website on copied users), asks the user to check the
copied courses, and offers one button: deactivate the source
plugin (`POST masteriyo/v1/migrations/deactivate-source`, behind `activate_plugins`).
Removing the source data stays with that plugin's own uninstall, which has its own
confirmation.

### Tested against stubs

The WordPress-backed suite cannot load the source plugins. `tests/bootstrap-wp.php`
carries a `tutor_utils()` stub; each migrator's copy test registers the source post
types and roles, creates the source tables it needs (temporary under the test
library) and asserts that a snapshot of every source post is identical after the
copy, that a second run finds nothing, and that dependent rows land on the copies.

## Consequences

- A site can run the copy, test the Masteriyo side while the source LMS stays live,
  run it again to pick up new enrollments or progress, and switch the old plugin off
  when satisfied. Nothing in the source is lost by running the tool.
- A rerun copies what the batch queries find unmapped: new courses, users,
  enrollments, progress, orders and (Tutor, MasterStudy) assignments. A course is
  copied once, curriculum included, so a lesson, quiz or Q&A reply added to a
  course after its copy is not picked up; the UI says so. A copied course the site
  deleted is unmapped again and recopied.
- A LearnDash, LearnPress or MasterStudy step shared by several courses has one
  copy, kept by the course that copied it first; later courses skip it with a logged
  warning. A copy whose course the site deleted follows the course's new copy.
- Copied courses keep their slugs; the source course under the source plugin's
  permalink base and the copy under Masteriyo's coexist until deactivation.
- The source and the copy both react to a purchase of a shared WooCommerce or EDD
  product during the overlap, since both carry the product link. That mirrors the
  enrollment into the copy, which is what a verification window needs.
- Source taxonomy relationships ride along on the copy and go stale once the source
  plugin is deactivated. They are harmless to Masteriyo, which reads its own
  taxonomies.
- Users keep source roles until the source plugin is deactivated, when WordPress
  stops recognising them.
- The map grows by one row per copied record and is never pruned; a migration is a
  one-off, and the rows are what make a re-run and a retry correct.
