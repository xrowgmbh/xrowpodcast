{* A pagelayout for standalone, pop-up windows displaying a single podcast episode *}
<!DOCTYPE html>
<html>
<head>
  <title>Ricochet Podcast</title>
  <link rel="shortcut icon" type="image/png" href={"favicon16.png"|ezimage} />
  <link rel="apple-touch-icon" type="image/png" href={"apple-touch-icon.png"|ezimage} />
{literal}
  <style type="text/css">
    body, p, div, td {font-family:Georgia,"Times New Roman",Times,serif;font-size:14px;line-height:140%;color:#000;}
    h2 { margin:5px 0;font-size:17px; }
    p { margin:5px 0; }
  </style>
{/literal}
{ezcss_load()}
{ezscript('ezjsc::jquery')}
</head>

<body bgcolor="#FFFFFF">
{$module_result.content}
{ezscript_load()}
</body>
</html>
