# Build Testable ZIP Workflow

This document explains how to use the automated build workflow to create testable ZIP files for Masteriyo Pro.

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
   - Runs `yarn release` which:
     - Builds frontend assets
     - Builds blocks
     - Generates translation files
     - Creates the release ZIP

2. **Artifact Upload:**
   - Uploads the ZIP as a GitHub Actions artifact
   - Names it: `masteriyo-pro-pr-{number}` (e.g., `masteriyo-pro-pr-123`)
   - Keeps it for 30 days

3. **PR Comment:**
   - Posts a comment on the PR with download instructions
   - Updates the comment on subsequent builds (doesn't spam)

### How to Download (For PR Builds)

**Option 1: From PR Comment**
1. Go to the pull request page
2. Find the comment titled "📦 Build Artifact Ready"
3. Click the download link
4. Scroll to "Artifacts" section at the bottom
5. Download the ZIP file

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
   - Examples: `feature/new-feature`, `bugfix/issue-123`, `release-pro/3.0.4`

   **Custom artifact name (optional):**
   - Leave empty for automatic naming
   - Or enter a custom name (e.g., `release-candidate-v3.0.4`)

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

## Artifact Naming Convention

The workflow uses smart naming based on how it was triggered:

### For Pull Requests
```
masteriyo-pro-pr-{number}
```
**Examples:**
- `masteriyo-pro-pr-42`
- `masteriyo-pro-pr-123`

### For Manual Builds (with custom name)
```
{your-custom-name}
```
**Examples:**
- `release-candidate-v3.0.4`
- `hotfix-build-urgent`
- `qa-testing-build`

### For Manual Builds (without custom name)
```
masteriyo-pro-{branch}-{timestamp}
```
**Examples:**
- `masteriyo-pro-develop-20251129-143025`
- `masteriyo-pro-feature-new-payment-20251129-150530`
- `masteriyo-pro-bugfix-checkout-issue-20251129-162245`

Note: Branch names are sanitized (special characters replaced with hyphens)

## Use Case Examples

### Example 1: Working on a PR with Multiple Commits
**Scenario:** You're working on a feature and making multiple commits, but only want to build when ready to test.

**Steps:**
```bash
# Open PR - automatic build runs
git checkout -b feature/new-checkout-flow
git push origin feature/new-checkout-flow
# Create PR on GitHub → Build runs automatically

# Make several commits without triggering builds
git commit -m "fix: update validation logic"
git push
# → No build (no #build keyword)

git commit -m "refactor: improve code structure"
git push
# → No build

git commit -m "docs: add comments"
git push
# → No build

# Ready to test? Trigger a build
git commit -m "feat: complete checkout implementation #build"
git push
# → Build runs! ZIP available for testing
```

### Example 2: Quick Test After Bug Fix
**Scenario:** Fixed a critical bug and need to test immediately.

**Steps:**
```bash
git commit -m "#build - urgent: fix payment gateway crash"
git push
# → Build runs immediately
```

### Example 3: Testing a Feature Branch (Manual Build)
**Scenario:** You have a feature branch and want to test it before creating a PR.

**Steps:**
1. Go to Actions → Build Testable ZIP → Run workflow
2. Branch: `feature/stripe-integration`
3. Custom name: (leave empty)
4. Result: `masteriyo-pro-feature-stripe-integration-20251129-143025.zip`

### Example 4: Creating a Release Candidate (Manual Build)
**Scenario:** Preparing for a release and need a build for final testing.

**Steps:**
1. Go to Actions → Build Testable ZIP → Run workflow
2. Branch: `develop`
3. Custom name: `release-candidate-v3.0.4`
4. Result: `release-candidate-v3.0.4.zip`

### Example 5: Quick Develop Build (Manual Build)
**Scenario:** Need the latest develop branch for testing.

**Steps:**
1. Go to Actions → Build Testable ZIP → Run workflow
2. Branch: (leave default `develop`)
3. Custom name: (leave empty)
4. Result: `masteriyo-pro-develop-20251129-143025.zip`

### Example 6: Testing a Bugfix (Manual Build)
**Scenario:** Need to test a specific bugfix branch.

**Steps:**
1. Go to Actions → Build Testable ZIP → Run workflow
2. Branch: `bugfix/MAS-2935-php-8-4-compatibility`
3. Custom name: `php84-compatibility-test`
4. Result: `php84-compatibility-test.zip`

## What Gets Built

The workflow runs the `yarn release` command, which includes:

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

## Build Artifacts

### Retention Period
All artifacts are kept for **30 days** and then automatically deleted.

### File Size
Typical ZIP size: **50-80 MB** (varies based on dependencies and assets)

### What's Included
The ZIP contains:
- ✅ Compiled JavaScript/CSS
- ✅ PHP source code
- ✅ Vendor dependencies
- ✅ Translation files
- ✅ Templates
- ✅ Addons

### What's Excluded
Based on `.distignore`, these are not included:
- ❌ Source TypeScript/React files
- ❌ Node modules (dev dependencies)
- ❌ Build configuration files
- ❌ Git files and directories
- ❌ Development scripts

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

### ZIP File is Too Large

**What to check:**
- Ensure `.distignore` is properly excluding development files
- Check if unnecessary files are being included in the `release/` directory

### Manual Trigger Button Not Visible

**Possible reasons:**
- You don't have write permissions to the repository
- The workflow file is not on the default branch yet
- Browser cache issue (try hard refresh)

## Best Practices

### For Developers

1. **Test before PR:** Use manual builds to test your feature branch before opening a PR
2. **Clean commits:** Ensure your code is ready before triggering a build
3. **Meaningful names:** Use descriptive custom artifact names for important builds
4. **Check artifacts:** Always verify the build succeeded and download the artifact

### For QA Team

1. **Use PR builds:** For testing pull requests, use the automatic builds from PR comments
2. **Consistent naming:** When requesting manual builds, use consistent naming conventions
3. **Document testing:** Note which artifact version you tested
4. **Report issues:** Include the artifact name in bug reports

### For Release Managers

1. **Release candidates:** Use custom names like `rc-v3.0.4` for release candidates
2. **Final testing:** Create a build from the release branch before tagging
3. **Archive important builds:** Download and archive release candidate artifacts locally
4. **Verify checksums:** Compare artifact contents with your local build if needed

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

## Related Documentation

- [Masteriyo Release Process](../README.md#release-process)
- [Development Setup](../README.md#development-setup)
- [Contributor Guide](./contributor-guide.md)
