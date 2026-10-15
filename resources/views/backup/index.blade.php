@extends('layouts.app')

@section('content')

<!-- Quick Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-md-2 col-6">
        <div class="card p-3 text-center h-100 shadow-sm border-0">
            <span class="text-muted small text-uppercase fw-semibold">Total Backups</span>
            <h3 class="fw-bold text-dark mt-2 mb-0">{{ $totalBackups }}</h3>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card p-3 text-center h-100 shadow-sm border-0">
            <span class="text-muted small text-uppercase fw-semibold">Today's Backups</span>
            <h3 class="fw-bold text-primary mt-2 mb-0">{{ $todayBackups }}</h3>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card p-3 text-center h-100 shadow-sm border-0">
            <span class="text-muted small text-uppercase fw-semibold">Success</span>
            <h3 class="fw-bold text-success mt-2 mb-0">{{ $successBackups }}</h3>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card p-3 text-center h-100 shadow-sm border-0">
            <span class="text-muted small text-uppercase fw-semibold">Failed</span>
            <h3 class="fw-bold text-danger mt-2 mb-0">{{ $failedBackups }}</h3>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card p-3 text-center h-100 shadow-sm border-0">
            <span class="text-muted small text-uppercase fw-semibold">Avg Duration</span>
            <h3 class="fw-bold text-info mt-2 mb-0">{{ $avgDuration }}s</h3>
        </div>
    </div>
    <div class="col-md-2 col-12">
        <div class="card p-3 text-center h-100 shadow-sm border-0">
            <span class="text-muted small text-uppercase fw-semibold">Dropbox Status</span>
            <div class="mt-2" id="dropboxLiveStatusBadge">
                @if($dropboxStatus['connected'])
                    <span class="badge bg-success p-2 w-100"><i class="fa-solid fa-cloud-check me-1"></i> Connected</span>
                @else
                    <span class="badge bg-secondary p-2 w-100"><i class="fa-solid fa-cloud-slash me-1"></i> Standby / Offline</span>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Storage Insights & Growth Analytics Row -->
<div class="row g-4 mb-4">
    <!-- Dropbox Storage Quota Widget -->
    <div class="col-lg-4 col-md-12">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fa-brands fa-dropbox text-primary me-2"></i> Cloud Storage Quota
                </h5>
                <button class="btn btn-sm btn-outline-primary" id="btnRefreshDropboxStatus" onclick="pingDropboxConnection()">
                    <i class="fa-solid fa-arrows-rotate" id="refreshIcon"></i> Test Ping
                </button>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline mb-2">
                    <span class="text-muted">Used Storage:</span>
                    <span class="fw-bold text-dark" id="quotaUsedText">{{ $dropboxQuota['used_formatted'] }}</span>
                </div>
                <div class="progress mb-3" style="height: 12px; border-radius: 6px;">
                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                         role="progressbar"
                         id="quotaProgressBar"
                         style="width: {{ $dropboxQuota['percent_used'] }}%;"
                         aria-valuenow="{{ $dropboxQuota['percent_used'] }}"
                         aria-valuemin="0"
                         aria-valuemax="100">
                    </div>
                </div>
                <div class="d-flex justify-content-between small text-muted mb-3">
                    <span>Usage: <strong id="quotaPercentText">{{ $dropboxQuota['percent_used'] }}%</strong></span>
                    <span>Total Capacity: <strong id="quotaTotalText">{{ $dropboxQuota['total_formatted'] }}</strong></span>
                </div>

                <div class="border-top pt-3">
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted">Available Free Space:</span>
                        <span class="text-success fw-bold" id="quotaAvailableText">{{ $dropboxQuota['available_formatted'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted">Account / Mode:</span>
                        <span class="fw-semibold">{{ $dropboxQuota['account_name'] ?? 'Production Storage' }}</span>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">Active Policy:</span>
                        <span class="badge bg-light text-dark border">Dual Sync Supported</span>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <a href="{{ route('backup.create') }}" class="btn btn-primary w-100 fw-semibold">
                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> New Backup to Cloud
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 30-Day Growth Analytics Chart (Chart.js) -->
    <div class="col-lg-8 col-md-12">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-chart-line text-info me-2"></i> Backup Size Growth &amp; Activity (Last 30 Days)
                </h5>
                <span class="badge bg-light text-muted border">Real-time Analytics</span>
            </div>
            <div class="card-body">
                <div style="height: 250px;">
                    <canvas id="backupGrowthChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Retention Policy & Operations Row -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card p-3 shadow-sm border-0 h-100">
            <h5 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-broom text-warning me-2"></i> Backup Retention Policy</h5>
            <p class="text-muted small mb-3">Enforce safety limits by archiving older backups across local and cloud disks.</p>
            <form action="{{ route('backup.cleanup') }}" method="POST">
                @csrf
                <div class="row g-2">
                    <div class="col-8">
                        <select name="retention_limit" class="form-select">
                            <option value="5">Keep Latest 5 Backups</option>
                            <option value="10" selected>Keep Latest 10 Backups</option>
                            <option value="20">Keep Latest 20 Backups</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <button class="btn btn-warning w-100 fw-semibold" onclick="return confirm('Execute backup retention cleanup?')">
                            Run Cleanup
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card p-3 shadow-sm border-0 h-100 d-flex flex-column justify-content-between">
            <div>
                <h5 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-shield-halved text-success me-2"></i> Disaster Recovery &amp; Audit Logs</h5>
                <p class="text-muted small mb-3">Review historic database restoration events and automated cleanup log entries.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('backup.restore.history') }}" class="btn btn-outline-primary flex-fill fw-semibold">
                    <i class="fa-solid fa-rotate-left me-1"></i> Restore History
                </a>
                <a href="{{ route('backup.cleanup.history') }}" class="btn btn-outline-secondary flex-fill fw-semibold">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Cleanup Logs
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Main Backup History Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-database text-primary me-2"></i> Backup History</h4>
            <span class="badge bg-primary rounded-pill">{{ $backups->total() }}</span>
        </div>

        <!-- Filter & Search Controls -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('backup.index', ['search' => request('search')]) }}"
                   class="btn {{ !request('disk') ? 'btn-primary' : 'btn-outline-secondary' }}">All</a>
                <a href="{{ route('backup.index', ['disk' => 'both', 'search' => request('search')]) }}"
                   class="btn {{ request('disk') === 'both' ? 'btn-primary' : 'btn-outline-secondary' }}">Dual Sync</a>
                <a href="{{ route('backup.index', ['disk' => 'dropbox', 'search' => request('search')]) }}"
                   class="btn {{ request('disk') === 'dropbox' ? 'btn-primary' : 'btn-outline-secondary' }}">Dropbox Only</a>
                <a href="{{ route('backup.index', ['disk' => 'local', 'search' => request('search')]) }}"
                   class="btn {{ request('disk') === 'local' ? 'btn-primary' : 'btn-outline-secondary' }}">Local Only</a>
            </div>

            <form action="{{ route('backup.index') }}" method="GET" class="d-flex">
                @if(request('disk'))
                    <input type="hidden" name="disk" value="{{ request('disk') }}">
                @endif
                <div class="input-group input-group-sm">
                    <input type="text"
                           class="form-control"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Search filename...">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    @if($search)
                        <a href="{{ route('backup.index', ['disk' => request('disk')]) }}" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Archive Name</th>
                        <th>Size</th>
                        <th>Storage Target</th>
                        <th>Speed</th>
                        <th>Status</th>
                        <th>Verification</th>
                        <th>Created</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $backup)
                    <tr>
                        <td class="ps-3 fw-bold text-muted">#{{ $backup->id }}</td>
                        <td>
                            <div class="fw-semibold text-truncate" style="max-width: 230px;" title="{{ $backup->file_name }}">
                                {{ $backup->file_name }}
                            </div>
                            @if($backup->checksum_sha256)
                                <small class="text-muted d-block text-truncate" style="max-width: 230px;" title="{{ $backup->checksum_sha256 }}">
                                    SHA: {{ substr($backup->checksum_sha256, 0, 16) }}...
                                </small>
                            @endif
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $backup->file_size }}</span></td>
                        <td>
                            @if($backup->storage_disk === 'both')
                                <span class="badge badge-dual"><i class="fa-solid fa-cloud-arrow-up me-1"></i> Dual (Local + Dropbox)</span>
                            @elseif($backup->storage_disk === 'dropbox')
                                <span class="badge badge-cloud"><i class="fa-brands fa-dropbox me-1"></i> Dropbox Cloud</span>
                            @else
                                <span class="badge badge-local"><i class="fa-solid fa-hard-drive me-1"></i> Local Disk</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">
                                @if($backup->duration_seconds)
                                    <i class="fa-regular fa-clock me-1"></i>{{ $backup->duration_seconds }}s
                                @else
                                    -
                                @endif
                            </small>
                        </td>
                        <td>
                            @if($backup->status === 'Success')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="fa-solid fa-circle-check me-1"></i> Success
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                    <i class="fa-solid fa-circle-xmark me-1"></i> Failed
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($backup->verification_status === 'Verified')
                                <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> Verified</span>
                            @elseif($backup->verification_status === 'Corrupted')
                                <span class="badge bg-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Corrupted</span>
                            @elseif($backup->verification_status === 'Missing')
                                <span class="badge bg-warning text-dark"><i class="fa-solid fa-question me-1"></i> Missing</span>
                            @else
                                <span class="badge bg-secondary">Pending</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">{{ $backup->created_at->format('M d, Y h:i A') }}</small>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <!-- Download Button -->
                                <a href="{{ route('backup.download', $backup->id) }}"
                                   class="btn btn-outline-success"
                                   title="Download Backup Archive">
                                    <i class="fa-solid fa-download"></i>
                                </a>

                                <!-- Share Temporary Link Button -->
                                <button type="button"
                                        class="btn btn-outline-info"
                                        onclick="openShareModal('{{ $backup->id }}', '{{ $backup->file_name }}')"
                                        title="Get Secure Direct Share Link">
                                    <i class="fa-solid fa-share-nodes"></i>
                                </button>

                                <!-- Sync to Dropbox button if only local -->
                                @if($backup->storage_disk === 'local')
                                    <form action="{{ route('backup.sync', $backup->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button class="btn btn-outline-primary"
                                                onclick="return confirm('Sync this local backup archive to Dropbox Cloud?')"
                                                title="Sync to Dropbox Cloud">
                                            <i class="fa-brands fa-dropbox"></i>
                                        </button>
                                    </form>
                                @endif

                                <!-- Integrity Check -->
                                <form action="{{ route('backup.verify', $backup->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button class="btn btn-outline-secondary"
                                            onclick="return confirm('Perform integrity verification on this archive?')"
                                            title="Verify Archive Integrity">
                                        <i class="fa-solid fa-shield-halved"></i>
                                    </button>
                                </form>

                                <!-- Restore Database Button (Triggers Safe Modal) -->
                                <button type="button"
                                        class="btn btn-outline-warning text-dark"
                                        onclick="openRestoreModal('{{ $backup->id }}', '{{ $backup->file_name }}', '{{ $backup->storage_disk }}')"
                                        title="One-Click Database Restore">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </button>

                                <!-- Verification History -->
                                <a href="{{ route('backup.verification.history', $backup->id) }}"
                                   class="btn btn-outline-dark"
                                   title="View Verification Logs">
                                    <i class="fa-solid fa-list-check"></i>
                                </a>

                                <!-- Delete -->
                                <form action="{{ route('backup.delete', $backup->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to permanently delete this backup from local and cloud storage?')"
                                            title="Delete Archive">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-box-open fs-2 mb-2 d-block text-secondary"></i>
                            No backup archives found matching criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center p-3">
            {{ $backups->withQueryString()->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

<!-- Modal 1: One-Click Database Restore Confirmation Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="restoreModalLabel">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> Disaster Recovery - Database Restore
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="restoreForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning mb-3">
                        <strong class="d-block mb-1"><i class="fa-solid fa-radiation me-1"></i> High Priority Action</strong>
                        This action will extract the database dump from the selected ZIP archive and execute it against your live database.
                        Existing data may be overwritten.
                    </div>

                    <div class="mb-3 bg-light p-3 rounded">
                        <div class="small text-muted">Target Backup Archive:</div>
                        <div class="fw-bold text-dark fs-6" id="restoreFileName">filename.zip</div>
                        <div class="small text-muted mt-1">Source Storage: <span class="badge bg-secondary" id="restoreDiskBadge">Local</span></div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="confirmCheckbox" required>
                        <label class="form-check-label text-dark fw-semibold" for="confirmCheckbox">
                            I understand that current database tables will be replaced by this backup.
                        </label>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small text-muted">Type <span class="badge bg-danger">RESTORE</span> below to proceed:</label>
                        <input type="text" class="form-control" id="confirmWordInput" placeholder="RESTORE" autocomplete="off" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-semibold" id="btnExecuteRestore" disabled>
                        <i class="fa-solid fa-rotate-left me-1"></i> Proceed with Restore
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Direct Secure Share Link Modal -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="shareModalLabel">
                    <i class="fa-solid fa-share-nodes me-2"></i> Direct Secure Download Link
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    A temporary secure download URL has been generated. This URL allows authorized access to download the backup archive without requiring direct server access.
                </p>

                <div class="mb-3">
                    <label class="form-label small text-muted">Archive:</label>
                    <div class="fw-bold text-dark" id="shareFileName">-</div>
                    <small class="text-muted" id="shareLinkType">-</small>
                </div>

                <div class="input-group mb-3">
                    <input type="text" class="form-control font-monospace small" id="shareUrlInput" readonly>
                    <button class="btn btn-outline-primary" type="button" onclick="copyShareUrl()" id="btnCopyUrl">
                        <i class="fa-regular fa-copy me-1"></i> Copy
                    </button>
                </div>

                <div class="alert alert-info py-2 small mb-0">
                    <i class="fa-solid fa-clock me-1"></i> Direct cloud links are valid for up to 4 hours. Local signed links expire after 60 minutes.
                </div>
            </div>
            <div class="modal-footer bg-light">
                <a href="#" class="btn btn-success fw-semibold" id="shareDirectOpenBtn" target="_blank">
                    <i class="fa-solid fa-up-right-from-square me-1"></i> Open Download
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // --- Chart.js: 30-Day Backup Growth Analytics ---
    document.addEventListener('DOMContentLoaded', function () {
        fetch("{{ route('backup.analytics') }}")
            .then(res => res.json())
            .then(data => {
                const ctx = document.getElementById('backupGrowthChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.chart.labels,
                        datasets: [
                            {
                                label: 'Storage Size (MB)',
                                data: data.chart.sizes_mb,
                                borderColor: '#0284c7',
                                backgroundColor: 'rgba(2, 132, 199, 0.1)',
                                fill: true,
                                tension: 0.3,
                                yAxisID: 'y',
                            },
                            {
                                label: 'Archive Count',
                                data: data.chart.counts,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderDash: [4, 4],
                                tension: 0.2,
                                yAxisID: 'y1',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: { display: true, text: 'Size (MB)' },
                                beginAtZero: true
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: { display: true, text: 'Backups' },
                                grid: { drawOnChartArea: false },
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            })
            .catch(err => {
                console.warn('Analytics chart loading skipped:', err);
            });
    });

    // --- Dropbox Live Connection Checker ---
    function pingDropboxConnection() {
        const icon = document.getElementById('refreshIcon');
        const badge = document.getElementById('dropboxLiveStatusBadge');
        icon.classList.add('fa-spin');

        fetch("{{ route('backup.dropbox.test') }}")
            .then(res => res.json())
            .then(data => {
                icon.classList.remove('fa-spin');
                if (data.connected) {
                    badge.innerHTML = `<span class="badge bg-success p-2 w-100"><i class="fa-solid fa-cloud-check me-1"></i> Connected</span>`;
                } else {
                    badge.innerHTML = `<span class="badge bg-secondary p-2 w-100"><i class="fa-solid fa-cloud-slash me-1"></i> Offline (${data.message})</span>`;
                }
            })
            .catch(() => {
                icon.classList.remove('fa-spin');
                badge.innerHTML = `<span class="badge bg-danger p-2 w-100"><i class="fa-solid fa-circle-exclamation me-1"></i> Connection Error</span>`;
            });
    }

    // --- Restore Modal Safeguard Setup ---
    function openRestoreModal(id, fileName, disk) {
        document.getElementById('restoreFileName').innerText = fileName;
        document.getElementById('restoreDiskBadge').innerText = disk.toUpperCase();
        document.getElementById('restoreForm').action = "/backup/" + id + "/restore";
        document.getElementById('confirmCheckbox').checked = false;
        document.getElementById('confirmWordInput').value = '';
        document.getElementById('btnExecuteRestore').disabled = true;

        const modal = new bootstrap.Modal(document.getElementById('restoreModal'));
        modal.show();
    }

    const confirmCheck = document.getElementById('confirmCheckbox');
    const confirmInput = document.getElementById('confirmWordInput');
    const btnExecuteRestore = document.getElementById('btnExecuteRestore');

    function checkRestoreSafeguard() {
        if (confirmCheck.checked && confirmInput.value.trim().toUpperCase() === 'RESTORE') {
            btnExecuteRestore.disabled = false;
        } else {
            btnExecuteRestore.disabled = true;
        }
    }

    confirmCheck.addEventListener('change', checkRestoreSafeguard);
    confirmInput.addEventListener('input', checkRestoreSafeguard);

    document.getElementById('restoreForm').addEventListener('submit', function() {
        btnExecuteRestore.disabled = true;
        btnExecuteRestore.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Restoring Database...';
    });

    // --- Direct Share Link Modal ---
    function openShareModal(id, fileName) {
        document.getElementById('shareFileName').innerText = fileName;
        document.getElementById('shareUrlInput').value = 'Generating secure link...';
        document.getElementById('shareLinkType').innerText = 'Please wait...';
        document.getElementById('shareDirectOpenBtn').setAttribute('href', '#');

        const modal = new bootstrap.Modal(document.getElementById('shareModal'));
        modal.show();

        fetch("/backup/" + id + "/share-link")
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('shareUrlInput').value = data.url;
                    document.getElementById('shareLinkType').innerText = data.type;
                    document.getElementById('shareDirectOpenBtn').setAttribute('href', data.url);
                } else {
                    document.getElementById('shareUrlInput').value = 'Failed to generate link.';
                }
            })
            .catch(() => {
                document.getElementById('shareUrlInput').value = 'Error generating link.';
            });
    }

    function copyShareUrl() {
        const input = document.getElementById('shareUrlInput');
        input.select();
        navigator.clipboard.writeText(input.value).then(() => {
            const btn = document.getElementById('btnCopyUrl');
            btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Copied!';
            btn.classList.replace('btn-outline-primary', 'btn-success');
            setTimeout(() => {
                btn.innerHTML = '<i class="fa-regular fa-copy me-1"></i> Copy';
                btn.classList.replace('btn-success', 'btn-outline-primary');
            }, 2000);
        });
    }
</script>
@endsection