<?php

it('allows requests from the Bluespace frontend', function () {
    config()->set('cors.allowed_origins', ['https://bluespace.live']);

    $this->withHeader('Origin', 'https://bluespace.live')
        ->get('/up')
        ->assertHeader('Access-Control-Allow-Origin', 'https://bluespace.live');
});
