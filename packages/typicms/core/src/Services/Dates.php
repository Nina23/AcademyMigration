<?php

namespace TypiCMS\Modules\Core\Services;

use Carbon\Carbon;
use DateTimeInterface;

class Dates
{
    /**
     * Carbon 2's formatLocalized(), which Carbon 3 removed: strftime() with the
     * date's local date and time, exactly as Carbon 2.73 did it.
     */
    public static function formatLocalized(DateTimeInterface $date, string $format): string
    {
        // Check for Windows to find and replace the %e modifier correctly.
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
        }

        return strftime($format, strtotime($date->format('Y-m-d H:i:s')));
    }

    /**
     * Concat two dates.
     *
     * @param string $startingDate
     * @param string $endingDate
     *
     * @return string
     */
    public static function concat($startingDate, $endingDate)
    {
        $startingDateArray = explode('-', $startingDate);
        $endingDateArray = explode('-', $endingDate);
        $endingDateFormat = '%A %d %B %Y';

        if ($startingDate == $endingDate) {
            $startingDateFormat = '%A %d %B %Y';

            return self::formatLocalized(Carbon::createFromFormat('Y-m-d', $startingDate), $startingDateFormat);
        }

        if ($startingDateArray[1] == $endingDateArray[1]) {
            // mois égaux
            $startingDateFormat = '%A %d';
        } elseif ($startingDateArray[0] == $endingDateArray[0]) {
            // annee égales
            $startingDateFormat = '%A %d %B';
        } else {
            $startingDateFormat = '%A %d %B %Y';
        }

        return self::formatLocalized(Carbon::createFromFormat('Y-m-d', $startingDate), $startingDateFormat)
            .' to '.self::formatLocalized(Carbon::createFromFormat('Y-m-d', $endingDate), $endingDateFormat);
    }
}
