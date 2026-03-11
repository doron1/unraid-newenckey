<?PHP
/* NewEncKey.php - UI for changing Unraid array encryption key
 * (c) 2026 - CC BY-SA 4.0 (same as unraid-newenckey)
 * Installed to: /usr/local/emhttp/plugins/unraid-newenckey/NewEncKey.php
 */
?>
<link type="text/css" rel="stylesheet" href="/webGui/styles/jquery.ui.css">

<style>
/* ------ Scope everything under our wrapper ----- */
#nek-wrap {
  max-width: 800px;
}

/* ------ Warning banner ------ */
#nek-wrap .nek-warning {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: rgba(220, 130, 30, 0.15);
  border: 1px solid #c8791a;
  border-radius: 4px;
  padding: 10px 14px;
  margin-bottom: 20px;
  font-size: 0.93em;
  line-height: 1.55;
}
#nek-wrap .nek-warning .fa {
  color: #e0901a;
  font-size: 1.25em;
  margin-top: 1px;
  flex-shrink: 0;
}

/* ------ Sections (fieldsets) ------- */
#nek-wrap .nek-section {
  margin-bottom: 20px;
}
#nek-wrap .nek-section fieldset {
  padding: 14px 18px 12px;
}
#nek-wrap .nek-section legend {
  font-weight: bold;
  font-size: 1.05em;
  padding: 0 6px;
}

/* ------ Mode tabs --------------------- */
/*
 * Plain <span> elements toggled by JS.  No :checked + label tricks.
 * Explicit background/color so they are visible in both light and
 * dark Unraid themes - no CSS variables that may not be defined.
 */
#nek-wrap .nek-tabs {
  display: flex;
  margin-bottom: 16px;
  border-bottom: 2px solid #888;
}
#nek-wrap .nek-tab {
  padding: 6px 20px;
  cursor: pointer;
  border: 1px solid #888;
  border-bottom: none;
  border-radius: 4px 4px 0 0;
  margin-right: 4px;
  background: #555;
  color: #ccc;
  font-size: 0.93em;
  user-select: none;
  transition: background 0.12s, color 0.12s;
}
#nek-wrap .nek-tab:hover {
  background: #666;
  color: #fff;
}
#nek-wrap .nek-tab.nek-tab-active {
  background: #f5a623;
  color: #fff;
  border-color: #c8791a;
  font-weight: bold;
}

/* ------ Input rows ------------------ */
#nek-wrap .nek-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
}
#nek-wrap .nek-row .nek-lbl {
  min-width: 185px;
  text-align: right;
  flex-shrink: 0;
}
#nek-wrap .nek-row input[type="password"],
#nek-wrap .nek-row input[type="text"] {
  flex: 1;
  min-width: 0;
  font-family: monospace;
}
#nek-wrap .nek-row .nek-eye {
  flex-shrink: 0;
}

/* file picker row */
#nek-wrap .nek-file-grp {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  min-width: 0;
}
#nek-wrap .nek-file-grp .nek-filename {
  flex: 1;
  min-width: 0;
  font-family: monospace;
  font-size: 0.9em;
  opacity: 0.75;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ------ Validation errors ------ */
#nek-wrap .nek-err {
  color: #e53935;
  font-size: 0.85em;
  padding-left: 193px;
  margin-top: -6px;
  margin-bottom: 8px;
}

/* ------ Confirmation panel ------ */
#nek-wrap .nek-confirm-box {
  margin-top: 16px;
  border: 2px solid #f5a623;
  border-radius: 4px;
  padding: 14px 18px;
}
#nek-wrap .nek-confirm-title {
  font-weight: bold;
  font-size: 1.05em;
  margin: 0 0 6px 0;
  color: #f5a623;
}
#nek-wrap .nek-confirm-hint {
  margin: 0 0 12px 0;
  font-size: 0.93em;
}
#nek-wrap .nek-confirm-actions {
  display: flex;
  gap: 10px;
}

/* ------ Action row ------------------ */
#nek-wrap .nek-actions {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-top: 4px;
}
#nek-spinner { display: none; }

/* ------ Output log ------------------ */
#nek-output-wrap {
  margin-top: 20px;
  /* display is controlled entirely by inline style on the element;
     never set display:none here - a CSS rule beats JS style.display='' */
}
#nek-output {
  /* Inherit page background and text so it follows light/dark theme */
  background: inherit;
  color: inherit;
  font-family: monospace;
  font-size: 0.87em;
  padding: 12px 14px;
  border: 1px solid rgba(128,128,128,0.4);
  border-radius: 4px;
  min-height: 80px;
  max-height: 340px;
  overflow-y: auto;
  white-space: pre-wrap;
  word-break: break-all;
  box-shadow: inset 0 1px 4px rgba(0,0,0,0.15);
}
/* Hidden file-tree dialog container - not used */
#nek-status {
  margin-top: 8px;
  font-weight: bold;
  font-size: 0.95em;
}
#nek-status.nek-ok    { color: #43a047; }
#nek-status.nek-error { color: #e53935; }
#nek-status.nek-busy  { color: #f5a623; }
</style>

<div id="nek-wrap">

  <!-- Intro -->
  <p>This tool will help you change the unlock key on your encrypted drives.</p>

  <!-- Warning -->
  <div class="nek-warning">
    <i class="fa fa-exclamation-triangle"></i>
    <span>
      <strong>Important:</strong> This tool does <em>not</em> save your new key anywhere.
      Before proceeding, make sure your new passphrase or key file is safely backed up -
      <em>not</em> on the encrypted array. If you lose the new key, your data cannot be recovered.
    </span>
  </div>

  <!-------- Current key ------->
  <div class="nek-section">
    <fieldset>
      <legend>Current Key</legend>

      <div class="nek-tabs">
        <span class="nek-tab nek-tab-active" id="cur-tab-passphrase" onclick="nekSetMode('cur','passphrase')">
          <i class="fa fa-keyboard-o"></i>&nbsp; Passphrase
        </span>
        <span class="nek-tab" id="cur-tab-keyfile" onclick="nekSetMode('cur','keyfile')">
          <i class="fa fa-file-o"></i>&nbsp; Key File
        </span>
      </div>
      <input type="hidden" id="cur_mode" value="passphrase">

      <div id="cur-panel-passphrase">
        <div class="nek-row">
          <span class="nek-lbl">Current passphrase:</span>
          <input type="password" id="cur_pass" autocomplete="new-password" maxlength="512" placeholder="Enter current passphrase" tabindex="1" oninput="nekCheckChars(this,'cur_pass_charset_err')" onkeydown="nekTabWrap(event)">
          <input type="button" class="nek-eye" value="Show" onclick="nekToggleShow('cur_pass',this)" tabindex="-1">
        </div>
        <div class="nek-err" id="cur_pass_err" style="display:none">Please enter the current passphrase.</div>
        <div class="nek-err" id="cur_pass_charset_err" style="display:none">Invalid character. Allowed: a-z A-Z 0-9 ~ ! @ # $ % ^ &amp; * - = + _ and space.</div>
      </div>

      <div id="cur-panel-keyfile" style="display:none">
        <div class="nek-row">
          <span class="nek-lbl">Current key file:</span>
          <div class="nek-file-grp">
            <span class="nek-filename" id="cur_file_name">No file chosen</span>
            <input type="file" id="cur_file_input" style="display:none" onchange="nekFileChosen('cur')">
            <input type="button" value="Choose File&hellip;" onclick="document.getElementById('cur_file_input').click()" tabindex="-1">
          </div>
        </div>
        <div class="nek-err" id="cur_file_err" style="display:none">Please choose the current key file.</div>
      </div>

    </fieldset>
  </div>

  <!----- New key ----->
  <div class="nek-section">
    <fieldset>
      <legend>New Key</legend>

      <div class="nek-tabs">
        <span class="nek-tab nek-tab-active" id="new-tab-passphrase" onclick="nekSetMode('new','passphrase')">
          <i class="fa fa-keyboard-o"></i>&nbsp; Passphrase
        </span>
        <span class="nek-tab" id="new-tab-keyfile" onclick="nekSetMode('new','keyfile')">
          <i class="fa fa-file-o"></i>&nbsp; Key File
        </span>
      </div>
      <input type="hidden" id="new_mode" value="passphrase">

      <div id="new-panel-passphrase">
        <div class="nek-row">
          <span class="nek-lbl">New passphrase:</span>
          <input type="password" id="new_pass" autocomplete="new-password" maxlength="512" placeholder="Enter new passphrase" tabindex="2" oninput="nekCheckChars(this,'new_pass_charset_err')" onkeydown="nekTabWrap(event)">
          <input type="button" class="nek-eye" value="Show" onclick="nekToggleShow('new_pass',this)" tabindex="-1">
        </div>
        <div class="nek-err" id="new_pass_err" style="display:none">Please enter the new passphrase.</div>
        <div class="nek-err" id="new_pass_charset_err" style="display:none">Invalid character. Allowed: a-z A-Z 0-9 ~ ! @ # $ % ^ &amp; * - = + _ and space.</div>

        <div class="nek-row">
          <span class="nek-lbl">Confirm new passphrase:</span>
          <input type="password" id="new_pass2" autocomplete="new-password" maxlength="512" placeholder="Re-enter new passphrase" tabindex="3" oninput="nekCheckChars(this,'new_pass2_charset_err')" onkeydown="nekTabWrap(event)">
          <input type="button" class="nek-eye" value="Show" onclick="nekToggleShow('new_pass2',this)" tabindex="-1">
        </div>
        <div class="nek-err" id="new_pass2_err" style="display:none">Passphrases do not match.</div>
        <div class="nek-err" id="new_pass2_charset_err" style="display:none">Invalid character. Allowed: a-z A-Z 0-9 ~ ! @ # $ % ^ &amp; * - = + _ and space.</div>
      </div>

      <div id="new-panel-keyfile" style="display:none">
        <div class="nek-row">
          <span class="nek-lbl">New key file:</span>
          <div class="nek-file-grp">
            <span class="nek-filename" id="new_file_name">No file chosen</span>
            <input type="file" id="new_file_input" style="display:none" onchange="nekFileChosen('new')">
            <input type="button" value="Choose File&hellip;" onclick="document.getElementById('new_file_input').click()" tabindex="-1">
          </div>
        </div>
        <div class="nek-err" id="new_file_err" style="display:none">Please choose the new key file.</div>
      </div>

    </fieldset>
  </div>

  <!------ Action ------->
  <div class="nek-actions">
    <input type="button" id="nek-btn" value="Change Encryption Key" onclick="nekSubmit()">
    <span id="nek-spinner"><i class="fa fa-spinner fa-spin"></i>&nbsp;Working&hellip;</span>
  </div>

  <!-------- Confirmation panel (shown after dry-run) -------->
  <div id="nek-confirm-wrap" style="display:none">
    <div class="nek-confirm-box">
      <p class="nek-confirm-title"><i class="fa fa-exclamation-circle"></i>&nbsp; Confirm key change</p>
      <p class="nek-confirm-hint">The drives listed below will have their encryption key replaced. This cannot be undone - make sure your new key is safely backed up before continuing.</p>
      <div class="nek-confirm-actions">
        <input type="button" id="nek-confirm-btn" value="Yes, update the key on these drives" onclick="nekConfirm()">
        <input type="button" value="Cancel" onclick="nekCancel()">
      </div>
    </div>
  </div>

  <!------ Output ------->
  <div id="nek-output-wrap" style="display:none">
    <div id="nek-output"></div>
    <div id="nek-status"></div>
  </div>

</div><!-- #nek-wrap -->

<script>
// ------ Mode switching ---------------
function nekSetMode(section, mode) {
  document.getElementById(section + '_mode').value = mode;

  var allModes = ['passphrase', 'keyfile'];
  for (var i = 0; i < allModes.length; i++) {
    var tab   = document.getElementById(section + '-tab-'   + allModes[i]);
    var panel = document.getElementById(section + '-panel-' + allModes[i]);
    if (allModes[i] === mode) {
      tab.classList.add('nek-tab-active');
      panel.style.display = '';
    } else {
      tab.classList.remove('nek-tab-active');
      panel.style.display = 'none';
    }
  }
  nekClearErrors(section);
}

// ------ Show / hide passphrase ---------
function nekToggleShow(fieldId, btn) {
  var f = document.getElementById(fieldId);
  if (f.type === 'password') {
    f.type = 'text';
    btn.value = 'Hide';
  } else {
    f.type = 'password';
    btn.value = 'Show';
  }
}

// ------ File chosen via native picker ------
// Read the file as binary, store as base64 on the input element itself.
// This avoids the File object going stale if the user waits before submitting.
function nekFileChosen(section) {
  var input = document.getElementById(section + '_file_input');
  var label = document.getElementById(section + '_file_name');
  var file  = input.files[0];

  if (!file) {
    label.textContent = 'No file chosen';
    input._b64 = null;
    return;
  }

  label.textContent = file.name + ' (' + file.size + ' bytes)';
  label.style.opacity = '1';

  var reader = new FileReader();
  reader.onload = function(e) {
    // e.target.result is "data:<mime>;base64,<data>" - strip the prefix
    var b64 = e.target.result.split(',')[1];
    input._b64 = b64;
  };
  reader.onerror = function() {
    label.textContent = 'Error reading file';
    input._b64 = null;
  };
  reader.readAsDataURL(file);
}

// ------ Charset validation (mirrors VALID_CHARS in the CLI script) ---------
//   a-z A-Z 0-9 ~ ! @ # $ % ^ & * - = + _ <space>
var NEK_VALID_RE = /^[a-zA-Z0-9~!@#$%^&*\-=+_ ]*$/;

function nekCheckChars(input, errId) {
  var el = document.getElementById(errId);
  if (!NEK_VALID_RE.test(input.value)) {
    el.style.display = '';
  } else {
    el.style.display = 'none';
  }
}

// ------ Tab wrap: cycle only through currently visible text/password fields ------
//
// All candidate fields in logical order. We filter to only those whose
// containing panel is currently visible, then wrap within that set.
var NEK_TAB_FIELDS = ['cur_pass', 'new_pass', 'new_pass2'];

function nekTabWrap(e) {
  if (e.key !== 'Tab') return;

  // Build list of currently visible fields
  var visible = NEK_TAB_FIELDS.filter(function(id) {
    var el = document.getElementById(id);
    if (!el) return false;
    // Walk up to the nearest panel div and check its display
    var panel = el.closest('[id$="-panel-passphrase"],[id$="-panel-keyfile"]');
    if (panel && panel.style.display === 'none') return false;
    return true;
  });

  if (visible.length === 0) return;

  var currentId = document.activeElement ? document.activeElement.id : '';
  var idx = visible.indexOf(currentId);

  if (!e.shiftKey) {
    // Forward Tab: if we're on the last visible field, wrap to first
    if (idx === visible.length - 1) {
      e.preventDefault();
      document.getElementById(visible[0]).focus();
    }
    // Otherwise let the browser handle it naturally
  } else {
    // Shift-Tab: if we're on the first visible field, wrap to last
    if (idx === 0) {
      e.preventDefault();
      document.getElementById(visible[visible.length - 1]).focus();
    }
  }
}

// ------ ANSI -†’ HTML coloring -------
// Handles the _R (red) and _T (reset) escape sequences from the CLI script.
// Must HTML-escape first so script output can't inject tags.
function nekAnsiToHtml(text) {
  // 1. HTML-escape so < > & in output are safe
  var escaped = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
  // 2. Substitute ANSI red + reset with spans
  return escaped
    .replace(/\x1b\[31m/g, '<span style="color:#e53935">')
    .replace(/\x1b\[0m/g,  '</span>');
}

// ------ Clear validation errors --------
function nekClearErrors(section) {
  var ids = [
    section + '_pass_err',
    section + '_pass_charset_err',
    section + '_pass2_err',
    section + '_pass2_charset_err',
    section + '_file_err'
  ];
  for (var i = 0; i < ids.length; i++) {
    var el = document.getElementById(ids[i]);
    if (el) el.style.display = 'none';
  }
}

// ------ Validate form ------------------
function nekValidate() {
  var ok = true;
  var curMode = document.getElementById('cur_mode').value;
  var newMode = document.getElementById('new_mode').value;

  nekClearErrors('cur');
  nekClearErrors('new');

  if (curMode === 'passphrase') {
    var cv = document.getElementById('cur_pass').value;
    if (!cv) {
      document.getElementById('cur_pass_err').style.display = '';
      ok = false;
    } else if (!NEK_VALID_RE.test(cv)) {
      document.getElementById('cur_pass_charset_err').style.display = '';
      ok = false;
    }
  } else {
    var curInput = document.getElementById('cur_file_input');
    if (!curInput._b64) {
      document.getElementById('cur_file_err').style.display = '';
      ok = false;
    }
  }

  if (newMode === 'passphrase') {
    var p1 = document.getElementById('new_pass').value;
    var p2 = document.getElementById('new_pass2').value;
    if (!p1) {
      document.getElementById('new_pass_err').style.display = '';
      ok = false;
    } else if (!NEK_VALID_RE.test(p1)) {
      document.getElementById('new_pass_charset_err').style.display = '';
      ok = false;
    } else if (p1 !== p2) {
      document.getElementById('new_pass2_err').style.display = '';
      ok = false;
    } else if (!NEK_VALID_RE.test(p2)) {
      document.getElementById('new_pass2_charset_err').style.display = '';
      ok = false;
    }
  } else {
    var newInput = document.getElementById('new_file_input');
    if (!newInput._b64) {
      document.getElementById('new_file_err').style.display = '';
      ok = false;
    }
  }

  return ok;
}

// ------ Collect current payload from form ------
function nekPayload() {
  var curMode = document.getElementById('cur_mode').value;
  var newMode = document.getElementById('new_mode').value;
  return {
    cur_mode:     curMode,
    new_mode:     newMode,
    cur_pass:     (curMode === 'passphrase') ? document.getElementById('cur_pass').value : '',
    cur_file_b64: (curMode === 'keyfile')    ? (document.getElementById('cur_file_input')._b64 || '') : '',
    new_pass:     (newMode === 'passphrase') ? document.getElementById('new_pass').value : '',
    new_file_b64: (newMode === 'keyfile')    ? (document.getElementById('new_file_input')._b64 || '') : ''
  };
}

// ------ Shared AJAX runner ------
function nekRun(action, payload, onSuccess) {
  var btn        = document.getElementById('nek-btn');
  var confirmBtn = document.getElementById('nek-confirm-btn');
  var spinner    = document.getElementById('nek-spinner');
  var outWrap    = document.getElementById('nek-output-wrap');
  var outBox     = document.getElementById('nek-output');
  var status     = document.getElementById('nek-status');

  btn.disabled        = true;
  confirmBtn.disabled = true;
  spinner.style.display = 'inline';
  outWrap.style.display = 'block';
  outBox.textContent  = '';
  status.className    = 'nek-busy';
  status.textContent  = (action === 'dry_run')
    ? 'Verifying current key and identifying drives to update\u2026'
    : 'Updating encryption key on your drives\u2026';

  if (typeof $ === 'undefined' || typeof $.ajax === 'undefined') {
    btn.disabled = false; confirmBtn.disabled = false;
    spinner.style.display = 'none';
    outBox.textContent = 'jQuery is not available on this page.';
    status.className = 'nek-error';
    status.textContent = '\u2718 Internal error.';
    return;
  }

  var data = Object.assign({ action: action }, payload);

  $.ajax({
    type:     'POST',
    url:      '/plugins/unraid-newenckey/include/NewEncKey_ajax.php',
    data:     data,
    dataType: 'text',
    success: function(raw) {
      btn.disabled = false; confirmBtn.disabled = false;
      spinner.style.display = 'none';
      var resp;
      try { resp = JSON.parse(raw); }
      catch(e) {
        outBox.textContent = 'Server returned unexpected response (not JSON):\n\n' + raw;
        status.className = 'nek-error';
        status.textContent = '\u2718 Server error \u2014 see output above.';
        console.error('nekRun: JSON parse failed:', e, raw);
        return;
      }
      outBox.innerHTML = nekAnsiToHtml(resp.output || '(no output)');
      outBox.scrollTop = outBox.scrollHeight;
      onSuccess(resp);
    },
    error: function(xhr, textStatus, errorThrown) {
      btn.disabled = false; confirmBtn.disabled = false;
      spinner.style.display = 'none';
      outBox.textContent = 'HTTP ' + xhr.status + ' ' + xhr.statusText + '\n\n' + xhr.responseText;
      status.className = 'nek-error';
      status.textContent = '\u2718 Request failed \u2014 see output above.';
      console.error('nekRun AJAX error:', textStatus, errorThrown);
    }
  });
}

// ------ Submit: fire dry-run --------
function nekSubmit() {
  try {
    if (!nekValidate()) return;

    // Hide any previous confirmation panel
    document.getElementById('nek-confirm-wrap').style.display = 'none';

    var payload = nekPayload();

    nekRun('dry_run', payload, function(resp) {
      if (!resp.success) {
        document.getElementById('nek-status').className = 'nek-error';
        document.getElementById('nek-status').textContent = '\u2718 Key verification failed \u2014 see output above.';
        return;
      }
      // Dry run succeeded - show confirmation panel
      document.getElementById('nek-status').className = 'nek-ok';
      document.getElementById('nek-status').textContent = '\u2714 Key verified. Review the drives listed above, then confirm to proceed.';
      document.getElementById('nek-confirm-wrap').style.display = 'block';
      // Stash payload on the confirm button for reuse
      document.getElementById('nek-confirm-btn')._payload = payload;
    });
  } catch(e) {
    console.error('nekSubmit exception:', e);
    alert('JavaScript error in nekSubmit: ' + e.message);
  }
}

// ------ Confirm: fire real run with same payload ------
function nekConfirm() {
  try {
    var payload = document.getElementById('nek-confirm-btn')._payload;
    if (!payload) { alert('No pending operation - please start again.'); return; }

    // Hide confirm panel while working
    document.getElementById('nek-confirm-wrap').style.display = 'none';

    nekRun('confirm', payload, function(resp) {
      var status = document.getElementById('nek-status');
      if (resp.success) {
        status.className = 'nek-ok';
        status.textContent = '\u2714 Encryption key updated successfully on your drives.';
        nekClearFields();
      } else {
        status.className = 'nek-error';
        status.textContent = '\u2718 Operation failed \u2014 see output above.';
      }
    });
  } catch(e) {
    console.error('nekConfirm exception:', e);
    alert('JavaScript error in nekConfirm: ' + e.message);
  }
}

// ------ Cancel: dismiss confirmation panel ------
function nekCancel() {
  document.getElementById('nek-confirm-wrap').style.display = 'none';
  document.getElementById('nek-confirm-btn')._payload = null;
  var status = document.getElementById('nek-status');
  status.className = '';
  status.textContent = 'Cancelled.';
}

// ------ Clear sensitive fields after success ------
function nekClearFields() {
  var passIds = ['cur_pass', 'new_pass', 'new_pass2'];
  for (var i = 0; i < passIds.length; i++) {
    var el = document.getElementById(passIds[i]);
    if (el) { el.value = ''; el.type = 'password'; }
  }
  var eyes = document.querySelectorAll('#nek-wrap .nek-eye');
  for (var j = 0; j < eyes.length; j++) { eyes[j].value = 'Show'; }

  ['cur', 'new'].forEach(function(s) {
    var fi = document.getElementById(s + '_file_input');
    if (fi) { fi.value = ''; fi._b64 = null; }
    var fn = document.getElementById(s + '_file_name');
    if (fn) { fn.textContent = 'No file chosen'; fn.style.opacity = '0.75'; }
  });
}
</script>
