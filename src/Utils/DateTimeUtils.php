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
        $arrFormats = [
            // "10.4. 10:40", "10. 04. 1990 10:40"
            '\\s*(?P<day>[0-9]{1,2})\\.\\s*(?P<month>[0-9]{1,2})\\.(?:\\s*(?P<year>[0-9]+))?\\s+(?P<hour>[0-9]{1,2}):(?P<minute>[0-9]{1,2})\\s*',

            // "4/10 10:40", "4/10/90 10:40", "4/10/1990 10:40"
            '\\s*(?P<month>[0-9]{1,2})\\/(?P<day>[0-9]{1,2})\\/(?P<year>[0-9]+)?\\s+(?P<hour>[0-9]{1,2}):(?P<minute>[0-9]{1,2})\\s*',

            // "1990-10-04 10:40"
            '\\s*(?P<year>[0-9]+)-(?P<month>[0-9]{1,2})-(?P<day>[0-9]{1,2})?\\s+(?P<hour>[0-9]{1,2}):(?P<minute>[0-9]{1,2})\\s*',
        ];

        foreach ($arrFormats as $strPattern)
        {
            if (preg_match("/{$strPattern}/", $strDateTime, $arrMatches) !== 1)
            {
                continue;
            }

            // yay
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
            $blnPotentiallyAdjustCentury = false;
            $blnPotentiallyAdjustYear = false;
            if (array_key_exists('year', $arrMatches))
            {
                // there is a year!
                $intYear = (int) $arrMatches['year'];
                if (strlen($arrMatches['year']) == 2)
                {
                    // assume the year has been written in shorthand

                    $intCurrentYear = (int) date('Y');
                    $intYear += ($intCurrentYear % 100);

                    $blnPotentiallyAdjustCentury = true;
                }
            }
            else
            {
                // no year; go with the current one
                $intYear = (int) date('Y');
                $blnPotentiallyAdjustYear = true;
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

            $dtmNow = new \DateTime('now');
            if ($dtmDateTime < $dtmNow)
            {
                // try adjustments
                if ($blnPotentiallyAdjustCentury)
                {
                    $dtmCenturyDateTime = static::buildDateTime($intYear + 100, $intMonth, $intDay, $intHour, $intMinute);
                    if ($dtmCenturyDateTime < $dtmNow)
                    {
                        // both are behind, assume the user knew what they were doing
                        return $dtmNow;
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
                        return $dtmNow;
                    }
                    else
                    {
                        return $dtmYearDateTime;
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
