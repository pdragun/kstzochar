#!/bin/sh
# Claude Code PreToolUse hook: refuses running scripts/merge-pr.sh in a session whose mode can skip the prompt. The
# session's settings don't show its mode, the hook's input does. Other commands pass, also without jq, so this can't lock
# anyone out. It reads the command as written and refuses when in doubt: a part naming the script counts as running it
# unless it starts with a command on the list below, or the command pipes into a shell. So a name built from a variable, a
# command substitution or alias inside such a command, or a session started in another directory gets past it. A message
# naming the script after a newline, `;`, `&` or `|` is refused: write it to a file first.
input=$(cat)
case "$input" in
    *merge-pr.sh*) ;;
    *) exit 0 ;;
esac
command -v jq >/dev/null || {
    echo "scripts/merge-pr.sh: jq is needed to read this session's permission mode" >&2
    exit 2
}
runs=$(printf '%s' "$input" | jq -r '.tool_input.command // empty' | tr ';&|\n' '\n\n\n\n' | grep -F merge-pr.sh | while read -r first _; do
    first=${first#\(}
    case "$first" in
        ls | test | \[ | cat | less | head | tail | grep | rg | wc | file | stat | git | gh | echo | printf | chmod | diff) ;;
        *) echo yes ;;
    esac
done)
# As one line, so a pipe on a line of its own counts
printf '%s' "$input" | jq -r '.tool_input.command // empty' | tr '\n' ' ' |
    grep -qE 'merge-pr\.sh.*\|[[:space:]]*([^[:space:]]*/)?(sh|bash|dash|zsh)([[:space:];&|)>]|$)' && runs=yes
[ -n "$runs" ] || exit 0
mode=$(printf '%s' "$input" | jq -r '.permission_mode // empty')
case "$mode" in
    default | acceptEdits) exit 0 ;;
esac
echo "This command looks like it runs scripts/merge-pr.sh, which always asks for permission, and this session is in mode '${mode:-unknown}', which can skip the prompt. Ask the user to run it themselves (\`! scripts/merge-pr.sh ...\`, from the project's checkout), or from a session in the default mode. Where settings set a mode that skips the prompt or allow this script, only a plain terminal works. If it only mentions the script in a message or a command this hook doesn't know, put the message in a file, or name the file another way." >&2
exit 2
