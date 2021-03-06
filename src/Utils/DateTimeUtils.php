<?php

namespace RavuAlHemio\TotstrichBundle\Utils;


class DateTimeUtils
{
    /**
     * @param string $strDateTime
     * @return \DateTime|null
     */
    public static function tryParseFutureDateTime($strDateTime)
    {
        $intCurrentYear = (int) date('Y');
        $dtmNow = new \DateTime('now');
        return static::actuallyTryParseFutureDateTime($strDateTime, $intCurrentYear, $dtmNow);
    }

    /**
     * @param \DateInterval $dinInterval
     * @return string
     */
    public static function intervalToEnglish($dinInterval)
    {
        if ($dinInterval === null)
        {
            throw new \InvalidArgumentException('interval is null');
        }
        if ($dinInterval->days === false)
        {
            throw new \InvalidArgumentException('interval does not have a valid days value');
        }

        $intWeeks = floor($dinInterval->days / 7);
        $intDays = $dinInterval->days % 7;
        $intHours = $dinInterval->h;
        $intMinutes = $dinInterval->i;
        $intSeconds = $dinInterval->s;

        $arrPieces = [];

        if ($intWeeks == 1)
        {
            $arrPieces[] = '1 week';
        }
        else if ($intWeeks > 0)
        {
            $arrPieces[] = "{$intWeeks} weeks";
        }

        if ($intDays == 1)
        {
            $arrPieces[] = '1 day';
        }
        else if ($intDays > 0)
        {
            $arrPieces[] = "{$intDays} days";
        }

        if ($intHours == 1)
        {
            $arrPieces[] = '1 hour';
        }
        else if ($intHours > 0)
        {
            $arrPieces[] = "{$intHours} hours";
        }

        if ($intMinutes == 1)
        {
            $arrPieces[] = '1 minute';
        }
        else if ($intMinutes > 0)
        {
            $arrPieces[] = "{$intMinutes} minutes";
        }

        if (count($arrPieces) == 0)
        {
            // nothing until now
            if ($intSeconds == 0)
            {
                return 'now';
            }
            else if ($intSeconds == 1)
            {
                $arrPieces[] = '1 second';
            }
            else
            {
                $arrPieces[] = "{$intSeconds} seconds";
            }
        }

        $strFinalString = null;
        if (count($arrPieces) == 1)
        {
            $strFinalString = $arrPieces[0];
        }
        else
        {
            $arrInitialPieces = array_slice($arrPieces, 0, count($arrPieces) - 1);
            $strFinalString = implode(', ', $arrInitialPieces) . ' and ' . $arrPieces[count($arrPieces) - 1];
        }

        if ($dinInterval->invert)
        {
            return $strFinalString . ' ago';
        }
        else
        {
            return 'in ' . $strFinalString;
        }
    }

    /**
     * @param string $strDateTime
     * @param int $intCurrentYear
     * @param \DateTime $dtmNow
     * @return \DateTime|null
     */
    protected static function actuallyTryParseFutureDateTime($strDateTime, $intCurrentYear, $dtmNow)
    {
        $arrFormats = [
            // "10:40"
            '\\s*(?P<hour>[0-9]{1,2}):(?P<minute>[0-9]{1,2})\\s*',

            // "10.4. 10:40", "10. 04. 1990 10:40"
            '\\s*(?P<day>[0-9]{1,2})\\.\\s*(?P<month>[0-9]{1,2})\\.(?:\\s*(?P<year>[0-9]+))?\\s+(?P<hour>[0-9]{1,2}):(?P<minute>[0-9]{1,2})\\s*',

            // "4/10 10:40", "4/10/90 10:40", "4/10/1990 10:40"
            '\\s*(?P<month>[0-9]{1,2})\\/(?P<day>[0-9]{1,2})(?:\\/(?P<year>[0-9]+))?\\s+(?P<hour>[0-9]{1,2}):(?P<minute>[0-9]{1,2})\\s*',

            // "1990-10-04 10:40"
            '\\s*(?P<year>[0-9]+)-(?P<month>[0-9]{1,2})-(?P<day>[0-9]{1,2})?\\s+(?P<hour>[0-9]{1,2}):(?P<minute>[0-9]{1,2})\\s*',
        ];

        foreach ($arrFormats as $strPattern)
        {
            if (preg_match("/^{$strPattern}$/", $strDateTime, $arrMatches) !== 1)
            {
                continue;
            }

            // yay

            $intDay = null;
            $blnPotentiallyAdjustCentury = false;
            $blnPotentiallyAdjustYear = false;
            $blnPotentiallyAdjustDay = false;
            if (array_key_exists('day', $arrMatches))
            {
                $intDay = (int) $arrMatches['day'];
                if ($intDay < 1 || $intDay > 31)
                {
                    continue;
                }

                $intMonth = (int) $arrMatches['month'];
                if ($intMonth < 1 || $intMonth > 12)
                {
                    continue;
                }

                $intYear = null;
                if (array_key_exists('year', $arrMatches) && $arrMatches['year'] !== '')
                {
                    // there is a year!
                    $intYear = (int) $arrMatches['year'];
                    if (strlen($arrMatches['year']) == 2)
                    {
                        // assume the year has been written in shorthand
                        $intYear += $intCurrentYear - ($intCurrentYear % 100);
                        $blnPotentiallyAdjustCentury = true;
                    }
                }
                else
                {
                    // no year; go with the current one
                    $intYear = $intCurrentYear;
                    $blnPotentiallyAdjustYear = true;
                }
            }
            else
            {
                $intYear = (int) $dtmNow->format('Y');
                $intMonth = (int) $dtmNow->format('m');
                $intDay = (int) $dtmNow->format('d');
                $blnPotentiallyAdjustDay = true;
            }

            $intHour = (int) $arrMatches['hour'];
            if ($intHour > 23)
            {
                continue;
            }

            $intMinute = (int) $arrMatches['minute'];
            if ($intMinute > 59)
            {
                continue;
            }

            // assemble
            $dtmDateTime = static::buildDateTime($intYear, $intMonth, $intDay, $intHour, $intMinute);
            if ($dtmDateTime === null)
            {
                // uh-oh
                continue;
            }

            if ($dtmDateTime < $dtmNow)
            {
                // try adjustments
                if ($blnPotentiallyAdjustCentury)
                {
                    $dtmCenturyDateTime = static::buildDateTime($intYear + 100, $intMonth, $intDay, $intHour, $intMinute);
                    if ($dtmCenturyDateTime < $dtmNow)
                    {
                        // both are behind, assume the user knew what they were doing
                        return $dtmDateTime;
                    }
                    else
                    {
                        return $dtmCenturyDateTime;
                    }
                }
                else if ($blnPotentiallyAdjustYear)
                {
                    $dtmYearDateTime = static::buildDateTime($intYear + 1, $intMonth, $intDay, $intHour, $intMinute);
                    if ($dtmYearDateTime < $dtmNow)
                    {
                        // both are behind, assume the user knew what they were doing
                        return $dtmDateTime;
                    }
                    else
                    {
                        return $dtmYearDateTime;
                    }
                }
                else if ($blnPotentiallyAdjustDay)
                {
                    $dtmDayLaterDateTime = clone $dtmDateTime;
                    $dtmDayLaterDateTime->add(new \DateInterval('P1D'));

                    // if there is a DST or timezone change in between, the hours and minutes don't match up anymore
                    // take the new date but the old time
                    $dtmDayDateTime = static::buildDateTime(
                        (int) $dtmDayLaterDateTime->format('Y'),
                        (int) $dtmDayLaterDateTime->format('m'),
                        (int) $dtmDayLaterDateTime->format('d'),
                        (int) $dtmDateTime->format('H'),
                        (int) $dtmDateTime->format('i'),
                        (int) $dtmDateTime->format('s')
                    );

                    if ($dtmDayDateTime < $dtmNow)
                    {
                        // both are behind, assume the user knew what they were doing
                        return $dtmDateTime;
                    }
                    else
                    {
                        return $dtmDayDateTime;
                    }
                }
            }

            return $dtmDateTime;
        }

        // nothing matched
        return null;
    }

    protected static function buildDateTime($intYear, $intMonth, $intDay, $intHour, $intMinute, $intSecond = 0)
    {
        return \DateTime::createFromFormat(
            '!Y-m-d H:i:s',
            sprintf('%04d-%02d-%02d %02d:%02d:%02d', $intYear, $intMonth, $intDay, $intHour, $intMinute, $intSecond)
        );
    }
}
