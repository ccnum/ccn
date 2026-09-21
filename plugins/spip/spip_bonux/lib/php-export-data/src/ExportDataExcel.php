<?php

namespace Export;

/**
 * ExportDataExcel exports data into an XML format  (spreadsheetML) that can be 
 * read by MS Excel 2003 and newer as well as OpenOffice
 * 
 * Creates a workbook with a single worksheet (title specified by
 * $title).
 * 
 * Note that using .XML is the "correct" file extension for these files, but it
 * generally isn't associated with Excel. Using .XLS is tempting, but Excel 2007 will
 * throw a scary warning that the extension doesn't match the file type.
 * 
 * Based on Excel XML code from Excel_XML (http://github.com/oliverschwarz/php-excel)
 *  by Oliver Schwarz
 */
class ExportDataExcel extends ExportData {

    const XmlHeader = "<?xml version=\"1.0\" encoding=\"%s\"?\>\n<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:html=\"http://www.w3.org/TR/REC-html40\">";
    const XmlFooter = "</Workbook>";

    public $encoding = 'UTF-8'; // encoding type to specify in file. 
    // Note that you're on your own for making sure your data is actually encoded to this encoding
    public $title = 'Sheet1'; // title for Worksheet 

    public function generateHeader() {

        // workbook header
        $output = stripslashes(sprintf(self::XmlHeader, $this->encoding)) . "\n";

        // Set up styles
        $output .= "<Styles>\n";

        // default style
        $output .= "<Style ss:ID=\"Default\" ss:Name=\"Normal\"><Alignment ss:Vertical=\"Top\"/></Style>\n";
        // multiline text style
        $output .= "<Style ss:ID=\"sTXT\"><Alignment ss:Vertical=\"Top\" ss:WrapText=\"1\"/></Style>\n";
        // date style
        $output .= "<Style ss:ID=\"sDT\"><Alignment ss:Vertical=\"Top\"/><NumberFormat ss:Format=\"Short Date\"/></Style>\n";
        // Number style
        $output .= "<Style ss:ID=\"sNUM\"><NumberFormat ss:Format=\"Standard\"/></Style>\n";

        $output .= "</Styles>\n";

        // worksheet header
        $output .= sprintf("<Worksheet ss:Name=\"%s\">\n    <Table>\n", htmlentities($this->title));

        return $output;
    }

    public function generateFooter() {
        $output = '';

        // worksheet footer
        $output .= "    </Table>\n</Worksheet>\n";

        // workbook footer
        $output .= self::XmlFooter;

        return $output;
    }

    public function generateRow($row) {
        $output = '';
        $output .= "        <Row>\n";
        foreach ($row as $k => $v) {
            $output .= $this->generateCell($v);
        }
        $output .= "        </Row>\n";
        return $output;
    }

    protected function formatDate($year, $month, $day, $hours, $minutes, $seconds) {
        return str_pad(intval($year), 4, 0, STR_PAD_LEFT)
          . '-' . str_pad(intval($month), 2, 0, STR_PAD_LEFT)
          . '-' . str_pad(intval($day), 2, 0, STR_PAD_LEFT)
          . 'T' . str_pad(intval($hours), 2, 0, STR_PAD_LEFT)
          . ':' . str_pad(intval($minutes), 2, 0, STR_PAD_LEFT)
          . ':' . str_pad(intval($seconds), 2, 0, STR_PAD_LEFT);
    }

    protected function detectTime($item) {
        static $zero = array(0, 0, 0);
        if (!strlen(trim($item))){
            return $zero;
        }

        if (preg_match('#(^T|/s+)?([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})\s*$#', $item, $match)) {
            array_shift($match);
            array_shift($match);
            return $match;
        }

        return false;
    }

    /**
     * Sniff for valid dates; should look something like 2010-07-14 or 14/07/2010 etc. Can
     * also have an optional time after the date.
     *
     * Note we want to be very strict in what we consider a date. There is the possibility
     * of really screwing up the data if we try to reformat a string that was not actually
     * intended to represent a date.
     *
     * @param $item
     * @return false|string
     */
    protected function detectDate($item) {
        if ($item = trim($item)) {
            if (preg_match('#^([0-9]{1,2})/([0-9]{1,2})/([0-9]{4}|[0-9]{1,2})#', $item, $match)) {
                $day = $match[1];
                $month = $match[2];
                $year = $match[3];
                if (strlen($year) <= 2) {
                    $year = 2000 + $year;
                }
                if ($time = $this->detectTime(substr($item, strlen($match[0])))) {
                  list($hours, $minutes, $seconds) = $time;
                  return $this->formatDate($year, $month, $day, $hours, $minutes, $seconds);
                }
            } elseif (preg_match('#^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})#', $item, $match)) {
                $year = $match[1];
                $month = $match[2];
                $day = $match[3];
                if ($time = $this->detectTime(substr($item, strlen($match[0])))) {
                    list($hours, $minutes, $seconds) = $time;
                    return $this->formatDate($year, $month, $day, $hours, $minutes, $seconds);
                }
            }
        }
        return false;
    }

    private function generateCell($item) {
        $output = '';
        $style = '';

        // Tell Excel to treat as a number. Note that Excel only stores roughly 15 digits, so keep 
        // as text if number is longer than that.
        if (preg_match("/^-?\d+(?:[.,]\d+)?$/", $item) && (strlen($item) < 15)) {
            $type = 'Number';

            // decimal numbers formated as number, but keep integers with default style
            if (strpos($item, '.') or strpos($item, ',')) {
		          $style = 'sNUM';
            }
        }
        elseif ($date = $this->detectDate($item)){
            $type = 'DateTime';
            $item = $date;
            $style = 'sDT'; // defined in header; tells excel to format date for display
        } else {
            $type = 'String';
        }

        $item = htmlspecialchars($item, ENT_QUOTES, $this->encoding);
        // not necessary, better keeping &#039; for quote
        //$item = str_replace('&#039;', '&apos;', $item);

        if (!$style and (strpos($item, "\r") !== false or strpos($item, "\n") !== false)) {
            $item = str_replace("\r\n", "\n", $item);
            $item = str_replace("\r", "\n", $item);
            $item = str_replace("\n", "&#013;", $item);
            $style = 'sTXT';
        }

        $output .= "            ";
        $output .= $style ? "<Cell ss:StyleID=\"$style\">" : "<Cell>";
        $output .= sprintf("<Data ss:Type=\"%s\">%s</Data>", $type, $item);
        $output .= "</Cell>\n";

        return $output;
    }

    public function sendHttpHeaders() {
        header("Content-Type: application/vnd.ms-excel; charset=" . $this->encoding);
        header("Content-Disposition: inline; filename=\"" . basename($this->filename) . "\"");
    }

}
