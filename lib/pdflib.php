<?php  // $Id: pdflib.php,v 1.7 2008/07/29 22:22:36 mudrd8mz Exp $

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas  http://moodle.com          //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * pdflib.php - Moodle PDF library
 * 
 * We currently use the TCPDF library by Nicola Asuni.
 *
 * The default location for fonts that are included with TCPDF is
 * lib/tcpdf/fonts/. If $CFG->datadir.'/fonts/' exists, this directory
 * will be used instead of lib/tcpdf/fonts/. If there is only one font
 * present in $CFG->datadir.'/fonts/', the font is used as the default
 * font.
 * 
 * See lib/tcpdf/fonts/README for details on how to convert fonts for use
 * with TCPDF.
 * 
 * Example usage:
 *    $doc = new pdf;
 *    $doc->setPrintHeader(false);
 *    $doc->setPrintFooter(false);
 *    $doc->AddPage();
 *    $doc->Write(5, 'Hello World!');
 *    $doc->Output();
 * 
 * @author Vy-Shane Sin Fat
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package moodlecore
 */



/// Includes
require_once(dirname(__FILE__).'/tcpdf/tcpdf.php');



/// Constants
define('PDF_CUSTOM_FONT_PATH', $CFG->dataroot.'/fonts/');
define('PDF_DEFAULT_FONT', 'DejaVuSerif');



/**
 * Wrapper class that extends TCPDF (lib/tcpdf/tcpdf.php).
 * Moodle customisations are done here.
 */
class pdf extends TCPDF {
        
    /**
     * Class constructor
     * 
     * See the parent class documentation for the parameters info.
     */
    public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8') {
        
        parent::__construct($orientation, $unit, $format, $unicode, $encoding);
        
        if (is_dir(PDF_CUSTOM_FONT_PATH)) {
            $fontfiles = $this->_getfontfiles(PDF_CUSTOM_FONT_PATH);
            
            if (count($fontfiles) == 1) {
                $autofontname = substr($fontfile[0], 0, -4);
                $this->AddFont($autofontname, '', $autofontname.'.php');
                $this->SetFont($autofontname);
            } else if (count($fontfiles == 0)) {
                $this->SetFont(PDF_DEFAULT_FONT);
            }
        } else {
            $this->SetFont(PDF_DEFAULT_FONT);
        }
    }
    
    
    /**
     * Return fonts path
     * Overriding TCPDF::_getfontpath()
     */
    protected function _getfontpath() {
        global $CFG;
        
        if (is_dir(PDF_CUSTOM_FONT_PATH)
                    && count($this->_getfontfiles(PDF_CUSTOM_FONT_PATH)) > 0) {
            $fontpath = PDF_CUSTOM_FONT_PATH;
        } else {
            $fontpath = $CFG->dirroot.'/lib/tcpdf/fonts/';
        }
        return $fontpath;
    }
    
    
    /**
     * Get the .php files for the fonts
     */
    protected function _getfontfiles($fontdir) {
        $dirlist = get_directory_list($fontdir);
        $fontfiles = array();
        
        foreach ($dirlist as $file) {
            if (substr($file, -4) == '.php') {
                array_push($fontfiles, $file);
            }
        }
        return $fontfiles;
    }
    
    
} // End class pdf


?>
