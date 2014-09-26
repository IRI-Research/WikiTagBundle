<?php
/*
 * This file is part of the WikiTagBundle package.
 *
 * (c) IRI <http://www.iri.centrepompidou.fr/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IRI\Bundle\WikiTagBundle;

/**
 * Class to store and retrieve WikiTagBundle Version
 *
 */
class Version {

    /** 
     * Current WikiTagBundle Version
     */
    const VERSION = '0.18';

    /** 
     * Compares a WikiTagBundlee version with the current one.
     *
     * @param string $version WikiTageBundle version to compare.
     * @return int Returns -1 if older, 0 if it is the same, 1 if version 
     *             passed as argument is newer.
     */
    public static function compare($version)
    {   
        $currentVersion = str_replace(' ', '', strtolower(self::VERSION));
        $version = str_replace(' ', '', $version);

        return version_compare($version, $currentVersion);
    }   

}
