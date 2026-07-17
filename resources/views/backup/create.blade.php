@extends('layouts.app')

@section('content')

<div class="row justify-content-center">

    <div class="col-md-8">

        <div class="card">

            <div class="card-header bg-primary text-white">
                <h4>Upload Backup</h4>
            </div>

            <div class="card-body">

                @if ($errors->any())

                <div class="alert alert-danger">

                    <ul class="mb-0">

                        @foreach ($errors->all() as $error)

                        <li>{{ $error }}</li>

                        @endforeach

                    </ul>

                </div>

                @endif

                <form action="{{ route('backup.store') }}"
                    method="POST"
                    enctype="multipart/form-data">

                    @csrf

                    <div class="mb-3">

                        <label class="form-label">
                            Backup File (.zip)
                        </label>

                        <input
                            type="file"
                            name="backup_file"
                            class="form-control"
                            accept=".zip"
                            required>

                    </div>

                    <div class="mb-3">

                        <button class="btn btn-primary">

                            Upload Backup

                        </button>

                        <a href="{{ route('backup.index') }}"
                            class="btn btn-secondary">

                            Back

                        </a>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>

@endsection