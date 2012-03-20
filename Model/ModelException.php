<?php
/*
 * This file is part of the WikiTagBundle package.
 *
 * (c) IRI <http://www.iri.centrepompidou.fr/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace IRI\Bundle\WikiTagBundle\Model;

class ModelException extends \Exception
{
    /**
     * Construct exception
     * @param $message[optional]
     * @param $code[optional]
     * @param $previous[optional]
     */
    public function __construct($message=null, $code=null, $previous=null)
    {
        parent::__construct($message,$code,$previous);
    }
    
}