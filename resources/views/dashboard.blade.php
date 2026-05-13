<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Registrations -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Registrations</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($totalRegistrations) }}</p>
                </div>

                <!-- Promotion Eligible -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Promotion Eligible</p>
                    <p class="mt-2 text-3xl font-bold text-green-600">{{ number_format($promotionEligible) }}</p>
                </div>

                <!-- Loan Approved -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Loan Approved</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-600">{{ number_format($loanApproved) }}</p>
                </div>

                <!-- Active Registrations (non-terminal) -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Active</p>
                    <p class="mt-2 text-3xl font-bold text-blue-600">
                        {{ number_format($totalRegistrations - ($statusCounts['purchased'] ?? 0) - ($statusCounts['cancelled'] ?? 0)) }}
                    </p>
                </div>
            </div>

            <!-- Status Breakdown + Recent Registrations -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Status Breakdown -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Registrations by Status</h3>
                    <div class="space-y-3">
                        @foreach ($statusCounts as $statusValue => $count)
                            @php
                                $statusEnum = \App\Enums\RegistrationStatus::tryFrom($statusValue);
                                $percentage = $totalRegistrations > 0
                                    ? round(($count / $totalRegistrations) * 100, 1)
                                    : 0;
                            @endphp
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>
                                        @if ($statusEnum)
                                            <x-status-badge :status="$statusEnum" />
                                        @else
                                            <span class="text-gray-500">{{ $statusValue }}</span>
                                        @endif
                                    </span>
                                    <span class="text-gray-600 font-medium">{{ number_format($count) }} ({{ $percentage }}%)</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    @php
                                        $barColor = match($statusValue) {
                                            'registered' => '#3b82f6',
                                            'test_drive_scheduled' => '#eab308',
                                            'test_drive_completed' => '#a855f7',
                                            'purchased' => '#22c55e',
                                            'cancelled' => '#ef4444',
                                            default => '#6b7280',
                                        };
                                    @endphp
                                    <div
                                        class="h-2 rounded-full transition-all"
                                        style="width: {{ $percentage }}%; background-color: {{ $barColor }}"
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Recent Registrations -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Registrations</h3>
                    @if ($recentRegistrations->isEmpty())
                        <p class="text-sm text-gray-500">No registrations yet.</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($recentRegistrations as $reg)
                                <a
                                    href="{{ route('agent.registrations.show', $reg) }}"
                                    class="block p-3 rounded-lg border border-gray-100 hover:bg-gray-50 transition-colors"
                                >
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $reg->customer_name }}</p>
                                            <p class="text-xs text-gray-500">{{ $reg->customer_email }}</p>
                                        </div>
                                        <x-status-badge :status="$reg->status" />
                                    </div>
                                    <div class="mt-1 flex items-center gap-3 text-xs text-gray-400">
                                        <span>{{ $reg->car_model }}</span>
                                        <span>{{ $reg->created_at->diffForHumans() }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <div class="mt-4">
                            <a
                                href="{{ route('agent.registrations.index') }}"
                                class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                            >
                                View all registrations &rarr;
                            </a>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
