<?php

namespace Tests\RavuAlHemio\TotstrichBundle\Utils;

use RavuAlHemio\TotstrichBundle\Utils\DateTimeUtils;


class DateTimeUtilsTest extends \PHPUnit_Framework_TestCase
{
    protected static function tryParseFutureDateTime($strDateTime)
    {
        $dtmFakeNow = \DateTime::createFromFormat('!Y-m-d H:i:s', '2016-10-19 23:45:00');
        $intCurrentYear = 2016;

        $objReflect = new \ReflectionClass('\\RavuAlHemio\\TotstrichBundle\\Utils\\DateTimeUtils');
        $objMethod = $objReflect->getMethod('actuallyTryParseFutureDateTime');
        $objMethod->setAccessible(true);

        return $objMethod->invoke(null, $strDateTime, $intCurrentYear, $dtmFakeNow);
    }

    protected function assertTryParseFutureDateTimeEqual($strExpected, $strInput)
    {
        if ($strExpected === null)
        {
            $this->assertNull(static::tryParseFutureDateTime($strInput));
        }
        else
        {
            $dtmExpected = \DateTime::createFromFormat('!Y-m-d H:i:s', $strExpected);
            $this->assertEquals($dtmExpected, static::tryParseFutureDateTime($strInput), "{$strInput} is parsed to mean {$strExpected}");
        }
    }

    protected function assertIntervalToEnglishEquals($strExpected, $strIntervalSpec)
    {
        $dinInterval = new \DateInterval($strIntervalSpec);
        $dtmNow = new \DateTime('now');
        $dtmThen = clone $dtmNow;
        $dtmThen->add($dinInterval);
        $dinActualInterval = $dtmNow->diff($dtmThen);

        $this->assertEquals($strExpected, DateTimeUtils::intervalToEnglish($dinActualInterval));
    }

    public function testTryParseFutureDateTime()
    {
        $this->assertTryParseFutureDateTimeEqual(null, '');

        $arrChristmas = [
            '2014-12-24 20:01',
            '2014-12-24  20:01',
            '24.12.2014 20:01',
            '24.12.2014  20:01',
            '24. 12. 2014  20:01',
            '12/24/2014 20:01',
            '12/24/2014   20:01',
        ];
        foreach ($arrChristmas as $strChristmas)
        {
            $this->assertTryParseFutureDateTimeEqual('2014-12-24 20:01:00', $strChristmas);
        }

        $arrUpcomingChristmas = [
            '24.12. 20:01',
            '24. 12. 20:01',
            '24. 12.  20:01',
            '12/24 20:01',
            '12/24  20:01',
        ];
        foreach ($arrUpcomingChristmas as $strChristmas)
        {
            $this->assertTryParseFutureDateTimeEqual('2016-12-24 20:01:00', $strChristmas);
        }

        // test advancing the year if the first guess is in the past
        $arrUpcomingAprilFools = [
            '1.4. 09:03',
            '1. 4. 09:03',
            '01.04. 09:03',
            '01. 04. 09:03',
            '4/1 09:03',
            '04/01 09:03',
            '4/01 09:03'
        ];
        foreach ($arrUpcomingAprilFools as $strAprilFools)
        {
            $this->assertTryParseFutureDateTimeEqual('2017-04-01 09:03:00', $strAprilFools);
        }

        // test autoguessing the century
        $arrChristmas16 = [
            '24.12.16 20:01',
            '24.12.16  20:01',
            '24. 12. 16  20:01',
            '12/24/16 20:01',
            '12/24/16   20:01',
        ];
        foreach ($arrChristmas16 as $strChristmas)
        {
            $this->assertTryParseFutureDateTimeEqual('2016-12-24 20:01:00', $strChristmas);
        }

        // test fixing up the century if the first guess is in the past
        $arrChristmas14 = [
            '24.12.14 20:01',
            '24.12.14  20:01',
            '24. 12. 14  20:01',
            '12/24/14 20:01',
            '12/24/14   20:01',
        ];
        foreach ($arrChristmas14 as $strChristmas)
        {
            $this->assertTryParseFutureDateTimeEqual('2114-12-24 20:01:00', $strChristmas);
        }

        // test time-only inputs (today and tomorrow)
        $this->assertTryParseFutureDateTimeEqual('2016-10-19 23:59:00', '23:59');
        $this->assertTryParseFutureDateTimeEqual('2016-10-20 13:37:00', '13:37');
    }

    public function testIntervalToEnglish()
    {
        $this->assertIntervalToEnglishEquals('in 2 weeks', 'P14D');
        $this->assertIntervalToEnglishEquals('in 1 week', 'P7D');
        $this->assertIntervalToEnglishEquals('in 6 days', 'P6D');
        $this->assertIntervalToEnglishEquals('in 1 day', 'PT24H');
        $this->assertIntervalToEnglishEquals('in 14 hours', 'PT14H');
        $this->assertIntervalToEnglishEquals('in 1 hour', 'PT60M');
        $this->assertIntervalToEnglishEquals('in 33 minutes', 'PT33M');
        $this->assertIntervalToEnglishEquals('in 1 minute', 'PT60S');
        $this->assertIntervalToEnglishEquals('in 2 seconds', 'PT2S');
        $this->assertIntervalToEnglishEquals('in 1 second', 'PT1S');
        $this->assertIntervalToEnglishEquals('now', 'P0D');

        $this->assertIntervalToEnglishEquals('in 2 weeks and 6 days', 'P20D');
        $this->assertIntervalToEnglishEquals('in 1 week and 1 day', 'P8D');
        $this->assertIntervalToEnglishEquals('in 2 weeks, 6 days and 14 hours', 'P20DT14H');
        $this->assertIntervalToEnglishEquals('in 2 weeks, 6 days, 14 hours and 33 minutes', 'P20DT14H33M');
        $this->assertIntervalToEnglishEquals('in 1 week, 1 day, 1 hour and 1 minute', 'P8DT1H1M');
        $this->assertIntervalToEnglishEquals('in 1 week, 2 hours and 1 minute', 'P7DT2H1M');

        // don't bother with seconds if there are larger units
        $this->assertIntervalToEnglishEquals('in 2 weeks, 6 days, 14 hours and 33 minutes', 'P20DT14H33M2S');
        $this->assertIntervalToEnglishEquals('in 1 week, 1 day, 1 hour and 1 minute', 'P8DT1H1M1S');
    }
}
