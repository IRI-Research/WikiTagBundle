<?php
/*
 * This file is part of the WikiTagBundle package.
 *
 * (c) IRI <http://www.iri.centrepompidou.fr/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IRI\Bundle\WikiTagBundle\Services;

class WikiTagServiceException extends \Exception
{
    /**
     * The error code
     * @var string
     */
    protected $error_code;
    
    public function __construct ($message = "", $code = 0, $previous = null, $error_code = "")
    {
        parent::__construct($message, $code, $previous);
        $this->error_code = $error_code;
    }
    
    public function getErrorCode() {
        return $this->error_code;
    }
}