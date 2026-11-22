#!/bin/sh
# Whether this checkout is free for a session to work in: on the branch given, with no git operation paused and
# nothing uncommitted. Run by `task checkout:check -- <branch>`, on the host, where git runs. Another branch, a
# paused operation or uncommitted changes mean another session may be working here. A detached HEAD on the default
# branch's history passes for the default branch: a merge leaves an idle worktree there. It can't see a run of the
# merge script before it switches this checkout to the head or after it switched back.
# Exit codes: 0 free, 1 another session may be using it, 2 a wrong call or git failed. Through `task` every failure
# is 201 (`task -x` passes the code on), so read the message.
usage() {
    echo "Usage: task checkout:check -- <branch> (the default branch for a checkout to start new work in)." >&2
    echo "If you gave one, Task is older than 3.44.0, which passes the task no arguments: task --version" >&2
    exit 2
}
broken() {
    echo "git failed, so the checkout couldn't be checked${1:+: $1}." >&2
    exit 2
}
busy() {
    echo "$1: another session may be using it (git worktree list)." >&2
    exit 1
}
[ $# -eq 1 ] && [ -n "$1" ] || usage
want=$1
have=$(git branch --show-current) || broken
# A rebase, bisect, merge, cherry-pick or revert another session paused, even with a clean tree
states=$(git rev-parse --git-path rebase-merge --git-path rebase-apply --git-path sequencer \
    --git-path BISECT_LOG --git-path MERGE_HEAD --git-path CHERRY_PICK_HEAD --git-path REVERT_HEAD) || broken
while IFS= read -r state; do
    [ ! -e "$state" ] || busy "This checkout is in the middle of a git operation (${state##*/})"
done <<EOF
$states
EOF
at=$want
if [ -z "$have" ]; then
    # Only here is the default branch needed. Without origin/HEAD (a clone made another way than git clone) the
    # branch asked for counts as it when it is one of the usual names and the remote has it. Full names throughout,
    # as a local branch or tag called origin/main would win over the short one
    remote=refs/remotes/origin/
    if default=$(git symbolic-ref --quiet "${remote}HEAD"); then
        case "$default" in
            "$remote"?*) ;;
            *) broken "origin/HEAD names $default, which isn't a branch of origin" ;;
        esac
    else
        case "$want" in
            main | master) default=$remote$want ;;
            *) busy "This checkout is on a detached HEAD, not $want (origin/HEAD is missing, so the default branch is unknown; git remote set-head origin --auto sets it)" ;;
        esac
    fi
    name=origin/${default#"$remote"}
    # origin/HEAD can point at a branch that is gone: a git problem, not another session, so before the comparison
    git rev-parse --verify --quiet "$default^{commit}" >/dev/null ||
        broken "$name doesn't exist (if origin/HEAD is out of date: git remote set-head origin --auto)"
    [ "$default" = "$remote$want" ] ||
        busy "This checkout is on a detached HEAD, not $want (a detached HEAD passes only for the default branch, $name by origin/HEAD)"
    # 1 means not on its history, more a git error
    git merge-base --is-ancestor HEAD "$default"
    case $? in
        0) at="a detached HEAD on $name's history (start from it: git switch --no-track -c <branch> $default)" ;;
        1) busy "This checkout is on a detached HEAD that isn't on $name's history" ;;
        *) broken ;;
    esac
elif [ "$have" != "$want" ]; then
    busy "This checkout is on $have, not $want"
fi
# --porcelain, as --short follows the user's settings (status.branch adds a line to a clean checkout).
# --no-optional-locks, so the check never makes another session's git command fail on the index lock
# A linked worktree inside this checkout (such as .claude/worktrees/<name>) is another checkout, not a change in
# this one, so each is left out by its path, whatever characters it has. --untracked-files=normal names an untracked
# folder once, not each file in it
top=$(git rev-parse --show-toplevel) || broken
worktrees=$(git worktree list --porcelain) || broken
set -- ':(top)'
while IFS= read -r line; do
    case "$line" in
        "worktree $top/"?*) set -- "$@" ":(exclude,top,literal)${line#"worktree $top/"}" ;;
    esac
done <<EOF
$worktrees
EOF
changes=$(git --no-optional-locks status --porcelain --untracked-files=normal -- "$@") || broken
if [ -n "$changes" ]; then
    echo "This checkout has uncommitted changes, so another session may be working in it:" >&2
    printf '%s\n' "$changes" >&2
    exit 1
fi
echo "On $at with nothing uncommitted."
