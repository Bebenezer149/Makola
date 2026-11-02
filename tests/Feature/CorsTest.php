<?php

it('allows requests from the Bluespace frontend', function () {
    config()->set('cors.allowed_origins', [
        'https://bluespace.live',
        'https://www.bluespace.live',
    ]);

    $this->withHeader('Origin', 'https://bluespace.live')
        ->get('/up')
        ->assertHeader('Access-Control-Allow-Origin', 'https://bluespace.live');
});

it('allows preflight requests from the www Bluespace frontend', function () {
    config()->set('cors.allowed_origins', [
        'https://bluespace.live',
        'https://www.bluespace.live',
    ]);

    $this->withHeaders([
        'Origin' => 'https://www.bluespace.live',
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'content-type,authorization',
    ])->options('/api/login')
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'https://www.bluespace.live')
        ->assertHeader('Access-Control-Allow-Methods', 'POST')
        ->assertHeader('Access-Control-Allow-Headers', 'content-type,authorization');
});
