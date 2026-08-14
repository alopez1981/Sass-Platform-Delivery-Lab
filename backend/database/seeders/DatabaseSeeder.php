<?php

namespace Database\Seeders;

use App\Enums\RequestStatus;
use App\Models\Organization;
use App\Models\Request;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Reproducible demo data: two independent organizations, each with one user
 * per role and a handful of requests in different states. Having two
 * organizations from the start is deliberate — it is what lets Session 3's
 * tenant-isolation tests (and manual exploration) prove that org A can never
 * see org B's data.
 *
 * Demo password for every seeded user: "password". This is public demo data,
 * never used outside a local/demo environment.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedOrganization(
            name: 'Northwind Logistics',
            slug: 'northwind-logistics',
            domain: 'northwind.test',
        );

        $this->seedOrganization(
            name: 'Blue Harbor Retail',
            slug: 'blue-harbor-retail',
            domain: 'blueharbor.test',
        );
    }

    private function seedOrganization(string $name, string $slug, string $domain): void
    {
        $organization = Organization::factory()->create([
            'name' => $name,
            'slug' => $slug,
        ]);

        $admin = User::factory()->administrator()->create([
            'organization_id' => $organization->id,
            'name' => 'Ada Administrator',
            'email' => "admin@{$domain}",
        ]);

        $manager = User::factory()->manager()->create([
            'organization_id' => $organization->id,
            'name' => 'Mia Manager',
            'email' => "manager@{$domain}",
        ]);

        $member = User::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Max Member',
            'email' => "member@{$domain}",
        ]);

        $draft = Request::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $member->id,
            'title' => 'Set up new workstation',
            'status' => RequestStatus::Draft,
        ]);

        $inProgress = Request::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $member->id,
            'assigned_to' => $manager->id,
            'title' => 'Fix printer on 2nd floor',
            'status' => RequestStatus::Open,
        ]);
        $this->transition($inProgress, $manager, RequestStatus::Open, RequestStatus::InProgress);

        $resolved = Request::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $member->id,
            'assigned_to' => $admin->id,
            'title' => 'Grant access to reporting dashboard',
            'status' => RequestStatus::Open,
        ]);
        $this->transition($resolved, $admin, RequestStatus::Open, RequestStatus::InProgress);
        $this->transition($resolved, $admin, RequestStatus::InProgress, RequestStatus::Resolved);

        $resolved->comments()->create([
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'body' => 'Access granted, please confirm you can log in.',
        ]);
    }

    private function transition(Request $request, User $changedBy, RequestStatus $from, RequestStatus $to): void
    {
        $request->statusHistories()->create([
            'organization_id' => $request->organization_id,
            'changed_by' => $changedBy->id,
            'from_status' => $from,
            'to_status' => $to,
        ]);

        $request->update(['status' => $to]);
    }
}
