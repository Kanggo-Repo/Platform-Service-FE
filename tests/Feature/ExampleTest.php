<?php

test('root path redirects to workspace shell', function () {
    $this->get('/')
        ->assertRedirect('/workspace');
});
