<?php
// $Id:

header("Content-type: text/css");
// load required classes
chdir('..');
include_once('etc/cs_constants.php');
include_once('etc/cs_config.php');
include_once('classes/cs_environment.php');

// create environment of this page
$color = $cs_color['DEFAULT'];

// find out the room we're in
if (!empty($_GET['cid'])) {
   $cid = $_GET['cid'];
   $environment = new cs_environment();
   $environment->setCurrentContextID($cid);
   $room = $environment->getCurrentContextItem();
   $color = $room->getColorArray();
}
?>


/*General Settings */
body {
	margin: 0px;
	padding: 0px;
	font-family: Arial, "Nimbus Sans L", sans-serif;
	font-size: 10pt;
	background-color: white;
}

.fade-out-link{
    font-size:8pt;
    color:black;
}

img {
	border: 0px;
}


/*Hyperlinks*/
a {
	color: <?php echo($color['hyperlink'])?>;
	text-decoration: none;
}

a:hover, a:active {
	text-decoration: underline;
}


/* Font-Styles */
.infocolor{
	color: <?php echo($color['info_color'])?>;
}

.disabled, .key .infocolor{
	color: <?php echo($color['disabled'])?>;
}

.changed {
	color: <?php echo($color['warning'])?>;
	font-size: 8pt;
}

.infoborder{
    border-top: 1px solid <?php echo($color['info_color'])?>;
    padding-top:10px;
}

.infoborder_display_content{
    width: 70%;
    border-top: 1px solid <?php echo($color['info_color'])?>;
    padding-top:10px;
}

.required {
	color: <?php echo($color['warning'])?>;
	font-weight: bold;
}

.normal{
	font-size: 10pt;
}

.handle_width{
    overflow:auto;
    padding-bottom:3px;
}

.handle_width_border{
    overflow:auto;
    padding:3px;
    border: 1px solid <?php echo($color['info_color'])?>;
}

.desc {
	font-size: 8pt;
}

.bold{
	font-size: 10pt;
	font-weight: bold;
}


/* Room Design */
div.main{
	padding: 20px 5px 0px 5px;
}

div.content_fader{
	margin:0px;
	padding: 0px 3px;
	background: url(images/layout/bg-fader_<?php echo($color['schema'])?>.gif) repeat-x;
}

div.content{
	padding:0px;
	margin:0px;
	background-color: <?php echo($color['content_background'])?>;
}

div.content_display_width{
	width:71%;
}

div.frame_bottom {
	position:relative;
	font-size: 1px;
	border-left: 2px solid #C3C3C3;
	border-right: 2px solid #C3C3C3;
	border-bottom: 2px solid #C3C3C3;
}

div.content_bottom {
	position:relative; width: 100%;
}

/*Panel Style*/
#commsy_panels .commsy_panel, #commsy_panel_form .commsy_panel{
   margin:0px;
}

#commsy_panels .panelContent, #commsy_panel_form .panelContent{
   font-size:0.7em;
   padding:0px;
   overflow:hidden;
   position:relative;
}

#commsy_panels .small, #commsy_panel_form .small{
   font-size:8pt;
}

#commsy_panels .panelContent div, #commsy_panel_form .panelContent div{
   position:relative;
}

#commsy_panels .commsy_panel .topBar, #commsy_panel_form .commsy_panel .topBar{
   <?
   echo('background: url(commsy.php?cid='.$cid.'&mod=picture&fct=getfile&picture=cs_gradient_24.png) repeat-x;');
   ?>
   background-color:<?php echo($color['tabs_background'])?>;
   color:<?php echo($color['headline_text'])?>;
   padding: 0px 0px;
   height:20px;
   overflow:hidden;
}

#commsy_panels .commsy_panel .topBar span, #commsy_panel_form .commsy_panel .topBar span{
   line-height:20px;
   vertical-align:baseline;
   color:<?php echo($color['headline_text'])?>;
   font-weight:bold;
   float:left;
   padding-left:5px;
}

#commsy_panels .commsy_panel .topBar img, #commsy_panel_form .commsy_panel .topBar img{
   float:right;
   cursor:pointer;
}

#otherContent{  /* Normal text content */
   float:left;  /* Firefox - to avoid blank white space above panel */
   padding-left:10px;   /* A little space at the left */
}

ul.item_list {
   margin: 3px 0px 2px 2px;
   padding: 0px 0px 3px 15px;
   list-style: circle;
}



/* Tab Style */
div.tabs_frame {
   position:relative;
   <?
   echo('background: url(commsy.php?cid='.$cid.'&mod=picture&fct=getfile&picture=cs_gradient_24.png) repeat-x;');
   ?>
   background-color: <?php echo($color['tabs_background'])?>;
   padding:0px;
   margin:0px;
   font-weight: bold;
   border-left: 2px solid #CBD0D6;
   border-right: 2px solid #CBD0D6;
   border-top: 2px solid #CBD0D6;
}

div.tabs {
   position:relative;
   width: 100%;
   border-bottom: 1px solid <?php echo($color['tabs_title'])?>;
   padding:4px 0px 3px 0px;
   margin:0px;
   font-weight: bold;
   font-size: 10pt;
}

span.navlist{
   color:<?php echo($color['headline_text'])?>;
}
a.navlist{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 6px;
   border-right:1px solid <?php echo($color['headline_text'])?>;
   text-decoration:none;
   font-size: 10pt;
}

a.navlist_current{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 6px;
   border-right:1px solid <?php echo($color['headline_text'])?>;
   text-decoration:none;
   <?
   echo('background: url(commsy.php?cid='.$cid.'&mod=picture&fct=getfile&picture=cs_gradient_24_focus.png) repeat-x;');
   ?>
   background-color:<?php echo($color['tabs_focus'])?>;
}

a.navlist_current:hover, a.navlist_current:active, a.navlist:hover{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 6px;
   text-decoration:none;
   <?
   echo('background: url(commsy.php?cid='.$cid.'&mod=picture&fct=getfile&picture=cs_gradient_24_focus.png) repeat-x;');
   ?>
   background-color:<?php echo($color['tabs_focus'])?>;
}

a.navlist:active{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 6px;
   text-decoration:none;
}

a.navlist_help, a.navlist_help:hover, a.navlist_help:active{
   color:<?php echo($color['headline_text'])?>;
   padding:4px 6px 3px 3px;
   text-decoration:none;
}

/*Headlines*/
h1{
	margin:0px;
	padding-left:0px 0px 0px 10px;
	font-size:30px;
}

.pagetitle{
	margin:0px;
   font-size: 16pt;
	font-family: verdana, arial, sans-serif;
}


/*Special Designs*/
.top_of_page {
	padding:5px 0px 3px 10px;
	font-size: 8pt;
	color: <?php echo($color['info_color'])?>;
}

.top_of_page a{
	color: <?php echo($color['info_color'])?>;
}

#form_formatting_box{
   margin-top:5px;
   margin-bottom:0px;
   width:400px;
   padding:5px;
   border: 1px #B0B0B0 dashed;
   background-color:<?php echo($color['boxes_background'])?>;
}
.form_formatting_checkbox_box{
   margin-top:0px;
   margin-bottom:0px;
   width:300px;
   padding:5px 10px 5px 10px;
}

#template_information_box{
   margin-top:5px;
   margin-bottom:0px;
   padding:5px;
   border: 1px #B0B0B0 dashed;
   background-color:<?php echo($color['boxes_background'])?>;
}
