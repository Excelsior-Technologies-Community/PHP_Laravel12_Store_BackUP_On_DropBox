@extends('layouts.app')

@section('content')

<div class="row mb-4">

    <div class="col-md-12">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">

                <div>
                    <h4 class="mb-0">Backup Verification History</h4>
                </div>

                <a href="{{ route('backup.index') }}" class="btn btn-light">
                    ← Back
                </a>

            </div>

            <div class="card-body">

                <div class="row mb-4">

                    <div class="col-md-6">

                        <table class="table table-bordered">

                            <tr>
                                <th width="180">Backup File</th>
                                <td>{{ $backup->file_name }}</td>
                            </tr>

                            <tr>
                                <th>File Size</th>
                                <td>{{ $backup->file_size }}</td>
                            </tr>

                            <tr>
                                <th>Backup Status</th>
                                <td>

                                    @if($backup->status == 'Success')

                                        <span class="badge bg-success">
                                            Success
                                        </span>

                                    @else

                                        <span class="badge bg-danger">
                                            Failed
                                        </span>

                                    @endif

                                </td>
                            </tr>

                            <tr>
                                <th>Verification Status</th>
                                <td>

                                    @switch($backup->verification_status)

                                        @case('Verified')

                                            <span class="badge bg-success">
                                                Verified
                                            </span>

                                            @break

                                        @case('Missing')

                                            <span class="badge bg-warning text-dark">
                                                Missing
                                            </span>

                                            @break

                                        @case('Corrupted')

                                            <span class="badge bg-danger">
                                                Corrupted
                                            </span>

                                            @break

                                        @default

                                            <span class="badge bg-secondary">
                                                Pending
                                            </span>

                                    @endswitch

                                </td>
                            </tr>

                            <tr>
                                <th>Last Verified</th>
                                <td>

                                    @if($backup->verified_at)

                                        {{ $backup->verified_at->format('d M Y h:i A') }}

                                    @else

                                        -

                                    @endif

                                </td>
                            </tr>

                        </table>

                    </div>

                </div>

                <hr>

                <h5 class="mb-3">
                    Verification Logs
                </h5>

                <table class="table table-bordered table-hover">

                    <thead class="table-light">

                        <tr>

                            <th>ID</th>

                            <th>Status</th>

                            <th>Remarks</th>

                            <th>Verified At</th>

                            <th>Created</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($histories as $history)

                            <tr>

                                <td>{{ $history->id }}</td>

                                <td>

                                    @switch($history->verification_status)

                                        @case('Verified')

                                            <span class="badge bg-success">
                                                Verified
                                            </span>

                                            @break

                                        @case('Missing')

                                            <span class="badge bg-warning text-dark">
                                                Missing
                                            </span>

                                            @break

                                        @case('Corrupted')

                                            <span class="badge bg-danger">
                                                Corrupted
                                            </span>

                                            @break

                                    @endswitch

                                </td>

                                <td>

                                    {{ $history->remarks }}

                                </td>

                                <td>

                                    {{ \Carbon\Carbon::parse($history->verified_at)->format('d M Y h:i A') }}

                                </td>

                                <td>

                                    {{ $history->created_at->format('d M Y h:i A') }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="5" class="text-center text-muted">

                                    No verification history found.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

                <div class="d-flex justify-content-center mt-3">

                    {{ $histories->links('pagination::bootstrap-4') }}

                </div>

            </div>

        </div>

    </div>

</div>

@endsection