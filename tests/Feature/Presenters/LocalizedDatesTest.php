<?php

namespace Tests\Feature\Presenters;

use Tests\TestCase;
use TypiCMS\Modules\Core\Presenters\Presenter;
use TypiCMS\Modules\Core\Services\Dates;
use TypiCMS\Modules\News\Models\News;

/**
 * Localized dates of the core Presenter and Dates::concat(), as Carbon 2's
 * formatLocalized() (strftime) renders them. Carbon 3 removed formatLocalized().
 *
 * The server runs in the "C" locale (core SetLocale builds invalid locale names,
 * so setlocale() fails), hence the English names; %e pads the day with a space.
 * No module view reaches these methods today: the modules that render
 * dateLocalized override it with format('d.m.Y').
 */
class LocalizedDatesTest extends TestCase
{
    private function presenter(string $date): Presenter
    {
        $news = new News();
        $news->date = $date;

        return new class($news) extends Presenter {};
    }

    public function test_core_presenter_localized_dates()
    {
        $this->assertSame(' 5 October 2026', $this->presenter('2026-10-05 08:00:00')->dateLocalized('date'));
        $this->assertSame(' 5 October 2026 08:00', $this->presenter('2026-10-05 08:00:00')->dateTimeLocalized('date'));
        $this->assertSame('15 January 2026', $this->presenter('2026-01-15 00:00:00')->dateLocalized('date'));
        $this->assertSame('31 December 2026 23:59', $this->presenter('2026-12-31 23:59:59')->dateTimeLocalized('date'));
        $this->assertSame(' 1 February 2025 13:07', $this->presenter('2025-02-01 13:07:00')->dateTimeLocalized('date'));
    }

    public function test_dates_concat()
    {
        $this->assertSame('Monday 05 October 2026', Dates::concat('2026-10-05', '2026-10-05'));
        $this->assertSame('Monday 05 to Wednesday 07 October 2026', Dates::concat('2026-10-05', '2026-10-07'));
        $this->assertSame('Monday 05 October to Monday 02 November 2026', Dates::concat('2026-10-05', '2026-11-02'));
        $this->assertSame('Tuesday 30 December 2025 to Friday 02 January 2026', Dates::concat('2025-12-30', '2026-01-02'));
    }
}
