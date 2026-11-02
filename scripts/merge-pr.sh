#!/bin/sh
# Merges a pull request after running this project's checks on its head, so the result doesn't rest on what anyone
# reported. Usage, from the default branch, up to date:
#
#   scripts/merge-pr.sh <number> --merge|--squash|--rebase
#
# Starting from the default branch means this script and its commands are the reviewed ones, not the pull request's.
# What the commands call, such as a task, still comes from the head, so a change to those is for the review to catch.
#
# Never give it an allow rule: it runs what must always ask, and a pull request's code. Inside a Claude Code session it
# refuses settings that would skip the prompt, and scripts/merge-pr-hook.sh refuses sessions in a mode that does.
# MERGE_PR_TIMEOUT limits each command, in seconds (default 3600). Output of the checks: .git/merge-pr.log.
set -eu

refuse() {
    echo "merge-pr: not merged: $*" >&2
    if [ -n "${ran:-}" ]; then
        echo "merge-pr: this checkout and anything it runs may still have what the head left, such as installed packages, images and migrations" >&2
    fi
    exit 1
}

settings_check() {
    command -v jq >/dev/null || refuse "jq is needed to read the Claude Code settings"
    for file in "${CLAUDE_CONFIG_DIR:-$HOME/.claude}/settings.json" .claude/settings.json .claude/settings.local.json; do
        [ -r "$file" ] || continue
        mode=$(jq -r '.permissions.defaultMode // empty' "$file") || refuse "can't read $file"
        case "$mode" in
            auto | bypassPermissions | dontAsk) refuse "$file sets the default mode $mode, which can skip the prompt; run this from a plain terminal, outside Claude Code" ;;
        esac
        rules=$(jq -r '.permissions.allow // [] | .[] | strings' "$file") || refuse "can't read $file"
        while IFS= read -r rule; do
            case "$rule" in
                Bash) refuse "$file allows every command, also this one; run this from a plain terminal, outside Claude Code" ;;
                "Bash("*")") ;;
                *) continue ;;
            esac
            pattern=${rule#Bash(}
            pattern=${pattern%)}
            case "$pattern" in
                "~/"*) pattern="$HOME/${pattern#\~/}" ;;
            esac
            case "$pattern" in
                *merge-pr*) refuse "$file allows this script without a prompt ($rule); remove the rule, or run this from a plain terminal, outside Claude Code" ;;
                *:\*) pattern="${pattern%:\*}*" ;;
            esac
            for path in scripts/merge-pr.sh ./scripts/merge-pr.sh "$PWD/scripts/merge-pr.sh"; do
                for wrapper in "" "sh " "bash " "dash " "env " "nohup " "time " "timeout 60 "; do
                    spelling="$wrapper$path 1 --merge"
                    # shellcheck disable=SC2254 # the rule is the pattern
                    case "$spelling" in
                        $pattern) refuse "$file allows this script without a prompt ($rule); remove the rule, or run this from a plain terminal, outside Claude Code" ;;
                    esac
                done
            done
        done <<EOF
$rules
EOF
    done
}

# Sets head and branch and base; refuses what can't be merged.
look_at_github() {
    # GitHub works out mergeability only when asked, so it is often UNKNOWN right after the base moved
    for attempt in 1 2 3 4; do
        pr=$(gh pr view "$number" --json state,reviewDecision,mergeable,headRefOid,headRefName,baseRefName,isCrossRepository,isDraft,statusCheckRollup,body) ||
            refuse "can't read pull request $number"
        state=$(printf '%s' "$pr" | jq -r .state)
        [ "$state" = OPEN ] || refuse "pull request $number is $state"
        [ "$(printf '%s' "$pr" | jq -r .isDraft)" = false ] || refuse "it is a draft; mark it ready first (gh pr ready $number)"
        [ "$(printf '%s' "$pr" | jq -r .mergeable)" = UNKNOWN ] || break
        [ "$attempt" = 4 ] || sleep 2
    done
    [ "$(printf '%s' "$pr" | jq -r .reviewDecision)" != CHANGES_REQUESTED ] || refuse "changes were requested"
    case "$(printf '%s' "$pr" | jq -r .mergeable)" in
        CONFLICTING) refuse "it conflicts with its base" ;;
        UNKNOWN) refuse "GitHub hasn't worked out whether it can merge yet; try again in a moment" ;;
    esac
    head=$(printf '%s' "$pr" | jq -r .headRefOid)
    branch=$(printf '%s' "$pr" | jq -r .headRefName)
    base=$(printf '%s' "$pr" | jq -r .baseRefName)
    fork=$(printf '%s' "$pr" | jq -r '.isCrossRepository // false')
    named=$(printf '%s' "$pr" | jq -r .body | tr -d '\r' |
        sed -n -E 's/^(Reviewed|Merge without review requested): ([0-9a-f]{7,40})[[:space:]]*$/\2/p')
    reviewed=
    for sha in $named; do
        case "$head" in
            "$sha"*) reviewed=1 ;;
        esac
    done
    [ -n "$reviewed" ] || refuse "no \`Reviewed:\` or \`Merge without review requested:\` line names its head, $head"
    look_at_github_checks
}

# No checks reported and no open suite means none to wait for. A check is pending, or its result is
# known to be good, or it counts as failing, so a kind GitHub adds or a missing result doesn't pass.
look_at_github_checks() {
    checks=$(printf '%s' "$pr" | jq -r '
        [.statusCheckRollup // [] | .[]
            | (if (.conclusion // "") != "" then .conclusion else (.state // "") end) as $result
            | ((if has("status") then .status else "COMPLETED" end) != "COMPLETED" or ($result | IN("PENDING", "EXPECTED"))) as $pending
            | {name: ([.name, .context, "unnamed"] | map(select(. != null and . != "")) | first | gsub("[\r\n]"; " ")),
               pending: $pending,
               failed: (($pending | not) and ($result | IN("SUCCESS", "NEUTRAL", "SKIPPED") | not))}]
        | (map(select(.failed).name) | join(", ")), (map(select(.pending).name) | join(", "))') ||
        refuse "can't read the checks of $head"
    failed=$(printf '%s\n' "$checks" | sed -n 1p)
    pending=$(printf '%s\n' "$checks" | sed -n 2p)
    [ -z "$failed" ] || refuse "failing checks on GitHub: $failed"
    [ -z "$pending" ] || refuse "pending checks on GitHub: $pending; wait for them and run this again"
    # Actions opens a suite as soon as a workflow is triggered, before its first check run. A finished one's outcome is
    # in the checks above. Other apps' suites don't count: their runs are above too, or they never run (Claude leaves one
    # queued on every push).
    suites=$(gh api "repos/{owner}/{repo}/commits/$head/check-suites?per_page=100") ||
        refuse "can't read the check suites of $head"
    open=$(printf '%s' "$suites" | jq -r '[.check_suites[] | select(.app.slug == "github-actions" and .status != "completed")] | length') ||
        refuse "can't read the check suites of $head"
    [ "$open" = 0 ] || refuse "pending checks on GitHub: an Actions workflow of $head is still running; wait for it and run this again"
}

# Whether every path the pull request changes is one the checks don't read.
only_skipped_paths() {
    git fetch --quiet origin "$base" "refs/pull/$number/head" || refuse "can't fetch the pull request"
    git cat-file -e "$head^{commit}" 2>/dev/null ||
        refuse "its head $head can't be fetched"
    changed=$(git diff --name-only --no-renames "$(git merge-base "origin/$base" "$head")" "$head")
    [ -n "$changed" ] || return 1
    while IFS= read -r file; do
        matched=
        for glob in $skip; do
            # shellcheck disable=SC2254 # the line of check-paths is the pattern
            case "$file" in
                $glob) matched=1 ;;
            esac
        done
        [ -n "$matched" ] || return 1
    done <<EOF
$changed
EOF
}

back_to_start() {
    [ -n "$switched" ] || return 0
    switched=
    # shellcheck disable=SC2086 # start can be "--detach <sha>"
    git switch --quiet --no-overwrite-ignore $start || {
        echo "merge-pr: couldn't switch back; run: git switch $start" >&2
        return 1
    }
}

run_checks() {
    if [ "$(git rev-parse --git-dir)" != "$(git rev-parse --git-common-dir)" ] && [ -n "$no_worktree" ]; then
        refuse "the checks would take over this project's running stack from a worktree; run this in the main checkout"
    fi
    # Here, after the fetches: a background gc a fetch starts would inherit it. Children don't inherit it (9>&-), or a
    # container started by a check would hold it for good.
    exec 9>"$lock"
    flock -n 9 || refuse "another run is checking in this repository"
    [ -z "$(git status --porcelain --untracked-files=no)" ] || refuse "this checkout has uncommitted changes to tracked files, which the checks would read; nothing was touched"
    # They stay across the switch, so a file the head forgot to add can pass its checks. Blocking on them would stop on
    # every stray file.
    untracked=$(git ls-files --others --exclude-standard)
    if [ -n "$untracked" ]; then
        echo "merge-pr: untracked files take part in the checks; is any of them missing from the pull request?" >&2
        printf '%s\n' "$untracked" | sed 's/^/  /' >&2
    fi
    log=$(git rev-parse --git-path merge-pr.log)
    : >"$log"
    switched=1
    trap 'back_to_start || true' EXIT
    trap 'exit 130' INT TERM
    trap 'exit 129' HUP
    # A tracked file of the head would otherwise replace an ignored one here, such as .env.local, and go on the way back
    git switch --quiet --detach --no-overwrite-ignore "$head" ||
        refuse "can't switch to $head, which may track a file this checkout ignores; move that file first, as a pull would replace it too"
    ran=1
    while IFS= read -r command; do
        echo "merge-pr: checking ${head%"${head#???????}"} with $command"
        echo "\$ $command" >>"$log"
        code=0
        # No terminal: a command that asks would wait for ever
        timeout --foreground -k 30 "${MERGE_PR_TIMEOUT:-3600}" sh -c "$command" </dev/null >>"$log" 2>&1 9>&- || code=$?
        [ "$(git rev-parse HEAD)" = "$head" ] || {
            switched=
            refuse "this checkout left $head during \`$command\`, so the checks may have read other code"
        }
        if [ "$code" != 0 ]; then
            tail -n 15 "$log" >&2
            case "$code" in
                124) echo "merge-pr: \`$command\` still ran after ${MERGE_PR_TIMEOUT:-3600}s; what it started may still run" >&2 ;;
                137) echo "merge-pr: \`$command\` was killed, by the limit of ${MERGE_PR_TIMEOUT:-3600}s or for lack of memory; what it started may still run" >&2 ;;
            esac
            [ -z "$(git status --porcelain --untracked-files=no)" ] || {
                switched=
                refuse "\`$command\` failed and changed tracked files, so this checkout stays on $head (see git status, then git switch $start; output in $log)"
            }
            refuse "\`$command\` failed with $code (output in $log)"
        fi
        [ -z "$(git status --porcelain --untracked-files=no)" ] || {
            switched=
            refuse "\`$command\` changed tracked files, so this checkout stays on $head (see git status, then git switch $start)"
        }
    done <<EOF
$commands
EOF
    back_to_start || refuse "this checkout couldn't switch back after the checks"
    trap - EXIT INT TERM HUP
    exec 9>&-
}

main() {
    [ $# = 2 ] || refuse "usage: scripts/merge-pr.sh <number> --merge|--squash|--rebase"
    number=$1
    method=$2
    case "$method" in
        --merge | --squash | --rebase) ;;
        *) refuse "unknown merge method $method" ;;
    esac
    cd "$(git rev-parse --show-toplevel)"
    switched=
    started=$(date +%s)
    if [ -n "${CLAUDECODE:-}" ]; then
        settings_check
        echo "merge-pr: settings check passed"
    else
        echo "merge-pr: settings check skipped, not inside a Claude Code session"
    fi
    lock="$(git rev-parse --git-common-dir)/merge-pr.lock"
    git fetch --quiet origin
    default=$(gh repo view --json defaultBranchRef -q .defaultBranchRef.name) || refuse "can't read the default branch"
    # A second run in this checkout finds it on the first run's head, and mustn't advise a switch under it
    if [ "$(git rev-parse HEAD)" != "$(git rev-parse "origin/$default")" ]; then
        flock -n "$lock" true 2>/dev/null || refuse "another run is checking in this repository"
    fi
    [ "$(git rev-parse HEAD)" = "$(git rev-parse "origin/$default")" ] ||
        refuse "start from $default equal to origin/$default, so the script and its commands are the reviewed ones (git switch $default && git pull --ff-only; in a worktree, git switch --detach origin/$default)"
    start=$(git symbolic-ref --quiet --short HEAD || echo "--detach $(git rev-parse HEAD)")

    # Read from the commit before any switch: not the head's copies, nor uncommitted edits, which could skip every check
    git diff --quiet HEAD -- scripts/merge-pr.sh scripts/check-commands scripts/check-paths ||
        refuse "scripts/ has uncommitted changes to this script or its lists"
    commands=$(git show HEAD:scripts/check-commands 2>/dev/null | grep -vE '^[[:space:]]*(#|$)' || true)
    [ -n "$commands" ] || refuse "scripts/check-commands is missing or holds no command"
    paths=$(git show HEAD:scripts/check-paths 2>/dev/null || true)
    no_worktree=$(printf '%s\n' "$paths" | grep -xF no-worktree || true)
    # The globs stay literal: they are patterns for the paths, not for the files here
    skip=$(printf '%s\n' "$paths" | sed -n 's/^skip //p')

    look_at_github
    set -f
    if only_skipped_paths; then
        set +f
        echo "merge-pr: every changed path is one the checks don't read, so none ran"
    else
        set +f
        run_checks
        # The checks take minutes: a review or a push meanwhile counts
        checked=$head
        look_at_github
        [ "$head" = "$checked" ] || refuse "a new head, $head, was pushed during the checks"
    fi

    # Exactly the head commit checked above. Not --delete-branch: gh would also delete a local branch of that name, or
    # switch away from it, and can fail after the merge when a worktree has it checked out.
    gh pr merge "$number" "$method" --match-head-commit "$head" || refuse "gh pr merge failed"
    echo "merge-pr: merged pull request $number with $method in $(($(date +%s) - started))s"
    # Only tidying from here on: a failure leaves the merge as it is
    # A fork's branch isn't here: a branch of the same name here is another one
    if [ "$fork" != true ]; then
        git push --quiet origin --delete "$branch" 2>/dev/null || true
    fi
    if [ "$fork" != true ] && [ "$(git rev-parse --verify --quiet "refs/heads/$branch" || true)" = "$head" ]; then
        git branch --quiet -D "$branch" 2>/dev/null || true
    fi
    git fetch --quiet --prune origin || true
}

# In a function, so the whole script is parsed before the first switch can change this file
main "$@"
exit 0
