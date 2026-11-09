<?php

namespace App\Http\Controllers;

use App\Services\OrganizationService;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationSettingsController extends Controller
{
    public function __construct(
        private OrganizationService $organizationService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('settings/organization', $this->organizationService->settings());
    }
}