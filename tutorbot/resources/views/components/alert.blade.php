<div class="px-4 pt-4">
    @if ($message = session()->has('succes'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <p class="text-black mb-0">{{ session()->get('succes') }}</p>
        </div>
    @endif
    @if ($message = session()->has('error'))
        <div class="alert alert-danger" role="alert">
            <p class="text-black mb-0">{{ session()->get('error') }}</p>
        </div>
    @endif
    @if ($message = session()->has('status'))
        <div class="alert alert-danger" role="alert">
            <p class="text-black mb-0">{{ session()->get('status') }}</p>
        </div>
    @endif
</div>
