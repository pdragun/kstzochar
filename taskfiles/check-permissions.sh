#!/bin/sh
# Fails when the Claude Code settings let a risky command run without a prompt: an allow rule in
# .claude/settings.json, .claude/settings.local.json (where "don't ask again" adds rules) or the user's settings,
# or a bypassPermissions default mode. Run by task check:permissions and, before every Bash command, by a Claude
# Code hook, so a rule added with "don't ask again" is caught before it is used. Exit 1: such a rule (in any file that can be read);
# exit 2: none, but the check can't run (no jq, a settings file that isn't JSON).
#
# The probes are commands that must always ask for permission: they run arbitrary code (installing a package runs
# its scripts), change, publish or delete data, or never return. Add new tasks of that kind here. Rules match like
# Claude Code's: a trailing :* or " *" is the prefix, then nothing or a space and anything; any other * is a plain
# glob. Task runs every task named on its command line, so a wildcard before " -- " also lets any other task run.
# The runners' arguments run code (any npm or composer command), so every rule for them is risky but the safe ones
# (a safe command, or it followed by a space, : or *). Task's options other than the listing ones are risky too:
# -t and -d run another Taskfile.
set -eu

cd "$(dirname "$0")/.."

probes='["task docker:exec", "task sh", "task shell", "task console -- x", "task c -- x",
    "task console -- doctrine:database:drop --force", "task c -- doctrine:database:drop --force",
    "task composer -- x", "task install", "task app:install", "task ci", "task composer:install", "task npm -- x",
    "task npm -- ci", "task npm -- install", "task node:exec", "task build", "task assets:build", "task follow",
    "task app:follow-logs", "task db:reset", "task dbr", "task app:db:reset", "task db:grant-test", "task db:shell",
    "task dbsh", "task fix-perms", "task down", "task app:down", "task dr", "task docker:rebuild", "task update",
    "task docker:update", "task dmm", "task dmmprev", "task docker:up", "task composer:install:stale",
    "task doctrine:migrations:migrate", "task doctrine:migrations:migrate:prev", "task up", "task app:up"]'
runners='composer|npm|node:exec|docker:exec|sh|shell|dbsh|db:shell|console|c'
safe='["task composer -- outdated", "task composer -- validate", "task composer -- show", "task npm -- outdated",
    "task npm -- ls", "task console -- about", "task console -- debug", "task console -- lint", "task c -- about",
    "task c -- debug", "task c -- lint"]'

command -v jq >/dev/null || { echo "check-permissions: needs jq, so the allow rules aren't checked" >&2; exit 2; }
user=${CLAUDE_CONFIG_DIR:-${HOME:-}/.claude}/settings.json
allowed=
unreadable=
for file in .claude/settings.json .claude/settings.local.json "$user"; do
    [ -f "$file" ] || continue
    # One file at a time, so a broken one doesn't hide the rules of the others
    found=$(jq -r --argjson probes "$probes" --arg runners "$runners" --argjson safe "$safe" '
        (select(.permissions.defaultMode? == "bypassPermissions")
            | "every command  <-  defaultMode bypassPermissions in \(input_filename)"),
        ((.permissions.allow[]? | strings) as $rule
            | if $rule == "Bash" then "every command  <-  Bash in \(input_filename)"
              elif ($rule | startswith("Bash(")) then
                  ($rule | ltrimstr("Bash(") | rtrimstr(")")) as $cmd
                  | ($cmd | test("[ :]\\*$")) as $prefix
                  | ($cmd | sub("[ :]\\*$"; "")) as $stem
                  | ($stem
                      | gsub("(?<c>[.+?^${}()|\\[\\]\\\\])"; "\\\(.c)") | gsub("\\*"; ".*")
                      | "^" + . + (if $prefix then "( .*)?" else "" end) + "$") as $re
                  | ($stem | select(test("^\\s*\\**\\s*$")) | "every command  <-  \($rule) in \(input_filename)"),
                    ($probes[] | select(test($re)) | "\(.)  <-  \($rule) in \(input_filename)"),
                    ($cmd | select(startswith("task ") and (split(" -- ")[0] | contains("*")))
                      | "task <any other task>  <-  \($rule) in \(input_filename)"),
                    ($cmd | select(test("^task -") and (test("^task (--list|--list-all|-l|-a|--version)( |$)") | not))
                      | "\(.) (Task options)  <-  \($rule) in \(input_filename)"),
                    ($cmd | select(test("^task (" + $runners + ")( |$)")
                          and ((. as $c | any($safe[]; . as $s | $c == $s
                              or ($c | startswith($s) and IN($c[($s | length):($s | length) + 1]; " ", ":", "*")))) | not))
                      | "\(.) (its arguments run code)  <-  \($rule) in \(input_filename)")
              else empty end)' "$file") || {
        unreadable="$unreadable $file"
        continue
    }
    allowed="$allowed${found:+$found
}"
done
if [ -n "$allowed" ]; then
    echo "Allow rules that let risky commands run without a prompt (remove them):" >&2
    printf '%s' "$allowed" | sort -u >&2
    exit 1
fi
if [ -n "$unreadable" ]; then
    echo "check-permissions: can't read the allow rules of$unreadable" >&2
    exit 2
fi
