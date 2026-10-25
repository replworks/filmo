<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_the_first_screen_is_the_filmo_landing_page_with_an_admin_link(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Filmo')
            ->assertSee('배우 필모그래피 카탈로그')
            ->assertSee('영상에서 찾고')
            ->assertSee('Gemini')
            ->assertSee('NamuWiki')
            ->assertSee('Naver')
            ->assertSee('운영자 페이지로 이동')
            ->assertSee('/admin')
            ->assertDontSee('Laravel');
    }
}
