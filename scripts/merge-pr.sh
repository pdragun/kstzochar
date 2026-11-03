#!/bin/sh
# Merges a pull request after running this project's checks on its head, so the result doesn't rest on what anyone
# reported. Usage, from the default branch, up to date:
#
#   scripts/merge-pr.sh <number> --merge|--squash|--rebase
#
# Starting from the default branch means this script and its commands are the reviewed ones, not the pull request's.
# What the commands call, such as a task, still comes from the head, so a change to those is for the review to catch.
# The commands are scripts/check-commands, then this project's own in scripts/check-commands-own, if it has that file:
# one per line, kept by the project. Both are read from the default branch but run on the head, so:
# - a change to the own list counts from the merge after the one that brings it;
# - land a task before the line that calls it: a head from before the task fails until it is rebased;
# - a line that fails on every head, such as a renamed task's, also blocks the pull request that fixes it, which the user
#   then merges with `gh pr merge` from a plain terminal, as this script has no override;
# - a merge that changes only `skip` paths (scripts/check-paths) runs none, so don't list a command that reads them;
# - a line can't ask for the main checkout: where scripts/check-paths has `no-worktree`, every line runs there, and
#   elsewhere a run from a worktree runs them all in it;
# - a list that exists must be a committed regular file without a NUL byte, or the merge is refused, and so is a line of
#   scripts/check-paths this script doesn't know, which likewise blocks the pull request that fixes it.
#
# Never give it an allow rule: it runs what must always ask, and a pull request's code. Inside a Claude Code session it
# refuses settings that would skip the prompt (an allow rule for it, or a default mode `auto`, `bypassPermissions` or
# `dontAsk`), and scripts/merge-pr-hook.sh stops sessions in a mode that does: in `auto` switched on for the session it
# asks for the prompt, in the others it refuses.
# MERGE_PR_TIMEOUT limits each command, in seconds (default 3600). Output of the checks: .git/merge-pr.log.
set -eu

refuse() {
    printf 'merge-pr: not merged: %s\n' "$*" >&2
    if [ -n "${ran:-}" ]; then
        echo "merge-pr: this checkout and anything it runs may still have what the head left, such as installed packages, images and migrations" >&2
    fi
    exit 1
}

# The lines of a list in the commit $tip, without comments and blank lines. Only a missing file means none: a failed read
# mustn't drop a check, nor may an uncommitted file look like a list that is used. A shell's $(...) drops a NUL byte, which
# would change a command, so that is refused. One sed, so its status counts, in the C locale, so no byte stops it.
list_lines() {
    entry=$(git ls-tree "$tip" -- "$1") || refuse "can't read $1"
    case "$entry" in
        "")
            [ ! -e "$1" ] && [ ! -L "$1" ] ||
                refuse "$1 isn't committed, so it wouldn't be used; move it, or bring it in with a pull request"
            return 0
            ;;
        "100644 blob "* | "100755 blob "*) ;;
        *) refuse "$1 isn't a regular file, so it can't be read as a list" ;;
    esac
    size=$(git cat-file -s "$tip:$1") || refuse "can't read $1"
    [ "$(git cat-file blob "$tip:$1" | LC_ALL=C tr -d '\000' | wc -c)" -eq "$size" ] || refuse "$1 holds a NUL byte, or can't be read"
    text=$(git cat-file blob "$tip:$1") || refuse "can't read $1"
    cr=$(printf '\r')
    printf '%s\n' "$text" | LC_ALL=C sed -E -e "s/$cr\$//" -e '/^[[:space:]]*(#|$)/d' || refuse "can't read $1"
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
    changed=$(git diff --name-only --no-renames "$(git merge-base "refs/remotes/origin/$base" "$head")" "$head")
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

# The lists, and what changed on the default branch since the head branched (a new line, a changed task), come from
# $tip, so a head without $tip may lack what a command calls. Only a may: a real failure gets this too, and stays after
# the rebase. A head that has $tip gets nothing, as a rebase can't change it, and so does one git can't tell (status
# other than 1, such as a missing object), as the advice would be a guess.
advise_rebase() {
    # Not $code, which the caller reports after this
    behind=0
    git merge-base --is-ancestor "$tip" "$head" || behind=$?
    [ "$behind" = 1 ] || return 0
    if [ "$base" = "$default" ] || git merge-base --is-ancestor "$tip" "refs/remotes/origin/$base" 2>/dev/null; then
        printf 'merge-pr: this head lacks the latest %s, whose lists ran; if `%s` failed on what it lacks, rebase it onto %s and try again\n' "$default" "$1" "$base" >&2
    else
        printf 'merge-pr: this head lacks the latest %s, whose lists ran, and so does %s; if `%s` failed on what it lacks, this waits until %s has it\n' "$default" "$base" "$1" "$base" >&2
    fi
}

back_to_start() {
    [ -n "$switched" ] || return 0
    switched=
    # A switch would carry a check's edits onto the default branch. A status that fails counts as changed
    changes=$(git status --porcelain --untracked-files=no) || changes=unknown
    [ -z "$changes" ] || {
        echo "merge-pr: tracked files changed, so this checkout stays on the head (see git status, restore or stash them, then git switch $start, which would otherwise carry them over)" >&2
        return 1
    }
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
    trap 'exit 141' PIPE
    # A tracked file of the head would otherwise replace an ignored one here, such as .env.local, and go on the way back
    git switch --quiet --detach --no-overwrite-ignore "$head" ||
        refuse "can't switch to $head, which may track a file this checkout ignores; move that file first, as a pull would replace it too"
    ran=1
    while IFS= read -r command; do
        printf 'merge-pr: checking %s with %s\n' "${head%"${head#???????}"}" "$command"
        printf '$ %s\n' "$command" >>"$log"
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
                124) printf 'merge-pr: `%s` still ran after %ss; what it started may still run\n' "$command" "${MERGE_PR_TIMEOUT:-3600}" >&2 ;;
                137) printf 'merge-pr: `%s` was killed, by the limit of %ss or for lack of memory; what it started may still run\n' "$command" "${MERGE_PR_TIMEOUT:-3600}" >&2 ;;
                # A missing task fails at once: not what a limit or a kill means
                *) advise_rebase "$command" ;;
            esac
            [ -z "$(git status --porcelain --untracked-files=no)" ] || {
                switched=
                refuse "\`$command\` failed and changed tracked files, so this checkout stays on $head (see git status, restore or stash them, then git switch $start, which would otherwise carry them over; output in $log)"
            }
            refuse "\`$command\` failed with $code (output in $log)"
        fi
        [ -z "$(git status --porcelain --untracked-files=no)" ] || {
            switched=
            refuse "\`$command\` changed tracked files, so this checkout stays on $head (see git status, restore or stash them, then git switch $start, which would otherwise carry them over)"
        }
    done <<EOF
$commands
EOF
    back_to_start || refuse "this checkout couldn't switch back after the checks"
    trap - EXIT INT TERM HUP PIPE
    exec 9>&-
}

main() {
    [ $# = 2 ] || refuse "usage: scripts/merge-pr.sh <number> --merge|--squash|--rebase"
    number=$1
    method=$2
    case "$method" in
        --merge) allowed=mergeCommitAllowed ;;
        --squash) allowed=squashMergeAllowed ;;
        --rebase) allowed=rebaseMergeAllowed ;;
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
    # Now, not after minutes of checks. A failed read, or a ruleset that narrows it, leaves it to the merge itself
    allowance=$(gh repo view --json "$allowed" -q ".$allowed") ||
        echo "merge-pr: can't read whether $method is allowed; the merge itself will say" >&2
    [ "${allowance:-}" != false ] || refuse "this repository doesn't allow $method"
    # One commit for every list read below: another run's switch can move HEAD, and a head can empty a list. The full name,
    # as a local branch or tag called origin/<default> would otherwise win, and its lists pass for the reviewed ones
    tip=$(git rev-parse --verify --quiet "refs/remotes/origin/$default^{commit}") ||
        refuse "the fetch brought no origin/$default; check this remote's fetch refspec"
    # A second run in this checkout finds it on the first run's head, and mustn't advise a switch under it
    if [ "$(git rev-parse HEAD)" != "$tip" ]; then
        flock -n "$lock" true 2>/dev/null || refuse "another run is checking in this repository"
    fi
    [ "$(git rev-parse HEAD)" = "$tip" ] ||
        refuse "start from $default equal to origin/$default, so the script and its commands are the reviewed ones (git switch $default && git pull --ff-only; in a worktree, git switch --detach refs/remotes/origin/$default)"
    # Not --short, which says heads/<branch> when a tag has the branch's name, and git switch refuses that
    start=$(git symbolic-ref --quiet HEAD || echo "--detach $tip")
    start=${start#refs/heads/}

    # Read from the commit before any switch: not the head's copies, nor uncommitted edits, which could skip every check
    git diff --quiet "$tip" -- scripts/merge-pr.sh scripts/check-commands scripts/check-commands-own scripts/check-paths ||
        refuse "scripts/ has uncommitted changes to this script or its lists"
    commands=$(list_lines scripts/check-commands)
    [ -n "$commands" ] || refuse "scripts/check-commands is missing or holds no command"
    own=$(list_lines scripts/check-commands-own)
    [ -z "$own" ] || commands="$commands
$own"
    paths=$(list_lines scripts/check-paths)
    no_worktree=
    # The globs stay literal: they are patterns for the paths, not for the files here
    skip=
    # A line this doesn't know refuses: a slip in `no-worktree` would silently turn off the guard
    while read -r word rest; do
        known=
        case "$word" in
            "") known=1 ;;
            no-worktree) [ -n "$rest" ] || { no_worktree=1; known=1; } ;;
            # One glob: more words would each count, so `skip a # not code, so *` would skip every check
            skip) case "$rest" in
                "" | *[[:space:]]*) ;;
                *) skip="$skip
$rest"; known=1 ;;
            esac ;;
        esac
        [ -n "$known" ] ||
            refuse "scripts/check-paths has a line this script doesn't know: $word $rest. A pull request fixing it can't pass this either: the user merges that one with gh pr merge from a plain terminal"
    done <<EOF
$paths
EOF

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
        checked_base=$base
        look_at_github
        [ "$head" = "$checked" ] || refuse "a new head, $head, was pushed during the checks"
        # A retarget lands it on a base its checks and its skipped paths never saw
        [ "$base" = "$checked_base" ] || refuse "its base changed from $checked_base to $base during the checks"
    fi

    # Exactly the head commit checked above. Not --delete-branch: gh would also delete a local branch of that name, or
    # switch away from it, and can fail after the merge when a worktree has it checked out.
    # A signal now mustn't stop this script, so it can report and tidy. A handler, not ignoring it: children get the default
    # back, so Ctrl-C or a signal to the process group still ends a hung gh or git. One sent to this script alone waits
    # for the running command. A closed pipe mustn't end this script either.
    trap '' PIPE
    trap : HUP INT TERM
    gh pr merge "$number" "$method" --match-head-commit "$head" || refuse "gh pr merge failed"
    echo "merge-pr: merged pull request $number with $method in $(($(date +%s) - started))s" || true
    # Only tidying from here on: a failure leaves the merge as it is
    # A fork's branch isn't here: a branch of the same name here is another one
    if [ "$fork" != true ]; then
        # Only if it is still the merged commit: a push since then isn't lost. Full names, so a tag of that name is safe
        if ! git push --quiet --force-with-lease="refs/heads/$branch:$head" origin --delete "refs/heads/$branch" 2>/dev/null; then
            code=0
            git ls-remote --exit-code origin "refs/heads/$branch" >/dev/null 2>&1 || code=$?
            # 2 is no such branch: already gone, so nothing to say
            [ "$code" = 2 ] || echo "merge-pr: the branch $branch stays on GitHub: it has moved since the merged commit, or couldn't be deleted" >&2 || true
        fi
    fi
    if [ "$fork" != true ] && [ "$(git rev-parse --verify --quiet "refs/heads/$branch" || true)" = "$head" ]; then
        git branch --quiet -D "$branch" 2>/dev/null || true
    fi
    git fetch --quiet --prune origin || true
}

# In a function, so the whole script is parsed before the first switch can change this file
main "$@"
exit 0
