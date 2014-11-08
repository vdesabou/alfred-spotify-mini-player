<?php
/*
    PHPRtfLite
    Copyright 2010-2012 Steffen Zeidler <sigma_z@sigma-scripts.de>

    This file is part of PHPRtfLite.

    PHPRtfLite is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PHPRtfLite is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with PHPRtfLite.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * unit class for working with twips in rtf.
 * @version     1.2
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2010-2012 Steffen Zeidler
 */
class PHPRtfLite_Unit
{

    const UNIT_TWIPS        = 1;
    const UNIT_CM           = 567;
    const UNIT_INCH         = 1440;
    const UNIT_POINT        = 14.988078;


    /**
     *
     * @var float
     */
    private static $_unit = self::UNIT_CM;


    /**
     * sets global unit
     *
     * @param string $unit
     */
    public static function setGlobalUnit($unit)
    {
        self::$_unit = $unit;
    }


    /**
     * gets global unit
     *
     * @return string
     */
    public static function getGlobalUnit()
    {
        return self::$_unit;
    }


    /**
     * gets unit in twips
     *
     * @param  float $value
     * @return float
     */
    public static function getUnitInTwips($value)
    {
        return round($value * self::$_unit);
    }


    /**
     * gets points in twips
     *
     * @param  float $value
     * @return integer
     */
    public static function getPointsInTwips($value)
    {
        return round($value * self::UNIT_POINT);
    }


    /**
     * converts the value to another unit
     *
     * @param  float $value
     * @param  float $unitFrom
     * @param  float $unitTo
     * @return float
     */
    public static function convertTo($value, $unitFrom, $unitTo)
    {
        if ($unitTo == $unitFrom) {
            return $value;
        }
        return ($value * $unitFrom) / $unitTo;
    }

}