<?php

namespace Tests\Feature\Models;

use Illuminate\Support\Facades\App;
use Tests\TestCase;
use TypiCMS\Modules\Classschedules\Models\Classschedule;
use TypiCMS\Modules\Classschedules\Models\ClassScheduleItems;

/**
 * Classschedules: the most customised local module (child table with its own
 * admin endpoints, $fillable + $guarded, nullable translatable columns,
 * accessors built on relations).
 */
class ClassscheduleTest extends TestCase
{
    private function createSchedule(array $attributes = []): Classschedule
    {
        return Classschedule::create(array_merge([
            'title' => ['sr' => 'Распоред I година', 'sr-latn' => 'Raspored I godina', 'en' => 'Schedule year I'],
            'slug' => ['sr' => 'raspored-i-cr', 'sr-latn' => 'raspored-i', 'en' => 'schedule-i'],
            'status' => ['sr' => '1', 'sr-latn' => '1', 'en' => '0'],
            'summary' => ['sr' => '', 'sr-latn' => '', 'en' => ''],
            'year' => 1,
            'day_id' => 1,
            'announcement_category_id' => 1,
            'announcement_department_id' => 1,
            'from_date' => '2026-10-01 00:00:00',
            'to_date' => '2027-01-31 00:00:00',
        ], $attributes));
    }

    public function test_schedule_is_created_with_relations_and_accessors()
    {
        $schedule = Classschedule::find($this->createSchedule()->id);

        $this->assertSame('2026-10-01', $schedule->from_date->format('Y-m-d'));
        $this->assertSame(1, (int) $schedule->announcementCategory->id);
        $this->assertSame('Графички дизајн', $schedule->department);   // accessor on announcementDepartment, locale "sr"
        $this->assertSame(__('I'), $schedule->formatted_year);

        App::setLocale('sr-latn');
        $this->assertSame('Grafički dizajn', $schedule->fresh()->department);
    }

    public function test_schedule_items_belong_to_their_schedule()
    {
        $schedule = $this->createSchedule();
        $monday = ClassScheduleItems::create([
            'class_schedule_id' => $schedule->id, 'day_id' => 1, 'type' => 1, 'position' => 1,
            'title' => ['sr' => 'Цртање', 'sr-latn' => 'Crtanje', 'en' => 'Drawing'],
            'professor' => ['sr' => 'Проф. Петровић', 'sr-latn' => 'Prof. Petrović', 'en' => 'Prof. Petrović'],
            'location' => ['sr' => 'Сала 1', 'sr-latn' => 'Sala 1', 'en' => 'Room 1'],
            'from_date' => '2026-10-05 08:00:00', 'to_date' => '2026-10-05 10:00:00',
        ]);
        ClassScheduleItems::create(['class_schedule_id' => $schedule->id, 'day_id' => 2, 'type' => 2, 'title' => ['en' => 'Lab']]);

        $this->assertSame(2, $schedule->classScheduleItems()->count());
        $this->assertSame([$monday->id], ClassScheduleItems::day(1)->where('class_schedule_id', $schedule->id)->pluck('id')->all());
        $this->assertSame($schedule->id, $monday->classSchedule->id);
        $this->assertSame(__('Predavanja'), $monday->formatted_type);
        $this->assertSame('Prof. Petrović', $monday->getTranslation('professor', 'sr-latn'));
    }

    public function test_schedule_item_can_be_created_updated_and_deleted_through_admin_endpoints()
    {
        $this->actingAs($this->createSuperUser());
        $schedule = $this->createSchedule();

        $this->get("/admin/classschedules/{$schedule->id}/edit")->assertOk();

        $this->from("/admin/classschedules/{$schedule->id}/edit")
            ->post('/admin/class/schedules/item', [
                'class_schedule_id' => $schedule->id, 'day_id' => 3, 'type' => 1, 'position' => 1,
                'title' => ['sr' => 'Сликарство', 'sr-latn' => 'Slikarstvo', 'en' => 'Painting'],
                'professor' => ['sr' => '', 'sr-latn' => '', 'en' => ''],
                'location' => ['sr' => '', 'sr-latn' => '', 'en' => ''],
                'from_date' => '2026-10-07 12:00', 'to_date' => '2026-10-07 14:00',
            ])
            ->assertRedirect("/admin/classschedules/{$schedule->id}/edit");

        $item = ClassScheduleItems::where('class_schedule_id', $schedule->id)->firstOrFail();
        $this->assertSame('Slikarstvo', $item->getTranslation('title', 'sr-latn'));

        $this->put("/admin/class/schedules/item/{$item->id}", [
            'class_schedule_id' => $schedule->id, 'day_id' => 4, 'title' => ['en' => 'Painting II'],
            'from_date' => '2026-10-08 12:00', 'to_date' => '2026-10-08 14:00',
        ])->assertRedirect();
        $this->assertSame(4, (int) $item->fresh()->day_id);

        // Deletion is a POST in this module (not DELETE).
        $this->post("/admin/class/schedules/item/{$item->id}")->assertRedirect();
        $this->assertNull(ClassScheduleItems::find($item->id));
    }
}
