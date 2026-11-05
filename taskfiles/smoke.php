<?php

// Requests the routes of taskfiles/smoke-routes from the running app and fails when one answers with another status
// than the listed one. Run by task smoke in the php container, from the project's root, against Caddy's plain-HTTP
// site (php:80; SMOKE_URL, read in the container, overrides it for a direct run, as the tests do). One
// "<status> <path> [login]" per line, such as "200 /", "302 /account" or "200 /account login"; a "#" at the start of
// a line, or after a space, starts a comment. Redirects aren't followed,
// so a redirect where a page should be fails. A "login" route is requested with the session cookie that the
// project's taskfiles/smoke-login prints (an executable run here, which creates its user if it is missing), so the
// controller behind the firewall runs; it gets SMOKE_LOGIN_TIMEOUT seconds (120). Without the file only / is requested,
// and only a 5xx or no answer fails.
// Exit 1: a route failed or smoke-login did; exit 2: the file can't be read or has a line that isn't a route.

declare(strict_types=1);

const ROUTES = 'taskfiles/smoke-routes';
const LOGIN = 'taskfiles/smoke-login';

function out(string $line): void
{
    fwrite(\STDOUT, $line."\n");
}

function err(string $line): void
{
    fwrite(\STDERR, 'smoke: '.$line."\n");
}

/** @return list<array{status: int, path: string, login: bool}>|null A 1xx is no route: PHP's wrapper skips those answers */
function routes(): ?array
{
    if (!file_exists(ROUTES) && !is_link(ROUTES)) {
        return null;
    }
    $text = @file_get_contents(ROUTES);
    if (false === $text) {
        err("can't read ".ROUTES);
        exit(2);
    }
    $text = preg_replace('/^\xEF\xBB\xBF/', '', $text); // an editor's UTF-8 BOM
    $routes = [];
    $bad = false;
    foreach (preg_split('/\r?\n/', $text) as $i => $raw) {
        $line = trim(preg_replace('/(^|\s)#.*/', '', $raw));
        if ('' === $line) {
            continue;
        }
        $words = preg_split('/\s+/', $line);
        if (!preg_match('/^[2-5]\d\d$/', $words[0]) || !isset($words[1]) || !str_starts_with($words[1], '/')
            || count($words) > 3 || (isset($words[2]) && 'login' !== $words[2])) {
            err(sprintf('%s:%d is not "<status> <path> [login]": %s', ROUTES, $i + 1, $line));
            $bad = true;
            continue;
        }
        $routes[] = ['status' => (int) $words[0], 'path' => $words[1], 'login' => isset($words[2])];
    }
    if ($bad) {
        exit(2);
    }
    if ([] === $routes) {
        err(ROUTES.' lists no route');
        exit(2);
    }

    return $routes;
}

/**
 * The Cookie header that smoke-login prints, or null when it fails or is still running after SMOKE_LOGIN_TIMEOUT
 * seconds (120, as long as a request may take for a cold cache; read in the container, as SMOKE_URL is). Its stdout
 * and stderr are pipes read until it exits, not until they close, so a child it leaves running (a server it started)
 * doesn't hang the task, or a merge that runs it. On a timeout only the script itself is killed.
 */
function login(): ?string
{
    if (!is_file(LOGIN) || !is_executable(LOGIN)) {
        err(ROUTES.' has login routes, but '.LOGIN.' is missing or not executable');

        return null;
    }
    $setting = getenv('SMOKE_LOGIN_TIMEOUT');
    $timeout = false !== $setting && preg_match('/^[1-9]\d{0,5}$/', $setting) ? (int) $setting : 120;
    if (false !== $setting && '' !== $setting && (string) $timeout !== $setting) {
        err("SMOKE_LOGIN_TIMEOUT=$setting is no number of seconds, using $timeout");
    }
    $process = proc_open(['./'.LOGIN], [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (false === $process) {
        err("can't run ".LOGIN);

        return null;
    }
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $output = '';
    // A chunk read from stdout goes to the cookie, from stderr through to the task's stderr
    $take = static function ($pipe) use (&$output, $pipes): void {
        $chunk = (string) fread($pipe, 8192);
        if ($pipe === $pipes[1]) {
            $output .= $chunk;
        } else {
            fwrite(\STDERR, $chunk);
        }
    };
    $deadline = hrtime(true) + $timeout * 1_000_000_000;
    $status = proc_get_status($process);
    while ($status['running'] && hrtime(true) < $deadline) {
        $read = array_values(array_filter([$pipes[1], $pipes[2]], static fn ($pipe) => !feof($pipe)));
        $none = null;
        if ([] === $read) {
            usleep(20000); // closed both but hasn't exited yet
        } elseif (stream_select($read, $none, $none, 0, 100000)) {
            array_walk($read, $take);
        }
        $status = proc_get_status($process);
    }
    if (!$status['running']) {
        foreach ([$pipes[1], $pipes[2]] as $pipe) { // what it printed before it exited; a child may keep writing
            for ($i = 0; $i < 8 && !feof($pipe); ++$i) {
                $take($pipe);
            }
        }
    }
    fclose($pipes[1]);
    fclose($pipes[2]);
    if ($status['running']) {
        proc_terminate($process, 9);
        proc_close($process);
        err(LOGIN." was still running after $timeout s, stopped it");

        return null;
    }
    proc_close($process);
    if ($status['signaled']) {
        err(LOGIN.' was killed by signal '.$status['termsig']);

        return null;
    }
    if (0 !== $status['exitcode']) {
        err(LOGIN." exited with {$status['exitcode']}");

        return null;
    }
    $cookie = trim(preg_replace('/^cookie:/i', '', trim($output))); // the header's value, with or without its name
    if ('' === $cookie || preg_match('/[\r\n]/', $cookie) || !str_contains($cookie, '=')) {
        err(LOGIN.' printed no cookie (one line, such as PHPSESSID=…)');

        return null;
    }

    return $cookie;
}

/** @return array{status: int, location: ?string, title: ?string}|null null when nothing answered */
function request(string $base, string $path, ?string $cookie): ?array
{
    $headers = ['User-Agent: smoke'];
    if (null !== $cookie) {
        $headers[] = 'Cookie: '.$cookie;
    }
    $context = stream_context_create(['http' => [
        'method' => 'GET',
        'header' => $headers,
        'follow_location' => 0,
        'ignore_errors' => true,
        'timeout' => 120, // the first request after a change rebuilds the dev cache
    ]]);
    $body = @file_get_contents($base.$path, false, $context);
    $response = http_get_last_response_headers(); // PHP 8.4; the images have 8.5
    if (false === $body || !$response || !preg_match('#^HTTP/\S+ (\d{3})#', $response[0], $m)) {
        return null;
    }
    $location = null;
    foreach ($response as $header) {
        if (preg_match('/^location:\s*(.*)$/i', $header, $l)) {
            $location = trim($l[1]);
        }
    }
    $title = null;
    if (preg_match('#<title[^>]*>(.*?)</title>#is', $body, $t)) {
        // Without mbstring, which not every image has: the first 200 characters of UTF-8, or the bytes
        $title = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($t[1]))));
        if (strlen($title) > 200) { // bytes; a UTF-8 title is cut at 200 characters only
            if (!preg_match('//u', $title)) {
                $title = substr($title, 0, 200).'…';
            } elseif (preg_match('/^.{200}(?=.)/su', $title, $cut)) {
                $title = $cut[0].'…';
            }
        }
    }

    return ['status' => (int) $m[1], 'location' => $location, 'title' => $title];
}

$base = rtrim(getenv('SMOKE_URL') ?: 'http://php', '/');
$routes = routes();
$listed = null !== $routes;
$routes ??= [['status' => 0, 'path' => '/', 'login' => false]]; // without the file, any status below 500 passes

$cookie = null;
$loginFailed = false;
foreach ($routes as $route) {
    if ($route['login']) {
        $cookie = login();
        $loginFailed = null === $cookie;
        break;
    }
}

$failed = 0;
foreach ($routes as $route) {
    $name = $route['path'].($route['login'] ? ' (login)' : '');
    if ($route['login'] && $loginFailed) {
        out("FAIL $name: no session, smoke-login failed");
        ++$failed;
        continue;
    }
    $got = request($base, $route['path'], $route['login'] ? $cookie : null);
    if (null === $got) {
        out("FAIL $name: no answer from $base");
        ++$failed;
        continue;
    }
    $detail = (null !== $got['location'] ? ' -> '.$got['location'] : '')
        .(null !== $got['title'] && $got['status'] >= 400 ? ' "'.$got['title'].'"' : '');
    $ok = $listed ? $got['status'] === $route['status'] : $got['status'] < 500;
    if ($ok) {
        out("ok   $name: {$got['status']}$detail");
    } else {
        out(sprintf('FAIL %s: %d, expected %s%s', $name, $got['status'], $listed ? $route['status'] : 'below 500', $detail));
        ++$failed;
    }
}

if (!$listed) {
    out('Only / was checked: list the routes to check in '.ROUTES.' ("<status> <path> [login]" per line).');
}
if ($failed > 0) {
    err("$failed of ".count($routes).' routes failed');
    exit(1);
}
