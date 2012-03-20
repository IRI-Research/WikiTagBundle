<?php
/*
 * This file is part of the WikiTagBundle package.
 *
 * (c) IRI <http://www.iri.centrepompidou.fr/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace IRI\Bundle\WikiTagBundle\Event;

final class WikiTagEvents
{
    /**
     * the wikitag.tag_change events is thrown each time the tag list of a document is modified.
     * @var string
     */
    const onTagChanged = 'wikitag.tag_changed';

}