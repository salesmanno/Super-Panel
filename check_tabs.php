<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Tab Diagnostikk</title>
</head>
<body>
    <h1>JavaScript Error Check</h1>
    <p>Denne siden vil vise ALLE JavaScript-feil fra company_dashboard.php</p>
    
    <div id="output" style="font-family: monospace; background: #f5f5f5; padding: 20px; margin: 20px 0; white-space: pre-wrap;"></div>
    
    <h2>Test: Åpne dashboardet i nytt vindu</h2>
    <button onclick="window.open('pages/company_dashboard.php', '_blank')" style="padding: 10px 20px; font-size: 16px;">
        Åpne Dashboard i nytt vindu
    </button>
    
    <script>
        // Overstyr console.error for å fange alle feil
        const output = document.getElementById('output');
        const originalError = console.error;
        const originalWarn = console.warn;
        const originalLog = console.log;
        
        console.error = function(...args) {
            output.innerHTML += '❌ ERROR: ' + args.join(' ') + '\n\n';
            originalError.apply(console, args);
        };
        
        console.warn = function(...args) {
            output.innerHTML += '⚠️ WARNING: ' + args.join(' ') + '\n\n';
            originalWarn.apply(console, args);
        };
        
        console.log = function(...args) {
            output.innerHTML += '📝 LOG: ' + args.join(' ') + '\n\n';
            originalLog.apply(console, args);
        };
        
        // Fang window errors
        window.addEventListener('error', function(event) {
            output.innerHTML += '💥 WINDOW ERROR:\n';
            output.innerHTML += 'Message: ' + event.message + '\n';
            output.innerHTML += 'File: ' + event.filename + '\n';
            output.innerHTML += 'Line: ' + event.lineno + '\n';
            output.innerHTML += 'Column: ' + event.colno + '\n\n';
        });
        
        output.innerHTML = '✅ Diagnostikk aktivert\n\n';
        output.innerHTML += 'Åpne dashboard-siden i nytt vindu og sjekk om feil vises her...\n\n';
    </script>
</body>
</html>
