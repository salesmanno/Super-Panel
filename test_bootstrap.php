<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bootstrap Tab Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Bootstrap Tab Test</h1>
        <p>Klikk på tabs nedenfor. Hvis ingenting skjer, er Bootstrap JavaScript ikke lastet eller blokkert.</p>
        
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab1" data-bs-toggle="tab" data-bs-target="#content1" type="button" role="tab">
                    Tab 1
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab2" data-bs-toggle="tab" data-bs-target="#content2" type="button" role="tab">
                    Tab 2
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab3" data-bs-toggle="tab" data-bs-target="#content3" type="button" role="tab">
                    Tab 3
                </button>
            </li>
        </ul>
        
        <div class="tab-content mt-3">
            <div class="tab-pane fade show active" id="content1" role="tabpanel">
                <div class="alert alert-success">✅ Tab 1 innhold vises</div>
            </div>
            <div class="tab-pane fade" id="content2" role="tabpanel">
                <div class="alert alert-info">✅ Tab 2 innhold vises</div>
            </div>
            <div class="tab-pane fade" id="content3" role="tabpanel">
                <div class="alert alert-warning">✅ Tab 3 innhold vises</div>
            </div>
        </div>
        
        <div class="mt-4">
            <h3>Diagnostikk:</h3>
            <div id="diagnostics" class="alert alert-secondary">
                <p><strong>Bootstrap version:</strong> <span id="bs-version">Sjekker...</span></p>
                <p><strong>Bootstrap objekt:</strong> <span id="bs-object">Sjekker...</span></p>
                <p><strong>Tab funksjon:</strong> <span id="bs-tab">Sjekker...</span></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Diagnostikk
        document.addEventListener('DOMContentLoaded', function() {
            // Sjekk Bootstrap version
            if (typeof bootstrap !== 'undefined') {
                document.getElementById('bs-version').textContent = '✅ Bootstrap lastet';
                document.getElementById('bs-object').textContent = '✅ Bootstrap objekt tilgjengelig';
            } else {
                document.getElementById('bs-version').textContent = '❌ Bootstrap IKKE lastet';
                document.getElementById('bs-object').textContent = '❌ Bootstrap objekt MANGLER';
            }
            
            // Sjekk Tab funksjon
            if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                document.getElementById('bs-tab').textContent = '✅ Bootstrap Tab tilgjengelig';
            } else {
                document.getElementById('bs-tab').textContent = '❌ Bootstrap Tab MANGLER';
            }
            
            // Logg til console
            console.log('Bootstrap:', typeof bootstrap !== 'undefined' ? 'Loaded' : 'NOT LOADED');
            console.log('Bootstrap.Tab:', typeof bootstrap !== 'undefined' && bootstrap.Tab ? 'Available' : 'NOT AVAILABLE');
        });
    </script>
</body>
</html>
