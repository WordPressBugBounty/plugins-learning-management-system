#### [Version 3.4.0](https://github.com/Codeinwp/learning-management-system-pro/compare/3.3.3...3.4.0) (2026-09-02)

- Feature - Added a free Enrollments screen with manual and bulk enrollment from email lists, CSV files, and existing students.
- Feature - Added an Immersive course layout with a full-width course story and sticky enroll card, plus redesigned layout pickers with hover previews.
- Feature - Added guided course setup with access choices, a payment setup panel for paid courses, and optional AI curriculum drafts.
- Feature - Added AI feedback drafts for quiz and assignment answers before instructors save grades. [PRO]
- Feature - Added a one-click Login | Logout link for classic and block theme navigation.
- Feature - Added dashboard-enabled Stripe payment methods including Klarna, iDEAL, and SEPA Direct Debit, with SEPA support for recurring payments.
- Feature - Added group enrollment to the Group Courses add-on.
- Feature - Added bulk editing for lesson options and drip date schedules.
- Feature - Added tools to export and remove learner data and record consent.
- Feature - Added separate editor settings for lessons and course overviews.
- Feature - Added controls that hide commerce menus on sites that do not sell courses.
- Feature - Added automatic Migration Tool activation and a notice when another LMS is detected.
- Feature - Added H5P installation and activation from the H5P editor popup.
- Feature - Added an Edit Course admin-bar link, direct Settings buttons for enabled addons, and a Create new certificate link in certificate selectors.
- Feature - New sites now start with three draft sample courses, no unused instructor pages, and instructor registration and marketplace pages turned off.
- Enhancement - Reworked the account area with separate instructor and student views, an onboarding screen for students with no courses, and My Courses cards with certificate downloads and coming-soon availability.
- Enhancement - Improved the course curriculum and quiz builders with faster editing, search, and bulk uploads.
- Enhancement - Redesigned checkout with clear sections, payment choices, a responsive order summary, and automatic learner account creation.
- Enhancement - Improved lesson previews with direct editor controls and a free upgrade prompt.
- Enhancement - Improved AI course generation and quiz questions with current models, lesson-based questions, and an editable review.
- Enhancement - Streamlined onboarding with a single course-access question, currency suggestions from your site address, and an optional Courses page.
- Enhancement - Improved the Status page with clear WordPress and server information cards.
- Enhancement - Simplified course display settings, linked them to global course settings, and removed the per-course Reports page.
- Enhancement - Improved compatibility of Account and Checkout pages with Neve and eLearning themes.
- Fix - Fixed order updates (including WooCommerce and PMPro) resetting or reassigning students enrollment start dates and restoring expired course access.
- Fix - Fixed native Stripe checkout: paid orders left pending, missing Apple Pay and Google Pay, decimal prices charged without cents, migration errors, unclear webhook notices, and Stripe Connect opening in the same tab.
- Fix - Fixed license plan names showing as Unknown and activation errors or delays when the license server is unavailable. [PRO]
- Fix - Enhanced security: unauthorized access to enrollment details, course progress, student reports, quiz answers, system status, and the Stripe connection; unsafe course imports, embedded audio, and course custom fields; CSV formula injection and imports accepting unapproved roles; instructors creating instructor accounts with their own automation keys.
- Fix - Enhanced security for Gradebook results, assignment answers, Zoom credentials, public profile email addresses, and OTP resend user enumeration. [PRO]
- Fix - Fixed coupons: Apply Coupon creating full-price orders, automatic coupons replacing an entered code, and uses counted before payment. [PRO]
- Fix - Fixed course bundles: missing original and sale prices in foreign currency zones, multiple currency alignment, and refunds removing access to courses bought separately. [PRO]
- Fix - Fixed Gradebook: overlapping sample grade ranges, stale grades after course retakes, results after deleting quizzes or assignments, empty grade rows after a first graded submission, and course filters for instructors. [PRO]
- Fix - Fixed content drip letting students start quizzes or complete locked lessons before their release date. [PRO]
- Fix - Fixed EDD orders not enrolling customers in every purchased course and order changes removing access from the wrong student. [PRO]
- Fix - Fixed course access remaining after a Paid Memberships Pro membership ends. [PRO]
- Fix - Fixed H5P quiz attempts starting without course access and scores exceeding 100%. [PRO]
- Fix - Fixed assignment and Zoom content crashing the learn page and assignment video links crashing the course editor. [PRO]
- Fix - Fixed Public Profile settings failing to open and profile cards disappearing on phones. [PRO]
- Fix - Fixed Razorpay payments completing a different order. [PRO]
- Fix - Fixed missing feedback when a prerequisite blocks starting an Immersive course. [PRO]
- Fix - Fixed extra commas after the last instructor on course pages. [PRO]
- Fix - Fixed White Label branding across WordPress admin pages, notices, and the Plugins page. [PRO]
- Fix - Fixed SCORM: failed lessons completing the course and SCORM 2004 progress not recording.
- Fix - Fixed certificates: backslashes stripped on save, 0m shown for courses without duration, and empty student names in classic certificates.
- Fix - Fixed manual enrollment into unpublished courses.
- Fix - Fixed course previews recording progress for admins, not opening preview lessons, opening a missing page for drafts, and not reflecting unsaved changes.
- Fix - Fixed course groups: status changes affecting unrelated courses, the Enroll Group course list, and missing emails on removal.
- Fix - Fixed setup wizard and starter templates: translations, oversized logos, demo-server errors, wrong saved layout default, missing Skill School styles, screen-reader text in the upload modal, and payment secrets leaking into usage tracking.
- Fix - Fixed Revenue Sharing re-enabling after being turned off during setup and instructor analytics showing full sales instead of revenue-share earnings.
- Fix - Fixed the learn page: external videos not playing, PDF lessons without a download fallback, videos restarting instead of resuming, large courses timing out, guests seeing a completed course, quiz reviews submitting the attempt, and featured videos hidden in the Immersive layout.
- Fix - Fixed the account area: notifications popover size and links, stuck logout buttons, overlapping My Courses buttons, empty enrolled course lists, course exits not opening Your Courses, missing password reset success message, and search results lost on back navigation.
- Fix - Fixed emails: duplicate completion reminders, blank instructor notification recipients, and missing tax amounts in order emails.
- Fix - Fixed pricing display: tax-inclusive sale prices, the checkout tax sign, blank manual currency prices showing courses as free, and Mollie totals with tax or discounts.
- Fix - Fixed WooCommerce: errors deleting an inactive Masteriyo product beside an active one, and guest checkout blocking orders when Create User is unchecked.
- Fix - Fixed course pages: empty Reviews tabs, review forms allowing empty submissions, payment pending not shown for offline orders, duplicate lock icons, block theme header styles, and the archive price filter with no courses.
- Fix - Fixed old course end dates unexpectedly unlisting courses and deleting enrollments.
- Fix - Fixed administrators losing access after changing their own role and bulk user deletion not reassigning courses.
- Fix - Fixed instructors enrolling Google Classroom students in courses they do not own.
- Fix - Fixed course completion records for users not enrolled and student lists for authorized course managers.
- Fix - Fixed course builder: changes lost when opening an editor, Auto Calculate pass marks exceeding the quiz total, the block editor sidebar staying hidden in full screen, and parent category search breaking with no matches.
- Fix - Fixed profile photo uploads causing a critical error when image processing fails.
- Fix - Fixed the Instructor Registration page showing blank content to logged-in visitors and Student Preview not returning to the requested admin page.
- Fix - Fixed announcements submitting without a selected course and password reset links for deleted users showing a server error.
- Fix - Fixed user CSV imports for files starting with a UTF-8 BOM.
- Fix - Fixed BuddyPress errors on orders with multiple courses.
- Fix - Fixed theme color sync for published Customizer changes and child themes.
- Fix - Fixed expired sessions showing repeated cookie errors and the review notice appearing twice in the admin.
- Fix - Fixed assorted admin UI issues: menu icon alignment, color picker position, table spacing, misspelled labels, dead Debug switches, the dashboard logo link, encoded ampersands in the categories column, and a clear Attributes button in the Shortcodes tool.

<!-- Entries above this line are generated by semantic-release from conventional commits. Entries below are the imported history of the pro product (the 3.x line both products now share), hand-written before the free/pro merge. -->

= 3.3.3 - 12-08-2026 =
------------------
- Fix - Enhance security for description fields and instructor content permissions.
- Fix - Enhance security for Course Builder REST endpoint access.
- Fix - Enhance security for instructor course approval and course import.

= 3.3.2 - 17-07-2026 =
------------------
- Fix - Enhanced security for Webhooks.
- Fix - Customizer settings and Additional CSS not persisting when Masteriyo is active.
- Fix - Auto order completion not working for iDEAL payments via Stripe.
- Fix - Course Curriculum widget rendering blank on Single Course Elementor template.
- Fix - Critical error on content drip x-days for assignments. [PRO]
- Fix - Guest requests marking free course progress as completed in the database.
- Fix - Font color changes not reflected on Masteriyo course and single course pages in Bricks builder.
- Fix - Style controls having no effect on several Masteriyo elements in Bricks builder.
- Fix - Retake Course button not working on Bricks/Elementor pages.
- Fix - Gradebook results inaccessible to administrators due to permission revoke. [PRO]
- Fix - Gradebook data wiped for all students on course unenrollment or progress reset. [PRO]

= 3.3.1 - 02-07-2026 =
------------------
- Enhancement - Improve Mollie subscription billing reliability. [PRO]
- Fix - Google reCAPTCHA validation failing on account login.
- Fix - Certificate not showing when viewed from the public profile page. [PRO]
- Fix - TypeError when _elementor_data meta returns PHP array instead of JSON string.
- Fix - TranslatePress translation editor only works on first lesson. [PRO]
- Fix - Security hardening for user session handling.
- Fix - Security hardening for course review field validation.
- Fix - WooCommerce product edit link returning 404 error.
- Fix - YouTube lesson fullscreen button not showing on iPhone.
- Fix - Draft course items counted in course progress. [PRO]
- Fix - Bricks builder Masteriyo templates not rendering properly on the frontend.
- Fix - Analytics composite indexes migration query crash on shared hosting sites. [PRO]
- Fix - Certificate download link not generated in course completion email.

= 3.3.0 - 23-06-2026 =
------------------
- Feature - Certificate Builder v2.
- Feature - H5P integration addon with H5P shortcode support [Free] and H5P quiz [PRO].
- Revamp - Analytics UI/UX.
- Compatibility - PHP v8.5 compatible.
- Enhancement - Added Simple, Modern, Overlay, and Custom layout support for course archive and single course page templates.
- Enhancement - Updated Migration Tool to display real-time migration progress and support migrating LMS data to Masteriyo.
- Enhancement - Added login/logout navigation menu link panel.
- Enhancement - Overhauled Course List widget with built-in pagination, filter controls, and renamed layout options to match the Carousel widget.
- Enhancement - Added three new Elementor widgets for the Single Course page: Course Progress Bar, Course Cohort, and Course Enrollment Expiration. - [PRO]
- Enhancement - Hidden the legacy Course Archive Pagination widget from the Elementor panel since pagination is now built into the Course List widget.
- Enhancement - Improved single course page templates with editor fallback notices, Wishlist icon support, and a "Set as active template" option.
- Enhancement - Added option to hide calendar and session information tab from the account sidebar.
- Enhancement - Added global eLearning and Neve theme color sync.
- Enhancement - Added auto-navigation to error tab and focus on invalid fields in course settings on save.
- Enhancement - Added easy switching between block and classic editor with source code support and additional default blocks.
- Enhancement - Added course creation button in WooCommerce product for one-click course creation.
- Enhancement - Added link product option in individual course WooCommerce settings to directly link a Woo product.
- Enhancement - Added guest checkout support for WooCommerce.
- Enhancement - Added auto-detection of video duration in video lessons.
- Enhancement - Added customizable color options for email templates. - [PRO]
- Enhancement - Added option to require attempting all quiz questions before submission.
- Enhancement - Added course bundle support for Stripe/PayPal recurring payments. - [PRO]
- Enhancement - Added support for custom sidebar buttons on the Account page via filter hook. - [PRO]
- Enhancement - Added automated job to check and revoke expired course enrollments. - [PRO]
- Enhancement - Implemented protected material downloads with enrollment verification. - [PRO]
- Fix - Removed unnecessary API calls on backend pages.
- Fix - Resolved multiple issues with view-mode switcher, courses JS not loading on Elementor pages, WP dependency notice, and pagination display.
- Fix - Fixed Wishlist support for the Course Carousel Elementor widget.
- Fix - Fixed incorrect purchase button display for SureCart courses on course archive and single course pages.
- Fix - Fixed group pricing multiple currency conversion, formatting, and checkout calculation issues.
- Fix - Fixed session-expired overlay not showing on admin pages when switching to student preview.
- Fix - Fixed OTP email not being sent after enabling 2FA. - [PRO]
- Fix - Fixed Mollie "No suitable payment methods found" error on default checkout.
- Fix - Fixed guest redirect to login on WooCommerce addon when guest checkout is disabled.
- Fix - Fixed minor course bundle issues related to the WooCommerce addon. - [PRO]
- Fix - Enhanced security for the Mollie payment addon.
- Fix - Fixed checkout not redirecting after applying a 100% discount coupon. - [PRO]
- Fix - Fixed single course page showing wrong course for additional instructors. - [PRO]
- Fix - Fixed course content not being restricted during quiz even when the option is enabled.
- Fix - Enhanced security for the course announcement addon.
- Fix - Fixed PDF viewer overlapping off-canvas menu and failing to load on the learn page. - [PRO]
- Fix - Fixed blank block editor on pages assigned as the Masteriyo account page.

= 3.2.1 - 20-05-2026 =
------------------
- Compatibility - WordPress v7.0 compatible.
- Update - Added filters for certificate course title and completion date smart tags.
- Update - Upgraded Isolated block editor version to v2.30.0.
- Fix - Enhance security for user course progress and user role.
- Fix - Skip abilities layer below WordPress v6.9.
- Fix - Allow admin with instructor role to list all course Q&A.

= 3.2.0 - 12-05-2026 =
------------------
- Feature - Added 'View as Student' option for admins and instructors to preview the student experience.
- Feature - Exposed full CRUD abilities for Course, Section, Lesson, Quiz, Question, Enrollment, Order, User, and Settings via the WP Abilities API.
- Enhancement - Added email settings and notifications for lesson comments/replies and Q&A.
- Enhancement - Synced group status with different order statuses.
- Enhancement - Added 'Auto Redirect to Courses' option in WooCommerce integration settings.
- Enhancement - Improved UX for chat input fields by replacing text input with textarea and enhancing message formatting.
- Security - Added authorization check for Invoice PDF access.
- Security - Added capability checks to AJAX notice handlers.
- Security - Improved BuddyPress integration security.
- Fix - Enqueued public.css on course bundle and public profile pages. [PRO]
- Fix - Fixed missing Elementor CSS and body classes with block themes.
- Fix - Added masteriyo-notice-link class to admin notice links.
- Fix - Fixed review replies not appearing in user details edit review. [PRO]
- Fix - Scoped gradebook queries to current user and guarded against bulk deletion. [PRO]
- Fix - Resolved stale cache served after AJAX login on account page with LiteSpeed Cache.
- Fix - Fixed search by username or email for quiz attempts and assignments. [PRO]
- Fix - Fixed students being unable to post reviews on private courses and lessons. [PRO]
- Fix - Fixed invalid interval format sent to Mollie subscription API.
- Fix - Fixed WooCommerce order with no customer ID overwriting wcorder_id on wrong user enrollment.
- Fix - Fixed URL status param not syncing to filterParams on backend page reload.
- Fix - Fixed user being unable to reply to reviews after submitting their own review.
- Fix - Fixed quiz questions not loading for guest users on open access courses.
- Fix - Fixed incorrect redirect to first step after Stripe Connect on onboarding.
- Fix - Course sometime shows continue button and wrong URL even after completion.
- Fix - Render quiz question math (MathJax) and preserve LaTeX backslashes.

= 3.1.8 - 04-05-2026 =
------------------
- Enhancement - Load Masteriyo public CSS only on Masteriyo-related content pages.
- Fix - Enhance security.
- Fix - Log email delivery failures silently through the Masteriyo logger.
- Fix - Courses shortcode rendering private courses to non-enrolled users.
- Fix - Course Bundles shortcode rendering private courses to non-enrolled users. [PRO]
- Fix - Exclude Learn and Account pages from cache plugins.
- Fix - Show contextual error notices on Learn page redirects.
- Fix - Block paid course access for logged-in users without a student role.
- Fix - Randomize Questions setting not applied on Learn page quiz.
- Fix - Course featured image missing in Course Bundle editor. [PRO]
- Fix - Group enrollment course selector not listing courses with tiered pricing. [PRO]

= 3.1.7 - 06-04-2026 =
------------------
- Enhancement – Made the Stripe webhook secret mandatory to improve security.
- Fix – Enhance security.
- Fix – Resolved RTL issues in the quiz timer and Masteriyo player.

= 3.1.6 - 25-03-2026 =
------------------
- Enhancement - Improve webhook delivery reliability, expand lesson completion tracking coverage, and add observability.
- Enhancement - Extend Two-Factor Authentication OTP support to WordPress admin login popup. [PRO]
- Enhancement - Add PDF download enable/disable toggle option for PDF lessons. [PRO]
- Fix - Enhance security.
- Fix - Fix log file download and incorrect filename issues in the logs tool.
- Fix - Make the course name a clickable link on the account page.
- Fix - Exclude deleted users from course analytics student count. [PRO]
- Fix - Prevent duplicate contact errors in HubSpot integration during user registration. [PRO]

= 3.1.5 - 11-03-2026 =
------------------
- Fix - Security issue related to Stripe addon.
- Fix - Course archive page default layout Elementor template not importing.

= 3.1.4 - 26-02-2026 =
------------------
- Enhancement - Added MathJax support in question descriptions.
- Enhancement - Added option for users to choose whether to start with a starter template.
- Compatibility - Added Mollie support for Klarna.
- Refactor - Improved handling of course “Coming Soon” metadata.
- Update - Added filters for the Start Course button target attribute and Learn page logo URL.
- Update - Added filters for Facebook and Google redirect URLs. [PRO]
- Update - Made profile info labels translation-ready.
- Fix - Resolved issue where quiz attempt user filter was not working.
- Fix - Fixed course review popup showing even when course reviews are disabled.
- Fix - Fixed issue where assignment due date could not be left empty. [PRO]
- Fix - HubSpot integration now fetches all lists using pagination. [PRO]
- Fix - Resolved Add to Cart AJAX not triggering on course category archive pages.
- Fix - Fixed Remember Me login not persisting user sessions.
- Fix - Resolved RTL layout issue on Account page content.
- Fix - Ensured taxonomies are cloned during post duplication. [PRO]

= 3.1.3 - 08-01-2026 =
------------------
- Enhancement - Admin role support for Two-Factor Authentication OTP email. [PRO]
- Enhancement - Added filter to support custom redirect URL after user email verification.
- Update - Support custom admin URLs in Google Meet and Google Classroom setting.
- Fix - Question description blank issue when updating quiz.
- Fix - Account page minor UI issue with theme css.
- Fix - Question and Answer reply of a user is not visible in dark mode.
- Fix - Disable individual course review option when global review setting is disabled.
- Fix - Hamburger menu appearing while changing routes and sidebar height fixed to full in the account page.
- Fix - Course setting tab position for FAQ and Lemon Squeezy. [PRO]
- Fix - Restore import sample grade button. [PRO]

= 3.1.2 - 30-12-2025 =
------------------
- Enhancement - Improved Account page UI/UX.
- Fix - Resolved string translation issues.
- Fix - Prevented the Sell to Groups toggle from resetting after add-on activation.
- Fix - Prevented a fatal error when creating reviews with an invalid comment ID.
- Fix - Cast Stripe payment amount to integer to prevent floating-point error.
- Fix - Improved group buy button compatibility with Course Coming Soon mode.
- Fix - Resolved password strength string translation issue.
- Fix - Corrected group pricing logic based on available course seats.
- Fix - Hide the group buy button when the course enrollment limit is reached.
- Fix - Fixed spacing issue on the Forgot Password section of the sign-in page.
- Fix - Resolved issues with adding and removing additional instructors in course settings. [PRO]
- Fix - Fixed multiple-instructor backend filter issues across courses, quiz attempts, and assignments.
- Fix - Added a webhook menu for instructors in the backend.
- Fix - Stripe subscription fails for users created during checkout. [PRO]

= 3.1.1 - 19-12-2025 =
------------------
- Fix - Courses page filter issue.
- Fix - RTL issue in responsive mode.
- Fix - Social share icon showing even when social share is disabled. [PRO]
- Fix - Course update issue when selecting Bunny.net video type.
- Fix - Cohort course date picker showing current date as placeholder. [PRO]
- Fix - Checkout UI-related issue.
- Fix - Learn page sidebar lock icon UI issue.
- Fix - Course review affecting single course layout 1 UI.
- Update - Added filter `masteriyo_is_account_page` to check if the current page is a Masteriyo account page.

= 3.1.0 - 16-12-2025 =
------------------
- Feature - Cohort-based course feature added. [PRO]
- Feature - Multi-group pricing options added. [PRO]
- Update - Improved compatibility with TranslatePress plugin.
- Update - RTL layout update across frontend pages.
- Update - Upgrade PHP League Container from v3.4 to v4.2.
- Compatibility - WordPress v6.9 compatible.
- Refactor - Migrated selected addons to core (BunnyNet, Event Calendar [PRO], Two-Factor Authentication [PRO], Social Share [PRO], Password Strength, Course Coming Soon).
- Enhancement - 3 Layout select option added in courses blocks.
- Enhancement - Overall blocks setting improvement.
- Enhancement - Added show/hide component options for the single course page.
- Enhancement - Added Order Summary shortcode ([masteriyo_order_summary]) for post-checkout pages.
- Enhancement - Learn page UI/UX updated.
- Enhancement - Single course review section UI updated.
- Enhancement - Learn page comment section UI updated.
- Enhancement - Added review courses table in student report. [PRO]
- Enhancement - Match the Following question type selection UI updated. [PRO]
- Enhancement - Sample courses updated to match current demo courses.
- Enhancement - Single course settings rearranged with new Schedule & Access tab and related options moved accordingly.
- Enhancement - Group pricing single course UI updated.
- Fix - Aspect ratio issue in video lesson in safari browser.
- Fix - Accordion section expansion/collapse issue on navigation in learn page.
- Fix - Update enrollment status logic to keep enrollments active for 'publish' and 'private' courses. [PRO]
- Fix - Course list page multiple column selection UI issue.
- Fix - Links not clickable in PDF lesson. [PRO]
- Fix - Undefined method issue get_display_name().
- Fix - Fatal error when Google event deleted in Google calender.
- Fix - Caching issues with Redis in UserCourseRepository.
- Fix - File parsing error.
- Fix - 404 on Learn page when navigating from course curriculum.
- Fix - PHP 8.4 compatibility issues.

= 3.0.4 - 04-11-2025 =
------------------
- Fix - Security related issue.
- Fix - Fatal error in CourseEnrollButtonWidget during Elementor editing.
- Fix - Courses filters and sorting disappear issue when search or sorting is disabled.
- Fix - Masteriyo player full screen issue in small devices, audio boost issue and video stopping randomly issue.
- Fix - Course contents missing while exporting.
- Fix - Course retake popup modal not opening on single course modern layout.
- Fix - Single course and courses minor UI issues.
- Fix - Single course bundle page UI and tab issues. [PRO]

= 3.0.3 - 16-10-2025 =
------------------
- Fix - Curriculum tab showing only for user who has course progress.
- Fix - Review visibility control condition sometime not showing review tab.

= 3.0.2 - 15-10-2025 =
------------------
- Enhancement - Added full screen mode in PDF lesson. [PRO]
- Enhancement - Color palette UI updated and button hover color added.
- Enhancement - Modern and Overlay courses layout UI and component revamp.
- Enhancement - Single course and courses page responsiveness.
- Compatibility - Global color option in Masteriyo styling option for eLearning theme.
- Fix - "Start Course/Continue" button disappears after starting the course in the modern layout.
- Fix - Notice related issue.
- Fix - Required missing pages box not shown initially on home page.
- Fix - Course filter not working properly in the responsive view.
- Fix - Payment settings redirection issue fixed in the home page.
- Fix - Starter template typography and color not being set after importing.
- Fix - Incorrect license expiration information. [PRO]
- Fix - Retake course button not showing issue.
- Fix - Course end date not showing in modern layout.
- Fix - Lock icon missing for course password access mode.
- Fix - Group buy button issue in single course modern layout.
- Fix - Featured video popup modal scrolling issue. [PRO]
- Fix - Minor UI related issue on single course and courses page.
- Fix - Quiz setting pass type issue and auto calculate button color. [PRO]
- Fix - Unlink WooCommerce product from duplicated courses. [PRO]
- Fix - Course setting data removed issue when updated in course analytics page. [PRO]
- Fix - Course completion reminder emails goes even after course expiration. [PRO]
- Fix - Extra box appear even though there is nothing on header in dashboard.
- Fix - Stripe payment description information changed to course name.
- Fix - Question/Answer tabs disappear when marking all questions as spam or moving to trash.

= 3.0.1 - 26-09-2025 =
------------------
- Fix - Courses shortcode layout UI issue.
- Fix - Header UI issue in Masteriyo dashboard.
- Fix - Empty contents UI in backend pages.
- Fix - Manual groups enrollment leader change is not reflected in groups page. [PRO]
- Fix - Deleted manual group enrollment appears in manual enrollment page. [PRO]
- Fix - Stripe subscription issue on checkout. [PRO]
- Fix - Group pricing enable issue even when course is free.
- Fix - Pages not being set in the setting for first installation.
- Fix - Onboarding user selected data being overwritten when importing starter templates.
- Fix - Stripe connect not working initially when activated from payment setting.
- Fix - Starter templates actual typography not showing issue.
- Fix - Home page UI related issue.
- Fix - Single course and courses page minor UI issues.
- Fix - UI issues in Elementor course component widgets.
- Fix - Course preview not clickable issue. [PRO]
- Fix - Curriculum content link leads to 404 not found page.

= 3.0.0 - 23-09-2025 =
------------------
- Feature - Manual group enrollment option added. [PRO]
- Feature - Support for 4 additional AI-generated question types added (Text Answer, Match the Following, Sortable & Fill in the Blanks). [PRO]
- Feature - Added starter template.
- Feature - Added a new minimal single layout.
- Feature - Added 8 new starter templates.
- Refactor - Eliminated unnecessary PayPal configuration.
- Refactor - Stripe connect platform.
- Update - As single course and courses components has been updated, some of the builder [Elementor/Divi and others] elements or widgets might need to be updated.
- Update - Renamed Courses layout to Default, Modern, and Overlay; renamed Single course layout to Default and Modern.
- Update - Added external video URL option in Free version.
- Update - Added course completion reminder email, email from name, and address option in Free version.
- Update - Added Primary color for learn page and button styling option in Free version.
- Enhancement - Single course and courses page component UI revamp.
- Enhancement - Course prerequisites single course page UI update.
- Enhancement - Course enrollment expiration single course page UI update.
- Enhancement - Course coming soon single course page UI update.
- Enhancement - Onboarding page UI/UX revamp.
- Enhancement - Added Home page, which shows incomplete setup parts.
- Enhancement - New layout option for courses shortcode ([masteriyo_courses layout="default/layout1/layout2"]) added.
- Enhancement - Added email notifications for course Q&A.
- Enhancement - Added reply option for admin & instructors to lessons comment and course review from backend. [PRO]
- Enhancement - Added maximum attempts limit option for instructor applications.
- Enhancement - Added license details to the license page and added notice for no license key. [PRO]
- Enhancement - Bypass drip content in preview mode for admins and course instructors.
- Enhancement - Plugin rollback option. [PRO]
- Enhancement - Action buttons label according to specific content in builder header.
- Enhancement - Empty content info and no result found UI updated.
- Enhancement - Overall UI/UX update in backend pages.
- Fix - Show search only if categories exist in course edit page.
- Fix - Masteriyo conflict with Pressidium Cookie Consent plugin.
- Fix - Learn page emoji size inconsistent issue.
- Fix - Prevented Save CSS Block API call when Masteriyo block is absent on pages.
- Fix - Prevent user_id being set to 0 in user course enrollments.
- Fix - Download material addon issue.
- Fix - Save settings not working when WooCommerce addon activated.
- Fix - Fixed issue where video lesson couldn’t be saved without adding a video (lesson_type meta added).
- Fix - Woocommerce name null issue.

= 2.30.2 - 22-08-2025 =
------------------
- Fix - Account page user's courses listing in dashboard.

= 2.30.1 - 21-08-2025 =
------------------
- Enhancement - Show titles of timestamped notes. [PRO]
- Enhancement - Choose Builder added in Stater Templates import.
- Fix - Draft lesson display bug.  [PRO]
- Fix - Vimeo unlisted video not working.
- Fix - "Add to Cart" button shows only if course not purchased.  [PRO]
- Fix - Correct UserCourseRepository query.
- Fix - Right-click disabled on lesson links even with Content Protection off.  [PRO]
- Fix - VAT issue on checkout when only country is selected.  [PRO]
- Fix - Menu conflict with WooCommerce.
- Fix - Price not showing with WooCommerce.
- Fix - Enroll button design issue with Elementor.

= 2.30.0 - 11-08-2025 =
------------------
- Update - Added Stripe Connect.
- Update - Integrate ThemeIsle SDK for deactivation feedback. [PRO]
- Update - Introduce common header for multiple pages.
- Update - Group Course addon rename to Groups.
- Update - Integrate Formbricks for survey collection.
- Refactor - Groups are now created after group purchase by user, manual group creation has been removed, group purchase is now visible to non-logged in users.
- Refactor - Masteriyo blocks and additional single course blocks added.
- Refactor - Masteriyo sub-menu cleaned up, renamed, and reorganized.
- Enhancement - Addons Page UI/UX revamp.
- Enhancement - Added automatic email reminders for live sessions. [PRO]
- Enhancement - Added starter templates.
- Enhancement - Added link for notifications.
- Enhancement - All payment related settings move to payment methods tab.
- Enhancement - Display password strength on reset password page.
- Enhancement - Improved single course page & archive page UI/UX for enrolled users.
- Enhancement - Multi-currency support for third-party page builders.
- Enhancement - Quiz builder UI/UX revamp.
- Enhancement - Restrict multiple reviews for a course by a single user.
- Enhancement - Show alert in backend pages for unsaved changes.
- Enhancement - Translation support added for email's link placeholder.
- Fix - Adding video duration manually does not save correct values.
- Fix - Course block in backend page not responsive on mobile preview.
- Fix - Course filter & sorting not working with Divi.
- Fix - Course list widget search issue and Zakra theme design issue in Elementor.
- Fix - Correct tax calculation logic on checkout page. [PRO]
- Fix - Duplicate certificates display on course completion.
- Fix - Error displayed to students on reply submission in course Q&A.
- Fix - Expiration date not displayed for courses expiring in 1 day.
- Fix - Export tool allows instructors to export courses created by others.
- Fix - Fatal error in courses page due to PMPRO Integration and RCP Integration. [PRO]
- Fix - Logo removed in Masteriyo learn page settings when deleted from media library.
- Fix - Manual enrollment updates not reflected on manual enrollment page. [PRO]
- Fix - Multiple instructor assigned course gets removed after update by another instructor. [PRO]
- Fix - PHP deprecated warnings in script styles and AddonsController.
- Fix - Question reply color-mode issue, dashboard redirect issue & custom field renderer crash issue.
- Fix - Removing a media item from one lesson also removes it from other lessons.
- Fix - Sample course does not trigger completion notification and leads to 404 error on continue.
- Fix - Send enrollment notification emails on CSV import for manually added students. [PRO]
- Fix - Setup wizard UI issue.
- Fix - Tax resets to zero when clicking "Buy Now" again after country selection. [PRO]
- Fix - Tooltip text for “Public Profile” addon contains wrong text. [PRO]
- Fix - User registration redirect to public profile issue when username already exists. [PRO]
- Fix - Elementor create new template link doesn't work and added layout skeleton.
- Fix - Randomize answer not working.

= 2.21.3 - 17-07-2025 =
------------------
- Fix - Security related issues.

= 2.21.2 - 16-07-2025 =
------------------
- Fix - Builder price now persists correctly after updates.
- Fix - Parent category is now properly selected when assigning categories.

= 2.21.1 - 30-06-2025 =
------------------
- Fix - Google Meet error on the Account page.
- Fix - Assignment filter not persisting on page change with user data.
- Fix - Misaligned “Note” cell in the order confirmation email.
- Fix - Misplaced one-time pricing setting in the course creation form.

= 2.21.0 - 24-06-2025 =
------------------
- Feature - Added support for Private Courses. [PRO]
- Feature - Introduced Tax option. [PRO]
- Feature - Export individual course as a PDF files. [PRO]
- Refactor - Improved internal structure of Masteriyo blocks.
- Update - Minor UI revamp of Global Settings for a more consistent experience.
- Update - Dashboard menu updated to About and contents in about page updated.
- Enhancement - Enhanced Course Builder UI/UX for improved usability.
- Enhancement - Added support to reveal quiz answers across multiple attempts.
- Enhancement - Enrolled users are now added to Google Meet calendar, with event sync to Google Calendar.
- Enhancement - Google Meet event sync with event calendar. [PRO]
- Enhancement - Added addon plan tags and filtering options for easier navigation.
- Enhancement - Manual course enrollment now sends email notifications to students. [PRO]
- Fix - Resolved issue where Multiple Instructor Addon didn’t work in draft courses. [PRO]
- Fix - Issue causing automatic user course deletion after purchase when enrollment expiration was set.
- Fix - Facebook login not working. [PRO]
- Fix - Corrected 'Expand All' label display on initial curriculum load.
- Fix - Added handler for accurate social login path detection. [PRO]
- Fix - Resolved ArgumentCountError in masteriyo_maybe_define_constant() with W3 Total Cache compatibility.
- Fix - General performance improvements.
- Fix - Ensured all sections render properly on the student reports page. [PRO]
- Fix - Builder section disable for Google Classroom course.


= 2.20.1 - 12-06-2025 =
------------------
- Refactor - Improved code structure for user creation in social login flow.
- Fix - Critical OAuth Role Escalation Vulnerability in Social Login.
- Fix - Pro showcase incorrectly displayed for premium users.

= 2.20.0 - 02-06-2025 =
------------------
- Feature - Google reCAPTCHA & Password strength addon now available in Free.
- Enhancement - Integrated Themeisle SDK for rollback updates and deactivation feedback.
- Update - Feature and addon availability now varies by plan. [PRO]
- Refactor - Codebase updated to support plan-based feature access. [PRO]
- Fix - Invalid invoice PDF download issue in Edit Order.
- Fix - Hide quiz description in student quiz scoreboard.
- Fix - VdoCipher embed issue caused by encrypted-media restriction on iframe.
- Fix - Enforced email verification before account access after checkout.
- Fix - Translation issue in thankyou page.
- Fix - Password strength message issue.

= 2.19.0 - 20-05-2025 =
------------------
- Feature - Webhook actions. [PRO]
- Feature - Custom fields in course builder.
- Enhancement - Masteriyo onboarding UI/UX revamp.
- Enhancement - Instructor list page UI revamp.
- Enhancement - Google Meet tab option in the account page.
- Enhancement - Dynamic Minimum Payout Amount for Instructors.
- Enhancement - Added refresh template button to refetch certificate templates.
- Enhancement - Added additional certificate templates.
- Refactor - Questions per page max limit is set to 999.
- Refactor - Course archive filter UI revamp.
- Refactor - Enqueue style and scripts for page speed.
- Refactor - Overall new icons updated in backend and frontend side.
- Fix - Other Users order invoice PDF downloadable. [PRO]
- Fix - i.map is not a function.
- Fix - Course review reply undefined get_avatar_url.
- Fix - Show review for enrolled users only not working.
- Fix - Revenue sharing minimum payout amount issue.
- Fix - Account Page width in WP default theme.
- Fix - Learnpress conflicting our backend pages.
- Fix - Compatibility issue with YITH gift cards.
- Fix - Course bundle and webhook issue in checkout. [PRO]
- Fix - Course start email not sending issue to admin and instructor.

= 2.18.3 - 01-05-2025 =
------------------
- Feature - Coupons can now be applied to specific courses, bundles, and course categories. [PRO]
- Feature - Added support for automatic and stackable coupons. [PRO]
- Compatibility - PHP 8.4 compatible.
- Enhancement - Send login info to the user when created via admin.
- Enhancement - Custom font support in certificate blocks. [PRO]
- Refactor - Implement transient cache in user course repository.
- Fix - Extra questions appearing after importing quiz.
- Fix - User role undefined issue in account page.
- Fix - useMasteriyoPlayer scope issue.
- Fix - Cannot declare class WpOrg\Requests\Requests. [PRO]
- Fix - PDF lesson flickering issue. [PRO]
- Fix - Correct password handling in CSV user enrolment. [PRO]
- Fix - Reduce unnecessary activity log requests by skipping tracking for non-logged-in users. [PRO]
- Fix - Fill in the blanks answers prefill issue. [PRO]
- Fix - Order invoice fatal error when certificate addon is disable.

= 2.18.2 - 18-04-2025 =
------------------
- Fix - Security related issues.
- Fix - Import users password does not match while login.
- Fix - Update authentication error messages and API references in RestAPIAuth and RestAuthController.
- Fix - Three elements with same id warning in course settings.
- Fix - Review star reset issue on IOS devices.

= 2.18.1 - 02-04-2025 =
------------------
- Enhancement - Email Translations support using WPML.
- Enhancement - Added group pricing multiple currencies option.
- Enhancement - Instructor auto approval when added by admin.
- Enhancement - Added duplicate question option in Question Bank.
- Enhancement - Order purchase email send only after payment is successful in case of payment gateway excluding offline payment.
- Refactor - REST API Success/Error Handling.
- Refactor - Masteriyo player.
- Fix - Certificate font compatibility issue for different OS.
- Fix - Public profile pagination issue. [PRO]
- Fix - Improve contact existence check and update/create logic in Brevo integration.
- Fix - Deprecated issue for course pagination in PHP 8.1 or above.
- Fix - Global setting svg alignItem console warning.
- Fix - Timer not updating when switching between contents in learn page.
- Fix - Questions not showing in quiz builder if exceeds 100.
- Fix - Invoice download issue from account page. [PRO]
- Fix - Runtime error in Gamipress setting. [PRO]
- Fix - Pass user ID instead of user object to allow_password_reset filter.
- Fix - Check if Masteriyo account shortcode is exists or not in the account page.
- Fix - Mollie payment issue for course bundle. [PRO]
- Fix - Fatal error call to a member function get_page_permastruct().
- Fix - Resolve conflict between Masteriyo checkout and WooCommerce checkout.

= 2.18.0 - 12-03-2025 =
------------------
- Feature - Question bank.
- Feature - Custom fonts upload option for certificate. [PRO]
- Refactor - Show/hide components of courses page.
- Refactor - Used Mailchimp REST API instead of SDK.
- Enhancement - Featured video support on related course and course bundle. [PRO]
- Enhancement - Added option to reflect show/hide component in single course page.
- Enhancement - Added a scroll bar to the single course curriculum UI when the section's exceeds 17 contents.
- Enhancement - Add functionality to mark course or content complete from student report. [PRO]
- Fix - Hide courses per row option in list view mode and other layouts.
- Fix - Disable enroll button on enrollment limit reached.
- Fix - Curriculum count showing only lessons count issue.
- Fix - Fatal error for SCORM course when certificate is disabled.
- Fix - Permission notice for non previewable content in learn page for non enrolled users. [PRO]
- Fix - WooCommerce product delete issue if course is linked. [PRO]
- Fix - Start URL issue for Google Meet.
- Fix - Start URL issue for Zoom. [PRO]

= 2.17.2 - 05-03-2025 =
------------------
- Enhancement - Gamipress support for SCORM's course completion.
- Enhancement - Added `view` attribute in courses shortcode for list and grid view.
- Enhancement - Option to show course attachments to login user or enrolled users. [PRO]
- Fix - Resolved security vulnerabilities.
- Fix - Global setting's single course page icon.
- Fix - Group courses enrolment limit notice.
- Fix - Light/dark mode text not translatable.
- Fix - Supported WooCommerce Shipping & Tax in course related product page.
- Fix - Design issue in Divi Builder.
- Fix - URL, Image support for quiz's questions.
- Fix - React Warning for Data targetId.
- Fix - Zoom in PDF lesson Viewer. [PRO]
- Fix - Get Gravatar img url if only enable.
- Fix - Whitelisted CSS for plugin like Elementor, Rank Math, MonsterInsight in learn page.

= 2.17.1 - 18-02-2025 =
------------------
- Refactor - Allow Razorpay to use WordPress WpOrg\Requests class.
- Fix - Security related issue caused by rmccue package.

= 2.17.0 - 12-02-2025 =
------------------
- Feature - Mollie Payment Gateway.
- Feature - MasterStudy and Lifter LMS migration.
- Feature - REST API Authentication.
- Feature - Individual quiz import and export option in course builder.
- Feature - Added audio lesson type. [PRO]
- Feature - Video subtitle upload options in video lesson. [PRO]
- Enhancement - Filters and sorting option for courses page.
- Enhancement - Wire transfer option in offline payment.
- Enhancement - Added option to enable/disable OpenAI.
- Enhancement - Additional file type support for audio and video types in doc uploader.
- Enhancement - Masteriyo Global Setting UI minor revamp.
- Enhancement - Added option to convert Youtube livestream to normal Youtube video after live stream end. [PRO]
- Refactor - Social login for plain permalink selected case. [PRO]
- Fix - Backend pages not working with AI engine plugin.
- Fix - Stripe recipient email issue.
- Fix - Compatibility with Divi builder plugin.
- Fix - Correct answer handling when switching from multiple to single choice question type.
- Fix - Watch full video issue when content drip is sequential.
- Fix - Quiz title media displayed as plain text in learn page.
- Fix - Password updated for same current and new passwords issue.
- Fix - Lesson comment redirection for guest user after logged in.
- Fix - Course exports key translation issue.
- Fix - Completed course showing in account page dashboard in continue studying.
- Fix - Ajax filter not working in course archive for layout 1 and 2. [PRO]
- Fix - Courses sorting query logic in Courses page. [PRO]
- Fix - Assignment retake issue when it is reviewed. [PRO]
- Fix - Enrollment limit issue.
- Fix - Course list badge UI.
- Fix - Single course permalink URL changed based on permalink structure of WordPress.
- Fix - Invoice download fails after PayPal payment.
- Fix - .mov and .flv video support in Doc Uploader.
- Fix - Issue on UI on layout 1 and 2 while filtering courses in courses page.
- Fix - Delete the registered users data associated with the Masteriyo while uninstalling delete all data.
- Fix - Stripe transaction id not generating issue.

= 2.16.2 - 28-01-2025 =
------------------
- Enhancement - Unmute autoplay option added in Masteriyo player settings.
- Fix - Semicolon appear in single course page setting.
- Fix - Backend pages not working with latest Gutenberg update.

= 2.16.1 - 10-01-2025 =
------------------
- Fix - Multiple pages creation on activation issue.
- Fix - Elementor single course page template issue.
- Fix - Multiple carousels to function on the same page.
- Fix - Set iframe height to full for YouTube videos on Masteriyo Player.
- Fix - Correct rounding logic for total amount calculation in Stripe addon.
- Fix - Review and comment filter text and count issue.
- Fix - Approve review and comment notice visible on update.
- Fix - User Registration add-on does not automatically enable the Integrations tab in the settings.
- Fix - Question name default value issue.
- Fix - Not found child error on dashboard.
- Fix - Course FAQ Issue.

= 2.16.0 - 02-01-2025 =
------------------
- Feature - BuddyPress Integration.
- Feature - PDF lesson.
- Feature - Lemon Squeezy support for Course Bundles.
- Tweak - `Reviews` submenu name updated with `Reviews & Comments`.
- Tweak - Replace `Question Name` input field with WP basic editor.
- Update - Emails content improvised.
- Update - JS packages upgraded and fix console deprecation warning of several packages.
- Refactor - Rearrangement global settings options and optimize API calls.
- Refactor - Backend pages UI responsive fixes and minor revamp.
- Refactor - Admin notices for Masteriyo Page.
- Enhancement - Added quiz reveal mode.
- Enhancement - Added multiple emails for admin, instructors and students.
- Enhancement - Lesson comments can be accessible through `Reviews & Comments` page for editing and reviewing lesson comments.
- Enhancement - Account section consistency with RTL languages.
- Enhancement - Multiple cache plugin compatibility, auto set recommended settings and display warning for affected cache setting.
- Enhancement - Notification count in backend submenus for Orders, Users, Reviews and Subscription.
- Enhancement - Added notice if Masteriyo pages not setup correctly and option for auto setup missing pages.
- Enhancement - Quiz builder overall performance optimized.
- Fix - Timestamp title crash issue with non ASCII characters.
- Fix - Google meet course id null on update.
- Fix - Show lemon squeezy checkout option for course link with lemon squeezy.
- Fix - Quiz points minimum value set to 1.
- Fix - Gradebook table UI issue in single course page.
- Fix - UI issue in public profile.
- Fix - Editor image alignment not working.
- Fix - Learn page missing scrollbar for longer contents.

= 2.15.3 - 19-12-2024 =
------------------
- Enhancement - Added report issue button on error page.
- Enhancement - Added logic to clear duplicate lesson progress from `Clear Cache` button.
- Update - Added states for Venezuela.
- Fix - Video Lesson `Mark as complete` issue.
- Fix - Setting `Advance` title updated to `Advanced`.
- Fix - Download material preview issue.
- Fix - Course reminder email sending issue.
- Fix - `item_type` issue in student reports and also filter drafted lessons from being rendered.
- Fix - Student redirection to course page issue after registration.

= 2.15.2 - 03-12-2024 =
------------------
- Enhancement - Added course completion button in SCORM course.
- Enhancement - Added option to update course review reply status.
- Tweak - Default course content access to true.
- Fix - Do not add student role for admin & instructor while starting course.
- Fix - Valid phone number showing as invalid in checkout.
- Fix - Course ID not found issue for password protected course with WooCommerce Integration.
- Fix - Bracket displaying in course list of Bricks builder.
- Fix - Certificates not listing in account page.
- Fix - Translation properly not working issue.
- Fix - Incorrect user course progress data in account dashboard tab.
- Fix - Large quiz attempt data not updating issue.
- Fix - CSS effecting theme customizer UI.
- Fix - Google meet completed typo.
- Fix - Image to image match the following question issue.
- Fix - User account course progress data consistent with public profile and student progress report.
- Fix - Course FAQ not showing issue.

= 2.15.1 - 20-11-2024 =
------------------
- Fix - Sample courses file not found issue after first sample course install.
- Fix - Instructor approval issue in add new instructor page.
- Fix - Course progress bar division by zero error.
- Fix - WooCommerce add to cart issue if product is not publish and start course issue.
- Fix - Account page UI issue in twenty twenty five theme.

= 2.15.0 - 19-11-2024 =
------------------
- Feature - Fluent CRM Integration.
- Feature - Student activity log.
- Feature - Lesson Comments.
- Feature - Added video option in assignment.
- Feature - Added support for Math equations in lesson, quiz and assignment.
- Feature - Individual or multiple courses exportable from course listing page.
- Feature - Added course bundle support for SureCart integration.
- Compatibility - Compatible with WordPress v6.7.
- Enhancement - Import/Export overall optimization in backend processing.
- Enhancement - Lessons now have individual type (Text, Video and Live Stream lesson.)
- Enhancement - Add new content in course builder now appear in modal instead of popover.
- Enhancement - Added revenue sharing table in individual course reports.
- Enhancement - Set required percentage for video watch in order to mark as complete in Masteriyo player.
- Enhancement - Popup warning for content protection while content inspection.
- Enhancement - Added option for post checkout landing page in page setting.
- Enhancement - Added shortcode `[masteriyo_student_registration]` for student registration form.
- Enhancement - Added progress bar and started at info in single course page.
- Enhancement - Analytics range selector added and minor UI revamp.
- Enhancement - Added SCORM in advance global setting to add additional extension file type.
- Refactor - Lesson builder UI revamp.
- Refactor - Backend pages minor UI revamp (Button outline, colors, font sizes and styles, icons).
- Refactor - Google classroom backend revamp.
- Update - JS packages upgraded.
- Tweak - Group member limit error message.
- Fix - Global settings UI.
- Fix - Single course page UI issues.
- Fix - Prevent error when retrieving capabilities for non-existent roles.
- Fix - Fill in the blanks empty answer issue.
- Fix - Show course attachments tab to admin.
- Fix - Account page access to other user roles.
- Fix - Error in account page when SCORM addon is active.
- Fix - Hide Lemon Squeezy Settings from course page if it's disabled on global setting.
- Fix - Accordance Issue in Setting Page.
- Fix - Permission check for certificate share preview.
- Fix - Content drip issue for guest user.
- Fix - Forward ref issue in async select.
- Fix - See More Issue in Review Filter in Single Course Page.
- Fix - Revenue sharing withdraw section box UI.
- Fix - Tooltip now consistent with global settings.
- Fix - Body color removed from single course block.
- Fix - Console warning related to react defaultProps.
- Fix - Hide curriculum tab for SCORM and Google classroom courses.
- Fix - Dark mode issue in locked contents.
- Fix - Dynamic primary colour not reflecting for course archive layout 1 and 2.
- Fix - Add student role to enrolled users if user has no student role.
- Fix - Allow download/preview of certificate link even when certificate option is disabled on the course.
- Fix - Rate this course for guest non logged in users in learn page after course completion.
- Fix - Resolve course completion reminder email scheduling issue.
- Fix - Issue in course purchase after WooCommerce product trash or deletion.
- Fix - Compatibility issue with GIFT4U - Gift Cards All in One for WooCommerce.
- Fix - Stripe checkout fields not showing issue if stripe description is empty.
- Fix - Course preview not working for embedded video.

= 2.14.6 - 15-11-2024 =
------------------
- Fix – JavaScript 'Selectors' error in WP 6.7 version.

= 2.14.5 - 22-10-2024 =
------------------
- Fix - Resolved security vulnerabilities.
- Fix - SCORM course type user progress issue.
- Fix - Fill in the blanks new line issue.

= 2.14.4 - 03-10-2024 =
------------------
- Feature - Brevo, HubSpot and MailerLite Integration.
- Enhancement - Auto Sync in WooCommerce product if course is updated.
- Enhancement - Answer explanation UI revamp.
- Enhancement - Replaced textarea with basic classic editor for answer explanation.
- Enhancement - Added option to show or hide header/footer in the account page.
- Enhancement - Added courses tab in analytics.
- Enhancement - Countdown timer UI revamp in learn page.
- Enhancement - Added certificate share option.
- Enhancement - Certificate for SCORM course.
- Enhancement - Content width adjustable in learn page.
- Fix - Redirect to incorrect checkout page issue when WooCommerce product is in draft and Masteriyo course is in publish.
- Fix - Instructor page not listing the additional instructor's courses if admin is logged-in.
- Fix - Recurring price not showing issue.
- Fix - Other quiz attempts access by student if user id param is given.
- Fix - Permission related issues.
- Fix - Video mute on start issue in other platform except YouTube.
- Fix - Video sharing option and right click option changed to false by default.

= 2.14.3 - 24-09-2024 =
------------------
- Enhancement - Added support and debug docs in error page.
- Enhancement - Global setting minor UI update.
- Fix - Course bundle issue.
- Fix - Author name issue in instructor course list page.
- Fix - Course preview related issue.
- Fix - Other quiz attempts listing by student related issue.
- Fix - Permissions related issue in backend and frontend.
- Fix - cssRules issue and used chakra animation for loader.
- Fix - Course continue URL issue in SureCart integration.
- Fix - Set default value of lesson video share and right click option to false.

= 2.14.2 - 16-09-2024 =
------------------
- Fix - Global setting issue due to the translations.

= 2.14.1 - 13-09-2024 =
------------------
- Fix - Quiz attempts of other students were incorrectly showing in the student quiz attempt list on the account page while searching.

= 2.14.0 - 12-09-2024 =
------------------
- Feature - Tutor LMS migration.
- Feature - Course bundle Support in WooCommerce integration.
- Refactor - Global Settings UI revamp and performance optimization.
- Refactor - Learn page performance optimization.
- Enhancement - Learn page responsiveness.
- Enhancement - Now only content will be loaded instead of full screen loader in learn page.
- Enhancement - Added multi currency support for course bundle.
- Enhancement - Added option to download invoice from order history in the account page.
- Enhancement - Added option to enable/disable profile tab, apply for instructor and edit profile in the account page.
- Enhancement - Added Course, Course bundle and Course categories carousel Elementor Widgets.
- Enhancement - Addon page UI update and addon submenu instantly reflect on activation/deactivation.
- Enhancement - Added option for quiz auto abandon or submission after quiz time expires.
- Enhancement - Optimize PHP queries data by caching.
- Enhancement - Lock icon added in curriculum if user is not enrolled in a course.
- Enhancement - Notification content editable option.
- Enhancement - Content drip condition on course preview content.
- Enhancement - Additional blocks for certificate (Course start date, duration, instructor, date, time, timestamp, co-instructors and grade result).
- Enhancement - Added option to check plugin update and force update in Tools > Utilities page.
- Update - Package woocommerce/action-scheduler upgraded to v3.8.1.
- Fix - Primary color not reflecting on some icons and missing text translation.
- Fix - YouTube video not playing after refresh.
- Fix - Google meet instructor permission issue.
- Fix - Courses block not working issue.
- Fix - PHP8 deprecation warning.
- Fix - Focus mode save issue.
- Fix - Instructor's page not displaying courses if they are a co-instructor.
- Fix - Course count issue for additional authors in course listing backend page.
- Fix - Course bundle selected course categories list UI issue.
- Fix - Account page items can be seen from url even if disable in global setting.
- Fix - User role not getting updated to Masteriyo Student after SureCart checkout.
- Fix - Additional instructor select default option issue.
- Fix - Instructor not able to directly enroll student from course report.

= 2.13.3 - 20-08-2024 =
------------------
- Fix - Minor fixes.

= 2.13.2 - 19-08-2024 =
------------------
- Feature - Gutenberg blocks for single course.
- Enhancement - System performance optimized.
- Enhancement - Additional Elementor widgets (Course Coming Soon and Group Course, Prerequisites, Social Share).
- Enhancement - Focus mode in learn page.
- Enhancement - Redirect user to course or checkout page if guest user logged in or sign up through course register now or buy now button.
- Enhancement - Logger Functionality implemented.
- Update - Requires WordPress version 6.5 or higher.
- Update - Isolated Editor updated to version 2.29.0.
- Fix - Certificate sample 4 and 5 displayed the same image.
- Fix - Error in Certificate block editor when converting text to italics.
- Fix - Addressed design issue with public profile tabs.
- Fix - Ensure correct plugin functionality when activating with the free version enabled.
- Fix - Added toast notification for the "Add New Google Meet" button on the builder page.
- Fix - Bricks Builder UI Issue.
- Fix - Google Meet Delete issue.
- Fix - Text translation issue in order status and account page toggle.
- Fix - Isolated block editor version issue.
- Fix - Mark as complete button issue in interactive lesson.
- Fix - Lesson's block editor text not highlighted.
- Fix - Course announcement permission issue for instructors.
- Fix - Divi categories and instructor include/exclude settings.
- Fix - Logo changes are now reflected on the learning page.
- Fix - issue with scrolling into view when adding a new section.
- Fix - Addressed "Route not found" error on the learning page when Google Meet addon is disabled.

= 2.13.1 - 05-08-2024 =
------------------
- Enhancement - Replace text area with classic editor in question description field.
- Fix - Mark as complete button issue in the learn page.
- Fix - Thumbnail UI issue in player.

= 2.13.0 - 24-07-2024 =
------------------
- Feature - Added Masteriyo Player.
- Feature - SureCart Integration Addon.
- Feature - Course categories slider using shortcode.
- Feature - Quiz question answer explanation option.
- Enhancement - Email setting UI.
- Enhancement - Added email template and option to add header logo, header background image and footer text.
- Enhancement - Added from email and name in email general setting for global email use.
- Enhancement - Added additional fonts download option in certificate settings.
- Enhancement - Added Child theme support for Bricks Builder.
- Enhancement - Restructure Masteriyo submenus.
- Enhancement - Added minified JS files.
- Fix - Double course completion and certificate attachment to student.
- Fix - Public profile offered courses issue if instructor has no courses.
- Fix - Prerequisite UI issue in default themes.
- Fix - Prerequisites template not showing for guest users.
- Fix - The additional instructor's course is not shown on the public profile.
- Fix - Author course display on the public profile if an addon is enabled.
- Fix - Admin email notification in student registration.
- Fix - Unable to edit the course items while course is password protected.
- Fix - Added coming soon timer for single course layout 1.
- Fix - Single course page review section design issue.
- Fix - Made notification content translatable.
- Fix - Remove correct key from answer data in question API.
- Fix - Prevent access to unpublished courses and course items.
- Fix - Double password reset email to student.
- Fix - Meta data not hidden for layout 1 single course for course coming soon.
- Fix - Course coming soon UI issue in layout 1 single course.
- Fix - Instructor approval email not sending to instructor.
- Fix - Error on edit page while WC integration is enable and cart is not empty.
- Fix - Course password and end date issue while updating the course items.
- Fix - Isolated block editor UI issues.
- Fix - UI issues in default themes for layout 1 single course.
- Fix - User Registration Plugin compatibility issue fixes for forgot password.
- Fix - Pointer events unset in default theme footer.

2.12.3 - 17-07-2024
------------------
- Fix – Compatibility issue with WordPress 6.6 version.

2.12.2 - 09-07-2024
------------------
- Fix - Course bundle responsive issue.
- Fix - Primary colour issue in the selected section in the learn page.
- Fix - Modal does not appear when Elementor template is selected for single course page.
- Fix - Download materials not showing in the lesson learn page.
- Fix - Ensure course is published before review creation.
- Fix - Prevent unverified instructors from accessing users.
- Fix - Unauthorized access to password protected course.
- Fix - Blank heading tag issue if question heading is not set.

2.12.1 - 28-06-2024
------------------
- Fix - Course not selecting issue on search in course bundle.
- Fix - User first and last name blank issue on webhook student registration trigger.
- Fix - Video ID issue while editing lesson.
- Fix - Primary colour issue in the learn page.

2.12.0 - 26-06-2024
------------------
- Feature - Course Bundle.
- Feature - Zapier Integration.
- Feature - Download materials for assignment.
- Feature - Single course and course archive custom template for Bricks builder.
- Feature - Add YouTube live stream option for lesson videos.
- Feature - Course archive styles customize from global settings.
- Enhancement - Add email notification option for admin/instructor on quiz attempt and assignment reply.
- Enhancement - Show reviews to everyone but enrolled users can only post reviews.
- Enhancement - Added Add to cart option in WooCommerce integration.
- Enhancement - Added dark and light mode in the learn page.
- Fix - Guest user not able to start quiz.
- Fix - Permission issue on fetching changelog.
- Fix - Addon requirement check for multisite network active.

2.11.2 - 14-06-2024
------------------
- Fix - Resolve multiple currency issue in courses shortcode page.
- Fix - Resolve Elementor builder issue related to course retake feature.
- Fix - Divi course description text format issue.

2.11.1 - 07-06-2024
------------------
- Fix - Social share sign up/sign in text update.
- Fix - Course coming soon timer issue.
- Fix - Checkout page login link increased font weight.
- Fix - Google Meet filter issue.

2.11.0 - 06-06-2024
------------------
- Feature - Multiple Currency addon.
- Feature - Google Meet integration addon.
- Feature - BunnyNet integration addon.
- Feature - Course Coming Soon addon.
- Feature - Embed Video Option for lesson.
- Enhancement - Added social share live preview in setting.
- Enhancement - Added certificate settings.
- Enhancement - Added sidebar and lock icon for content in lesson preview.
- Enhancement - Prerequisite addon for draft course.
- Enhancement - Add initial password field validation on page load.
- Enhancement - Implemented transient cache.
- Enhancement - Implement auto woocommerce product creation from course setting.
- Enhancement - User course progress query optimization.
- Enhancement - Backend page UI responsiveness.
- Enhancement - Course one time fee validation before course update.
- Enhancement - Added course retake and google classroom meta elementor widget.
- Enhancement - Addons checkbox and description and prevent page from reloading after activating and deactivating addons.
- Fix - Match the following question type data persist issue.
- Fix - Public profile page certificate tab and rating issue
- Fix - Video question type missing url.
- Fix - Fatal error on social login.
- Fix - Instructor unable to update the review on their course.
- Fix - PHP 8.2 deprecated notice
- Fix - Allow admin updates only to status for other groups
- Fix - Rating review and quiz review in learn page issue.
- Fix - Backend course filter issue
- Fix - Prevent instructors from viewing other reviews, quiz attempts and Q&A.

2.10.1 - 22-05-2024
------------------
- Enhancement - Supports international currencies as supported by Razorpay.
- Fix - Enrollment expiration time period resetting issue while retaking the course.
- Fix - Course preview link in new single course layout issue.
- Fix - Question and answers not deleting issue.
- Fix - Account page UI issue in Divi and Astra theme.
- Fix - Account page responsive issue.
- Fix - Individual course review setting issue.

2.10.0 - 20-05-2024
------------------
- Feature - New dashboard page.
- Feature - Two new course archive layout and one new single course layout options.
- Feature - Beaver Builder Integration.
- Feature - Individual course student reports and management.
- Enhancement - Account section UI revamp.
- Enhancement - Added H5P embed button in classic editor.
- Enhancement - Added analytics tab on instructor dashboard.
- Fix - Complete quiz button not working on percentage pass mark type.
- Fix - Course preview on elementor editor.
- Fix - Course badge value disappearing after course update.
- Fix - Fatal error when deleting course review from single course page.
- Fix - Gutenberg courses setting category filter not working in select options.
- Fix - To address not working for course completion email of student.

2.9.4 - 01-05-2024
------------------
- Refactor - Manage all addons permission from core capabilities file.
- Fix - Show course attachments to enrolled user only.
- Fix - Notice not showing on login form.
- Fix - Fill in the blanks question not being updated.
- Fix - Duplicate available seats count option in course archive setting.
- Fix - Instructor transfer issue while deleting the instructor.
- Fix - Used `$wpdb->prefix` instead of `wp_` in course analytics.
- Fix - Video and image preview not working on attachment.
- Fix - Certificate blocks not showing on certificate builder.
- Fix - Integration tab not showing on global setting when integration addon active.
- Fix - Course welcome message and started time issue.
- Fix - Redirect to EDD checkout issue on all paid courses.
- Fix - Remove login session limit for administrator.

2.9.3 - 22-04-2024
------------------
- Feature - Lemon Squeezy payment integration.
- Feature - Login Session Management.
- Enhancement - Added course review filter in single course page. 
- Enhancement - Added assignment retake option in course setting.
- Enhancement - Multiple students enroll option in Manual enrollment addon.
- Enhancement - Filter analytics section for individual instructors.
- Enhancement - Welcome message to first time user in a course. 
- Enhancement - Added static enroll count option in course setting. 
- Enhancement - Addons page UI revamp. 
- Enhancement - Added Course badge option in course setting.
- Enhancement - Added class name on the account profile page.
- Tweak - Global settings search and Save setting position changed.
- Fix - Allow auto-generation username for bulk enrollment (CSV).
- Fix - Course FAQ duplicate issue.
- Fix - White label logo width issue in order invoice.
- Fix - Removed unnecessary Addons tab from account page.
- Fix - With course refresh access, set non existing enrolled users course status to inactive.
- Fix - Deactivate user course enrollment upon user deletion.
- Fix - Global setting tab issues.

2.9.2 - 09-04-2024
------------------
- Enhancement - Added lesson video URL type option for learn page.
- Fix - Course report button missing.
- Fix - Razorpay live mode issue.
- Fix - Hide enrollment expiration info when course is inactive on single course page.
- Fix - Correct course expiration information on single course page.
- Fix - Block editor and certificate builder edit error issue.
- Fix - Content protection issue in mobile devices.
- Fix - Course review issue in learn page.

2.9.1 - 04-04-2024
------------------
- Compatibility - Compatibility with WP 6.5.
- Enhancement - Order PDF invoice logo use white label logo if exist.
- Fix - Duplicated social share setting.
- Fix - Learn page redirecting issue in some mobile devices.
- Fix - Global setting issue.
- Fix - PHP 8.2 warning: Deprecated strtolower() call.
- Fix - Private js console error in WP 6.5.

2.9.0 - 27-03-2024
------------------
- Feature - Email Preview Capability.
- Feature - Group Course.
- Feature - QR Code Login.
- Feature - Bricks Builder Integration.
- Enhancement - Stripe iDEAL Payment Support.
- Enhancement - Supports manual review for fill-in-the-blanks question type.
- Enhancement - Course review need approval.
- Enhancement - Added Utilities tab in Tools page.
- Enhancement - Added search functionality in Settings.
- Enhancement - Show/Hide Components Supports for Courses Shortcode Page.
- Enhancement - Added a Quick Edit Option in the Courses Section.
- Enhancement - Show Students available seats.
- Enhancement - Notifications UI update and addition in learn page.
- Enhancement - Added sale price option.
- Enhancement - Added courses order and orderby option for courses listing page.
- Enhancement - Added Shortcodes tab in tools page.
- Enhancement - Added "Download Invoice" Feature to the Thank You Page.
- Fix -  Resolved Notification and Course Access Settings Issue.
- Fix - User Registration Integration compatible issue with User Registration PRO.
- Fix - Account Section responsiveness.
- Fix - Old attempted quiz data duplicated in new quiz attempt issue.

2.8.5 - 20-03-2024
------------------
- Update - Text domain from `masteriyo` to `learning-management-system`, conforming to WordPress standards.

2.8.4 - 15-03-2024
------------------
Fix - Email Verification Issue.
Fix - Instructor Application Approval/Rejection Process.
Fix - Forgot Password Functionality.

2.8.3 - 07-03-2024
------------------
- Feature - Event Calendar.
- Feature - SCORM Complaint.
- Feature - Google Classroom Integration.
- Enhancement - Individual Course Analytics.
- Enhancement - Supports more webhook events.
- Enhancement - Flexible and Strict Mode for Assignment.
- Enhancement - Added option to enable/disable review in individual course setting.
- Fix - Remove course deletion capability for co-authors.
- Fix - Student not able to see quiz attempt review note in responsive mode.
- Fix - Content Redirect Learn Page Height.
- Fix - Quiz reviews error if user doesn't exist.
- Fix - Course enrollment duplication issue.
- Fix - Sanitize course review after course completion fields.
- Fix - Quiz user attempt data not clearing from session storage after submission.
- Fix - Localized editor settings in Masteriyo page only.

2.8.2 - 27-02-2024
------------------
- Fix - Fatal error while downloading order PDF invoice.

2.8.1 - 26-02-2024
------------------
- Feature - All Growth plan feature now available in Starter plan.
- Enhancement - Loading spinner changed to skeleton in addons page.
- Fix - Paid Memberships Pro issue with free course.
- Fix - User details UI issue.
- Fix - Prevent page from reloading on enter while filtering data in backend.
- Fix - Order itemmeta inserting issue while WooCommerce plugin is active.
- Fix - Setup wizard responsive issue.
- Fix - Masteriyo blocks effecting widgets section.

2.8.0 - 15-02-2024
------------------
- Feature - Migration from LearnPress and LearnDash LMS..
- Feature - Course access via password.
- Feature - Restrict course content access during quiz.
- Enhancement - Emails fields are now editable from individual email settings.
- Enhancement - Added notifications global settings and extra notifications for PRO.
- Enhancement - White label area enhanced for replacing Masteriyo text.
- Enhancement - Added option to disable mark as complete button on assignment if students fails.
- Enhancement - Admin can download order PDF invoice.
- Refactor - Notifications content manage from backend.
- Fix - Lesson deletion issue for learn page.
- Fix - Certificate blocks not showing issue and block editor css enqueuing in other backend pages than Masteriyo.
- Fix - Certificate PDF image not loading issue.
- Fix - Blank certificate creation and course difficulties color issues.
- Fix - Course featured image issue.
- Fix - Ordering issue for lesson and quizzes of course created using OpenAI.
- Fix - Ensure access to courses when pricing is added post-enrollment.
- Fix - Course retake text duplication.

2.7.4 - 23-01-2024
------------------
- Refactor - Course progress and notifications API permission.
- Fix - Fatal error on reactivating PRO plugin
- Fix - Notification read and clear issue.

2.7.3 - 17-01-2024
------------------
- Feature - Restrict Content Pro Integration.
- Feature - Added Gutenberg editor option.
- Enhancement - Added certificate templates.
- Enhancement - Added option to attach certificate on course completion and course completion email option to the student.
- Enhancement - Show confirmation dialog on submit the quiz.
- Enhancement - JS package has been upgraded.
- Enhancement - Additional role selection options for students and instructors.
- Enhancement - Added last course updated info in the single course page.
- Fix - Zoom bulk deletion issue.
- Fix - Draft enrolled course issue in the account page.
- Fix - Error message if there is error during creating user on checkout.
- Fix - Update the user enrollment status based on order status.
- Fix - All orders shown on admin account order history.
- Fix - User being able to change role from API request.
- Fix - Course highlight cursor issue.

2.7.2 - 26-12-2023
------------------
- Enhancement - Show sample courses and setup wizard tools tab to admin only.
- Refactor - Course exists checks in the WCIntegration addon.
- Fix - EDD issues and compatibility with EDD Pro.
- Fix - Zoom user's error issue.
- Fix - Permission issue in the account page.
- Fix - Lesson query runs on other pages than the lesson in learn page.
- Fix - Double order history filter on the account page.
- Fix - Unable to access dashboard if admin or instructor has student role.
- Fix - PHP8.1 deprecated notices.
- Fix - Permission issue in the account order history page.
- Fix - Course announcements permission issues.
- Fix - User notifications issue.
- Fix - Arrow function in Open AI causes an error in the backend.
- Fix - Course continues URL in the account page.

2.7.1 - 19-12-2023
------------------
- Feature - Razorpay Payment Integration.
- Feature - Course Content Protection.
- Feature - Mailchimp Integration.
- Feature - Refactor Match the Following question type to support Image Matching as well.
- Feature - Lesson Preview (Able to see lesson contents as well).
- Feature - [User Registration](https://wordpress.org/plugins/user-registration) integration addon.
- Feature - Notification systems for students.
- Feature - Course review after course completion from learn page.
- Enhancement - Add white label options for Masteriyo role names.
- Enhancement - Course, Lesson, Quiz contents generation with Open AI.
- Enhancement - Added option to show/hide tabs in the account page.
- Enhancement - Added filters on account page.
- Refactor - Updated reset password email content.
- Fix - Course draft issue on edit lesson.
- Fix - Course retake sql query issue if there are no assignments.
- Fix - Completed enrolled courses data not showing in the student report.
- Fix - Public profile constant not defined.
- Fix - Zooms listing and zoom status translable issue.
- Fix - Zooms listing order by start time filtering issue in backend.
- Fix - Multiple course completion reminder job schedule issue.
- Fix - Courses, Course Categories block registration issue on pages/posts.
- Fix - Course archive page search section design issue.
- Fix - Empty answer input field edit problem on quiz builder.
- Fix - Account page design issue in twenty twenty four theme.
- Fix - Console warning in the quiz review page.
- Fix - PayPal order status unchanged after successful payment.
- Fix - Learn page site title overflow issue.

2.7.0 - 28-11-2023
------------------
- Feature - Course enrollment expiration.
- Feature - Bulk Enrollment to the Courses using CSV File.
- Feature - Social Login (Facebook & Google ).
- Feature - Two Factor Authentication ( Email OTP ).
- Feature - Quiz review system.
- Feature - Course end feature.
- Feature - Course retake feature.
- Enhancement - Added option to show/hide quiz attempt details button on quiz setting.
- Enhancement - The instructor can now leave notes to each review-type question while reviewing quiz attempts.
- Enhancement - Added global option to show review for enrolled students only.
- Enhancement - Added quiz access options for guest users.
- Enhancement - The setup wizard has been re-enhanced.
- Enhancement - Added option to show or hide the learn page sidebar initially.
- Enhancement- Added WP media library to support attachments and download materials.
- Compatibility - Add Compatibility of course content access settings with Prerequisites addon.
- Compatibility - Compatibility with WP 6.4.
- Refactor - Set `template_include` priority to `100`.
- Fix - Certificate invalid character issue with languages other than english.
- Fix - Questions deletions issue if an empty section is deleted.
- Fix - Quiz attempts backend listing filters.
- Fix - Course highlight issue in WP 6.4.
- Fix - Course archive global setting responsive.
- Fix - Course archive page list view design issue in WP default themes.
- Fix - Elementor single and archive page element not rendering issue.

2.6.12 - 08-11-2023
------------------
- Fix - WordPress 6.4 compatibility issue.

2.6.11 - 16-10-2023
------------------
- Feature - WooCommerce Subscription Support.
- Feature - QR verification for certificate.
- Feature - Student course progress reports.
- Feature - Oxygen builder integration addon.
- Feature - Course announcements.
- Feature - Added Instructors list page and Instructors listing shortcode `[masteriyo_instructors_list]`.
- Enhancement - Added Popular Courses, Recent Reviews, New Students and New Instructors section in the Analytics.
- Enhancement - Added global settings for auto load next content on completing content.
- Enhancement - Added global setting option to disable 'Complete Quiz' button if students didn't pass quiz.
- Enhancement - Added filter options on addons page.
- Enhancement - Show icon based on template source (like Elementor, Divi, Masteriyo) on the settings page.
- Enhancement - Show create new template link below template options in Elementor.
- Enhancement - Add “Use template for Masteriyo” action in templates list table showed by Elementor.
- Refactor - Implement basic caching on user courses to eliminate duplicate queries.
- Fix - Subscription tab issue in account page.
- Fix - Multiple tab shown for import/export in tools page.
- Fix - Unable to view subscription in the account page for students.
- Fix - Undefined method `get_billing_cycle`.
- Fix - Certificate preview link and sticky header.
- Fix - PHP 8 Deprecated Parameter Order in OpenAI.
- Fix - Random 0 on the learn page.
- Fix - Preview permalink of quiz and lesson.
- Fix - Font CSS issue on learn page quiz description.
- Fix - To admin email gap issue on global email settings.
- Fix - Import popup showing up on unrelated pages in Elementor
- Fix - Unusable widgets showing up everywhere in Elementor.
- Fix - Randomize answers glitch issue on quiz.
- Fix - Multiple actions creation for subscriptions expiration and license expiration check.
- Fix - Coupon not applied for guest user and permission related issue.

2.6.10 - 27-09-2023
------------------
- Feature - Course Subscription or Recurring price.
- Feature - Open AI Chat GPT Integration.
- Feature - Gamipress Integration.
- Feature - Quizzes import/export.
- Enhancement - Allow admin/instructors to start their courses without enrolling.
- Enhancement - Bulk export for students and instructor in CSV.
- Enhancement - Added additional emails on email settings.
- Fix - Divi builder console JS error in learn page.
- Fix - Multiple requests for license check.
- Fix - Quiz option random issue.
- Fix - Quiz detail modal UI issue in learn page.
- Fix - Question description not working.

2.6.9 - 08-09-2023
------------------
- Feature - Paid Membership Pro integration addon.
- Feature - Revenue sharing addon.
- Feature - Settings import/export system.
- Enhancement - Advanced email settings.
- Enhancement - Disable 'Mark as complete' button if assignment is not submitted.
- Enhancement - Bulk activate/deactivate addons.
- Enhancement - Added purchased course on account page under ‘Your Courses’ tab.
- Enhancement - Course thank you page after successful course completion, option to select WP pages or custom URL in global page setting.
- Enhancement - Check course category exists before creating it.
- Enhancement - Enable/disable email verification option on global setting advance tab.
- Fix - Email verification not working.

2.6.8 - 28-08-2023
------------------
- Feature - EDD integration addon.
- Feature - Divi builder Integration.
- Feature - Course visibility option (logged in to view course).
- Feature - Users import/export system.
- Feature - Public profile addon for instructor and students.
- Enhancement - Apply for instructor option from student profile.
- Enhancement - Auto scroll to the completion notice after the course is completed.
- Enhancement - Show/hide component now reflect on single course, instructor and course category page.
- Enhancement - When continue learning a course, last open content will be visible instead of initial content.
- Fix - Instructor upload media not being saved on course description.
- Fix - Default order issue for course categories shortcode.
- Fix - Course preview permalink in backend header.
- Fix - Typo on payment details text.
- Fix - Webhooks position in instructor sub menu on dashboard page.
- Fix - GDPR message not being updated.
- Fix - Course and review search filter not working on backend.
- Fix - 'isActive' warning on backend pages console.
- Fix - Sortable Question not randomize while taking quiz.
- Fix - Course prerequisite lock icon not showing on frontend.
- Fix - Stripe payment UI issue in checkout.
- Fix - Zoom header UI issue.
- Fix - White label logo not working.
- Fix - Coupon addon issues.
- Fix - Missing new enrollment button on manual enrollment.

2.6.7 - 14-08-2023
------------------
- Feature - Course Preview Addon.
- Feature - Customize courses pages with Elementor.
- Feature - Email verification.
- Enhancement - Added masteriyo related course [shortcodes](https://docs.masteriyo.com/shortcodes) and enhanced existing shortcodes.
- Enhancement - Create user during checkout.
- Enhancement - Added view course button on account page when there is no enrolled courses.
- Enhancement - Assignment add/edit page responsiveness.
- Refactor - Backend pages header.
- Fix - Fatal error when webhook delivery url is invalid.
- Fix - Invalid delivery url in webhook can be set.
- Fix - Courses list view UI issue with WordPress default themes.
- Fix - Incorrect account page link in order thank you page.
- Fix - Courses page global settings not working in Divi theme.
- Fix - Certificate builder doesn't work on latest version.

2.6.6 - 02-08-2023
------------------
- Compatibility - Made compatible with SEO plugins (Yoast and Rank Math).
- Enhancement - Bulk Zoom meetings deletion.
- Enhancement - Bulk deletion of gradebook results and grades.
- Fix - Undefined roles attributes.
- Fix - List view UI issue when the featured video is used.
- Fix - Course archive search enable/disable not working.
- Fix - Quiz question type icon issue.
- Fix - Flicker of UI on initial page load when courses page is in list view.
- Fix - Bulk categories deletion not working.
- Fix - One instructor's webhooks triggers another instructor's webhooks.
- Fix - Course difficulty auto creation issue.
- Tweak - Check license expiration.
- Tweak - Added hook to redirect to a different page after a user is logged in.

2.6.5 - 25-07-2023
------------------
- Feature - Webhooks
- Feature - Dashboard analytics
- Compatibility - The updated minimum required PHP version to 7.2.
- Enhancement - Added option to select course difficulty color.
- Enhancement - Validate old password before changing password.
- Enhancement - Quiz question answer builder and improve the performance.
- Enhancement -  Course archive list and grid view option.
- Enhancement - Added global setting to show/hide course archive components.
- Tweak - Added margin to options to enable course filters and enable sorting.
- Tweak - Disable instructor selection in the course setting for an instructor.
- Fix - Error if self-hosted videos are deleted from the library.
- Fix - Course navigation issue on the learn page.
- Fix - Instructors not being able to list and add courses.
- Fix - Restrict instructors to change the author of the course.
- Fix - Account Order history status color.
- Fix - Categories select box issue.
- Fix - Learn page header toggle button background issue.
- Fix - Pagination issue with the background and color.

2.6.4 - 06-07-2023
------------------
- Fix - Sequential flow lock icon issue when content drip addon is enabled.
- Fix - Learn page sidebar issue when the sequential content drip is set and the section is reordered.
- Fix - Multiple course items in learn page sidebar.
- Fix - Return error object instead of null in the course rest response when the author doesn't exist.
- Fix - White label logo not working.
- Fix - stripe payment checkout UI issue.

2.6.3 - 03-07-2023
------------------
- Fix - Issue where low-level roles can list basic information.
- Fix - 505 error if answer is not array type or blank.
- Fix - Learn page zoom start timer issue.
- Fix - Zoom start date/time not set as the selected value.
- Fix - Zoom meeting not showing for guest user
- Fix - Course is set to buy now when the price is Free type

2.6.2 - 23-06-2023
------------------
- Fix - Course Ajax filter and sorting.

2.6.1 - 23-06-2023
------------------
- Fix - Course settings UI inconsistency with the setting tab.
- Fix - Account page pagination having color on the inactive state.
- Tweak - Remove duplicated codes from checkout.

2.6.0 - 21-06-2023
------------------
- Feature - GDPR Compliant.
- Feature - Question & Answer bulk trash, delete and restore.
- Feature - Order bulk trash, delete and restore.
- Feature - Reviews bulk trash, deletes and restores.
- Feature - Students and instructors bulk delete.
- Feature - Quiz attempts bulk delete.
- Feature - Import/Export system on the Tools page.
- Feature - Sample course installation on setup wizard and Tools page.
- Feature - Question and Answer management from the backend.
- Feature - Course bulk trash, delete and restore.
- Feature - Categories bulk trash, delete and restore.
- Compatibility - Made compatible with block theme.
- Compatibility - Updated minimum required WordPress version to 6.0.
- Enhancement - Refactor the error handle and show an error message instead of an error stack.
- Enhancement - Added Tools page in the backend.
- Enhancement - Added setting to add extra fields in checkout in the global settings payments tab.
- Enhancement - Added support for slashes in the course, lesson and quiz description.
- Enhancement - Implemented deactivation popup modal.
- Tweak - Update the courses archive page according to the page title.
- Fix - Q&A user profile image URL issue if the user does not exist.
- Fix - Account page not redirecting to the Dashboard tab initially.
- Fix - Category list table background issue.
- Fix - Design issue on the edit review page.
- Fix - Fatal error while creating and deleting course review.
- Fix - Warning constant DONOTCACHEPAGE already defined.
- Fix - Price format for PHP 8.
- Fix - JS not loading in WordPress 6.0 and 6.1 version.
- Fix - City, State and Postcode are required during checkout even if they are disabled.
- Fix -  GDPR required notice showing while checkout even if GDPR is disabled.

2.5.30 - 12-06-2023
------------------
- Tweak - Disable email send job.

2.5.29 - 12-06-2023
------------------
- Fix - Password email reset not working.

2.5.28 - 07-06-2023
------------------
- Fix - Paypal standard amount missing issue.

2.5.27 - 05-06-2023
------------------
- Feature - Added Social Share addon.
- Fix - [Addon: Multi Instructor] Courses not showing for additional instructors set by Multi Instructor Addon.

2.5.26 - 30-05-2023
------------------
- Fix - [Addon: Coupon] Apply and Remove the coupon on the checkout page for students.

2.5.25 - 30-05-2023
------------------
- Fix - Courses page filter and sorting when Ajax behavior is enabled.
- Fix - Added support for slashes in the course, lesson, quiz, assignment and zoom description.

2.5.24 - 22-05-2023
------------------
- Fix - [Addon: Certificate Builder] Error while downloading certificate by the student.

2.5.23 - 16-05-2023
------------------
- Fix - [Addon: Coupon] Apply and Remove the coupon on the checkout page.
- Fix - [Addon: Gradebook] Final grade result is being recreated after deletion.
- Tweak - [Addon: Certificate Builder] Remove helevetica font.

2.5.22 - 08-05-2023
------------------
- Enhancement - Show quiz result details of recent quiz attempts on the learn page.
- Fix - [Addon: WooCommerce Integration] Listing of courses for the Course product type.
- Fix - [Addon: Wishlist] Fix add/remove to wishlist in the single course page.

2.5.21 - 05-05-2023
------------------
- Enhancement - Isolated certificate block editor.
- Enhancement - Added Enrolled students count column in the course list backend page.
- Fix - Quotes not being supported in the quiz answers field.
- Fix - The search filter doesn't work on the courses page when Ajax search is enabled.
- Fix - 505 error if due_date is null in the assignment reply listing on the account page.
- Tweak - Refactor codes.

2.5.20 - 02-05-2023
------------------
- Feature - Added Gradebook Addon.
- Enhancement - Added external URL support in Featured Video, Lesson Video and Video Question type.
- Tweak - Refactor code.
- Tweak - Remove depreciated codes.

2.5.19 - 21-04-2023
------------------
- Feature - Added Zoom Integration Addon.
- Compatibility - Made compatible with default WordPress themes [TwentyTwentyOne, TwentyTwentyTwo and TwentyTwentyThree].
- Enhancement - Added classes to the account page.
- Fix - Removed Chakra UI dynamic class from styling in Astra, Divi and Hello Elementor theme.

2.5.18 - 11-04-2023
------------------
- Enhancement - Added show/hide password functionality in Paypal settings.
- Enhancement - Added filters and sorting in the course archive page.
- Enhancement - Added show/hide secret keys in stripe global settings.
- Fix - Different back-to-course links after completing a course on learn page.
- Fix - Unable to remove Youtube and Vimeo videos in the lesson.
- Fix - Quiz attempt for guest user.
- Fix - Course Progress summary for guest user.

2.5.17 - 30-03-2023
------------------
- Compatibility - WordPress v6.2 compatibility update.

2.5.16 - 28-03-2023
------------------
- Fix - [Addon: WooCommerce Integration] Checkout and deletion issue with subscription products.

2.5.15 - 23-03-2023
------------------
- Fix - Quiz Attempt review not working.

2.5.14 - 20-03-2023
------------------
- Feature - Added Elementor Integration Addon.
- Enhancement - Show quiz result on learn page and throw an error message if the quiz attempt exceeds.
- Enhancement - Make quiz duration translatable on learn page.
- Enhancement - Only show courses that exist and are enrolled by the users on the account page.
- Fix - Blank screen in learn page if Gutenberg plugin or WP 6.2 exists.
- Fix - Blank account page after registration.
- Fix - Submenu not showing on initial activation of certain add-ons.
- Fix - Fill in the blanks question type for multiple blanks in a single question.
- Tweak - Hide license information.

2.5.13 - 02-03-2023
------------------
- Fix - UI issue in courses page.
- Fix - Blank account page after registration.
- Tweak - [Addon: Multiple Instructor] Additional instructors avatar in the courses page.

2.5.12 - 01-03-2023
------------------
- Feature - Added Coupons Addon.
- Compatibility - Made compatible with different cache plugins. [HummingBird, LiteSpeed, W3TotalCache, WPFastestCache, WPOptimize, WPRocket and WPSuperCache]
- Enhancement - Make the logout action similar to the account page in learn page.
- Enhancement - Used formatted prices to show prices in courses price and orders.
- Enhancement - Remove course rating count as well when global course review is disabled.
- Tweak - Display country and state name instead of code in the profile billing page.
- Fix - Translation

2.5.11 - 21-02-2023
------------------
- Enhancement - Make strings translatable.
- Tweak - Set featured video at the center of the screen.
- Tweak - Make an empty message display for the wishlist similar to assignments and quiz attempts.
- Fix - Load courses.js on the courses page.
- Fix - Prevent from going to a single course page after clicking on the featured video on courses page.
- Fix - [Addon:	WooCommerce Integration] Price fields hidden in external product type when WC integration addon is enabled.

2.5.10 - 20-02-2023
------------------
- Enhancement - Enhance email settings with a newer way to control settings.
- Enhancement - Added author display name to alt attribute of author images.
- Tweak - Remove space around the author name in a single course, courses and related courses page.
- Fix - Author image not being displayed on the backend, courses listing page and learn page.
- Fix - Course name with ampersand sign for orders list.
- Fix - Added filter to change quiz icon.
- Fix - Translation not working after every version of the plugin is released.
- Fix - Remove space around author name in single, courses and related courses.
- Fix - Certificate edit post link for multi-sites.
- Fix - Added created date to new cloned post.

2.5.9 - 08-02-2023
------------------
- Fix - Course author profile image not being displayed on single and course archive page.

2.5.8 - 08-02-2023
------------------
- Fix - Depreciation warning where trim[] is being called on null instead of string.
- Fix - Show only enrolled courses by the user in the Account enrolled courses.
- Fix - User profile image not displaying in the learn page.
- Enhancement - Localize True/False label.

2.5.7 - 31-01-2023
------------------
- Feature - Added ability to duplicate Courses, Lessons, Quizzes, Assignments and Certificates.
- Enhancement - Added alt tags to author avatar image.
- Tweak - Remove unnecessary JS scripts in learn page.
- Fix - Undefined post property.
- Fix - Incorrect Enrolled students count for single courses and courses page.

2.5.6 - 25-01-2023
------------------
- Enhancement - Throw unauthorized error messages during the license activation or deactivation process.
- Fix - Instructor page overtaking the author page, therefore author's posts are not being displayed.
- Fix - Messed up a markup in the courses page which is breaking UI.
- Fix - Content drip for sequential type when Content Drip Addon is not activated.

2.5.5 - 23-01-2023
------------------
- Enhancement - Do not display quiz/lesson count in learn page when there are no lessons or quizzes in the course.
- Enhancement - [Addon: Assignment] Do not show assignment progress in learn page if there are no assignments.
- Enhancement - [Addon: Content Drip] Added drip days.
- Fix - Lesson/Quiz preview link for default permalink settings.
- Fix - Localization of expand all and collapse all text in a single course page.
- Fix - Unable to start course from account page if a course is completed.

2.5.4 - 16-01-2023
------------------
- Fix - Paypal enabled for non-supported currencies.

2.5.3 - 12-01-2023
------------------
- Fix - Typo

2.5.2 - 12-01-2023
------------------
- Tweak - Decrease update check interval from 1 day to 3 hours.
- Fix - [Addon: Stripe] Handle charge.succeeded event as well.
- Fix - [Addon: Stripe] Throw a success message when an order is not found.

2.5.1 - 11-01-2023
------------------
- Fix - Unable to activate the addon.

2.5.0 - 10-01-2023
------------------
- Addon - Introduced Content Drip addon.
- Enhancement - Adds plan ability and add-ons for specific plans locks add-ons page if license not activated.
- Enhancement - Add ability to store answer on cache for quiz & fixes quiz issues.
- Enhancement - Added extra img srcset for learn page images.
- Compatibility – Made compatible with the Hello Elementor theme.
- Compatibility – Made compatible with the Divi theme.
- Fix - Any lesson is accessible even if the course is not bought by rewriting the lesson ID in the URL.
- Fix - Checkout page form input field design issues.

2.4.10 - 23-12-2022
------------------
- Fix - Featured video removed issue when updating course.
- Compatibility - Made Masteriyo compatible with the Astra theme.
- Enhancement - By default learn page sidebar remains closed on mobile devices.

2.4.9 - 14-12-2022
------------------
- Fix - Duplicate enrolled courses.
- Fix - Unable to delete plugin due to function redecoration.

2.4.8 - 06-12-2022
------------------
- Enhancement - Added option to change the student name in the student block.
- Tweak - Added featured label to the related courses.
- Tweak - Added hook to change the featured text for the course.
- Tweak - Added authors' posts URL in a single course and related courses.
- Tweak - Added localization context for the course duration.
- Tweak - Added permalink to course category in related courses.
- Tweak - Added alt attribute to course category link.
- Fix - Stripe is always chosen when more than 2 payments are present on Checkout Page.
- Fix - Listing of wishlist items for PHP>=8.

2.4.7 - 01-12-2022
------------------
- Feature - Added 'Fill in the blanks question' type.
- Tweak - Use full name instead of a username for certificates.
- Tweak - Use course excerpt by get_excerpt[] instead of manually creating.
- Fix - Sending of password reset email after updating the user.
- Fix - Typo '.masteriyo-expand-collape-all' to '.masteriyo-expand-collapse-all'.
- Fix - Featured image width issue while adding a new course.
- Fix - Unauthorized access error while accessing the individual open courses from guests.

2.4.6 - 25-11-2022
------------------
- Enhancement - Set student role to WC Customer [WooCommerce Integration].
- Fix - Stripe display error issue.

2.4.5 - 23-11-2022
------------------
- Enhancement - Add assignment submissions to the account page.
- Tweak - Reload the addons page after activating the addon.
- Fix - Certificate and Wishlist not working on the account page.

2.4.4 - 15-11-2022
------------------
- Feature - Manual Enrollment addon.
- Enhancement - Add primary color support on the account page.
- Enhancement - Added course completion date block for Certificate Builder.
- Enhancement - Added quiz attempts listing on the Account page.
- Fix - Course FAQ adds and deletes issues.
- Fix - Remove the strayed tilde sign on the courses page.
- Fix - Fatal error due to call of get_name[] on a null object.
- Fix - Difficulty badge on the single course page.
- Fix - WC Integration issue when WC is disabled but WC Integration addon is enabled.
- Fix - Button color scheme issue with the pagination
- Fix - The certificate was automatically disabled after the course update.
- Fix - Word break in the quiz.
- Fix - Course slug error.
- Fix - Random name being displayed on the logout modal on the account page.
- Fix - Other user score data being shown on the new user quiz page.

2.4.3 - 03-11-2022
-------------------
- Fix - 404 page not found issue while checking out when WooCommerce is active.

2.4.2 - 02-11-2022
-------------------
- Fix - Fatal error due to Type Error in Masteriyo\MetaData::get_data[]

2.4.1 - 01-11-2022
-------------------
- Enhancement - Add an option in global settings to delete plugin data while uninstalling.
- Enhancement - Added content flow [free/sequential] for learn page.
- Fix - Payment method enums in orders controller.

2.4.0 - 19-10-2022
-------------------
- Feature - Added Advanced Quiz addon.
- Enhancement - Add course difficulty slug in the difficulty badge HTML markup.
- Enhancement - Removed account endpoints from global settings.
- Fix - Fatal error in widgets editor.

2.3.10 - 13-10-2022
-------------------
- Feature - Manage course difficulties through the categories page.
- Fix - Course difficulties translation issue.
- Fix - Recover quiz auto calculate, points/percent and randomize question settings.

2.3.9 - 11-10-2022
-------------------
- Fix - Undefined get_id[] method.
- Fix - Lessons count on the courses page.
- Fix - Enrolled users count on the courses page.

2.3.8 - 29-09-2022
-------------------
- Fix - WooCommerce Integration permission issue when WC Customer tries to access the Masteriyo account page.
- Fix - Undefined get_id[] call on string.

2.3.7 - 28-09-2022
-------------------
- Feature - Certificate builder addon.
- Fix - Display questions to additional instructors.
- Fix - Course author being changed while updating a course
- Fix - Additional instructor not being removed.
- Fix - Course progress issue.
- Tweak - Show only quiz attempts of quizzes created by the instructor.

2.3.6 - 22-09-2022
-------------------
- Fix - The wishlist sidebar menu not showing up on the account page.

2.3.5 - 21-09-2022
-------------------
- Feature - Added Assignment Addon.
- Feature - Added RTL support in react pages like admin, learn and account.
- Enhancement - Global settings and course settings UI.
- Enhancement - Added support to change course slug.
- Tweak - Updated skeleton loader.
- Tweak - Compatibility fixes.
- Fix - Activate button color on the license page.
- Fix - Clear stored license key after deactivation.
- Fix - Admin menu disappearance on the first activation when free is activated.

2.3.4 - 13-09-2022
------------------
* Fix - Currency decimals not being set to zero.
* Fix - Responsive issue on the account page.
* Feature - Added WishList Addon.

2.3.3 - 06-09-2022
------------------
* Fix - Paypal lives payment issue.
* Fix - Width issue in Astra Theme.
* Fix - Responsive issue on Edit Lesson.
* Fix - The instructor's course archive page throws an error when there are no author courses.
* Fix - Lesson preview issue.
* Enhancement - Added button color and primary color for learn page styling options.

2.3.2 - 23-08-2022
------------------
* Enhancement - Made Masteriyo backend pages responsive.
* Enhancement - Support non-English characters in the single-choice answers option.
* Enhancement - If the course pricing type is 'Need Registration', a logged-in user can now directly access the course.
* Enhancement - Renamed 'Buy Now' to 'Register Now' button when course pricing type is 'Need registration' and the user is not logged in.
* Enhancement - Move to randomize answer switch inside the question menu.
* Fix - Initialize placeholder image in case the file is deleted from the uploads directory.
* Fix - Featured image breaking in Twenty Twenty Two theme.
* Feature - Added Multiple Instructors Addon.
* Feature - Added Pre-Requisites Addon.


2.3.1 - 10-08-2022
------------------
* Fix - Translation issue.
* Fix - Long single-word overflows in quiz questions answers.
* Fix - Sorting in backend pages.
* Fix - Order items listing permission issue causing 505 error in the account page order history.
* Fix - Logout issue due to undefined callback destroy_session.
* Fix - Submission attachments issue when it is not enabled.
* Enhancement - Add multiple categories support to courses shortcode.
* Enhancement - Login form design updated.
* Enhancement - Added limit and relatable attribute to related course setting.
* Tweak - Removed updated button from Course FAQ.

2.3.0 - 03-08-2022
------------------
* Enhancement - Added skeleton loader in add new quiz page skeleton loader.
* Enhancement - Added skeleton loader in add new lesson and lesson edit page.
* Fix - Question and answer overflow in quiz attempt detail page
* Fix - Backend throwing 505 if missing learn page logo image.
* Fix - Undefined variable page_id.
* Feature - Added Password Strength addon.
* Feature - Added Google reCAPTCHA addon.
* Feature - Course attachments addon.

2.2.9 - 20-07-2022
------------------
* Feature - Allow drafting lessons and quizzes.
* Feature - Allow adding quiz description/hint.
* Feature - Added White Label Addon.
* Enhancement - Add a load more button in the course reviews listing on the single course page.
* Enhancement - Improved course FAQ styles on the single course page.
* Enhancement - Added settings for related courses on a single course page.
* Fix - Heading text color.
* Fix - Redundant courses in the cart when the order is uncompleted.
* Fix - Categories list disappearing after opening the add new category modal.
* Fix - request_filesystem_credentials[] not exists.
* Fix - Syntax token error while loading global settings.
* Fix - 505 error when adding a new course after activating/deactivating the Course FAQ addon.

2.2.8 - 12-07-2022
------------------
* Enhancement - Added two-column layout on lesson page backend.
* Enhancement - Randomize quiz questions.
* Fix - Cannot read property of undefined [reading 24] issue on avatar URL.
* Fix - Backend page throwing 505 when deleting featured images from the site.
* Fix - Course duration not being saved on adding a new course.
* Fix - Translation not working.
* Tweak - Added version to the functions and filters.

2.2.7 - 07-07-2022
------------------
* Enhancement - Moved pages on top of general global settings.
* Enhancement - Added filters for the admin menu and icon.
* Fix - Alignment issue on the add-ons page.
* Fix - Eliminate unnecessary loading in backend pages.
* Feature - Added course FAQ addon.
* Feature - Allow to randomize answers in quizzes.
* Feature - Allow to set pass points in the quiz as a percentage.

2.2.6 - 29-06-2022
------------------
* Enhancement - Load all the questions of the quiz on the learn page for faster pagination.
* Fix - Quiz timer expires issue.

2.2.5 - 27-06-2022
-------------------
* Feature - Added auto calculate of quiz full marks.
* Enhancement - Added class names on the account page.
* Enhancement - Show skeleton loader when changing status in courses, orders and reviews page backend.
* Fix - Edit lesson page throwing 505 error when lesson video deleted from the media library.
* Fix - Text color in courses page being affected by theme customizer.
* Fix - Enrolled courses count and start button on the account page.

2.2.4 - 17-06-2022
-------------------
* Enhancement -  Moved pages tab from advance to general tab in global settings.
* Enhancement - Added order status tab on the orders list page.
* Enhancement - Make learn page responsive.
* Enhancement - Add formatting feature using keyboard shortcuts [CTRL+B to bold, CTRL+I to italic and CTRL+U to underline] in course highlights.
* Fix - Course highlights design issues on the single course page.
* Fix - Quiz options flickering issue on live server.
* Fix - Users admin menu is not being highlighted when going to the instructors tab.
* Fix - Students and Instructors list filtering by order issue.
* Fix - Courses lists filtering by order issue.
* Fix - Approval status filter not working on instructors listing page.
* Fix - Question not being permanently deleted.
* Fix - Enrolled courses count when the order status is updated.
* Fix - Typo 'No reviewes found.' to 'No reviews found.'.

2.2.3 - 07-06-2022
-------------------
* Feature - Implemented drag and drop feature on quiz question builder.
* Enhancement - Show delete action for a quiz in progress on the quiz attempts listing page.
* Enhancement - Make addons listing page responsive.
* Enhancement - Replace full-screen loader [Spinner] with skeleton loader in backend pages.
* Fix - Show approval notice for instructors only on the account page.
* Fix - Enrolled course count on the account page.
* Fix - Instructor unable to access add new course page when WooCommerce is enabled.

2.2.2 - 31-05-2022
-------------------
* Fix - 404 Not found page while viewing Quiz Attempts

2.2.1 - 30-05-2022
-------------------
* Enhancement - Set the minimum value to 0 and the maximum to 5 on the number of decimals in a global setting.
* Fix - Translation issue on course builder backend and account page.
* Fix - Renamed "Publish" to "Published" on the course listing page tab on the backend.
* Fix - Renamed "No state founds" to "No state found" on the state option account page.
* Fix - Backend courses, orders and users listing orders by id on initial query.

2.2.0 - 26-05-2022
-------------------
* Feature - WooCommerce Integration Addon
* Enhancement - Admin can now view details of quiz attempts on the backend.
* Enhancement - Filter courses, users and orders by ascending descending order on the backend page.
* Fix - Font size of a website being overwritten by the plugin.
* Fix - Load React account page js file only on the account page.
* Fix - Addons submenu color replicated to other submenus.
* Fix - All courses count based on the draft and published courses on the backend course listing page.

2.1.0 - 17-05-2022
-------------------
* Feature - Add a course review management page on the backend.
* Enhancement - Made banner responsive on addons listing page.
* Enhancement - Made account page responsive.
* Fix - Instructor approval notification on the account page.
* Fix - Unable to create course review as a student.

2.0.9 - 04-05-2022
-------------------
* Enhancement - Added tabs to differentiate the status of the course on backend course list page.
* Fix - Renamed "No state founds" to "No state found" on the state option.
* Fix - Cancel queries being cached on the error boundary which leads to 505 errors on backend pages.
* Fix - Deprecated Message: Required parameter follows the optional parameter in PHP8.

2.0.8 - 01-05-2022
-------------------
* Enhancement - Added Masteriyo addons listing page.
* Enhancement - By default show 10 options on the dropdown of filters.
* Fix - The question answers tab not working on learn page.
* Fix - The backend sub menu not being active when clicking on the Masteriyo logo.

2.0.7 - 25-04-2022
-------------------
* Enhancement - Lazy load categories filter options.
* Enhancement - Show the completed button instead of continuing if the user has completed the course.
* Fix - Account page enrolled progress and completed courses count issue.
* Fix - Clearing all content on the editor not being updated.
* Fix - Courses, Lessons and categories featured image not being set.

2.0.6 - 12-04-2022
-------------------
* Enhancement - Added sub-categories feature.
* Enhancement - On learn page hide the user avatar menu if the user is not logged in.
* Enhancement - Added 'Users not found' message in the filters while the user doesn't exist.
* Enhancement - Added order status color on order listing.
* Fix - Course preview link being directed to the learning page.
* Fix - Deprecated Message: usort[]: Returning bool from comparison function is deprecated in PHP8.
* Fix - Deprecated Message: Required parameter follows the optional parameter in PHP8.
* Fix - Extra skeleton loading on orders listing.

2.0.5 - 05-04-2022
-------------------
* Enhancement - Adds new style for the editor on our plugin.
* Enhancement - Disable right-clicking on the self-hosted video player.
* Fix - Start course URL when the first item is a quiz in the course section.
* Fix - Highlight the submenu during page load and when changed.

2.0.4 - 30-03-2022
-------------------
* Feature - User profile image uploader on the account page.
* Enhancement - Added delete button for quiz attempts.
* Enhancement - Backend listing pages minor enhancements.
* Enhancement - Display users option and user not found message in the course instructor setting if the user is not found.

2.0.3 - 23-03-2022
-------------------
* Enhancement - Getting started steps label orientation to vertical.
* Enhancement - Replace WordPress logout with a custom logout process.
* Fix - Enroll button for guest users not working properly.
* Fix - Session key duplication.
* Fix - `__wakeup[]` should be public warning message.
* Fix - Course completion for guest users.

2.0.2 - 17-03-2022
-------------------
* Enhancement - Add user billing details while using Stripe.
* Feature - Added download material to the lesson.
* Fix - Disable Google Pay and Apple Pay while using a credit card with Stripe payment intent.
* Fix - Handle zero decimal currencies.
* Fix - Add receipt email while using a credit card with Stripe.
* Fix - License activation issue.

2.0.1 - 09-03-2022
-------------------
* Enhancement - Replaced tip-tap editor with WordPress TinyMCE Editor.
* Enhancement - Added Company Name and Company VAT Number on user profile billing details.
* Fix - Stripe was automatically disabled when the sandbox option was disabled.
* Fix - Extra space created by review notice in the header section.
* Fix - Only 10 pages are listed in the page setup of global settings.
* Tweak - Added Masteriyo Pro compatibility.

### 2.0.0 - 08-03-2022
----------------------
* Feature - Added Stripe credit card payment gateway.
* Enhancement - Added course categories archive page.
* Enhancement - Added instructor course archive page.
* Fix - String translation in the Account page.
