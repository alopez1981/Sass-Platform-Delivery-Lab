<?php

namespace App\Http\Controllers\Api;

use App\Features\MembersCanCloseOwnRequests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;

/**
 * Lets an Administrator turn feature flags on/off for their own
 * organization only — the point of Pennant's per-scope activation is that
 * one tenant can try a feature without it affecting anyone else.
 */
class FeatureFlagController extends Controller
{
    /**
     * @var array<string, class-string>
     */
    private const FLAGS = [
        'members-can-close-own-requests' => MembersCanCloseOwnRequests::class,
    ];

    public function index(Request $request)
    {
        $organization = $request->user()->organization;

        $flags = collect(self::FLAGS)->map(fn (string $class, string $key) => [
            'key' => $key,
            'active' => Feature::for($organization)->active($class),
        ])->values();

        return response()->json($flags);
    }

    public function update(Request $request, string $key)
    {
        abort_unless($request->user()->isAdministrator(), 403, 'Only an Administrator can change feature flags.');

        $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $class = self::FLAGS[$key] ?? abort(404, "Unknown feature flag [{$key}].");

        $organization = $request->user()->organization;

        $request->boolean('active')
            ? Feature::for($organization)->activate($class)
            : Feature::for($organization)->deactivate($class);

        return response()->json([
            'key' => $key,
            'active' => Feature::for($organization)->active($class),
        ]);
    }
}
