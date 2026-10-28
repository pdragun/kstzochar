---
name: ship
description: Ship the current change through the repository's pull request workflow - branch, commit, update the plan, push, open a pull request, review it, and merge only when asked. Use when the user says "ship it", "open a PR", "commit and PR this", or asks to merge a pull request.
---

# Ship a change

Takes the current change from the working tree to a reviewed pull request on GitHub, and merges it when the user asks. Every change goes through a pull request, and every pull request through a review (step 7). Written for any AI agent: Claude Code loads it as the `/ship` skill, other agents follow it when `AGENTS.md` points here.

Arguments: optional `merge` (also merge once the pull request is open and reviewed, see step 8), and an optional short description of the change.

## 1. Read the repository's rules

- Read the workflow rules in `AGENTS.md` (or `CLAUDE.md`). They win over this skill where they differ: branch names, commit message style, required checks, merge method.
- Find the default branch: `gh repo view --json defaultBranchRef -q .defaultBranchRef.name`. Never commit to it directly.
- Find out where the plans live. If `AGENTS.md` says plans are GitHub issues (a `## Planning` section), the issue is the plan: steps 4 to 8 say what changes. Find the issue the change belongs to: the branch name (`feat/42-…`), the conversation, or `gh issue list --search "<keywords>"`. Without one, stop and offer to write it first (`/issues`), unless the branch is `docs/*` or `chore/local-dev-*`.

## 2. Check the working tree

- `git status` and `git diff`. Ship only the files that belong to this change. Leave unrelated changes (IDE files such as `.idea/`, local settings, other work in progress) out of the commit and say so in the summary.
- If the change is unclear or mixes several topics, ask which files to ship before going on.
- Never commit secrets: `.env` files with real values, API keys, tokens.

## 3. Run the checks

Run the lint, static analysis and tests that `AGENTS.md` names for the files that changed, through the commands it prescribes. Fix failures that this change caused. If a check fails for another reason, or can't run, say so in the pull request and in the summary; don't hide it.

## 4. Update the plan

**Plans as issues.** Update the issue instead of a plan file, and commit no status to the repository:

- Tick the steps the change finishes: `gh issue view <N> --json body -q .body > <file>`, edit the checkboxes, `gh issue edit <N> --body-file <file>`.
- Add decisions, deviations and follow-up work as a comment: `gh issue comment <N> --body-file <file>`. Open new issues for follow-ups that don't belong in this one (`/issues`), and name them in the comment.
- Move knowledge that has to outlive the issue into the repository in this change: architecture facts and gotchas into `AGENTS.md`, lasting decisions into `docs/decisions/<topic>.md`, designs into `docs/design/<topic>.md`.

**Plans as files.** If the work comes from a plan document (the one `AGENTS.md` names, or `docs/plans/`, `doc/plans/`, `PLAN.md`, `TODO.md`), or does work a plan describes, update the plan in the same change:

- Find every plan item the change touches, not only the one you started from: grep the plan files for the files, classes, tests and features the change adds or edits. An item can live in a detailed plan that the index only links. Planned work done without marking its item leaves the plan looking open.
- Mark items done, in progress or open, with a short status, such as "Done in PR #12" (fill in the number after step 6 with a follow-up commit on the same branch, before the review in step 7).
- Record decisions, deviations and follow-up work in the matching item.
- Keep index plans consistent with the detailed ones.
- If the plan lives in another repository (`AGENTS.md` says where), it gets its own pull request there. Open the code pull request first (steps 5 and 6), then the plan pull request, which names the code pull request by repository and number as "In progress" while it is open. When the code pull request is merged, change that to "Done" with a commit on the plan branch before the plan pull request is merged.

## 5. Branch and commit

- If you are on the default branch, create a branch first: `git switch -c <type>/<short-topic>`, such as `feat/session-logging`, `fix/csv-decimal`, `docs/plans-status`. With plans as issues, put the issue number in it: `<type>/<N>-<short-topic>`, such as `feat/42-ics-export`. If you are already on a feature branch for this change, stay on it. If it isn't pushed yet and its name doesn't fit (no issue number where one is needed), rename it now with `git branch -m`. Never rename a branch that has an open pull request: GitHub closes the pull request and can't reopen it on the new name; keep the name and put `Closes #<N>` in the body.
- Stage files by name, not with `git add -A`.
- Commit message: an imperative subject of at most about 70 characters, a blank line, then a body that says why. Add the attribution lines your harness requires.

## 6. Push and open the pull request

With plans as issues, check first that no other pull request is on the issue: run `.claude/skills/issues/next-issues.sh prs <N>` (from the issues skill, which such repositories have) from the repository root. If it lists an open pull request on a different branch that closes it (`closes`), don't open a second one: suggest splitting the issue into sub-issues instead. A `no-closes` one on a different branch has no `Closes` line; if its branch or title shows it is on this issue, ask the user before opening another. On your own branch it is this pull request: add `Closes #<N>` to its body (below).

```bash
git push -u origin <branch>
gh pr create --base <default-branch> --title "<subject>" --body "<body>"
```

The body says what changed and why, how it was checked, and anything left out or still open. If a pull request for the branch already exists, push to it and update its title and body with `gh pr edit` instead of opening another.

With plans as issues, the body starts with `Closes #<N>`, so merging closes the issue. One issue gets one pull request (checked above).

Give the user the pull request URL.

## 7. Review the pull request

Every pull request gets a review before it is merged, also when the user asked to merge right away.

- Before the review starts, push everything and note the head commit it will read: `git rev-parse HEAD`, which must equal `gh pr view <number> --json headRefOid -q .headRefOid`. A push during the review isn't covered by it.
- Claude Code: run the `/code-review` skill on the pull request at a fixed depth, `/code-review high <number>`, and wait for its findings.
- Other agents: read the whole diff (`gh pr diff <number>`) and review it for correctness bugs: wrong logic, unhandled edge cases, broken callers, missing tests for changed behaviour.
- Fix the findings that are real and belong to this change, run the checks again (step 3), and push to the same branch. A finding you don't fix needs a decision with a reason: rejected (why it isn't a problem) or a follow-up (the issue or plan item that tracks it). When the user made the decision, end it with `(decided by the user, <date>)`.
- List in the pull request body what the review found, what was fixed, and what is left open and why. Edit the existing body, so `Closes #<N>` and the rest stay: `gh pr view <number> --json body -q .body > <file>`, edit it, `gh pr edit <number> --body-file <file>`. Open issues for follow-ups that don't belong in this change (`/issues`, with plans as issues).
- Add a plain line `Reviewed: <sha>` at the end of the body (before an attribution footer your harness requires), with the full sha of the head commit the review read (noted above) and no formatting, so a later merge, or a script, can tell whether the latest changes were reviewed. The next bullet says when it may move.
- A push after the review needs another review, unless it only updates a plan status (step 4) or every fix stays inside code the review has read: a changed condition or value, an early return or a check in an existing function, a comment, a test.
  - A fix that adds code the review hasn't read (a new function, class, file, signal, handler or setting, a changed signature or return value) or moves logic to another place gets the pull request reviewed again (in Claude Code, `/code-review high <number>` again), and its findings are handled the same way.
  - The `Reviewed:` line names the last reviewed head commit, and moves to a newer one only when every change since then needs no review of its own; until then it stays at the commit that was reviewed.
  - Head each round's findings in the body `### Round <n>`, so a later session can count the rounds. In a body without such headings, count its lists of review findings, and ask when that's unclear.
  - After three rounds, one more round is allowed without asking. Fix its findings as usual. When it found no correctness bug you fix (one you reject, with a reason, is left for the user to see, step 8) and its fixes need no review of their own, move the line to the new head and go on. Otherwise fix them and stop: ask the user whether to review again, merge, or leave the pull request open. Every further round needs the user's say.
  - If the user chooses to merge, leave the `Reviewed:` line where it is, add a plain line `Merge without review requested: <sha>` with the full sha of the head the user accepted, and merge by hand (step 8, without another review of that head).

## 8. Merge (only when asked)

Merge only when the user asks for it, now or in the arguments. A request to ship is not a request to merge.

- Merge method: the one `AGENTS.md` names. Otherwise check `gh repo view --json squashMergeAllowed,mergeCommitAllowed,rebaseMergeAllowed` and use the only allowed method. When several are allowed, match the history of the default branch: merge commits (`git log --merges`) mean `--merge`, subjects ending in `(#<number>)` without merge commits mean `--squash`. If the history shows neither, ask.
- If the body's `Reviewed:` line doesn't name the head commit (`gh pr view <number> --json headRefOid,body`), and no `Merge without review requested:` line names the head (step 7; a newer head needs the normal check from the `Reviewed:` commit), look at the changes since then (`git diff <sha> <head>`) and apply step 7: when they only fix within code the review read, check them and move the line; when they add code the review hasn't read, review again as step 7 says. After a rebase, compare only the pull request's own commits, `git range-diff <old base>..<sha> <new base>..<head>`: when they are unchanged, only move the line. Without a `Reviewed:` line, or when its commit is gone, review the whole pull request.
- In every case: if the pull request has failing or pending checks, requested changes, or review findings with no fix and no recorded decision, say so and ask before merging.
- When you merge on the `merge` argument rather than on a request the user made after seeing the review, also stop if an agent rejected a finding or made it a follow-up without the user deciding it (one marked `decided by the user` doesn't count): list those with their reasons and ask before merging.
- Stacked pull requests (one whose base is another pull request's branch): GitHub closes them, without retargeting, when their base branch is deleted, and a closed pull request can't be retargeted. Before merging the lower one, retarget every pull request based on its branch to the default branch (`gh pr list --base <branch>`, then `gh pr edit <number> --base <default-branch>`), then merge the lower one, then the next. After a squash merge, the next branch still carries the lower one's original commits: note the lower one's head before merging it (`gh pr view <lower> --json headRefOid`), then rebase the next onto the updated default branch (`git rebase --onto origin/<default-branch> <that head> <branch>`), force-push it, and move its `Reviewed:` line as above. Avoid stacking when the changes can go in one pull request.
- `gh pr merge <number> --<method> --delete-branch`
- Then update the local repository: `git switch <default-branch>`, `git pull --ff-only`, `git fetch --prune`.
- With plans as issues, check that the issue closed (`gh issue view <N> --json state -q .state`). If it is a sub-issue, look at its parent (`gh api repos/{owner}/{repo}/issues/<N>/parent --jq .number`): when none of the parent's sub-issues is open any more (`gh api repos/{owner}/{repo}/issues/<parent>/sub_issues --jq '[.[] | select(.state == "open")] | length'` is 0), close the parent with `gh issue close <parent> --comment "All sub-issues done."`.

## 9. Report

A short summary: branch, commit, pull request URL, the issue and its state, the review's findings and what became of them, merge state, the checks that ran and their result, and any files left out of the commit.
