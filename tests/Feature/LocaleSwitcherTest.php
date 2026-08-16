<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocaleSwitcherTest extends TestCase
{
    public function test_it_switches_locale_to_indonesian(): void
    {
        $response = $this->get(route('lang.switch', 'id'));

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'id');
    }

    public function test_it_switches_locale_to_english(): void
    {
        $response = $this->withSession(['locale' => 'id'])
            ->get(route('lang.switch', 'en'));

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
    }

    public function test_it_ignores_invalid_locale(): void
    {
        $response = $this->get(route('lang.switch', 'fr'));

        $response->assertRedirect();
        $response->assertSessionMissing('locale');
    }

    public function test_it_renders_homepage_in_indonesian_when_locale_is_set(): void
    {
        $response = $this->withSession(['locale' => 'id'])->get('/');

        $response->assertOk();
        $response->assertSee('Audit repositori GitHub publik dengan percaya diri.');
        $response->assertSee('Analisis Repositori');
    }
}
