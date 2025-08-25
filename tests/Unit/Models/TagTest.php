<?php

namespace Models;

use App\Models\Domain;
use App\Models\Link;
use App\Models\Tag;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_a_tag()
    {
        $tag = Tag::factory()->create([
            'name' => 'Test Tag',
        ]);

        $this->assertDatabaseHas('tags', [
            'name' => 'Test Tag',
        ]);

        $this->assertEquals('Test Tag', $tag->name);
    }

    #[Test]
    public function it_has_links_relationship()
    {
        $tag = Tag::factory()->create();
        $link = Link::factory()->create();

        $tag->links()->attach($link);

        $this->assertTrue($tag->links->contains($link));
        $this->assertEquals(1, $tag->links->count());
    }

    #[Test]
    public function it_can_be_converted_to_string()
    {
        $tag = Tag::factory()->create([
            'name' => 'Test Tag',
        ]);

        $this->assertEquals('Test Tag', (string) $tag);
    }

    #[Test]
    public function it_has_timestamps()
    {
        $tag = Tag::factory()->create();

        $this->assertNotNull($tag->created_at);
        $this->assertNotNull($tag->updated_at);
    }

    #[Test]
    public function it_has_guarded_attributes()
    {
        $tag = new Tag;

        $this->assertEquals([], $tag->getGuarded());
    }

    #[Test]
    public function it_has_visits_relationship(): void
    {
        $domain = Domain::factory()->create(['is_active' => true, 'is_admin_panel_active' => true]);
        $tag = Tag::factory()->create();
        $link = Link::factory()->create();
        $link->tags()->attach($tag);
        $visit = Visit::factory()->create(['link_id' => $link->id, 'domain_id' => $domain->id]);

        $this->assertTrue($tag->visits->contains($visit));
        $this->assertEquals(1, $tag->visits->count());
    }
}
