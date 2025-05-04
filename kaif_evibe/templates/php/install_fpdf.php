<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Target directory
$target_dir = __DIR__ . '/../vendor/fpdf';

// Create directory if it doesn't exist
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// FPDF content
$fpdf_content = '<?php
define("FPDF_VERSION","1.84");

class FPDF {
    protected $page;               // current page number
    protected $n;                  // current object number
    protected $offsets;            // array of object offsets
    protected $buffer;             // buffer holding in-memory PDF
    protected $pages;              // array containing pages
    protected $state;              // current document state
    protected $compress;           // compression flag
    protected $k;                  // scale factor (number of points in user unit)
    protected $DefOrientation;     // default orientation
    protected $CurOrientation;     // current orientation
    protected $StdPageSizes;       // standard page sizes
    protected $DefPageSize;        // default page size
    protected $CurPageSize;        // current page size
    protected $CurRotation;        // current page rotation
    protected $PageInfo;           // page-related data
    protected $wPt, $hPt;         // dimensions of current page in points
    protected $w, $h;             // dimensions of current page in user unit
    protected $lMargin;           // left margin
    protected $tMargin;           // top margin
    protected $rMargin;           // right margin
    protected $bMargin;           // page break margin
    protected $cMargin;           // cell margin
    protected $x, $y;             // current position in user unit
    protected $lasth;             // height of last printed cell
    protected $LineWidth;         // line width in user unit
    protected $fontpath;          // path containing fonts
    protected $CoreFonts;         // array of core font names
    protected $fonts;             // array of used fonts
    protected $FontFiles;         // array of font files
    protected $encodings;         // array of encodings
    protected $cmaps;             // array of ToUnicode CMaps
    protected $FontFamily;        // current font family
    protected $FontStyle;         // current font style
    protected $underline;         // underlining flag
    protected $CurrentFont;       // current font info
    protected $FontSizePt;        // current font size in points
    protected $FontSize;          // current font size in user unit
    protected $DrawColor;         // commands for drawing color
    protected $FillColor;         // commands for filling color
    protected $TextColor;         // commands for text color
    protected $ColorFlag;         // indicates whether fill and text colors are different
    protected $WithAlpha;         // indicates whether alpha channel is used
    protected $ws;                // word spacing
    protected $images;            // array of used images
    protected $PageLinks;         // array of links in pages
    protected $links;             // array of internal links
    protected $AutoPageBreak;     // automatic page breaking
    protected $PageBreakTrigger;  // threshold used to trigger page breaks
    protected $InHeader;          // flag set when processing header
    protected $InFooter;          // flag set when processing footer
    protected $AliasNbPages;      // alias for total number of pages
    protected $ZoomMode;          // zoom display mode
    protected $LayoutMode;        // layout display mode
    protected $title;             // title
    protected $subject;           // subject
    protected $author;            // author
    protected $keywords;          // keywords
    protected $creator;           // creator
    protected $PDFVersion;        // PDF version number

    function __construct($orientation="P", $unit="mm", $size="A4") {
        // Some checks
        $this->_dochecks();
        // Initialize
        $this->page = 0;
        $this->n = 2;
        $this->buffer = "";
        $this->pages = array();
        $this->state = 0;
        $this->compress = false;
        $this->k = 1;
        $this->DefOrientation = $orientation;
        $this->CurOrientation = $orientation;
        $this->StdPageSizes = array("a3"=>array(841.89,1190.55), "a4"=>array(595.28,841.89), "a5"=>array(420.94,595.28), "letter"=>array(612,792), "legal"=>array(612,1008));
        $this->DefPageSize = $this->_getpagesize($size);
        $this->CurPageSize = $this->DefPageSize;
        $this->CurRotation = 0;
        $this->PageInfo = array();
        $this->wPt = $this->CurPageSize[0];
        $this->hPt = $this->CurPageSize[1];
        $this->w = $this->wPt/$this->k;
        $this->h = $this->hPt/$this->k;
        $this->lMargin = 10;
        $this->tMargin = 10;
        $this->rMargin = 10;
        $this->bMargin = 10;
        $this->cMargin = 0;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->lasth = 0;
        $this->LineWidth = .2;
        $this->fontpath = dirname(__FILE__)."/font/";
        $this->CoreFonts = array("courier", "helvetica", "times", "symbol", "zapfdingbats");
        $this->fonts = array();
        $this->FontFiles = array();
        $this->encodings = array();
        $this->cmaps = array();
        $this->FontFamily = "";
        $this->FontStyle = "";
        $this->underline = false;
        $this->CurrentFont = null;
        $this->FontSizePt = 12;
        $this->FontSize = $this->FontSizePt/$this->k;
        $this->DrawColor = "0 G";
        $this->FillColor = "0 g";
        $this->TextColor = "0 g";
        $this->ColorFlag = false;
        $this->WithAlpha = false;
        $this->ws = 0;
        $this->images = array();
        $this->PageLinks = array();
        $this->links = array();
        $this->AutoPageBreak = true;
        $this->PageBreakTrigger = $this->h-$this->bMargin;
        $this->InHeader = false;
        $this->InFooter = false;
        $this->AliasNbPages = "{nb}";
        $this->ZoomMode = "fullpage";
        $this->LayoutMode = "single";
        $this->title = "";
        $this->subject = "";
        $this->author = "";
        $this->keywords = array();
        $this->creator = "FPDF";
        $this->PDFVersion = "1.3";
    }

    function _dochecks() {
        // Check for mbstring overloading
        if(ini_get("mbstring.func_overload") & 2)
            $this->Error("mbstring overloading must be disabled");
    }

    function _getpagesize($size) {
        if(is_string($size)) {
            $size = strtolower($size);
            if(!isset($this->StdPageSizes[$size]))
                $this->Error("Unknown page size: $size");
            $a = $this->StdPageSizes[$size];
            return array($a[0], $a[1]);
        }
        else {
            if($size[0]>$size[1])
                return array($size[1], $size[0]);
            else
                return array($size[0], $size[1]);
        }
    }

    function Error($msg) {
        die("FPDF error: ".$msg);
    }

    function AddPage($orientation="", $size="") {
        if($this->state==0)
            $this->Open();
        $family = $this->FontFamily;
        $style = $this->FontStyle.($this->underline ? "U" : "");
        $size = $this->FontSizePt;
        $this->AddPage($orientation, $size);
        $this->SetFont($family, $style, $size);
        if($this->lineWidth!=0)
            $this->SetLineWidth($this->lineWidth);
        for($i=0;$i<count($this->PageInfo);$i++)
            $this->PageInfo[$i]["n"] = $this->n;
    }

    function SetFont($family, $style="", $size=0) {
        $family = strtolower($family);
        if($family=="")
            $family = $this->FontFamily;
        else
            $family = str_replace(" ", "", $family);
        if($style=="")
            $style = $this->FontStyle;
        if($size==0)
            $size = $this->FontSizePt;
        if($this->FontFamily==$family && $this->FontStyle==$style && $this->FontSizePt==$size)
            return;
        $this->FontFamily = $family;
        $this->FontStyle = $style;
        $this->FontSizePt = $size;
        $this->FontSize = $size/$this->k;
        $this->CurrentFont = &$this->fonts[$family.$style];
        if($this->page>0)
            $this->_out(sprintf("BT /F%d %.2f Tf ET", $this->CurrentFont["i"], $this->FontSizePt));
    }

    function Cell($w, $h=0, $txt="", $border=0, $ln=0, $align="", $fill=false, $link="") {
        if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
            $x = $this->x;
            $ws = $this->ws;
            if($ws>0) {
                $this->ws = 0;
                $this->_out("0 Tw");
            }
            $this->AddPage($this->CurOrientation, $this->CurPageSize);
            $this->x = $x;
            if($ws>0) {
                $this->ws = $ws;
                $this->_out(sprintf("%.3F Tw", $ws*$this->k));
            }
        }
        if($w==0)
            $w = $this->w-$this->rMargin-$this->x;
        $s = "";
        if($fill || $border==1) {
            if($fill)
                $op = ($border==1) ? "B" : "f";
            else
                $op = "S";
            $s = sprintf("%.2F %.2F %.2F %.2F re %s ", $this->x*$this->k, ($this->h-$this->y)*$this->k, $w*$this->k, -$h*$this->k, $op);
        }
        if(is_string($border)) {
            $x = $this->x;
            $y = $this->y;
            if(strpos($border, "L")!==false)
                $s .= sprintf("%.2F %.2F m %.2F %.2F l S ", $x*$this->k, ($this->h-$y)*$this->k, $x*$this->k, ($this->h-($y+$h))*$this->k);
            if(strpos($border, "T")!==false)
                $s .= sprintf("%.2F %.2F m %.2F %.2F l S ", $x*$this->k, ($this->h-$y)*$this->k, ($x+$w)*$this->k, ($this->h-$y)*$this->k);
            if(strpos($border, "R")!==false)
                $s .= sprintf("%.2F %.2F m %.2F %.2F l S ", ($x+$w)*$this->k, ($this->h-$y)*$this->k, ($x+$w)*$this->k, ($this->h-($y+$h))*$this->k);
            if(strpos($border, "B")!==false)
                $s .= sprintf("%.2F %.2F m %.2F %.2F l S ", $x*$this->k, ($this->h-($y+$h))*$this->k, ($x+$w)*$this->k, ($this->h-($y+$h))*$this->k);
        }
        if($txt!=="") {
            if($align=="R")
                $dx = $w-$this->cMargin-$this->GetStringWidth($txt);
            elseif($align=="C")
                $dx = ($w-$this->GetStringWidth($txt))/2;
            else
                $dx = $this->cMargin;
            if($this->ColorFlag)
                $s .= "q ".$this->TextColor." ";
            $s .= sprintf("BT %.2F %.2F Td (%s) Tj ET", ($this->x+$dx)*$this->k, ($this->h-($this->y+.5*$h+.3*$this->FontSize))*$this->k, $this->_escape($txt));
            if($this->underline)
                $s .= " ".$this->_dounderline($this->x+$dx, $this->y+.5*$h+.3*$this->FontSize, $txt);
            if($this->ColorFlag)
                $s .= " Q";
            if($link)
                $this->Link($this->x+$dx, $this->y+.5*$h-.5*$this->FontSize, $this->GetStringWidth($txt), $this->FontSize, $link);
        }
        if($s)
            $this->_out($s);
        $this->lasth = $h;
        if($ln>0) {
            $this->y += $h;
            if($ln==1)
                $this->x = $this->lMargin;
        }
        else
            $this->x += $w;
    }

    function _escape($s) {
        return str_replace(")", "\\)", str_replace("(", "\\(", str_replace("\\", "\\\\", $s)));
    }

    function _dounderline($x, $y, $txt) {
        $up = $this->CurrentFont["up"];
        $ut = $this->CurrentFont["ut"];
        $w = $this->GetStringWidth($txt)+$this->ws*substr_count($txt, " ");
        return sprintf("%.2F %.2F %.2F %.2F re f", $x*$this->k, ($this->h-($y-$up/1000*$this->FontSize))*$this->k, $w*$this->k, -$ut/1000*$this->FontSizePt);
    }

    function GetStringWidth($s) {
        $s = (string)$s;
        $cw = &$this->CurrentFont["cw"];
        $w = 0;
        $l = strlen($s);
        for($i=0;$i<$l;$i++)
            $w += $cw[$s[$i]];
        return $w*$this->FontSize/1000;
    }

    function Link($x, $y, $w, $h, $link) {
        $this->PageLinks[$this->page][] = array($x*$this->k, $this->hPt-$y*$this->k, $w*$this->k, $h*$this->k, $link);
    }

    function _out($s) {
        if($this->state==2)
            $this->pages[$this->page] .= $s."\n";
        else
            $this->buffer .= $s."\n";
    }

    function Output($dest="", $name="", $isUTF8=false) {
        if($this->state<3)
            $this->Close();
        $dest = strtoupper($dest);
        if($dest=="") {
            if($name=="") {
                $name = "doc.pdf";
                $dest = "I";
            }
            else
                $dest = "F";
        }
        switch($dest) {
            case "I":
                if(ob_get_length())
                    $this->Error("Some data has already been output, can\'t send PDF file");
                if(php_sapi_name()!="cli") {
                    header("Content-Type: application/pdf");
                    header("Content-Disposition: inline; filename=\"".$name."\"");
                    header("Cache-Control: private, max-age=0, must-revalidate");
                    header("Pragma: public");
                }
                echo $this->buffer;
                break;
            case "D":
                if(ob_get_length())
                    $this->Error("Some data has already been output, can\'t send PDF file");
                header("Content-Type: application/pdf");
                header("Content-Disposition: attachment; filename=\"".$name."\"");
                header("Cache-Control: private, max-age=0, must-revalidate");
                header("Pragma: public");
                echo $this->buffer;
                break;
            case "F":
                $f = fopen($name, "wb");
                if(!$f)
                    $this->Error("Unable to create output file: ".$name);
                fwrite($f, $this->buffer, strlen($this->buffer));
                fclose($f);
                break;
            case "S":
                return $this->buffer;
            default:
                $this->Error("Incorrect output destination: ".$dest);
        }
        return "";
    }

    function Close() {
        if($this->state==3)
            return;
        if($this->page==0)
            $this->AddPage();
        $this->state = 3;
    }
}';

// Write FPDF content to file
file_put_contents($target_dir . '/fpdf.php', $fpdf_content);

// Verify installation
if (file_exists($target_dir . '/fpdf.php')) {
    echo "FPDF installed successfully!";
} else {
    echo "FPDF installation failed. Please check the directory: " . $target_dir;
}
?> 