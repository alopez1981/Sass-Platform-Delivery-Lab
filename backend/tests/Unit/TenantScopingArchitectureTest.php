<?php

use App\Models\Comment;
use App\Models\Request;
use App\Models\TenantScopedModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Architecture guardrail promised in ADR 0003: every model backed by a
 * table that has an `organization_id` column must extend TenantScopedModel
 * (which wires up the automatic OrganizationScope), so tenant isolation
 * never depends on a developer remembering to add it by hand.
 *
 * `User` is the one documented exception (see the ADR's amendment) — it is
 * asserted explicitly below rather than silently skipped, so this test
 * still fails loudly if someone removes that documentation without
 * updating this list.
 */
it('applies TenantScopedModel to every model whose table has organization_id, except the documented User exception', function () {
    $modelFiles = glob(app_path('Models/*.php'));
    $knownExceptions = [User::class];

    $checked = [];

    foreach ($modelFiles as $file) {
        $class = 'App\\Models\\'.basename($file, '.php');

        if (! class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
            continue;
        }

        /** @var Model $instance */
        $instance = new $class;
        $table = $instance->getTable();

        if (! Schema::hasColumn($table, 'organization_id')) {
            continue;
        }

        $checked[] = $class;

        if (in_array($class, $knownExceptions, true)) {
            expect($instance)->not->toBeInstanceOf(TenantScopedModel::class);
        } else {
            expect($instance)->toBeInstanceOf(TenantScopedModel::class);
        }
    }

    // Guard against the test silently checking nothing if models get moved.
    expect($checked)->toContain(Request::class, Comment::class, User::class);
});
