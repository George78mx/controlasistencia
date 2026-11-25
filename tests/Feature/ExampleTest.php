<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_sesion_redirige_al_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_la_pagina_de_login_carga(): void
    {
        $this->get('/login')->assertOk();
    }
}
