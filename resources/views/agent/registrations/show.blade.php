<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Registration #:id', ['id' => $registration->id]) }}
            </h2>
            <a
                href="{{ route('agent.registrations.index') }}"
                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 transition-colors"
            >
                &larr; Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Messages -->
            @if (session('success'))
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Validation Errors -->
            @if ($errors->any())
                <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm font-medium text-red-800 mb-1">Please fix the following errors:</p>
                    <ul class="list-disc list-inside text-sm text-red-600 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Customer Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Customer Information</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Name</dt>
                            <dd class="text-sm text-gray-900 mt-0.5">{{ $registration->customer_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="text-sm text-gray-900 mt-0.5">
                                <a href="mailto:{{ $registration->customer_email }}" class="text-indigo-600 hover:text-indigo-800">
                                    {{ $registration->customer_email }}
                                </a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Phone</dt>
                            <dd class="text-sm text-gray-900 mt-0.5">
                                <a href="tel:{{ $registration->customer_phone }}" class="text-indigo-600 hover:text-indigo-800">
                                    {{ $registration->customer_phone }}
                                </a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Car Model</dt>
                            <dd class="text-sm text-gray-900 mt-0.5">{{ $registration->car_model }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="text-sm mt-0.5">
                                <x-status-badge :status="$registration->status" />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Registered On</dt>
                            <dd class="text-sm text-gray-900 mt-0.5">{{ $registration->created_at->format('d M Y, h:i A') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Pricing & Promotion -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Pricing & Promotion</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Car Price</dt>
                            <dd class="text-sm text-gray-900 mt-0.5 font-medium">
                                <x-money :cents="$registration->car_price_cents" />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Down Payment</dt>
                            <dd class="text-sm text-gray-900 mt-0.5 font-medium">
                                <x-money :cents="$registration->down_payment_cents" />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Effective Price</dt>
                            <dd class="text-sm text-gray-900 mt-0.5 font-medium">
                                <x-money :cents="$registration->effectivePriceCents()" />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Promotion</dt>
                            <dd class="text-sm mt-0.5">
                                <x-promotion-badge :eligible="$registration->promotion_eligible" />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Loan</dt>
                            <dd class="text-sm mt-0.5">
                                @if ($registration->loan_amount_cents !== null)
                                    <span class="font-medium"><x-money :cents="$registration->loan_amount_cents" /></span>
                                    @if ($registration->loan_approved === true)
                                        <span class="text-green-600 text-xs ml-1">✅ Approved</span>
                                    @elseif ($registration->loan_approved === false)
                                        <span class="text-red-600 text-xs ml-1">❌ Rejected</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">Not calculated</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Actions</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Status Transition -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium text-gray-700 mb-2">Update Status</h4>
                            @if (!empty($allowedTransitions))
                                <form
                                    method="POST"
                                    action="{{ route('agent.registrations.update-status', $registration) }}"
                                    class="space-y-2"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <select
                                        name="status"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        @foreach ($allowedTransitions as $transition)
                                            <option value="{{ $transition->value }}">
                                                {{ $transition->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-primary-button class="w-full justify-center">
                                        {{ __('Change Status') }}
                                    </x-primary-button>
                                </form>
                            @else
                                <p class="text-sm text-gray-500">No further transitions available.</p>
                            @endif
                        </div>

                        <!-- Down Payment -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium text-gray-700 mb-2">Update Down Payment</h4>
                            <form
                                method="POST"
                                action="{{ route('agent.registrations.update-down-payment', $registration) }}"
                                class="space-y-2"
                            >
                                @csrf
                                @method('PATCH')
                                <div>
                                    <input
                                        type="number"
                                        name="down_payment_cents"
                                        value="{{ old('down_payment_cents', $registration->down_payment_cents) }}"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Amount in cents (e.g. 2000000 for RM 20,000)"
                                        min="0"
                                    />
                                    <p class="text-xs text-gray-400 mt-1">
                                        Current: <x-money :cents="$registration->down_payment_cents" />
                                    </p>
                                    <x-input-error :messages="$errors->get('down_payment_cents')" class="mt-1" />
                                </div>
                                <x-primary-button class="w-full justify-center">
                                    {{ __('Update Down Payment') }}
                                </x-primary-button>
                            </form>
                        </div>

                        <!-- Check Promotion -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium text-gray-700 mb-2">Promotion Eligibility</h4>
                            <p class="text-sm text-gray-500 mb-2">
                                Check if this customer qualifies for the 15% discount.
                            </p>
                            <form
                                method="POST"
                                action="{{ route('agent.registrations.check-promotion', $registration) }}"
                            >
                                @csrf
                                <x-secondary-button class="w-full justify-center">
                                    {{ __('Check Promotion') }}
                                </x-secondary-button>
                            </form>
                        </div>

                        <!-- Calculate Loan -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium text-gray-700 mb-2">Loan Calculation</h4>
                            <p class="text-sm text-gray-500 mb-2">
                                Calculate the loan amount based on price and down payment.
                            </p>
                            <form
                                method="POST"
                                action="{{ route('agent.registrations.calculate-loan', $registration) }}"
                            >
                                @csrf
                                <x-secondary-button class="w-full justify-center">
                                    {{ __('Calculate Loan') }}
                                </x-secondary-button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Notes</h3>
                    @if ($registration->notes)
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $registration->notes }}</p>
                    @else
                        <p class="text-sm text-gray-400 italic">No notes added yet.</p>
                    @endif
                </div>
            </div>

            <!-- Status History -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Status History</h3>
                    @if ($registration->statusLogs->isEmpty())
                        <p class="text-sm text-gray-500">No history recorded.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">From</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">To</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Changed By</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($registration->statusLogs as $log)
                                        <tr>
                                            <td class="px-4 py-3 text-sm">
                                                @if ($log->from_status)
                                                    <x-status-badge :status="$log->from_status" />
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <x-status-badge :status="$log->to_status" />
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500">
                                                {{ $log->changedBy?->name ?? 'System' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500">
                                                {{ $log->created_at->format('d M Y, h:i A') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
