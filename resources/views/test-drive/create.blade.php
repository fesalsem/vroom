<x-guest-layout>
    <div class="max-w-md mx-auto mt-10 p-6 bg-white rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-center mb-2">Book a Test Drive</h1>
        <p class="text-gray-600 text-center mb-6">
            Experience the all-new <strong>CapBay Vroom</strong> — <x-money :cents="20_000_000" />
        </p>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                <p class="text-sm font-medium text-red-800 mb-1">Please fix the following errors:</p>
                <ul class="list-disc list-inside text-sm text-red-600 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('test-drive.store') }}" class="space-y-4">
            @csrf

            <!-- Name -->
            <div>
                <x-input-label for="customer_name" :value="__('Full Name')" />
                <x-text-input
                    id="customer_name"
                    name="customer_name"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('customer_name')"
                    required
                    autofocus
                    placeholder="e.g. Ahmad bin Ismail"
                />
                <x-input-error :messages="$errors->get('customer_name')" class="mt-2" />
            </div>

            <!-- Email -->
            <div>
                <x-input-label for="customer_email" :value="__('Email Address')" />
                <x-text-input
                    id="customer_email"
                    name="customer_email"
                    type="email"
                    class="mt-1 block w-full"
                    :value="old('customer_email')"
                    required
                    placeholder="e.g. ahmad@example.com"
                />
                <x-input-error :messages="$errors->get('customer_email')" class="mt-2" />
            </div>

            <!-- Phone -->
            <div>
                <x-input-label for="customer_phone" :value="__('Phone Number')" />
                <x-text-input
                    id="customer_phone"
                    name="customer_phone"
                    type="tel"
                    class="mt-1 block w-full"
                    :value="old('customer_phone')"
                    required
                    placeholder="e.g. +60 12-345 6789"
                />
                <x-input-error :messages="$errors->get('customer_phone')" class="mt-2" />
            </div>

            <!-- Car Model (hidden, fixed to CapBay Vroom) -->
            <input type="hidden" name="car_model" value="CapBay Vroom" />

            <div class="flex items-center justify-end mt-6">
                <x-primary-button>
                    {{ __('Register for Test Drive') }}
                </x-primary-button>
            </div>
        </form>

        <p class="text-xs text-gray-400 text-center mt-4">
            By registering, you agree to our terms and conditions.
        </p>
    </div>
</x-guest-layout>
