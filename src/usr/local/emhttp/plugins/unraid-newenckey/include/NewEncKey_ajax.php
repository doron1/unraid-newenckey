<?php
/* NewEncKey_ajax.php
 * AJAX backend for the NewEncKey UI.
 * Installed to: /usr/local/emhttp/plugins/unraid-newenckey/include/NewEncKey_ajax.php
 *
 * Set $DEBUG = true to write verbose progress entries to syslog.
 * Set $DEBUG = false for production (no syslog output).
 *
 * POST param 'action':
 *   dry_run  --- run script with --dry-run; returns drive list for UI confirmation step
 *   confirm  --- run script with --yes;     returns final result
 *
 * Both actions receive the full key payload and create+shred their own
 * temp files independently --- no state is held between calls.
 */

$DEBUG = false;   // flip to false to silence syslog
$script = '/usr/local/sbin/unraid-newenckey';
$myname = 'newenckey-ui';

ob_start();

/* ------ Debug logger -------- */
function nek_log(string $msg): void {
    global $DEBUG;
    if ($DEBUG) {
        openlog($myname, LOG_PID, LOG_USER);
        syslog(LOG_DEBUG, $msg);
        closelog();
    }
}

/* ------ Temp file helpers --------- */
function nek_make_temp(string $data): string|false {
    $path = tempnam('/tmp', 'nek_');
    if ($path === false) return false;
    if (file_put_contents($path, $data) === false) { @unlink($path); return false; }
    chmod($path, 0600);
    return $path;
}

function nek_shred(?string $path): void {
    if (!$path || !file_exists($path)) return;
    exec('shred -u ' . escapeshellarg($path) . ' 2>/dev/null');
    if (file_exists($path)) @unlink($path);
}

/* ------ Response helpers ------ */
function nek_respond(array $payload): void {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function nek_fail(string $msg): void {
    nek_log("FAIL: $msg");
    nek_respond(['success' => false, 'output' => $msg]);
}

/* ------ Shutdown safety net ------- */
$cur_tmp = null;
$new_tmp = null;
register_shutdown_function(function() use (&$cur_tmp, &$new_tmp) {
    if ($cur_tmp) nek_shred($cur_tmp);
    if ($new_tmp) nek_shred($new_tmp);
});

/* ------ Main logic ------ */

nek_log("Request received. METHOD=" . ($_SERVER['REQUEST_METHOD'] ?? '?'));

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    nek_fail('Invalid request method --- POST required.');
}

/* ------ Read and validate POST params ------ */
$action        = $_POST['action']        ?? '';
$cur_mode      = $_POST['cur_mode']      ?? '';
$new_mode      = $_POST['new_mode']      ?? '';
$cur_pass      = $_POST['cur_pass']      ?? '';
$cur_file_b64  = $_POST['cur_file_b64']  ?? '';
$new_pass      = $_POST['new_pass']      ?? '';
$new_file_b64  = $_POST['new_file_b64']  ?? '';

nek_log("action=$action cur_mode=$cur_mode new_mode=$new_mode");

if (!in_array($action, ['dry_run', 'confirm'], true)) nek_fail("Invalid action: '$action'");
if (!in_array($cur_mode, ['passphrase', 'keyfile'], true)) nek_fail("Invalid cur_mode: '$cur_mode'");
if (!in_array($new_mode, ['passphrase', 'keyfile'], true)) nek_fail("Invalid new_mode: '$new_mode'");

$valid_charset_re = '/^[a-zA-Z0-9~!@#$%^&*\-=+_ ]*$/';

if ($cur_mode === 'passphrase') {
    if (strlen($cur_pass) > 512)                               nek_fail('Current passphrase exceeds 512-character limit.');
    if ($cur_pass !== '' && !preg_match($valid_charset_re, $cur_pass)) nek_fail('Current passphrase contains invalid characters.');
}
if ($new_mode === 'passphrase') {
    if (strlen($new_pass) > 512)                               nek_fail('New passphrase exceeds 512-character limit.');
    if ($new_pass !== '' && !preg_match($valid_charset_re, $new_pass)) nek_fail('New passphrase contains invalid characters.');
}

/* ------ Decode key file data if needed ------- */
if ($cur_mode === 'keyfile') {
    nek_log("Decoding cur_file_b64 (" . strlen($cur_file_b64) . " b64 chars)");
    if ($cur_file_b64 === '') nek_fail('No current key file data received.');
    $cur_file_data = base64_decode($cur_file_b64, true);
    if ($cur_file_data === false) nek_fail('Current key file data is not valid base64.');
    nek_log("cur key file decoded: " . strlen($cur_file_data) . " bytes");
}
if ($new_mode === 'keyfile') {
    nek_log("Decoding new_file_b64 (" . strlen($new_file_b64) . " b64 chars)");
    if ($new_file_b64 === '') nek_fail('No new key file data received.');
    $new_file_data = base64_decode($new_file_b64, true);
    if ($new_file_data === false) nek_fail('New key file data is not valid base64.');
    nek_log("new key file decoded: " . strlen($new_file_data) . " bytes");
}

/* ------ Verify CLI script ------- */
nek_log("Checking script: $script");
if (!file_exists($script))   nek_fail("Script not found: $script --- is the plugin installed correctly?");
if (!is_executable($script)) nek_fail("Script is not executable: $script");

/* ------ Write temp files -------- */
$cur_tmp = nek_make_temp($cur_mode === 'passphrase' ? $cur_pass : $cur_file_data);
if ($cur_tmp === false) nek_fail('Failed to create temp file for current key.');
nek_log("cur_tmp=$cur_tmp");

$new_tmp = nek_make_temp($new_mode === 'passphrase' ? $new_pass : $new_file_data);
if ($new_tmp === false) nek_fail('Failed to create temp file for new key.');
nek_log("new_tmp=$new_tmp");

/* ------ Verify temp file contents (no trailing newline) ------ */
// Log byte count and hex of last 4 bytes so we can confirm no 0x0a appended
foreach (['cur' => $cur_tmp, 'new' => $new_tmp] as $label => $path) {
    $bytes = file_get_contents($path);
    $len   = strlen($bytes);
    $tail  = $len > 0
        ? implode(' ', array_map(fn($b) => sprintf('%02x', ord($b)), str_split(substr($bytes, -4))))
        : '(empty)';
    nek_log("$label key temp: $len bytes, last 4 bytes hex: [$tail]" .
            ($len > 0 && ord($bytes[-1]) === 0x0a ? ' *** TRAILING NEWLINE DETECTED ***' : ' OK'));
}

/* ------ Build and execute command ------ */
// Flag is always first argument, per CLI spec
$flag = ($action === 'dry_run') ? '--dry-run' : '--yes';
$cmd_parts = [$script, $flag, $cur_tmp, $new_tmp];
nek_log("Executing: $script $flag <cur_tmp> <new_tmp>");

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$proc = proc_open($cmd_parts, $descriptors, $pipes);
if (!is_resource($proc)) {
    nek_fail('Failed to launch unraid-newenckey process.');
}

fclose($pipes[0]);
$stdout    = stream_get_contents($pipes[1]); fclose($pipes[1]);
$stderr    = stream_get_contents($pipes[2]); fclose($pipes[2]);
$exit_code = proc_close($proc);

nek_log("Process exited with code $exit_code");
if ($stdout !== '') nek_log("stdout: " . trim($stdout));
if ($stderr !== '') nek_log("stderr: " . trim($stderr));

/* ------ Shred temp files immediately ------ */
nek_shred($cur_tmp); $cur_tmp = null; nek_log("cur_tmp shredded");
nek_shred($new_tmp); $new_tmp = null; nek_log("new_tmp shredded");

/* ------ Assemble display output ----------- */
$display = rtrim($stdout);
if ($stderr !== '') {
    if ($display !== '') $display .= "\n";
    foreach (explode("\n", rtrim($stderr)) as $line) {
        $display .= "[stderr] $line\n";
    }
    $display = rtrim($display);
}
if ($display === '') $display = '(script produced no output)';

$success = ($exit_code === 0);
nek_log("action=$action success=" . ($success ? 'true' : 'false'));

/* ------ Respond -------- */
// For dry_run, pass 'confirmed' flag so UI knows to show the confirm panel
nek_respond([
    'success'  => $success,
    'output'   => $display,
    'action'   => $action,
]);
