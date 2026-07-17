<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Dropbox Backup</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .08);
        }

        .navbar {
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
        }

        .table {
            background: white;
        }
    </style>

</head>

<body>

    <nav class="navbar navbar-dark bg-primary">
        <div class="container">

            <a class="navbar-brand fw-bold" href="{{ route('backup.index') }}">
                Laravel Dropbox Backup
            </a>

            <a href="{{ route('backup.create') }}" class="btn btn-light">
                Upload Backup
            </a>

        </div>
    </nav>

    <div class="container mt-4">

        @if(session('success'))

        <div class="alert alert-success">
            {{ session('success') }}
        </div>

        @endif

        @yield('content')

    </div>

</body>

</html>