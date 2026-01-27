<?php

use App\Models\User;

test('chat page shows the new livewire chat widget when api token exists', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('see-app-mein-arbeitsschutz');

    $settings = $user->settings;
    $settings->ai->openWebUiApiToken = 'test-token';
    $user->settings = $settings;
    $user->save();

    $response = $this->actingAs($user)->get(route('apps.mein-arbeitsschutz.chat'));

    $response->assertOk();
    $response->assertSeeLivewire('open-web-ui-chat');
});

test('chat page is forbidden without permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('apps.mein-arbeitsschutz.chat'));

    $response->assertForbidden();
});
