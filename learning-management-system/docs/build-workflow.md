# Build Testable ZIP Workflow

This document explains how to use the automated build workflow to create testable ZIP files for Masteriyo.

## Overview

The **Build Testable ZIP** workflow automatically creates downloadable plugin ZIP files that can be used for testing. It supports both automatic builds (triggered by pull requests) and manual builds (on-demand for any branch).

## Automatic Builds (Pull Requests)

### When It Triggers

The workflow automatically runs when:
- **A new pull request is opened** against the `develop` branch (always builds)
- **New commits are pushed** to an existing PR **with `#build` in the commit message**
- A closed pull request is reopened

### Triggering a Build on Commit

To trigger a build when pushing new commits to an existing PR, include `#build` anywhere in your commit message:

```bash
# This WILL trigger a build (without makepot - faster)
git commit -m "fix: resolve checkout issue #build"
git commit -m "#build - test the new feature"
git commit -m "Update payment gateway #build"

# This WILL trigger a build WITH translation file generation
git commit -m "feat: add new strings #build:makepot"
git commit -m "#build:makepot - need to test translations"

# This will NOT trigger a build
git commit -m "fix: typo in comment"
git commit -m "docs: update README"
```

**Build Triggers:**
- `#build` - Standard build (skips makepot generation for faster builds)
- `#build:makepot` - Build with translation file generation (use when you've added/modified translatable strings)

**Why this approach?**
- Saves CI/CD resources by not building every commit
- You control when builds happen
- Skipping makepot saves ~30-60 seconds per build
- Useful when making multiple small commits before testing

### What Happens

1. **Build Process:**
   - Checks out the PR branch
   - Installs dependencies (Node.js, PHP, Composer, Yarn)
   - Runs optimized build which:
     - Builds frontend assets
     - Builds blocks
     - Generates translation files (only if requested)
     - Creates the release ZIP

2. **Artifact Upload:**
   - Uploads the ZIP as a GitHub Actions artifact
   - Names it: `masteriyo-pr-{number}` (e.g., `masteriyo-pr-123`)
   - Keeps it for 30 days

3. **PR Comment:**
   - Posts a comment on the PR with a **direct download link**
   - Updates the comment on subsequent builds (doesn't spam)

### How to Download (For PR Builds)

**Option 1: Direct Download Link (Recommended)**
1. Go to the pull request page
2. Find the comment titled "📦 Build Artifact Ready"
3. Click the **direct download link** - download starts immediately
4. Install in WordPress via Plugins → Add New → Upload Plugin

**Option 2: From Actions Tab**
1. Go to **Actions** tab in GitHub
2. Find the workflow run for your PR
3. Scroll to **Artifacts** section at the bottom
4. Click to download

## Manual Builds (On-Demand)

### When to Use Manual Builds

Use manual builds when you need to:
- Test a feature branch that doesn't have a PR yet
- Create a build from a specific commit
- Generate a release candidate for testing
- Build from any branch for debugging purposes

### How to Trigger a Manual Build

1. **Navigate to Actions:**
   - Go to your GitHub repository
   - Click the **Actions** tab at the top

2. **Select the Workflow:**
   - In the left sidebar, find and click **"Build Testable ZIP"**

3. **Run the Workflow:**
   - Click the **"Run workflow"** button (top right, blue button)
   - A dropdown will appear with options

4. **Configure the Build:**

   **Branch to build from:**
   - Enter the branch name you want to build
   - Default: `develop`
   - Examples: `feature/new-feature`, `bugfix/issue-123`, `release/1.8.0`

   **Custom artifact name (optional):**
   - Leave empty for automatic naming
   - Or enter a custom name (e.g., `release-candidate-v1.8.0`)

   **Generate translation POT file:**
   - Check this box if you need translation files in the build
   - Default: unchecked (faster builds)
   - Enable for release candidates or when testing translations

5. **Start the Build:**
   - Click the green **"Run workflow"** button at the bottom of the dropdown

6. **Monitor Progress:**
   - The workflow will appear at the top of the runs list
   - Click on it to see real-time progress
   - Build typically takes 5-10 minutes

7. **Download the Build:**
   - Once complete, scroll to the **Artifacts** section at the bottom
   - Click the artifact name to download

## What Gets Built

The workflow runs an optimized build process, which includes:

1. **Dependency Installation:**
   - `yarn install` - Install Node.js dependencies
   - `composer install` - Install PHP dependencies

2. **Asset Building:**
   - `yarn build` - Build frontend React/TypeScript assets
   - `yarn build:blocks` - Build WordPress blocks

3. **Translation (Optional):**
   - `composer run makepot` - Generate translation (POT) file
   - Only runs when explicitly requested (via `#build:makepot` or workflow input)
   - Skipped by default for faster PR builds

4. **ZIP Creation:**
   - `gulp release` - Package everything into a distributable ZIP
   - Excludes development files (see `.distignore`)

## Direct Download Links

### How It Works

All PR builds now include **direct download links** in PR comments using the nightly.link service:

- Click the link and download starts immediately
- No need to navigate to GitHub Actions artifacts page
- Works with all modern browsers
- Free and reliable service

### Fallback Option

If the direct link doesn't work:
- Use the "Alternative Download" link in the PR comment
- Or go to Actions → Find your workflow run → Download from Artifacts section

## Build Performance

### Expected Build Times

- **Standard build (no makepot):** 9-11 minutes
- **Build with makepot:** 10-12 minutes

### Speed Tips

1. Use `#build` instead of `#build:makepot` for faster PR testing
2. Only use `#build:makepot` when you've modified translatable strings
3. Consider manual builds for release candidates with translations enabled

## Troubleshooting

### Build Failed

**Check the logs:**
1. Go to the failed workflow run
2. Click on the failed job
3. Expand each step to see error messages

**Common issues:**
- **Syntax errors:** Check for PHP or JavaScript syntax errors in your code
- **Missing dependencies:** Ensure `package.json` and `composer.json` are up to date
- **Build errors:** Review TypeScript/webpack errors in the build logs

### Can't Find the Artifact

**Verify:**
1. Workflow completed successfully (green checkmark)
2. Look at the **Artifacts** section at the bottom of the workflow run page
3. If not there, check if the build failed in the "Find Release ZIP" step

### Direct Download Link Not Working

**Try:**
1. Wait a few seconds and try again (nightly.link needs time to process)
2. Use the "Alternative Download" link in the PR comment
3. Download directly from GitHub Actions artifacts section

## Use Case Examples

### Example 1: Quick Feature Test
```bash
# Working on a feature, ready to test
git commit -m "feat: implement new checkout flow #build"
git push
# → Build runs, PR comment appears with direct download link
# → Click link, download starts, install in WordPress
```

### Example 2: Testing Translations
```bash
# Added new translatable strings
git commit -m "feat: add user profile strings #build:makepot"
git push
# → Build runs with translation generation
```

### Example 3: Manual Build for Release Candidate
1. Go to Actions → Build Testable ZIP → Run workflow
2. Branch: `develop`
3. Custom name: `rc-v1.8.0`
4. Generate POT file: ✓ (checked)
5. Result: WordPress-ready zip with translations

## Best Practices

### For Developers

1. **Use standard builds for testing:** `#build` is faster, use it for most PR testing
2. **Use makepot when needed:** Only use `#build:makepot` when you've modified strings
3. **Clean commits:** Ensure your code is ready before triggering a build
4. **Test the artifact:** Always download and test before requesting review

### For QA Team

1. **Use direct download links:** Fastest way to get PR builds
2. **Test immediately:** Artifacts are kept for 30 days, test while fresh
3. **Document testing:** Note which artifact version you tested in your reports
4. **Report issues:** Include the artifact name in bug reports

### For Release Managers

1. **Use manual builds with makepot:** Enable translation generation for RCs
2. **Consistent naming:** Use `rc-v1.8.0` format for release candidates
3. **Archive builds:** Download and keep important RC builds locally
4. **Final verification:** Test RC before creating actual release

## Workflow File Location

The workflow is defined in:
```
.github/workflows/pr-build-zip.yml
```

## Support

If you encounter issues with the build workflow:

1. Check this documentation first
2. Review the workflow logs in GitHub Actions
3. Ask in the development team channel
4. Create an issue if it's a workflow bug
