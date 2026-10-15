@extends('layouts.app')

@section('content')

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h4 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i> Upload Backup Archive
                </h4>
                <a href="{{ route('backup.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>

            <div class="card-body p-4">
                @if ($errors->any())
                <div class="alert alert-danger shadow-sm">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Dropbox Connection Status Banner -->
                @if($dropboxStatus['connected'])
                <div class="alert alert-success d-flex align-items-center mb-4 py-2" role="alert">
                    <i class="fa-brands fa-dropbox fs-4 me-3 text-primary"></i>
                    <div>
                        <strong>Dropbox Cloud Sync Active:</strong> Connected to Dropbox storage account. Backups can be mirrored directly to the cloud.
                    </div>
                </div>
                @else
                <div class="alert alert-warning d-flex align-items-center mb-4 py-2" role="alert">
                    <i class="fa-solid fa-triangle-exclamation fs-4 me-3 text-warning"></i>
                    <div>
                        <strong>Dropbox Standby Mode:</strong> Cloud access token is not currently configured or authenticated. Cloud storage will operate in local fallback mode.
                    </div>
                </div>
                @endif

                <form action="{{ route('backup.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Storage Target Option -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">
                            <i class="fa-solid fa-server text-secondary me-1"></i> Storage Destination &amp; Cloud Sync
                        </label>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="card p-3 h-100 border text-center cursor-pointer storage-option" for="disk_both" style="cursor: pointer;">
                                    <div class="form-check text-start mb-2">
                                        <input class="form-check-input" type="radio" name="storage_disk" id="disk_both" value="both" checked>
                                        <span class="badge bg-primary ms-1">Recommended</span>
                                    </div>
                                    <i class="fa-solid fa-arrows-split-up-and-left fs-3 text-primary my-2"></i>
                                    <h6 class="fw-bold mb-1">Dual Storage</h6>
                                    <small class="text-muted">Save to Local Server &amp; Sync to Dropbox</small>
                                </label>
                            </div>

                            <div class="col-md-4">
                                <label class="card p-3 h-100 border text-center cursor-pointer storage-option" for="disk_dropbox" style="cursor: pointer;">
                                    <div class="form-check text-start mb-2">
                                        <input class="form-check-input" type="radio" name="storage_disk" id="disk_dropbox" value="dropbox">
                                    </div>
                                    <i class="fa-brands fa-dropbox fs-3 text-info my-2"></i>
                                    <h6 class="fw-bold mb-1">Dropbox Only</h6>
                                    <small class="text-muted">Store directly on Dropbox Cloud Storage</small>
                                </label>
                            </div>

                            <div class="col-md-4">
                                <label class="card p-3 h-100 border text-center cursor-pointer storage-option" for="disk_local" style="cursor: pointer;">
                                    <div class="form-check text-start mb-2">
                                        <input class="form-check-input" type="radio" name="storage_disk" id="disk_local" value="local">
                                    </div>
                                    <i class="fa-solid fa-hard-drive fs-3 text-secondary my-2"></i>
                                    <h6 class="fw-bold mb-1">Local Only</h6>
                                    <small class="text-muted">Save on local storage disk only</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- File Input -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">
                            <i class="fa-solid fa-file-zipper text-secondary me-1"></i> Backup File (.zip)
                        </label>
                        <input type="file"
                               name="backup_file"
                               class="form-control form-control-lg"
                               accept=".zip"
                               required>
                        <div class="form-text text-muted">
                            Upload a standard backup archive (.zip) containing database dumps (.sql) and application snapshots.
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="{{ route('backup.index') }}" class="btn btn-outline-secondary px-4">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="fa-solid fa-upload me-1"></i> Upload &amp; Process Backup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection