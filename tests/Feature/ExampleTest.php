<?php

it('returns a successful response', function () {
    $response = $this->get('/');

    $this->assertTrue(
        in_array($response->getStatusCode(), [200, 302]),
        'Response status is not 200 or 302'
    );
});
