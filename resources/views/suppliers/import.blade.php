@extends('layouts.sidebar')

@section('title', 'Import Suppliers')

@section('content')
<div class="mb-6">
    <div class="text-[22px] font-extrabold tracking-tight">Import Suppliers from CSV</div>
    <div class="text-[13px] text-ink-2 mt-0.5">Upload a CSV file to batch-import suppliers with optional auto-geocoding</div>
</div>

{{-- ══ Instructions Card ═══ --}}
<div class="card p-5 mb-6">
    <div class="text-[14px] font-extrabold mb-3">CSV Format</div>
    <div class="text-[12px] text-ink-2 leading-relaxed mb-3">
        Your CSV should have a header row. These column names are recognized (case-insensitive):
    </div>
    <div class="overflow-x-auto mb-3">
        <table class="data-table text-[12px]">
            <thead>
                <tr>
                    <th>Column</th>
                    <th>Required</th>
                    <th>Description</th>
                    <th>Examples</th>
                </tr>
            </thead>
            <tbody>
                <tr><td class="font-semibold">name</td><td><span class="text-accent-2 font-bold">Yes</span></td><td>Supplier name</td><td>Manila Dairy Supply</td></tr>
                <tr><td class="font-semibold">contact_person</td><td>No</td><td>Contact person name</td><td>Ricardo Santos</td></tr>
                <tr><td class="font-semibold">contact_number / phone</td><td>No</td><td>Phone number</td><td>0917-555-1234</td></tr>
                <tr><td class="font-semibold">address</td><td>No</td><td>Full address</td><td>123 Rizal Ave, Manila</td></tr>
                <tr><td class="font-semibold">landmark</td><td>No</td><td>Nearest landmark</td><td>Near LRT Carriedo</td></tr>
                <tr><td class="font-semibold">notes</td><td>No</td><td>Additional notes</td><td>Delivers Mon-Fri</td></tr>
                <tr><td class="font-semibold">latitude / lat</td><td>No</td><td>Latitude coordinate</td><td>14.5895</td></tr>
                <tr><td class="font-semibold">longitude / lng / lon</td><td>No</td><td>Longitude coordinate</td><td>120.9740</td></tr>
            </tbody>
        </table>
    </div>
    <div class="text-[12px] text-ink-2">
        <strong>Auto-Geocode:</strong> If latitude/longitude are missing, coordinates will be automatically looked up from the address using OpenStreetMap Nominatim.
    </div>
</div>

{{-- ══ Upload Card ═══ --}}
<div class="card p-5 mb-6" id="uploadCard">
    <div class="text-[14px] font-extrabold mb-3">Upload CSV</div>
    <form id="uploadForm" onsubmit="return uploadCSV(event)">
        <div class="flex gap-3 items-end">
            <div class="flex-1">
                <input type="file" class="form-input" id="csvFile" accept=".csv,.txt" required>
            </div>
            <label class="flex items-center gap-2 pb-1">
                <input type="checkbox" checked id="geocodeMissing" class="accent-[var(--accent)]">
                <span class="text-[12px] font-semibold">Auto-geocode missing coords</span>
            </label>
            <button type="submit" class="btn-primary h-[42px] px-6" id="uploadBtn">Upload & Preview</button>
        </div>
    </form>
    <div id="uploadError" class="text-[12px] text-red-600 mt-3" style="display:none;"></div>
</div>

{{-- ══ Preview Card ═══ --}}
<div class="card p-5 mb-6" id="previewCard" style="display:none;">
    <div class="flex items-center justify-between mb-4">
        <div>
            <div class="text-[14px] font-extrabold">Preview Import</div>
            <div class="text-[12px] text-ink-3 mt-0.5" id="previewSummary"></div>
        </div>
        <div class="flex gap-2">
            <button class="btn-sm" onclick="resetImport()">Cancel</button>
            <button class="btn-primary h-[36px] px-5" id="importBtn" onclick="executeImport()">Import All</button>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table text-[12px]" id="previewTable">
            <thead id="previewHead"></thead>
            <tbody id="previewBody"></tbody>
        </table>
    </div>
</div>

{{-- ══ Results Card ═══ --}}
<div class="card p-5" id="resultsCard" style="display:none;">
    <div class="text-[14px] font-extrabold mb-3">Import Results</div>
    <div id="resultsSummary" class="text-[13px] mb-4"></div>
    <div id="resultsList" class="max-h-[300px] overflow-y-auto"></div>
    <div class="mt-4">
        <a href="{{ route('suppliers.index') }}" class="btn-primary inline-block px-6 py-2.5 text-[13px] font-semibold">View Supplier Directory</a>
    </div>
</div>

<script>
var previewData = null;

async function uploadCSV(e) {
    e.preventDefault();
    var file = document.getElementById('csvFile').files[0];
    var errEl = document.getElementById('uploadError');
    var btn = document.getElementById('uploadBtn');

    if (!file) { errEl.textContent = 'Please select a CSV file.'; errEl.style.display = 'block'; return false; }

    errEl.style.display = 'none';
    btn.disabled = true;
    btn.textContent = 'Uploading...';

    var fd = new FormData();
    fd.append('csv_file', file);

    try {
        var res = await fetch('/suppliers/import/preview', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
        var data = await res.json();
        if (res.ok && data.success) {
            previewData = data;
            renderPreview(data);
        } else {
            errEl.textContent = data.message || 'Error uploading file.';
            errEl.style.display = 'block';
        }
    } catch (err) {
        errEl.textContent = 'Network error.';
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.textContent = 'Upload & Preview';
    }
    return false;
}

function renderPreview(data) {
    document.getElementById('uploadCard').style.display = 'none';
    document.getElementById('previewCard').style.display = '';

    var withErrors = data.rows.filter(function(r) { return r.errors.length > 0; }).length;
    var needGeo = data.rows.filter(function(r) { return r.needs_geocode; }).length;
    document.getElementById('previewSummary').textContent =
        data.total + ' suppliers found' +
        (withErrors ? ' (' + withErrors + ' with errors)' : '') +
        (needGeo ? ' (' + needGeo + ' need geocoding)' : '');

    // Build header
    var head = '<tr><th>#</th><th>Name</th><th>Contact</th><th>Phone</th><th>Address</th><th>Landmark</th><th>Coords</th><th>Status</th></tr>';
    document.getElementById('previewHead').innerHTML = head;

    // Build rows
    var body = data.rows.map(function(row) {
        var hasError = row.errors.length > 0;
        var coords = row.latitude && row.longitude ? row.latitude + ', ' + row.longitude :
                     row.needs_geocode ? '<span class="text-ink-3 italic">Will geocode</span>' : '—';
        return '<tr class="' + (hasError ? 'bg-red-50' : '') + '">' +
            '<td>' + row.row + '</td>' +
            '<td class="font-semibold">' + escHtml(row.name || '') + (hasError ? ' <span class="text-red-600 text-[10px]">' + escHtml(row.errors.join(', ')) + '</span>' : '') + '</td>' +
            '<td>' + escHtml(row.contact_person || '—') + '</td>' +
            '<td>' + escHtml(row.contact_number || '—') + '</td>' +
            '<td>' + escHtml(row.address || '—') + '</td>' +
            '<td>' + escHtml(row.landmark || '—') + '</td>' +
            '<td>' + coords + '</td>' +
            '<td>' + (hasError ? '<span class="text-red-600">Error</span>' : '<span class="text-green">OK</span>') + '</td>' +
        '</tr>';
    }).join('');
    document.getElementById('previewBody').innerHTML = body;
}

async function executeImport() {
    if (!previewData) return;
    var btn = document.getElementById('importBtn');
    btn.disabled = true;
    btn.textContent = 'Importing...';

    var validRows = previewData.rows.filter(function(r) { return r.errors.length === 0; });

    try {
        var res = await fetch('/suppliers/import/execute', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                rows: validRows,
                geocode_missing: document.getElementById('geocodeMissing').checked
            })
        });
        var data = await res.json();
        if (res.ok && data.success) {
            showResults(data);
        } else {
            alert('Import failed: ' + (data.message || 'Unknown error'));
        }
    } catch (err) {
        alert('Network error during import.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Import All';
    }
}

function showResults(data) {
    document.getElementById('previewCard').style.display = 'none';
    document.getElementById('resultsCard').style.display = '';

    document.getElementById('resultsSummary').innerHTML =
        '<span class="font-bold text-green">' + data.created + ' created</span>' +
        (data.errors > 0 ? ' · <span class="font-bold text-accent-2">' + data.errors + ' skipped</span>' : '');

    document.getElementById('resultsList').innerHTML = data.results.map(function(r) {
        var color = r.status === 'created' ? 'text-green' : 'text-ink-3';
        var icon = r.status === 'created' ? '✓' : '–';
        return '<div class="flex items-center gap-2 py-1.5 border-b border-line text-[12px]"><span class="' + color + ' font-bold">' + icon + '</span><span class="font-semibold">' + escHtml(r.name) + '</span><span class="' + color + '">' + r.status + '</span>' + (r.reason ? '<span class="text-ink-3 text-[11px]">(' + escHtml(r.reason) + ')</span>' : '') + '</div>';
    }).join('');
}

function resetImport() {
    previewData = null;
    document.getElementById('uploadCard').style.display = '';
    document.getElementById('previewCard').style.display = 'none';
    document.getElementById('resultsCard').style.display = 'none';
    document.getElementById('csvFile').value = '';
    document.getElementById('uploadError').style.display = 'none';
}

function escHtml(s) {
    var div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}
</script>
@endsection
