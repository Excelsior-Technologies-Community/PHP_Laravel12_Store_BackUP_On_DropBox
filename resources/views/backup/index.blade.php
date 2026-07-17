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

                    <th>Created</th>

                    <th width="220">Action</th>

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

                        {{ $backup->created_at->format('d M Y h:i A') }}

                    </td>

                    <td>

                        <a
                            href="{{ route('backup.download',$backup->id) }}"
                            class="btn btn-success btn-sm">

                            Download

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

                    <td colspan="6" class="text-center">

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