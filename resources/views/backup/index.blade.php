@extends('layouts.app')

@section('content')

<div class="row mb-4">

    <div class="col-md-3">
        <div class="card text-center p-3">
            <h5>Total Backups</h5>
            <h2>{{ $totalBackups }}</h2>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center p-3">
            <h5>Today's Backups</h5>
            <h2>{{ $todayBackups }}</h2>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center p-3">
            <h5>Success</h5>
            <h2 class="text-success">{{ $successBackups }}</h2>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center p-3">
            <h5>Failed</h5>
            <h2 class="text-danger">{{ $failedBackups }}</h2>
        </div>
    </div>

</div>

<div class="row mb-4">

    <div class="col-md-6">

        <div class="card p-3">

            <h5 class="mb-3">
                Backup Retention Policy
            </h5>

            <form action="{{ route('backup.cleanup') }}" method="POST">

                @csrf

                <div class="row">

                    <div class="col-md-7">

                        <select name="retention_limit" class="form-select">

                            <option value="5">
                                Keep Latest 5 Backups
                            </option>

                            <option value="10">
                                Keep Latest 10 Backups
                            </option>

                            <option value="20">
                                Keep Latest 20 Backups
                            </option>

                        </select>

                    </div>


                    <div class="col-md-5">

                        <button
                            class="btn btn-warning w-100"
                            onclick="return confirm('Run backup cleanup?')">

                            Run Cleanup

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <div class="col-md-6">

        <div class="card p-3 text-center">

            <h5>
                Cleanup History
            </h5>

            <p class="text-muted">
                View previous cleanup operations
            </p>

            <a href="{{ route('backup.cleanup.history') }}"
                class="btn btn-info">

                View Cleanup History

            </a>

        </div>

    </div>

</div>

<div class="card">

    <div class="card-header bg-primary text-white">

        <div class="row">

            <div class="col-md-6">

                <h4>Backup History</h4>

            </div>

            <div class="col-md-6">

                <form action="{{ route('backup.index') }}" method="GET">

                    <div class="input-group">

                        <input
                            type="text"
                            class="form-control"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search Backup File">

                        <button class="btn btn-light">

                            Search

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <div class="card-body">

        <table class="table table-bordered table-hover">

            <thead>

                <tr>

                    <th>ID</th>

                    <th>File Name</th>

                    <th>Size</th>

                    <th>Status</th>

                    <th>Verification</th>

                    <th>Verified At</th>

                    <th>Created</th>

                    <th width="420">Action</th>

                </tr>

            </thead>

            <tbody>

                @forelse($backups as $backup)

                <tr>

                    <td>{{ $backup->id }}</td>

                    <td>{{ $backup->file_name }}</td>

                    <td>{{ $backup->file_size }}</td>

                    <td>

                        @if($backup->status=='Success')

                        <span class="badge bg-success">

                            Success

                        </span>

                        @else

                        <span class="badge bg-danger">

                            Failed

                        </span>

                        @endif

                    </td>

                    <td>

                        @if($backup->verification_status == 'Verified')

                        <span class="badge bg-success">
                            Verified
                        </span>

                        @elseif($backup->verification_status == 'Missing')

                        <span class="badge bg-warning text-dark">
                            Missing
                        </span>

                        @elseif($backup->verification_status == 'Corrupted')

                        <span class="badge bg-danger">
                            Corrupted
                        </span>

                        @else

                        <span class="badge bg-secondary">
                            Pending
                        </span>

                        @endif

                    </td>

                    <td>

                        @if($backup->verified_at)

                        {{ $backup->verified_at->format('d M Y h:i A') }}

                        @else

                        -

                        @endif

                    </td>

                    <td>

                        {{ $backup->created_at->format('d M Y h:i A') }}

                    </td>

                    <td>

                        <a href="{{ route('backup.download',$backup->id) }}"
                            class="btn btn-success btn-sm">

                            Download

                        </a>

                        <form action="{{ route('backup.verify',$backup->id) }}"
                            method="POST"
                            style="display:inline;">

                            @csrf

                            <button
                                class="btn btn-primary btn-sm"
                                onclick="return confirm('Verify this backup?')">

                                Verify

                            </button>

                        </form>

                        <a href="{{ route('backup.verification.history',$backup->id) }}"
                            class="btn btn-info btn-sm">

                            History

                        </a>

                        <form
                            action="{{ route('backup.delete',$backup->id) }}"
                            method="POST"
                            style="display:inline;">

                            @csrf

                            @method('DELETE')

                            <button
                                class="btn btn-danger btn-sm"
                                onclick="return confirm('Delete Backup?')">

                                Delete

                            </button>

                        </form>

                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="8" class="text-center">

                        No Backup Found

                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

        <div class="d-flex justify-content-center mt-3">
            {{ $backups->links('pagination::bootstrap-4') }}
        </div>

    </div>

</div>

@endsection