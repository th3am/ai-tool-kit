<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mind Map Export</title>
    <style>
        body, html { margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden; background: white; }
        #app { width: 100vw; height: 100vh; }
        svg.markmap { width: 100%; height: 100%; }
        .markmap text { font-family: sans-serif; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/d3@7"></script>
    <script src="https://cdn.jsdelivr.net/npm/markmap-lib@0.15.4/dist/browser/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/markmap-view@0.15.4/dist/browser/index.min.js"></script>
</head>
<body>
    <div id="app">
        <svg id="mindmap"></svg>
    </div>

    <script>
        const markdown = @json($markdown);
        
        const { Transformer } = window.markmap;
        const { Markmap } = window.markmap;
        
        const transformer = new Transformer();
        const { root } = transformer.transform(markdown);
        
        Markmap.create('#mindmap', {
            height: '100%',
            autoFit: true,
            duration: 0
        }, root);
    </script>
</body>
</html>
