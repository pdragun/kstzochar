#!/bin/sh
# Fails when the Claude Code settings let a risky command run without a prompt: an allow rule in
# .claude/settings.json, .claude/settings.local.json (where "don't ask again" adds rules) or the user's settings,
# or a bypassPermissions default mode. Run by task check:permissions and, before every Bash command, by a Claude
# Code hook, so a rule added with "don't ask again" is caught before it is used. Exit 1: such a rule (in any file that can be read);
# exit 2: none, but the check can't run (no jq, a settings file that isn't JSON, an unreadable
# taskfiles/permission-probes).
#
# The probes are commands that must always ask for permission: they run arbitrary code (installing a package runs
# its scripts), change, publish or delete data, or never return. Add new shared tasks of that kind here, and a
# project's own ones to its taskfiles/permission-probes. Rules match like Claude Code's: a trailing :* or " *" is the
# prefix, then nothing or a space and anything; any other * is a plain glob. Task runs every task named on its command
# line, so a wildcard before " -- " also lets any other task run.
# A rule is read as the shell and Task read it: its shell words, unquoted, up to an unquoted separator, without
# redirections, and the names without the root namespace's ":". Each task it names is checked alone with the arguments
# after "--": a probe without arguments counts wherever a rule names its task, whatever flags, variables and arguments
# surround it, and a runner named after another task counts as a runner. A name the shell would still expand ($,
# ${...}, backticks, braces, globs, $'...' escapes) can be any task, so it counts as risky. A project's own probes are read
# the same way.
# The runners' arguments run code (any npm or composer command), so every rule for them is risky but the safe ones
# (a safe command, or it followed by a space, : or *). Task's options other than the listing ones are risky too,
# anywhere before "--": -t and -d run another Taskfile.
set -eu

cd "$(dirname "$0")/.."

probes='["task docker:exec", "task sh", "task shell", "task console -- x", "task c -- x",
    "task console -- doctrine:database:drop --force", "task c -- doctrine:database:drop --force",
    "task composer -- x", "task install", "task app:install", "task ci", "task composer:install", "task npm -- x",
    "task npm -- ci", "task npm -- install", "task node:exec", "task build", "task assets:build", "task follow",
    "task app:follow-logs", "task db:reset", "task dbr", "task app:db:reset", "task db:grant-test", "task db:shell",
    "task dbsh", "task fix-perms", "task down", "task app:down", "task dr", "task docker:rebuild", "task update",
    "task docker:update", "task dmm", "task dmmprev", "task docker:up", "task composer:install:stale",
    "task doctrine:migrations:migrate", "task doctrine:migrations:migrate:prev", "task up", "task app:up",
    "scripts/merge-pr.sh 1 --merge", "./scripts/merge-pr.sh 1 --merge"]'
runners='composer|npm|node:exec|docker:exec|sh|shell|dbsh|db:shell|console|c'
safe='["task composer -- outdated", "task composer -- validate", "task composer -- show", "task npm -- outdated",
    "task npm -- ls", "task console -- about", "task console -- debug", "task console -- lint", "task c -- about",
    "task c -- debug", "task c -- lint"]'

command -v jq >/dev/null || { echo "check-permissions: needs jq, so the allow rules aren't checked" >&2; exit 2; }
unreadable=
probes_unreadable=
# A project's own risky tasks: taskfiles/permission-probes, one command per line; a "#" at the start of a line, or
# after a space and not before a digit, starts a comment ("task issue -- #12" stays). Unreadable, it leaves the
# shared probes and says so.
if [ -e taskfiles/permission-probes ] || [ -L taskfiles/permission-probes ]; then
    if all=$(jq -cn --argjson probes "$probes" --rawfile own taskfiles/permission-probes \
        '$probes + [$own | split("\n")[] | sub("(^\\s*#|\\s#(\\D|$)).*"; "") | gsub("^\\s+|\\s+$"; "") | select(length > 0)
            | gsub("\\s+"; " ")]'); then
        probes=$all
    else
        echo "check-permissions: can't read taskfiles/permission-probes, so only the shared probes count" >&2
        probes_unreadable=1
    fi
fi
user=${CLAUDE_CONFIG_DIR:-${HOME:-}/.claude}/settings.json
allowed=
# The check of one settings file's rules. In a quoted heredoc, so a ' in it is harmless, and in a function, so
# no shell has to find the end of a $( ) around it (bash 3.2 looks for the ")" before it reads the heredoc).
rule_defs() {
    cat <<'JQ'
# The shell words of a command: [{w, x}] for a word, unquoted ('...', "...", \x, $'...'), x when the shell would
# change it further (a $ expansion, a backtick, ( ) { } or a glob outside quotes, an escape in $'...' other than \' \"
# \\), and [{op}] for a run of unquoted < > | & ; (with an unquoted fd number glued before a redirection, as in
# 2>&1). A backslash before a newline joins the lines; another newline is whitespace, so a later line still counts
def flush: if .cur != null then .out += [{w: .cur, x: .x}] | .cur = null | .x = false | .qd = false else . end;
def shell_words:
    (explode | map([.] | implode)) as $c
    | reduce range(0; $c | length) as $i ({out: [], cur: null, x: false, qd: false, q: null, esc: false, skip: false, op: false};
        $c[$i] as $ch
        | if .skip then .skip = false
          elif .esc then
              (if .q == "$" then (if IN($ch; "'", "\"", "\\") then .cur += $ch else .cur += $ch | .x = true end)
               elif $ch == "\n" then .
               elif .q == "\"" then (if IN($ch; "$", "`", "\"", "\\") then .cur += $ch else .cur += "\\" + $ch end)
               else .cur += $ch end)
              | .esc = false
          elif .q == "'" then (if $ch == "'" then .q = null else .cur += $ch end)
          elif .q == "$" then (if $ch == "'" then .q = null elif $ch == "\\" then .esc = true else .cur += $ch end)
          elif .q == "\"" then
              (if $ch == "\"" then .q = null elif $ch == "\\" then .esc = true
               elif IN($ch; "$", "`") then .cur += $ch | .x = true else .cur += $ch end)
          elif $ch == "\\" then .esc = true | .cur = (.cur // "") | .qd = true | .op = false
          elif $ch == "$" and $c[$i + 1] == "'" then .q = "$" | .skip = true | .cur = (.cur // "") | .qd = true | .op = false
          elif $ch == "'" or $ch == "\"" then .q = $ch | .cur = (.cur // "") | .qd = true | .op = false
          elif ($ch | test("\\s")) then flush | .op = false
          elif ($ch | test("[<>|&;]")) then
              (if .cur == null then .
               elif ($ch | test("[<>]")) and (.qd | not) and (.cur | test("^[0-9]+$")) then
                   .out += [{op: .cur}] | .cur = null | .x = false | .op = true
               else flush end)
              | (if .op then .out[-1].op += $ch else .out += [{op: $ch}] end) | .op = true
          else .cur = ((.cur // "") + $ch) | .op = false
              | (if IN($ch; "$", "`", "(", ")", "{", "}", "*", "?", "[") then .x = true else . end) end)
    | flush | .out;
# The words of a command's first simple command: up to an unquoted separator (| & ; and their doubles, also &<),
# without redirections (< > >> <<< >| <> &> &>> and fd forms such as 2>&1) and their targets
def command_words:
    reduce shell_words[] as $t ({ws: [], stop: false, target: false};
        if .stop then .
        elif $t.op then (if $t.op | test("^[0-9]*(&>|[<>])") then .target = true else .stop = true end)
        elif .target then .target = false
        else .ws += [$t] end)
    | .ws;
# A command as Task reads it, from its shell words (all of them unquoted), the ones before a "--" word without a
# leading $ or the root namespace's ":", a word NAME=value a variable. Gives {task, names, opaque, before, flags,
# tail}: opaque are the names the shell would still expand, as unquoted, before every word before "--", tail
# " -- ..." or "".
def parse:
    command_words as $all
    | if $all[0].w == "task" then
          $all[1:] as $w
          | ($w | map(.w) | index("--")) as $i
          | ($w[:($i // ($w | length))] | map(.raw = .w | .w |= (ltrimstr("$") | ltrimstr(":")) | select(.w | length > 0)))
              as $before
          | ($before | map(select((.w | startswith("-") or test("^[A-Za-z_][A-Za-z0-9_]*=")) | not))) as $names
          | {task: true,
             names: [$names[].w],
             opaque: [$names[] | select(.x) | .raw],
             before: [$before[].w],
             flags: [$before[].w | select(startswith("-"))],
             tail: (if $i == null then "" else " " + ($w[$i:] | map(.w) | join(" ")) end)}
      # Any other command as written, spaces collapsed: a cut at a separator would hide a probe after it
      else {task: false, text: (gsub("\\s+"; " ") | sub("^ "; "") | sub(" $"; ""))} end;
# The same command without flags and variables: "task <names> -- ...", from a parsed command
def canon_of: if .task then (["task"] + .names | join(" ")) + .tail else .text end;
JQ
}
rules_check() {
    cat <<'JQ'
# The commands a rule allows, as Claude Code matches them: a trailing :* or " *" is the prefix, any other * a glob
def rule_re:
    test("[ :]\\*$") as $prefix
    | sub("[ :]\\*$"; "") | gsub("(?<c>[.+?^${}()|\\[\\]\\\\])"; "\\\(.c)") | gsub("\\*"; ".*")
    | "^" + . + (if $prefix then "( .*)?" else "" end) + "$";
def is_safe($safe):
    . as $c | any($safe[]; . as $s | $c == $s
        or ($c | startswith($s) and IN($c[($s | length):($s | length) + 1]; " ", ":", "*")));

  (select(.permissions.defaultMode? == "bypassPermissions")
      | "every command  <-  defaultMode bypassPermissions in \(input_filename)"),
  ((.permissions.allow[]? | strings) as $rule
      | if $rule == "Bash" then "every command  <-  Bash in \(input_filename)"
        elif ($rule | startswith("Bash(")) then
            ($rule | ltrimstr("Bash(") | rtrimstr(")")) as $raw
            | ($raw | parse) as $p
            | ($p | canon_of) as $cmd
            # Each task the rule runs, alone with the arguments after "--": "task lint npm -- x" runs "task npm -- x"
            | (if $p.task then [$p.names[] | "task " + . + $p.tail] else [] end) as $singles
            | ($raw | sub("[ :]\\*$"; "") | select(test("^\\s*\\**\\s*$"))
                  | "every command  <-  \($rule) in \(input_filename)"),
              ([$cmd] + $singles | unique[] | rule_re as $re | $probes[] | select(test($re))
                  | "\(.)  <-  \($rule) in \(input_filename)"),
              # A probe without arguments counts wherever a rule names its task, whatever surrounds it
              ($probes[] | select(test("^task [^ ]+$")) | select(ltrimstr("task ") as $n | any($p.names[]?; . == $n))
                  | "\(.)  <-  \($rule) in \(input_filename)"),
              # A wildcard before "--", also in a variable or after a separator or redirection, as written
              ($raw | gsub("\\s+"; " ") | sub("^ "; "") | select(startswith("task ") and (split(" -- ")[0] | contains("*")))
                  | "task <any other task>  <-  \($rule) in \(input_filename)"),
              # Task's options anywhere before "--", other than the listing ones
              ($p | select(.task) | .flags[] | select(test("^(--list|--list-all|-l|-a|--version)$") | not)
                  | "\($raw) (Task options)  <-  \($rule) in \(input_filename)"),
              ($singles[] | select(test("^task (" + $runners + ")( |$)") and (is_safe($safe) | not))
                  | "\(.) (its arguments run code)  <-  \($rule) in \(input_filename)"),
              # A name the shell would still expand: it can be any task
              ($p | select(.task) | .opaque[]
                  | "\($raw) (the shell expands the task name \(.))  <-  \($rule) in \(input_filename)"),
              # A wildcard before "--" in the rule's words (quoted "task", a redirection before it)
              ($p | select(.task and any(.before[]; contains("*")))
                  | "task <any other task>  <-  \($rule) in \(input_filename)")
        else empty end)
JQ
}
defs=$(rule_defs)
# The probes as the rules are read, once per run
probes=$(jq -cn --argjson probes "$probes" "$defs"' $probes | map(parse | canon_of)') || {
    echo "check-permissions: can't read the probes" >&2
    exit 2
}
program="$defs$(rules_check)"
for file in .claude/settings.json .claude/settings.local.json "$user"; do
    [ -f "$file" ] || continue
    # One file at a time, so a broken one doesn't hide the rules of the others
    found=$(jq -r --argjson probes "$probes" --arg runners "$runners" --argjson safe "$safe" "$program" "$file") || {
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
[ -z "$probes_unreadable" ] || exit 2
