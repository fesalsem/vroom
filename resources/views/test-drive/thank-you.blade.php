<x-guest-layout>
    <div class="max-w-md mx-auto mt-10 p-6 bg-white rounded-lg shadow-md text-center">
        <div class="text-6xl mb-4">🎉</div>
        <h1 class="text-2xl font-bold mb-2">Thank You!</h1>
        <p class="text-gray-600 mb-4">
            Your test drive registration has been received successfully.
        </p>
        <p class="text-gray-600 mb-6">
            Our sales team will contact you shortly to arrange an appointment.
        </p>
        <a
            href="{{ route('test-drive.create') }}"
            class="inline-block px-6 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 transition-colors"
        >
            Register Another
        </a>
    </div>
</x-guest-layout>
