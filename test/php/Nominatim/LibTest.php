<?php
/**
 * SPDX-License-Identifier: GPL-2.0-only
 *
 * This file is part of Nominatim. (https://nominatim.org)
 *
 * Copyright (C) 2022 by the Nominatim developer community.
 * For a full list of authors see the git log.
 */

namespace Nominatim;

require_once(CONST_LibDir.'/lib.php');
require_once(CONST_LibDir.'/ClassTypes.php');

class LibTest extends \PHPUnit\Framework\TestCase
{

    public function testAddQuotes()
    {
        // FIXME: not quoting existing quote signs is probably a bug
        $this->assertSame("'St. John's'", addQuotes("St. John's"));
        $this->assertSame("''", addQuotes(''));
    }

    public function testParseLatLon()
    {
        // no coordinates expected
        $this->assertFalse(parseLatLon(''));
        $this->assertFalse(parseLatLon('abc'));
        $this->assertFalse(parseLatLon('12 34'));

        // coordinates expected
        $this->assertNotNull(parseLatLon('0.0 -0.0'));

        $aRes = parseLatLon(' abc 12.456 -78.90 def ');
        $this->assertEquals($aRes[1], 12.456);
        $this->assertEquals($aRes[2], -78.90);
        $this->assertEquals($aRes[0], ' 12.456 -78.90 ');

        $aRes = parseLatLon(' [12.456,-78.90] ');
        $this->assertEquals($aRes[1], 12.456);
        $this->assertEquals($aRes[2], -78.90);
        $this->assertEquals($aRes[0], ' [12.456,-78.90] ');

        $aRes = parseLatLon(' -12.456,-78.90 ');
        $this->assertEquals($aRes[1], -12.456);
        $this->assertEquals($aRes[2], -78.90);
        $this->assertEquals($aRes[0], ' -12.456,-78.90 ');

        // http://en.wikipedia.org/wiki/Geographic_coordinate_conversion
        // these all represent the same location
        $aQueries = array(
                     '40 26.767 N 79 58.933 W',
                     '40° 26.767′ N 79° 58.933′ W',
                     "40° 26.767' N 79° 58.933' W",
                     "40° 26.767'
                         N 79° 58.933' W",
                     'N 40 26.767, W 79 58.933',
                     'N 40°26.767′, W 79°58.933′',
                     '	N 40°26.767′, W 79°58.933′',
                     "N 40°26.767', W 79°58.933'",
 
                     '40 26 46 N 79 58 56 W',
                     '40° 26′ 46″ N 79° 58′ 56″ W',
                     '40° 26′ 46.00″ N 79° 58′ 56.00″ W',
                     '40°26′46″N 79°58′56″W',
                     'N 40 26 46 W 79 58 56',
                     'N 40° 26′ 46″, W 79° 58′ 56″',
                     'N 40° 26\' 46", W 79° 58\' 56"',
                     'N 40° 26\' 46", W 79° 58\' 56"',
 
                     '40.446 -79.982',
                     '40.446,-79.982',
                     '40.446° N 79.982° W',
                     'N 40.446° W 79.982°',
 
                     '[40.446 -79.982]',
                     '[40.446,-79.982]',
                     '       40.446  ,   -79.982     ',
                     '       40.446  ,   -79.982     ',
                     '       40.446	,   -79.982	',
                     '       40.446,   -79.982	',
                    );


        foreach ($aQueries as $sQuery) {
            $aRes = parseLatLon($sQuery);
            $this->assertEqualsWithDelta(40.446, $aRes[1], 0.01, 'degrees decimal ' . $sQuery);
            $this->assertEqualsWithDelta(-79.982, $aRes[2], 0.01, 'degrees decimal ' . $sQuery);
            $this->assertEquals($sQuery, $aRes[0]);
        }
    }

    private function closestHouseNumberEvenOddOther($startnumber, $endnumber, $fraction, $aExpected)
    {
        foreach (array('even', 'odd', 'other') as $itype) {
            $this->assertEquals(
                $aExpected[$itype],
                closestHouseNumber(array(
                                    'startnumber' => $startnumber,
                                    'endnumber' => $endnumber,
                                    'fraction' => $fraction,
                                    'interpolationtype' => $itype
                                   )),
                "$startnumber => $endnumber, $fraction, $itype"
            );
        }
    }

    public function testClosestHouseNumber()
    {
        $this->closestHouseNumberEvenOddOther(50, 100, 0.5, array('even' => 76, 'odd' => 75, 'other' => 75));
        // upper bound
        $this->closestHouseNumberEvenOddOther(50, 100, 1.5, array('even' => 100, 'odd' => 100, 'other' => 100));
        // lower bound
        $this->closestHouseNumberEvenOddOther(50, 100, -0.5, array('even' => 50, 'odd' => 50, 'other' => 50));
        // fraction 0
        $this->closestHouseNumberEvenOddOther(50, 100, 0, array('even' => 50, 'odd' => 51, 'other' => 50));
        // start == end
        $this->closestHouseNumberEvenOddOther(50, 50, 0.5, array('even' => 50, 'odd' => 50, 'other' => 50));
    }
}
