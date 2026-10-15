@extends('layouts.app')

@section('content')

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-rotate-left text-danger me-2"></i> Database Restore History
            </h4>
            <small class="text-muted">Audit log of all disaster recovery and database restoration events.</small>
        </div>

        <a href="{{ route('backup.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Backup Archive</th>
                        <th>Storage Source</th>
                        <th>Duration</th>
                        <th>Tables Count</th>
                        <th>Status</th>
                        <th>Remarks &amp; Output</th>
                        <th class="pe-3">Restored At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($histories as $history)
                    <tr>
                        <td class="ps-3 fw-bold text-muted">#{{ $history->id }}</td>
                        <td>
                            @if($history->backup)
                                <span class="fw-semibold text-dark">{{ $history->backup->file_name }}</span>
                                <small class="text-muted d-block">{{ $history->backup->file_size }}</small>
                            @else
                                <span class="text-muted">Deleted Backup (ID: {{ $history->backup_history_id }})</span>
                            @endif
                        </td>
                        <td>
                            @if($history->storage_source === 'dropbox')
                                <span class="badge badge-cloud"><i class="fa-brands fa-dropbox me-1"></i> Dropbox</span>
                            @else
                                <span class="badge badge-local"><i class="fa-solid fa-hard-drive me-1"></i> Local</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">
                                @if($history->duration_seconds)
                                    <i class="fa-regular fa-clock me-1"></i>{{ $history->duration_seconds }}s
                                @else
                                    -
                                @endif
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $history->tables_restored_count }} Tables
                            </span>
                        </td>
                        <td>
                            @if($history->status === 'Success')
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
                            <small class="text-muted text-break" style="max-width: 320px; display: inline-block;">
                                {{ $history->remarks ?? '-' }}
                            </small>
                        </td>
                        <td class="pe-3">
                            <small class="text-muted">
                                {{ $history->restored_at ? $history->restored_at->format('M d, Y h:i A') : $history->created_at->format('M d, Y h:i A') }}
                            </small>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-box-open fs-2 mb-2 d-block text-secondary"></i>
                            No database restoration operations recorded yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center p-3">
            {{ $histories->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

@endsection
