<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Symphony Error</title>
    <link media="screen" href="{ASSETS_URL}/css/symphony.min.css" rel="stylesheet">
    <script src="{ASSETS_URL}/js/symphony.min.js"></script>
    <script>Symphony.Context.add('root', '{URL}');Symphony.Context.add('env', {});</script>
  </head>
  <body id="error">
    <div class="frame">
      <ul>
        <li>
          <h1><em>Symphony Error:</em> <code>mod_rewrite</code> is not enabled</h1>
          <p>It appears the <code>mod_rewrite</code> is not enabled or available on this server.</p>
        </li>
      </ul>
    </div>
  </body>
</html>
