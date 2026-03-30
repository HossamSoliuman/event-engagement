<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>App name</title>

    {{-- tailwind --}}
    @vite('resources/css/app.css')

    {{-- bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

    {{-- jqery --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>


    {{-- filepond css  --}}
    <link href="https://unpkg.com/filepond@^4/dist/filepond.css" rel="stylesheet" />

</head>

<body>
    @yield('app-content')
    @yield('scripts')
    <script src="https://unpkg.com/filepond@^4/dist/filepond.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous">
    </script>

    <script>
        const inputElements = document.querySelectorAll('.filepond');
        inputElements.forEach(inputElement => {
            const pond = FilePond.create(inputElement);
        });
        FilePond.setOptions({
            server: {
                url: '/temp/store',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                }
            }
        });
    </script>
</body>

</html>
