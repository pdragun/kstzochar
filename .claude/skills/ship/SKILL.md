---
name: ship
description: Ship the current change through the repository's pull request workflow - branch, commit, update the plan, push, open a pull request, and merge only when asked. Use when the user says "ship it", "open a PR", "commit and PR this", or asks to merge a pull request.
---

# Ship a change

Takes the current change from the working tree to a pull request on GitHub, and merges it when the user asks. Written for any AI agent: Claude Code loads it as the `/ship` skill, other agents follow it when `AGENTS.md` points here.

Arguments: optional `merge` (also merge once the pull request is open, see step 7), and an optional short description of the change.

## 1. Read the repository's rules

- Read the workflow rules in `AGENTS.md` (or `CLAUDE.md`). They win over this skill where they differ: branch names, commit message style, required checks, merge method.
- Find the default branch: `gh repo view --json defaultBranchRef -q .defaultBranchRef.name`. Never commit to it directly.

## 2. Check the working tree

- `git status` and `git diff`. Ship only the files that belong to this change. Leave unrelated changes (IDE files such as `.idea/`, local settings, other work in progress) out of the commit and say so in the summary.
- If the change is unclear or mixes several topics, ask which files to ship before going on.
- Never commit secrets: `.env` files with real values, API keys, tokens.

## 3. Run the checks

Run the lint, static analysis and tests that `AGENTS.md` names for the files that changed, through the commands it prescribes. Fix failures that this change caused. If a check fails for another reason, or can't run, say so in the pull request and in the summary; don't hide it.

## 4. Update the plan

If the work comes from a plan document (the one `AGENTS.md` names, or `docs/plans/`, `doc/plans/`, `PLAN.md`, `TODO.md`), update it in the same change:

- Mark items done, in progress or open, with a short status, such as "Done in PR #12" (fill in the number after step 6 with a follow-up commit on the same branch).
- Record decisions, deviations and follow-up work in the matching item.
- Keep index plans consistent with the detailed ones.

## 5. Branch and commit

- If you are on the default branch, create a branch first: `git switch -c <type>/<short-topic>`, such as `feat/session-logging`, `fix/csv-decimal`, `docs/plans-status`. If you are already on a feature branch for this change, stay on it.
- Stage files by name, not with `git add -A`.
- Commit message: an imperative subject of at most about 70 characters, a blank line, then a body that says why. Add the attribution lines your harness requires.

## 6. Push and open the pull request

```bash
git push -u origin <branch>
gh pr create --base <default-branch> --title "<subject>" --body "<body>"
```

The body says what changed and why, how it was checked, and anything left out or still open. If a pull request for the branch already exists, push to it and update its title and body with `gh pr edit` instead of opening another.

Give the user the pull request URL.

## 7. Merge (only when asked)

Merge only when the user asks for it, now or in the arguments. A request to ship is not a request to merge.

- Merge method: the one `AGENTS.md` names. Otherwise check `gh repo view --json squashMergeAllowed,mergeCommitAllowed,rebaseMergeAllowed` and use the only allowed method. When several are allowed, match the history of the default branch: merge commits (`git log --merges`) mean `--merge`, subjects ending in `(#<number>)` without merge commits mean `--squash`. If the history shows neither, ask.
- If the pull request has failing or pending checks or requested changes, say so and ask before merging.
- Stacked pull requests (one whose base is another pull request's branch): GitHub closes them, without retargeting, when their base branch is deleted, and a closed pull request can't be retargeted. Before merging the lower one, retarget every pull request based on its branch to the default branch (`gh pr list --base <branch>`, then `gh pr edit <number> --base <default-branch>`), then merge the lower one, then the next. Avoid stacking when the changes can go in one pull request.
- `gh pr merge <number> --<method> --delete-branch`
- Then update the local repository: `git switch <default-branch>`, `git pull --ff-only`, `git fetch --prune`.

## 8. Report

A short summary: branch, commit, pull request URL, merge state, the checks that ran and their result, and any files left out of the commit.
