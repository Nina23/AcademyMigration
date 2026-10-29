<?php

namespace Tests\Feature\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use TypiCMS\Modules\Advertismentboards\Models\Advertismentboard;
use TypiCMS\Modules\Announcements\Models\Announcement;
use TypiCMS\Modules\Classschedules\Models\Classschedule;
use TypiCMS\Modules\Classschedules\Models\ClassScheduleItems;
use TypiCMS\Modules\News\Models\News;

/**
 * The date columns of the 5 module models (declared in $dates up to Laravel 9,
 * as 'datetime' casts from Laravel 10 on, UPGRADE_PLAN §4): Carbon on read,
 * the same stored format on write, the same JSON, and no false dirty state.
 */
class DateCastsTest extends TestCase
{
    private const TRANSLATED = ['sr' => '', 'sr-latn' => '', 'en' => ''];

    public function test_advertismentboard_date()
    {
        $board = Advertismentboard::create([
            'title' => self::TRANSLATED, 'slug' => self::TRANSLATED, 'status' => self::TRANSLATED,
            'summary' => self::TRANSLATED, 'body' => self::TRANSLATED,
            'date' => '2026-10-05 10:00:07',
        ]);

        $this->assertDateColumn($board, 'date', '2026-10-05 10:00:07', '2026-10-05T10:00:07.000000Z');
    }

    public function test_announcement_date_and_expiry()
    {
        $announcement = Announcement::create([
            'title' => self::TRANSLATED, 'slug' => self::TRANSLATED, 'status' => self::TRANSLATED,
            'summary' => self::TRANSLATED, 'body' => self::TRANSLATED, 'highlight' => self::TRANSLATED,
            'program' => '[]',
            'date' => '2026-10-05 10:00', 'expiry' => '2026-11-30T23:59:00+01:00',
        ]);

        $this->assertDateColumn($announcement, 'date', '2026-10-05 10:00:00', '2026-10-05T10:00:00.000000Z');
        // An offset is dropped on write (the column has no time zone), as before.
        $this->assertDateColumn($announcement, 'expiry', '2026-11-30 23:59:00', '2026-11-30T23:59:00.000000Z');
    }

    public function test_classschedule_from_to_and_expiry()
    {
        $schedule = Classschedule::create([
            'title' => self::TRANSLATED, 'slug' => self::TRANSLATED, 'status' => self::TRANSLATED,
            'summary' => self::TRANSLATED,
            'from_date' => '2026-10-01', 'to_date' => Carbon::create(2027, 1, 31, 12, 30),
        ]);

        $this->assertDateColumn($schedule, 'from_date', '2026-10-01 00:00:00', '2026-10-01T00:00:00.000000Z');
        $this->assertDateColumn($schedule, 'to_date', '2027-01-31 12:30:00', '2027-01-31T12:30:00.000000Z');
        $this->assertNullDateColumn($schedule, 'expiry');
    }

    public function test_class_schedule_item_from_and_to()
    {
        $item = ClassScheduleItems::create([
            'title' => self::TRANSLATED,
            'from_date' => new \DateTime('2026-10-05 08:00:00'), 'to_date' => '2026-10-05 10:00:00',
        ]);

        $this->assertDateColumn($item, 'from_date', '2026-10-05 08:00:00', '2026-10-05T08:00:00.000000Z');
        $this->assertDateColumn($item, 'to_date', '2026-10-05 10:00:00', '2026-10-05T10:00:00.000000Z');
    }

    public function test_news_date()
    {
        $news = $this->createNews(['date' => '2026-01-15']);

        // A date column: the time part written by Eloquent is dropped by MariaDB.
        $this->assertDateColumn($news, 'date', '2026-01-15', '2026-01-15T00:00:00.000000Z');
    }

    private function assertDateColumn(Model $created, string $column, string $stored, string $json): void
    {
        $this->assertSame($stored, DB::table($created->getTable())->where('id', $created->id)->value($column));

        $model = $created->newQuery()->find($created->id);
        $this->assertInstanceOf(Carbon::class, $model->{$column});
        $this->assertSame(substr($json, 0, 10).' '.substr($json, 11, 8), $model->{$column}->format('Y-m-d H:i:s'));
        $this->assertSame($json, $model->toArray()[$column]);
        $this->assertSame($json, json_decode($model->toJson(), true)[$column]);

        // Assigning the same moment in another form is not a change.
        $model->{$column} = $model->{$column}->format('Y-m-d H:i:s');
        $this->assertFalse($model->isDirty($column));
    }

    private function assertNullDateColumn(Model $created, string $column): void
    {
        $this->assertNull(DB::table($created->getTable())->where('id', $created->id)->value($column));

        $model = $created->newQuery()->find($created->id);
        $this->assertNull($model->{$column});
        $this->assertNull($model->toArray()[$column]);
    }
}
