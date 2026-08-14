<?php

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Creates an Organization with one User per role (Administrator, Manager,
 * Member). Used across feature tests that need a realistic tenant, and by
 * the multi-tenant isolation tests that need two independent ones.
 *
 * @return array{0: Organization, 1: User, 2: User, 3: User}
 */
function makeOrgWithUsers(): array
{
    $organization = Organization::factory()->create();

    return [
        $organization,
        User::factory()->create(['organization_id' => $organization->id, 'role' => UserRole::Administrator]),
        User::factory()->create(['organization_id' => $organization->id, 'role' => UserRole::Manager]),
        User::factory()->create(['organization_id' => $organization->id, 'role' => UserRole::Member]),
    ];
}
