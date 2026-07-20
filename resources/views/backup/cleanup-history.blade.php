@extends('layouts.app')

@section('content')

<div class="card">

    <div class="card-header bg-primary text-white">

        <div class="row align-items-center">

            <div class="col-md-8">

                <h4 class="mb-0">
                    Backup Cleanup History
                </h4>

            </div>

            <div class="col-md-4 text-end">

                <a href="{{ route('backup.index') }}"
                    class="btn btn-light">

                    Back To Backups

                </a>

            </div>

        </div>

    </div>


    <div class="card-body">


        <table class="table table-bordered table-hover">

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Retention Limit</th>

                    <th>Deleted Backups</th>

                    <th>Freed Space</th>

                    <th>Status</th>

                    <th>Remarks</th>

                    <th>Cleaned At</th>

                </tr>

            </thead>


            <tbody>


                @forelse($histories as $history)


                <tr>

                    <td>

                        {{ $history->id }}

                    </td>


                    <td>

                        Keep Latest 
                        <strong>
                            {{ $history->retention_limit }}
                        </strong>

                    </td>


                    <td>

                        {{ $history->deleted_backups }}

                    </td>


                    <td>

                        {{ $history->freed_space ?? '-' }}

                    </td>


                    <td>


                        @if($history->status == 'Success')

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

                        {{ $history->remarks ?? '-' }}

                    </td>


                    <td>

                        @if($history->cleaned_at)

                            {{ $history->cleaned_at->format('d M Y h:i A') }}

                        @else

                            -

                        @endif

                    </td>


                </tr>


                @empty


                <tr>

                    <td colspan="7" class="text-center">

                        No Cleanup History Found

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


@endsection