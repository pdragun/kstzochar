#!/bin/sh
# Merges a pull request after running this project's checks on its head, so the result doesn't rest on what anyone
# reported. Usage, from the default branch, up to date:
#
#   scripts/merge-pr.sh <number> --merge|--squash|--rebase [--override '<what>' '<the user'\''s words>']...
#
# --override passes one policy refusal the user decided to accept, and adds a line
# `Merge override: <head> <what> (decided by the user, <date>: "<words>")` to the pull request before the merge:
# - `check:<command>`, a command of the lists, as written there, that failed; the next ones still run. Not one that hit
#   the limit, was killed or was ended by a hangup, interrupt or terminate signal, as what it started may still run,
#   nor a line with a tab or carriage return, which can't be named;
# - `no-commands`, a project without scripts/check-commands (or an empty one); scripts/check-commands-own still runs;
# - `check-paths`, every line of scripts/check-paths this script doesn't know, each then read as `no-worktree`, the
#   stricter reading, as it may be a misspelt one; so it runs from the main checkout.
# The words go in single quotes, with each ' in them written '\'', and can't hold a double quote. An override a run
# didn't need is said and records nothing.
# Each is its own flag, given once, so passing one passes nothing else. It never passes the settings check, the lock, a linked
# worktree, uncommitted changes, a default branch other than origin's, a check that changes tracked files, changes
# requested, a missing review line, or GitHub's checks.
#
# Starting from the default branch means this script and its commands are the reviewed ones, not the pull request's.
# What the commands call, such as a task, still comes from the head, so a change to those is for the review to catch.
# The commands are scripts/check-commands, then this project's own in scripts/check-commands-own, if it has that file:
# one per line, kept by the project. Both are read from the default branch but run on the head, so:
# - a change to the own list counts from the merge after the one that brings it;
# - land a task before the line that calls it: a head from before the task fails until it is rebased;
# - a line that fails on every head, such as a renamed task's, also blocks the pull request that fixes it, which the user
#   then passes with `--override check:<the line>`;
# - a merge that changes only `skip` paths (scripts/check-paths) runs none, so don't list a command that reads them;
# - a line can't ask for the main checkout: where scripts/check-paths has `no-worktree`, every line runs there, and
#   elsewhere a run from a worktree runs them all in it;
# - `own-stack` in scripts/check-paths checks, on the head and before any command, that the compose stack its checks
#   would reset and start is this checkout's own: the head's `task app:config` prints what `docker compose config`
#   resolves (JSON with the project's `name` and the services' published ports), and the run refuses when Docker has a
#   container of that project from another folder, volumes of it but no container from this one, or one of its ports
#   held by another project. A head without the task is refused until it is rebased;
# - a list that exists must be a committed regular file without a NUL byte, or the merge is refused, and so is a line of
#   scripts/check-paths this script doesn't know, which likewise blocks the pull request that fixes it (`--override
#   check-paths`).
#
# Never give it an allow rule: it runs what must always ask, and a pull request's code. Inside a Claude Code session it
# refuses settings that would skip the prompt (an allow rule for it, or a default mode `auto`, `bypassPermissions` or
# `dontAsk`), and scripts/merge-pr-hook.sh stops sessions in a mode that does: in `auto` switched on for the session it
# asks for the prompt, in the others it refuses.
# MERGE_PR_TIMEOUT limits each command, in seconds (default 3600), and MERGE_PR_ASK_TIMEOUT each question of `own-stack`
# (default 120). Output of the checks: merge-pr.log in the checkout's
# git directory (`git rev-parse --git-path merge-pr.log`: .git/ in the main checkout, .git/worktrees/<name>/ in a worktree).
set -eu

refuse() {
    printf 'merge-pr: not merged: %s\n' "$*" >&2
    if [ -n "${ran:-}" ]; then
        echo "merge-pr: this checkout and anything it runs may still have what the head left, such as installed packages, images and migrations" >&2
    fi
    exit 1
}

# The records below are lines of <what><tab><words>
tab=$(printf '\t')
cr=$(printf '\r')

# Whether the user passed --override $1; if so, records it once for the pull request's body and says so
overridden() {
    while IFS=$tab read -r what words; do
        [ -n "$what" ] && [ "$what" = "$1" ] || continue
        case "$used" in
            *"
$1$tab"*) ;;
            *) used="$used
$1$tab$words" ;;
        esac
        printf 'merge-pr: passed by --override %s ("%s")\n' "$1" "$words" >&2
        return 0
    done <<EOF
$overrides
EOF
    return 1
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
                    # Also with an override, so a rule allowing only those runs is caught
                    # Not $method: that is the merge this run makes, and a loop variable here would replace it
                    for probe in --merge --squash --rebase; do
                        for override in "" " --override check:x y" " --override 'check:x' 'y'" " --override no-commands y" \
                            " --override 'no-commands' 'y'" " --override check-paths y" " --override 'check-paths' 'y'"; do
                            spelling="$wrapper$path 1 $probe$override"
                            # shellcheck disable=SC2254 # the rule is the pattern
                            case "$spelling" in
                                $pattern) refuse "$file allows this script without a prompt ($rule); remove the rule, or run this from a plain terminal, outside Claude Code" ;;
                            esac
                        done
                    done
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

# `own-stack`: the checks reset and start the stack the head's Taskfile names, so a head without a name per checkout,
# or two folders whose names collide, would reset another checkout's stack and delete its volumes. The head only
# answers what docker compose resolves; Docker says which folder started each container of that project.
check_own_stack() {
    # Under the lock, so a stuck Docker or task mustn't hold it for good
    asks="timeout -k 10 ${MERGE_PR_ASK_TIMEOUT:-120}"
    printf '$ task --silent app:config\n' >>"$log"
    config=$($asks task --silent app:config </dev/null 2>>"$log" 9>&-) ||
        refuse "\`task app:config\` failed on $head, so which stack its checks would reset is unknown (output in $log); if the head lacks the task, rebase it onto $default; nothing was touched"
    name=$(printf '%s' "$config" | jq -er '.name | select(type == "string" and . != "")') ||
        refuse "\`task app:config\` on $head names no compose project; nothing was touched"
    ports=$(printf '%s' "$config" | jq -r '[.services[]?.ports[]?.published | select(. != null) | tostring] | unique | .[]') ||
        refuse "can't read the ports of \`task app:config\` on $head; nothing was touched"
    owners=$($asks docker ps -a --filter "label=com.docker.compose.project=$name" --format '{{.Label "com.docker.compose.project.working_dir"}}') ||
        refuse "can't ask Docker which checkout started the stack $name; nothing was touched"
    mine=
    while IFS= read -r dir; do
        [ -n "$dir" ] || continue
        # The checkout the folder is in, so a worktree inside this one isn't taken for it; a folder that is gone is none
        [ "$(git -C "$dir" rev-parse --show-toplevel 2>/dev/null)" = "$top" ] ||
            refuse "the checks of $head would reset the stack $name, which another checkout started ($dir); nothing was touched"
        mine=1
    done <<EOF
$owners
EOF
    if [ -z "$mine" ]; then
        # A volume doesn't say its folder: without a container of this checkout, it may be another's
        volumes=$($asks docker volume ls -q --filter "label=com.docker.compose.project=$name") ||
            refuse "can't ask Docker for the volumes of the stack $name; nothing was touched"
        [ -z "$volumes" ] ||
            refuse "the checks of $head would reset the stack $name, whose volumes ($(printf '%s' "$volumes" | tr '\n' ' ' | sed 's/ $//')) no container of this checkout uses, so they may be another checkout's; if they are this one's, run task --yes reset here first; nothing was touched"
    fi
    for port in $ports; do
        users=$($asks docker ps --filter "publish=$port" --format '{{.Label "com.docker.compose.project"}}') ||
            refuse "can't ask Docker who publishes port $port; nothing was touched"
        while IFS= read -r project; do
            [ -z "$project" ] || [ "$project" = "$name" ] ||
                refuse "the stack $name would publish port $port, which the stack $project holds, so its start would fail after the reset; give this checkout another port (DEV_PORT in .devcontainer/.env); nothing was touched"
        done <<EOF
$users
EOF
    done
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
    [ -z "$own_stack" ] || check_own_stack
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
            [ -z "$(git status --porcelain --untracked-files=no)" ] || {
                switched=
                refuse "\`$command\` failed and changed tracked files, so this checkout stays on $head (see git status, restore or stash them, then git switch $start, which would otherwise carry them over; output in $log)"
            }
            # What a limit or a kill left may still run, so no override passes it. A command can also exit so itself
            # (an OOM-killed container), which only a merge from a plain terminal passes
            nofinish="no override passes that, as what it started may still run: run it again once that has ended, or if the user accepts it, they merge from a plain terminal"
            case "$code" in
                124) refuse "\`$command\` still ran after ${MERGE_PR_TIMEOUT:-3600}s (output in $log); $nofinish" ;;
                137) refuse "\`$command\` was killed, by the limit of ${MERGE_PR_TIMEOUT:-3600}s or for lack of memory (output in $log); $nofinish" ;;
                # The signals that end a command from outside (hangup, interrupt, terminate), so what it started may
                # still run. Not every code above 128: task exits 201 for a failed command, PHP 255 for a fatal error
                129 | 130 | 143) refuse "\`$command\` was ended by signal $((code - 128)) (output in $log); $nofinish" ;;
                # A missing task fails at once: not what a limit or a kill means
                *) advise_rebase "$command" ;;
            esac
            overridden "check:$command" || {
                case "$command" in
                    *"$tab"* | *"$cr"*) refuse "\`$command\` failed with $code (output in $log); its line holds a tab or a carriage return, which --override can't name, so if the user accepts that, they merge it from a plain terminal" ;;
                esac
                # As one shell word, also for a line with a quote in it
                quoted=$(printf '%s' "check:$command" | sed "s/'/'\\\\''/g")
                refuse "\`$command\` failed with $code (output in $log); if the user accepts that, run this again with --override '$quoted' '<their words>'"
            }
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
    usage="usage: scripts/merge-pr.sh <number> --merge|--squash|--rebase [--override '<what>' '<the user'\\''s words>']..."
    [ $# -ge 2 ] || refuse "$usage"
    number=$1
    method=$2
    shift 2
    overrides=
    used=
    while [ $# -gt 0 ]; do
        [ "$1" = --override ] && [ $# -ge 3 ] || refuse "$usage"
        case "$2" in
            no-commands | check-paths | check:?*) ;;
            *) refuse "unknown override $2: it passes no-commands, check-paths or check:<a command of the lists>" ;;
        esac
        case "$3" in
            *[![:space:]]*) ;;
            *) refuse "--override $2 needs the user's words" ;;
        esac
        # One line each, a tab between: the record and the matching read them so. The record quotes the words
        case "$2$3" in
            *"
"* | *"$tab"* | *"$cr"*) refuse "--override takes its name and the user's words on one line, without tabs or carriage returns" ;;
        esac
        case "$3" in
            *'"'*) refuse "--override $2: the record quotes the user's words in double quotes, so they can't hold one" ;;
        esac
        # A second one would be dropped without a word
        case "$overrides" in
            *"
$2$tab"*) refuse "--override $2 is given twice; give it once, with the user's words" ;;
        esac
        overrides="$overrides
$2$tab$3"
        shift 3
    done
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
        # In a subshell: its loops set plain variables, and one named like main's (it was $method) changed the merge
        ( settings_check ) || exit 1
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

    # Read from the commit before any switch: not the head's copies, nor uncommitted edits, which could skip every check.
    # Also the file that runs, where scripts/merge-pr.sh is a link: a diff of the link alone misses edits to its target
    self=$(readlink -f "$0")
    top=$(git rev-parse --show-toplevel)
    case $self in "$top"/*) self=${self#"$top"/} ;; *) self=scripts/merge-pr.sh ;; esac
    git diff --quiet "$tip" -- scripts/merge-pr.sh "$self" scripts/check-commands scripts/check-commands-own scripts/check-paths ||
        refuse "scripts/ has uncommitted changes to this script or its lists"
    commands=$(list_lines scripts/check-commands)
    [ -n "$commands" ] || overridden no-commands ||
        refuse "scripts/check-commands is missing or holds no command; if the user accepts merging without it, run this again with --override no-commands '<their words>'"
    own=$(list_lines scripts/check-commands-own)
    if [ -z "$commands" ]; then
        commands=$own
    elif [ -n "$own" ]; then
        commands="$commands
$own"
    fi
    paths=$(list_lines scripts/check-paths)
    no_worktree=
    own_stack=
    # The globs stay literal: they are patterns for the paths, not for the files here
    skip=
    # A line this doesn't know refuses: a slip in `no-worktree` would silently turn off the guard
    while read -r word rest; do
        known=
        case "$word" in
            "") known=1 ;;
            no-worktree) [ -n "$rest" ] || { no_worktree=1; known=1; } ;;
            own-stack) [ -n "$rest" ] || { own_stack=1; known=1; } ;;
            # One glob: more words would each count, so `skip a # not code, so *` would skip every check
            skip) case "$rest" in
                "" | *[[:space:]]*) ;;
                *) skip="$skip
$rest"; known=1 ;;
            esac ;;
        esac
        # A passed line counts as `no-worktree`, as it may be a misspelt one
        [ -n "$known" ] || { overridden check-paths && no_worktree=1; } ||
            refuse "scripts/check-paths has a line this script doesn't know: $word $rest. A pull request fixing it can't pass this either: if the user accepts that, run this again with --override check-paths '<their words>', which passes every unknown line and reads each as no-worktree, so from the main checkout"
    done <<EOF
$paths
EOF

    look_at_github
    set -f
    if only_skipped_paths; then
        set +f
        echo "merge-pr: every changed path is one the checks don't read, so none ran"
    elif [ -z "$commands" ]; then
        set +f
        echo "merge-pr: no command to run"
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

    # Read again, so an edit to the body during the checks stays, and a line a failed run left isn't added twice
    # An override the run didn't need is said, as a misspelt one would otherwise go unnoticed
    printf '%s\n' "$overrides" | while IFS=$tab read -r what words; do
        [ -z "$what" ] || case "$used" in
            *"
$what$tab"*) ;;
            *) printf 'merge-pr: --override %s was not needed, so it is not recorded\n' "$what" >&2 ;;
        esac
    done
    if [ -n "$used" ]; then
        today=$(date +%Y-%m-%d)
        # Not in a pipe: only the last command's failure would count, and an empty body would replace the real one
        json=$(gh pr view "$number" --json body) || refuse "can't read the body of pull request $number"
        body=$(printf '%s' "$json" | jq -r .body) || refuse "can't read the body of pull request $number"
        # Compared without the CRs of a body edited on GitHub's site; the body itself is written back as it was
        plain=$(printf '%s' "$body" | tr -d '\r')
        # Only the same line is left out: other words, or another day, are another decision and are recorded too
        lines=$(printf '%s\n' "$used" | while IFS=$tab read -r what words; do
            [ -n "$what" ] || continue
            line=$(printf 'Merge override: %s %s (decided by the user, %s: "%s")' "$head" "$what" "$today" "$words")
            printf '%s\n' "$plain" | grep -qxF -- "$line" || printf '%s\n' "$line"
        done)
        if [ -n "$lines" ]; then
            # In the body's own line ends
            case "$body" in
                *"$cr"*) new="${body%"$cr"}$cr
$cr
$(printf '%s\n' "$lines" | sed "s/\$/$cr/")
" ;;
                *) new="$body

$lines" ;;
            esac
            gh pr edit "$number" --body "$new" >/dev/null || refuse "can't add the Merge override lines to pull request $number, so nothing was merged"
        fi
    fi
    # Exactly the head commit checked above. Not --delete-branch: gh would also delete a local branch of that name, or
    # switch away from it, and can fail after the merge when a worktree has it checked out.
    # A signal now mustn't stop this script, so it can report and tidy. A handler, not ignoring it: children get the default
    # back, so Ctrl-C or a signal to the process group still ends a hung gh or git. One sent to this script alone waits
    # for the running command. A closed pipe mustn't end this script either.
    trap '' PIPE
    trap : HUP INT TERM
    gh pr merge "$number" "$method" --match-head-commit "$head" ||
        refuse "gh pr merge failed${used:+; the Merge override lines stay in the pull request, and a run again with the same words on the same day adds none of them twice}"
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
