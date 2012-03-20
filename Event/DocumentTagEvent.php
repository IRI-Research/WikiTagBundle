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

use Symfony\Component\EventDispatcher\Event;
use IRI\Bundle\WikiTagBundle\Model\DocumentInterface;

class DocumentTagEvent extends Event
{
    protected $document;

    public function __construct(DocumentInterface $doc)
    {
        $this->document = $doc;
    }

    public function getDocument()
    {
        return $this->document;
    }
}