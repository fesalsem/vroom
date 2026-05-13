<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistrationRequest;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerRegistrationController extends Controller
{
    public function __construct(
        private readonly RegistrationService $registrationService,
    ) {}

    /**
     * Show the public test drive registration form.
     *
     * GET /test-drive
     */
    public function create(): View
    {
        return view('test-drive.create');
    }

    /**
     * Store a new test drive registration from the public form.
     *
     * POST /test-drive
     *
     * The car model defaults to "CapBay Vroom" at RM 200,000.
     * In a production system, this would be a dropdown selection.
     */
    public function store(StoreRegistrationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['car_model'] = 'CapBay Vroom';
        $data['car_price_cents'] = 20_000_000; // RM 200,000

        $this->registrationService->createRegistration($data);

        return redirect()->route('test-drive.thank-you');
    }

    /**
     * Show the thank-you page after successful registration.
     *
     * GET /test-drive/thank-you
     */
    public function thankYou(): View
    {
        return view('test-drive.thank-you');
    }
}
