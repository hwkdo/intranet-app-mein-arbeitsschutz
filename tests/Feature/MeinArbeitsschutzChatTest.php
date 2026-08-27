<?php

use App\Models\User;
use Hwkdo\IntranetAppBase\Support\AiUsage;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Permission::findOrCreate(AiUsage::PERMISSION, 'web');
    Permission::findOrCreate('see-app-mein-arbeitsschutz', 'web');
});

test('chat page shows prism-chat when api token and allow_ai_usage exist', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['see-app-mein-arbeitsschutz', AiUsage::PERMISSION]);

    $settings = $user->settings;
    $settings->ai->openWebUiApiToken = 'test-token';
    $user->settings = $settings;
    $user->save();

    $response = $this->actingAs($user)->get(route('apps.mein-arbeitsschutz.chat'));

    $response->assertOk();
    $response->assertSeeLivewire('prism-chat');
});

test('chat page is forbidden without see-app permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(AiUsage::PERMISSION);

    $response = $this->actingAs($user)->get(route('apps.mein-arbeitsschutz.chat'));

    $response->assertForbidden();
});

test('chat page is forbidden without allow_ai_usage', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('see-app-mein-arbeitsschutz');

    $response = $this->actingAs($user)->get(route('apps.mein-arbeitsschutz.chat'));

    $response->assertForbidden();
});
