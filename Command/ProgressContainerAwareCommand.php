<?php
/*
 * This file is part of the WikiTagBundle package.
 *
 * (c) IRI <http://www.iri.centrepompidou.fr/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace IRI\Bundle\WikiTagBundle\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class ProgressContainerAwareCommand extends ContainerAwareCommand
{
    protected function showProgress(OutputInterface $output, $current, $total, $label, $width)
    {
        $percent = (floatval($current)/floatval($total)) * 100.0;
        $marks = intval(floor(floatval($width) * ($percent / 100.0) ));
        $spaces = $width - $marks;
        
        $status_bar="\r[";
        $status_bar.=str_repeat("=", $marks);
        if($marks<$width){
            $status_bar.=">";
            $status_bar.=str_repeat(" ", $spaces);
        } else {
            $status_bar.="=";
        }
        
        $disp=str_pad(number_format($percent, 0),3, " ", STR_PAD_LEFT);
        
        $label = str_pad(substr($label,0,50), 50, " ");
        $current_str = str_pad($current, strlen("$total"), " ", STR_PAD_LEFT);
        
        $status_bar.="] $disp%  $current_str/$total : $label";
        
        $output->write("$status_bar  ");
        
        if($current == $total) {
            $output->writeln("");
        }
    }
    
}