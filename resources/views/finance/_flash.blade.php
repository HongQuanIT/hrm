@if (session('status'))
    <div class="mb-lg bg-primary/10 text-primary px-lg py-md rounded-xl">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="mb-lg bg-error-container text-on-error-container px-lg py-md rounded-xl">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="mb-lg bg-error-container text-on-error-container px-lg py-md rounded-xl">
        <ul class="list-disc list-inside text-body-md">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
