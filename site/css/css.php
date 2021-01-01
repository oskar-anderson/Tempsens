<?php
  header("Content-type: text/css");

  $lwidth = 150;

  $white            = "#ffffff";
  $sirowa           = "#8c458f";
  $light_grey_01    = "#f9f9f9";
  $light_grey_02    = "#ebebeb";
  $light_grey_03    = "#b9b9b9";
  $grey             = "#b8b8b8";
  $green            = "#009000";
  $green_01         = "#6fa773";
  $red              = "#ff0000";
  $black            = "#000000";

  /* main areas */
  $body_bg                     = $white;            // page bg
  $button_border               = $light_grey_01;    // button frame
  $select_bg                   = $green_01;         // select bg
  $select                      = $light_grey_01;    // select tekst

  $footer_bg                   = $sirowa;           // footer bg
  $norm_link                   = $sirowa;           // normal hyperlink
  $active_link                 = $sirowa;           // active hyperlink

  $content_taust               = $light_grey_01;    // content bg
  $content_tekst               = $black;            // content text
  $content_title_bg            = $light_grey_02;    // content title gb

  /* navi */
  $leftnavi_taust              = $grey;             // navi bg
  $leftnavi_active             = $sirowa;           // active menuitem bg
  $leftnavi_hover              = $grey;             // hover menuitem bg
  $leftnavi_norm_link          = $sirowa;           // norm menuitem text
  $leftnavi_active_link        = $white;            // active menuitem text
  $leftnavi_hover_active_link  = $sirowa;           // hover_active menuitem text

  /* others */
  $raam                        = $black;            // frame borders
  $err_msg                     = $red;              // error messages
  $ok_msg                      = $green;            // ok messages
  $koorik_bg                   = $light_grey_02;    // frame bg

  $input_shadow                = $red;
  $input_kast                  = $light_grey_03;    // form input area border;

?>

/* http://meyerweb.com/eric/tools/css/reset/
   v2.0 | 20110126
   License: none (public domain)
*/

html, body, div, span, applet, object, iframe,
h1, h2, h3, h4, h5, h6, p, blockquote, pre,
a, abbr, acronym, address, big, cite, code,
del, dfn, em, img, ins, kbd, q, s, samp,
small, strike, strong, sub, sup, tt, var,
b, u, i, center,
dl, dt, dd, ol, ul, li,
fieldset, form, label, legend,
table, caption, tbody, tfoot, thead, tr, th, td,
article, aside, canvas, details, embed,
figure, figcaption, footer, header, hgroup,
menu, nav, output, ruby, section, summary,
time, mark, audio, video {
    margin: 0;
    padding: 0;
    border: 0;
    font-size: 100%;
    font: inherit;
    vertical-align: baseline;
}

/* HTML5 display-role reset for older browsers */
article, aside, details, figcaption, figure,
footer, header, hgroup, menu, nav, section {
    display: block;
}
body {
    line-height: 1;
}
ol, ul {
    list-style: none;
}
blockquote, q {
    quotes: none;
}
blockquote:before, blockquote:after,
q:before, q:after {
    content: '';
    content: none;
}
table {
    border-collapse: collapse;
    border-spacing: 0;
}

/* EOF RESET CSS */

.small {
  font-size:8pt;
}

.small2 {
  font-size:9pt;
  font-weight:500;
}

.bigred {
  font-size:9pt;
  font-weight:bold;
  font-variant: small-caps;
  color:<?php echo $err_msg ?>;
  text-align:center;
}

b { font-weight:bold; }

a:link { font-weight:normal; color:<?php echo $norm_link ?>; text-decoration:none; }
a:visited { font-weight:normal; color:<?php echo $norm_link ?>; text-decoration:none; }
a:hover { font-weight:normal; color:<?php echo $active_link ?>; text-decoration:underline; }
a:active { font-weight:normal; color:<?php echo $active_link ?>; text-decoration:none; }

#menubox a:link { font-weight:normal; color:<?php echo $leftnavi_norm_link ?>; text-decoration:none; }
#menubox a:visited { font-weight:normal; color:<?php echo $leftnavi_norm_link ?>; text-decoration:none; }
#menubox a:hover { font-weight:normal; color:<?php echo $leftnavi_hover_active_link ?>; text-decoration:none; }
#menubox a:active { font-weight:normal; color:<?php echo $leftnavi_active_link ?>; text-decoration:none; }

body .groov {
   font-size:11px;
   font-family:Verdana,sans-serif;
   font-weight:bold;
   line-height:14px;
   vertical-align:center;
   color: <?php echo $select; ?>;
   background-color: <?php echo $select_bg; ?>;
   border-color: <?php echo $button_border; ?>
   padding: 0px;
   margin: 0px;
   height: 100%;
}

#content {
   float: left;
   width: 100%;
   min-width: 1100px;
   padding: 0px 0px 0px 15px;

   flex-wrap: wrap; 
   align-content: flex-start;
   min-height:850px;
   height:expression(this.scrollHeight > 850 ? "auto":"850px");
}

#master span {
  float:right;
  display: block;
}

select {
   background-color: <?php echo $select_bg; ?>;
   color: <?php echo $select; ?>;
}

#chart {
   width: 100%;
   height: 500px;
   border: 0px solid <?php echo $red; ?>;
}

#footer {
   float: left;
   //bottom: 0;
   margin: 0px;
   width:100%;
   //min-width: 1000px;
   border: 0px solid red;
}

#footleft {
   float:left;
   padding: 0px 0px 0px 0px;
   width:35%;
}

#footcenter {
   float:left;
   padding: 0px 0px 0px 0px;
   width:35%;
   border: 0px solid red;
}

#footright {
   float:right;
   padding: 0px 0px 0px 0px;
   width:30%;
}

#version {
   position: fixed;
   bottom: 0;
   background-color: <?php echo $footer_bg ?>;
   padding: 15px 0px 15px 10px;
   margin: 0;
   width:100%;
   min-width: 1000px;
   font-size:10px;
   color: rgba(191,191,191,1);
}
