<?php
// Minimal test av akkurat samme tab-struktur som dashboard bruker
session_start();
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Tab Structure Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .module-tabs .nav-link {
            color: #6b7280;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border: none;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        .module-tabs .nav-link:hover {
            color: #667eea;
            border-bottom-color: rgba(102, 126, 234, 0.3);
        }
        .module-tabs .nav-link.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Dashboard Tab Structure Test</h1>
        <p>Dette er AKKURAT samme tab-struktur som company_dashboard.php bruker.</p>
        
        <!-- SAMME STRUKTUR SOM DASHBOARD -->
        <div class="bg-light border-bottom">
            <ul class="nav nav-tabs module-tabs border-0" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" 
                            id="tab-sales" 
                            data-bs-toggle="tab" 
                            data-bs-target="#content-sales" 
                            type="button" 
                            role="tab">
                        <i class="bi bi-cart me-2"></i>Salg
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" 
                            id="tab-events" 
                            data-bs-toggle="tab" 
                            data-bs-target="#content-events" 
                            type="button" 
                            role="tab">
                        <i class="bi bi-calendar-event me-2"></i>Arrangement
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" 
                            id="tab-economy" 
                            data-bs-toggle="tab" 
                            data-bs-target="#content-economy" 
                            type="button" 
                            role="tab">
                        <i class="bi bi-cash-stack me-2"></i>Økonomi
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="tab-content mt-4">
            <div class="tab-pane fade show active" id="content-sales" role="tabpanel">
                <div class="alert alert-success">
                    <h4>✅ Salg-modul</h4>
                    <p>Hvis du ser dette, fungerer Sales-tab!</p>
                </div>
            </div>
            <div class="tab-pane fade" id="content-events" role="tabpanel">
                <div class="alert alert-info">
                    <h4>✅ Arrangement-modul</h4>
                    <p>Hvis du ser dette, fungerer Events-tab!</p>
                </div>
            </div>
            <div class="tab-pane fade" id="content-economy" role="tabpanel">
                <div class="alert alert-warning">
                    <h4>✅ Økonomi-modul</h4>
                    <p>Hvis du ser dette, fungerer Economy-tab!</p>
                </div>
            </div>
        </div>
        
        <hr class="mt-5">
        <div class="alert alert-secondary">
            <h5>Console Log:</h5>
            <div id="console-log" style="font-family: monospace; font-size: 12px;"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const consoleDiv = document.getElementById('console-log');
        
        function log(msg) {
            console.log(msg);
            consoleDiv.innerHTML += msg + '<br>';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            log('✅ DOMContentLoaded fired');
            log('✅ Bootstrap: ' + (typeof bootstrap !== 'undefined' ? 'LOADED' : 'NOT LOADED'));
            
            // Lytt til tab events
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function(tabButton) {
                log('✅ Found tab button: ' + tabButton.id);
                
                tabButton.addEventListener('shown.bs.tab', function(event) {
                    log('🎯 Tab shown: ' + event.target.id);
                });
                
                tabButton.addEventListener('click', function(event) {
                    log('👆 Tab clicked: ' + this.id);
                });
            });
        });
    </script>
</body>
</html>
