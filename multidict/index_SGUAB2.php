<?php
  if (!include('autoload.inc.php'))
    header("Location:http://claran.smo.uhi.ac.uk/mearachd/include_a_dhith/?faidhle=autoload.inc.php");

  header("Cache-Control:max-age=0");
  header('P3P: CP="CAO PSA OUR"');

  $sid = ( !empty($_GET['sid']) ? $_GET['sid'] : null);
  $wlSession = new SM_WlSession($sid);
  $wlSession->bestDict();
  $wlSession->storeVars();
  $sid  = $wlSession->sid;
  $sl   = $wlSession->sl;
  $tl   = $wlSession->tl;
  $dict = $wlSession->dict;
  $word = $wlSession->word;
  $wfs  = $wlSession->wfs;
  $mode = $wlSession->mode;
  $url  = $wlSession->url;

  $servername = $_SERVER['SERVER_NAME'];
  $standalone = ( empty($url) ? 1 : 0 );
  if ($standalone) {
      $serverlink = "<a class=button style='float:left;margin:0 1px 0 0;border-radius:0;padding:1px 2px;font-size:80%' href='/' target='_top'>$servername</a>";
      $slSelectOnInit  = 'block';
      $slSelectOffInit = 'none';
      $advClass = 'advanced';
  } else {
      $serverlink = '';
      $slSelectOnInit  = 'none';
      $slSelectOffInit = 'block';
      $advClass = 'compact';
  }

// The following lines are ad-hoc, to cure in a hurry a problem with the display of dictionary headword suggestions after converting Multidict from frames to iframe.
// They should be replaced with clean logic!!
$DbMultidict = SM_DbMultidictPDO::singleton('rw');
$stmt = $DbMultidict->prepare('SELECT wfs FROM wlSession WHERE sid=:sid');
$stmt->execute([':sid'=>$sid]);
$wlSession->wfs = $wfs = $stmt->fetch()['wfs'];

  $wlSession->csClickCounter(); //If called from a Clilstore unit, add 1 to the click count
  $robots = ( empty($wlSession->word) ? 'index,follow' : 'noindex,nofollow' );

  try {
  $nbSlHtml = $wlSession->nbSlHtml();
  $wordformArr = explode('|',$wlSession->wfs);
  if (sizeof($wordformArr)<2) { $wordformHtmlFull = ''; }
  else {
      foreach ($wordformArr as $key=>&$wf) {
          if ($wf==$word) { $wf = '<span class="lemmaword">' . $wf . '</span>';}
          if ($key==0)    { $wf = '<span class="lemma0">' . $wf . '</span>'; }
          if ($key<>0)    { $wf = "<a href=\"/multidict/?sid=$sid&amp;word=$word&amp;rot=$key\" class=\"lemmalink\">$wf</a>"; }
      }
      $wordformHtml = implode(' <span dir="ltr">←</span> ',$wordformArr);
      $wordformHtmlFull = <<<EODWFFH
<div class="formItem" style="margin:0 0 0 0.5%px;width:63%;border2:1px solid purple">
<div class="label" style="padding:4px 0 1px 0;overflow:hidden">Multidict will try these wordforms in rotation (on reclick)</div>
<div style="font-size:85%;color:brown">$wordformHtml ↩</div>
</div>
EODWFFH;
  }
  $dictClass = $wlSession->dictClass();
  if (substr($dictClass,0,1)=='p') { $pageNav = <<<EODpageNav
<input type="submit" name="go" value="<" style="padding:0 3px;margin-left:1.2em" title="Page back">
<input type="submit" name="go" value=">" style="padding:0 3px" title="Page forward">
EODpageNav;
  } else { $pageNav = ''; }

  $slOptionsHtml = $tlSelectHtml = $formItems = '';

  $slArr = SM_WlSession::slArr();
  foreach ($slArr as $lang=>$langInfo) { $slArray[$lang] = $langInfo['endonym']; }
  setlocale(LC_ALL,'en_GB.UTF-8');
  uasort($slArray,'strcoll');
  $slArray = array_merge(array(''=>'-Choose-'),$slArray);
  foreach ($slArray as $code=>$name) {
      $selectHtml = ( $sl==$code ? ' selected="selected"' : '');
      $slOptionsHtml .= "  <option value=\"$code\"$selectHtml>$name</option>\n";
  }

  if (!empty($sl)) {
      $tlArray = $wlSession->tlArr();
      setlocale(LC_ALL,'en_GB.UTF-8');
      uasort($tlArray,'strcoll');
      foreach ($tlArray as $code=>$name) {
          $selectedHtml = ( $tl==$code ? ' selected="selected"' : '');
          $tlSelectHtml .= "  <option value=\"$code\"$selectedHtml>$name</option>\n";
      }
      $dictSelectHtml = $wlSession->dictSelectHtml();
      $dictIconsHtml  = $wlSession->dictIconsHtml();
      $dictIconHtml   = $wlSession->dictIconHtml();
      $nbTlHtml       = $wlSession->nbTlHtml();
      $formItems = <<<EOD3
<div id="tlSelectOn" style="display:block">
<div class="formItem" style="min-width:95px;max-width:28%"><div class="label">To <a onclick="selectOff('tl')" title="hide selection">Ⓧ</a></div>
<select name="tl" id="tl" title="Choose a target language" onchange="submitForm('tl');">
  <option value="">-Choose-</option>
$tlSelectHtml
</select>$nbTlHtml
</div>
</div>
<div id="tlSelectOff" class="formItem" style="display:none;width:2em" onclick="selectOn('tl')" title="Click to reselect To language">
<div class="label">To</div>
<b onmouseover="selectOn('tl')">$tl</b>
</div>
<div id="dictSelectOn" style="display:block">
<div class="formItem" style="min-width:110px;max-width:40%;overflow:visible"><div class="label">Dictionary <a onclick="selectOff('dict')" title="hide selection">Ⓧ</a> $dictIconHtml</div>
<select id="dict" name="dict" onchange="submitForm();" title="Choose a dictionary (but reselect target language first if need be)">
  <option value="">-Choose-</option>
$dictSelectHtml
</select><br>
<div id="dictIcons">$dictIconsHtml</div>
</div>
</div>
<div id="dictSelectOff" class="formItem" style="display:none" onclick="selectOn('dict')" title="click to reselect dictionary">
<div class="label">Dictionary</div>
<b onmouseover="selectOn('dict')">$dict</b>
</div>
<div id="noJSinfo" style="position:absolute;bottom:4px;left:6px;font-size:55%;color:green;white-space:normal">
If JavaScript is disabled you must click Go after each language change</div>
EOD3;
  }

  $advToggleHtml = "<div class=compOnly><b>compact</b><input type=range min=0 max=0 value=0>advanced</div>"
                  ."<div class=advOnly>compact<input type=range min=0 max=1 value=1><b>advanced</b></div>";
  $advToggleHtml = "<div id=advToggle onclick='advToggle()'>$advToggleHtml</div>";

  if (!$standalone) {    //Don’t use with Wordlink because doesn’t work in a frame
      $schemeSwopHtml = '';
  } else {               //Only use with standalone Multidict
      $scheme = ( empty($_SERVER['HTTPS']) ? 'http' : 'https' );
      $server_name = $_SERVER['SERVER_NAME'];
      $php_self = $_SERVER['PHP_SELF'];
      $php_self = str_replace('index.php','',$php_self);
      $schemeValue = ( $scheme=='https' ? 1 : 0 );
      $schemeSwopRange = "<input type=range min=0 max=1 step=1 value=$schemeValue style=width:3em;margin:0;padding:0>";
      if ($scheme=='https') {
          $schemeSwopHtml = 'http' . $schemeSwopRange . '<b>https</b>';
          $schemeSwopLocation = 'http';
      } else {
          $schemeSwopHtml = '<b>http</b>' . $schemeSwopRange . 'https';
          $schemeSwopLocation = 'https';
      }
      $schemeSwopLocation .= "://$server_name$php_self";
      if (!empty($_GET)) { $schemeSwopLocation .= '?' . $_SERVER['QUERY_STRING']; }
      $schemeSwopHtml = "<div style='float:right;padding:0 3px;margin-left:1em;font-size:70%;background-color:#bfb' onclick=window.location.replace('$schemeSwopLocation');>$schemeSwopHtml</div>";
  }

  echo <<<EOD4
<!DOCTYPE html>
<html id='MD$sid'>
<head>
    <meta charset="UTF-8">
    <title>Multidict</title>
    <meta name="robots" content="$robots">
    <style>
        body, html {width:100%; height:100%; margin:0; padding:0; }
/*
Replace the following sometime with flexbox - Option 3 at https://stackoverflow.com/questions/325273/make-iframe-to-fit-100-of-containers-remaining-height
(Currently using Option 2, which works in older browsers)
*/
        div#framcontainer { display: table; empty-cells: show; border-collapse:collapse; width:100%; height:100%; }
        div#navigation { display:table-row; overflow:auto; }
        div#navigation-content { overflow:auto; border:3px solid orange; background-color:#e3ffe3; font-family:Tahoma,sans-serif; }
        div#dictionary { display:table-row; height:100%; background-color:#fee; overflow:hidden }
        div#dictionary iframe { width:100%; height:100%; border:none; margin:0; padding:0; display:block; }

        input { padding:0px 4px; }
        select { margin-bottom:1px; }
        ul.dluth { margin:0; }
        div.label  { font-size:80%; color:#777; }
        div.nbLang { font-size:80%; color:#aaa; padding-bottom:1px; }
        div.nbLang img { margin:0 3px; }
        div.nbLang a.box { margin:0 2px; border:1px solid #aaa; padding:0 1px; background-color:white; }
        div.nbLang a:hover { background-color:blue; }
        a         { text-decoration:none; }
        a:link    { color: #00f; }
        a:visited { color: #909; }
        a:hover, input[type=submit]:hover, span.clickable:hover { color: #ff0; background-color:blue; }
        a.lemmalink:link,
        a.lemmalink:visited { color:brown; }
        a.lemmalink:hover   {color:#ff0; }
        a.button { display:inline-block; margin-left:0.8em; padding:0 4px; border-radius:4px; background-color:#75c8fb; color:white; font-size:90%; }
        a.button:hover { background-color:blue; color:white; }
        div#slSelectOff:hover,
        div#tlSelectOff:hover,
        div#dictSelectOff:hover { background-color:#ddf; }
        span.lemma0 { font-weight:bold; text-decoration:underline; color:#bb2020; }
        span.lemmaword { font-style:italic; }
        a#esc span { border:1px solid grey; border-radius:3px; padding: 0 2px; color:grey; background-color:white; }
        a#esc:hover { background-color:inherit; }
        a#esc:hover span { color:yellow; background-color:blue; }
        div.formItem { float:left; margin:1px; white-space:nowrap; overflow:hidden; }
        select { max-height:1.8em; margin-bottom:2px; margin-top:1px; }
        select[name="sl"],select[name="tl"] { width:100%; }
        select[name="dict"] { width:100%; }
        div#dictIcons         { height:18px; display:block; }
        div#dictIcons img     { width:16px; height:16px; margin:1px; border:none; padding:0 0 3px 0; border:none; }
        div#dictIcons img.sel { border-left: 3px solid red; border-right:3px solid red; }
        div#dictIcons img.m   { padding:1px 0 0 0; border-top:2px solid red; }         /* mini */
        div#dictIcons img.p   { padding:0 0 1px 0; border-bottom:2px solid blue; }     /* page-image */
        div#dictIcons img.pw  { padding:0 0 1px 0; border-bottom:2px solid green; }    /* Web Archive */
        div#dictIcons img.pg  { padding:0 0 1px 0; border-bottom:2px solid red; }      /* Google Books */
        div#dictIcons img.s   { padding:0 0 1px 0; border-bottom:2px dotted black; }   /* Special */
        div#advToggle       { float:right; padding:0 3px; margin-right:1em; font-size:70%; background-color:#bfb }
        div#advToggle input { width:3em; margin:0; padding:0; }
        div.advanced div.compOnly { display:none; }
        div.compact  div.advOnly  { display:none; }
    </style>
    <script>
        var standalone = $standalone;
        var mdAdv = standalone;

        function bodyLoad() {
            if (standalone==1) {
                var mdSaAdvLocal   = localStorage.getItem('mdSaAdv');
                var mdSaAdvSession = sessionStorage.getItem('mdSaAdv');
                if (mdSaAdvLocal   === 0) { mdAdv = 0; }
                if (mdSaAdvSession === 1) { mdAdv = 0; }
            } else {
                var mdWlAdvLocal   = localStorage.getItem('mdWlAdv');
                var mdWlAdvSession = sessionStorage.getItem('mdWlAdv');
                if (mdWlAdvLocal   === 0) { mdAdv = 0; }
                if (mdWlAdvSession === 1) { mdAdv = 0; }
            }

            document.getElementById('noJSinfo').style.display = 'none';
            document.getElementById('dictIcons').style.display = 'block';
            document.getElementById('swop').style.display = 'block';
if (!mdAdv) { document.getElementById('dictIcons').style.display = 'none'; }
        }
        function advToggle() {
            var el = document.getElementById('navigation-content');
            if (mdAdv) { el.class = 'advanced'; }
             else      { el.class = 'compact';  }
        }
        function advToggleSGUAB() {
            var mdadv = 1 - document.getElementById('advTog').value;
            var servername = '$servername';
            var url = 'https://'+servername+'/multidict/ajax/setMDadv.php?sid=' + sid + '&mdadv=' + mdadv;
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.open('GET', url, false);
            xmlhttp.send();
            var resp = xmlhttp.responseText;
            if (resp!='OK') { alert('Error in setMDadv: ' + resp); }
            var loc = window.location;
            window.location = loc;
        }
    </script>
</head>
<body onload="bodyLoad();">
<div id="framcontainer">
<div id="navigation"><div id="navigation-content" class=$advClass>

TEST
</div>
</div>
</div>
EOD4;
?>

<?php
  } catch (exception $e) { echo $e; }
?>

</body>
</html>