import{j as n}from"./index-Cgf0GrNX.js";function a(){return n.jsxs("div",{className:"portal-current",onClick:t=>{t.target.closest("a")&&t.preventDefault()},children:[n.jsx("link",{rel:"stylesheet",href:"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"}),n.jsx("link",{rel:"stylesheet",href:"https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css"}),n.jsx("div",{dangerouslySetInnerHTML:{__html:`    <nav class="navbar navbar-gradient navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#"  style="cursor: pointer;" title="Klikk for å gå til Ditt nettsted">
                                    <i class="bi bi-building me-2"></i>
                                Fjordvik Kulturhus            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="navbar-text">
                    <i class="bi bi-person-circle me-1"></i>
                    <span class="d-none d-sm-inline">Alex</span>
                </span>
                                <a href="company_audit.php" class="btn btn-nav btn-sm" title="Se bedriftens endringshistorikk">
                    <i class="bi bi-list-check me-1"></i>
                    <span class="d-none d-sm-inline">Aktivitetslogg</span>
                </a>
                                <a href="admin_sessions.php" class="btn btn-nav btn-sm">
                    <i class="bi bi-clock-history me-1"></i>
                    <span class="d-none d-sm-inline">Sessions</span>
                </a>
                <a href="/auth/logout.php" class="btn btn-nav btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>
                    <span class="d-none d-sm-inline">Logg ut</span>
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div class="header-text-content" style="flex: 1; min-width: 200px;">
                    <h1 class="mb-2">Hei, Alex!</h1>
                    <p class="text-muted mb-1" style="font-size: 0.9rem;">Du har tilgang til 5 moduler</p>
                    <p class="text-muted mb-1" id="products-summary" style="font-size: 0.85rem;">
                        <i class="bi bi-box-seam me-1"></i><span id="products-text">12 oppføringer i produktkatalogen</span>
                        <a href="#" id="view-products-link"  style="display: none; margin-left: 0.5rem;">Se og slett</a>
                    </p>
                    <div id="activity-summary" style="font-size: 0.85rem; color: #6c757d;">
                        <i class="bi bi-activity me-1"></i>
                        <span id="activity-text">5 nye hendelser siden sist</span>
                    </div>
                                            <div class="subdomain-info">
                            <a href="https://fjordvik.salesman.no" target="_blank" class="subdomain-url">
                                fjordvik.salesman.no
                                <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.75rem;"></i>
                            </a>
                            <span class="status-badge status-active">
                                Aktiv                            </span>
                        </div>
                                    </div>
                            </div>
        </div>
    </div>
    
        <!-- Module Navigation Bar -->
    <div class="bg-light border-bottom">
        <div class="container">
            <ul class="nav nav-tabs module-tabs border-0" role="tablist">
                                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" 
                                id="tab-rental" 
                                data-bs-toggle="tab" 
                                data-bs-target="#content-rental" 
                                type="button" 
                                role="tab"
                                >
                                                        <i class="bi bi-house-door me-2"></i>
                            Rental                                                    </button>
                    </li>
                                    <li class="nav-item" role="presentation">
                        <button class="nav-link " 
                                id="tab-events" 
                                data-bs-toggle="tab" 
                                data-bs-target="#content-events" 
                                type="button" 
                                role="tab"
                                >
                                                        <i class="bi bi-calendar-event me-2"></i>
                            Events                                                    </button>
                    </li>
                                    <li class="nav-item" role="presentation">
                        <button class="nav-link " 
                                id="tab-sales" 
                                data-bs-toggle="tab" 
                                data-bs-target="#content-sales" 
                                type="button" 
                                role="tab"
                                >
                                                        <i class="bi bi-cart me-2"></i>
                            Sales                                                    </button>
                    </li>
                                    <li class="nav-item" role="presentation">
                        <button class="nav-link " 
                                id="tab-membership" 
                                data-bs-toggle="tab" 
                                data-bs-target="#content-membership" 
                                type="button" 
                                role="tab"
                                >
                                                        <i class="bi bi-people me-2"></i>
                            Medlemskap                                                    </button>
                    </li>
                                    <li class="nav-item" role="presentation">
                        <button class="nav-link " 
                                id="tab-economy" 
                                data-bs-toggle="tab" 
                                data-bs-target="#content-economy" 
                                type="button" 
                                role="tab"
                                >
                                                        <i class="bi bi-wallet2 me-2"></i>
                            Økonomi                                                    </button>
                    </li>
                                
                <!-- Announcements Tab (always visible for all admins) -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link" 
                            id="tab-announcements" 
                            data-bs-toggle="tab" 
                            data-bs-target="#content-announcements" 
                            type="button" 
                            role="tab">
                        <i class="bi bi-megaphone me-2"></i>
                        News
                    </button>
                </li>
            </ul>
        </div>
    </div>
        
`}}),n.jsx("div",{style:{maxWidth:1140,margin:"32px auto",padding:"24px",color:"#60758a",border:"1px dashed #d8e3ea",borderRadius:12},children:"Modulinnholdet fortsetter her. Dette er dagens toppområde, med eksempeldata."})]})}export{a as Current};
