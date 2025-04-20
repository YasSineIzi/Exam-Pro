<!-- <link rel="stylesheet" href="./style/navbar.css"> -->
 <style>
    /* Modern Sidebar Redesign */
:root {
    --sidebar-width: 260px;
    --collapsed-width: 80px;
    --primary-color: #6366f1;
    --hover-bg: #f8fafc;
    --transition-speed: 0.3s;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    background: #ffffff;
    width: var(--sidebar-width);
    box-shadow: 4px 0 15px rgba(0, 0, 0, 0.05);
    transition: all var(--transition-speed) ease;
    z-index: 1000;
    display: flex;
    flex-direction: column;
}

.sidebar.collapsed {
    width: var(--collapsed-width);
}

.sidebar-header {
    padding: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #f1f5f9;
}

.logo-container {
    display: flex;
    align-items: center;
    overflow: hidden;
}

.logo-text {
    font-size: 1.5rem;
    font-weight: 600;
    color: #0f172a;
    margin-left: 12px;
    white-space: nowrap;
    transition: opacity var(--transition-speed);
}

.toggle-btn {
    background: #f8fafc;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: grid;
    place-items: center;
}

.toggle-btn:hover {
    background: #e2e8f0;
    transform: rotate(180deg);
}

.nav-links {
    flex: 1;
    padding: 16px;
    overflow-y: auto;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    margin: 8px 0;
    border-radius: 8px;
    color: #64748b;
    text-decoration: none;
    position: relative;
    transition: all 0.2s ease;
    font-family: var(--bs-body-font-family, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif);
}

.nav-link:hover {
    background: var(--hover-bg);
    color: var(--primary-color);
}

.nav-link i {
    font-size: 1.2rem;
    width: 28px;
    height: 28px;
    display: grid;
    place-items: center;
}

.link-text {
    margin-left: 14px;
    white-space: nowrap;
    transition: opacity var(--transition-speed);
    font-weight: 500;
}

/* Collapsed State */
.sidebar.collapsed .logo-text,
.sidebar.collapsed .link-text {
    opacity: 0;
    pointer-events: none;
}

.sidebar.collapsed .toggle-btn {
    transform: rotate(180deg);

}

/* Active State */
.nav-link.active {
    background: #eef2ff;
    color: var(--primary-color);
    font-weight: 600;
}

.nav-link.active::after {
    content: '';
    position: absolute;
    right: -12px;
    height: 24px;
    width: 4px;
    background: var(--primary-color);
    border-radius: 2px;
}

/* Tooltips */
.nav-link::before {
    content: attr(data-tooltip);
    position: absolute;
    left: calc(var(--collapsed-width) + 10px);
    top: 50%;
    transform: translateY(-50%);
    background: #0f172a;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.875rem;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease;
    pointer-events: none;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.sidebar.collapsed .nav-link:hover::before {
    opacity: 1;
    visibility: visible;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .sidebar {
        width: var(--collapsed-width);
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
    }

    .sidebar.expanded {
        width: var(--sidebar-width);
    }

    .sidebar.expanded .logo-text,
    .sidebar.expanded .link-text {
        opacity: 1;
    }

    .toggle-btn {
        display: none;
    }

    .nav-link::before {
        display: none;
    }
}

/* Content Area Adjustment */
.main-content {
    margin-left: var(--sidebar-width);
    transition: margin-left var(--transition-speed) ease;
    padding: 20px;
}

.sidebar.collapsed~.main-content {
    margin-left: var(--collapsed-width);
}

@media (max-width: 768px) {
    .main-content {
        margin-left: var(--collapsed-width);
    }

    .sidebar.expanded~.main-content {
        margin-left: var(--sidebar-width);
    }
}
 </style>
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">

            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20" />
            </svg>
            <span class="logo-text">ExamPro</span>
        </div>
        <button class="toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>

    <div class="nav-links">
        <a href="index.php" class="nav-link" data-tooltip="Accueil">
            <i class="fas fa-home"></i>
            <span class="link-text">Accueil</span>
        </a>
        <a href="Examens.php" class="nav-link" data-tooltip="Examens">
            <i class="fas fa-file-alt"></i>
            <span class="link-text">Examens</span>
        </a>
        <a href="PageResultats.php" class="nav-link" data-tooltip="Résultats">
            <i class="fas fa-chart-bar"></i>
            <span class="link-text">Résultats</span>
        </a>
        <a href="profil.php" class="nav-link" data-tooltip="Profil">
            <i class="fas fa-user"></i>
            <span class="link-text">Profil</span>
        </a>
        <div class="nav-spacer" style="flex: 1;"></div>
        <a href="../" class="nav-link" data-tooltip="Déconnexion">
            <i class="fas fa-sign-out-alt"></i>
            <span class="link-text">Déconnexion</span>
        </a>
    </div>
</nav>

<main class="main-content" id="mainContent">
    <!-- Your page content here -->
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const toggleBtn = document.getElementById('sidebarToggle');

        // Toggle sidebar
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });

        // Mobile handling
        const mediaQuery = window.matchMedia('(max-width: 768px)');

        function handleMobile(e) {
            if (e.matches) {
                sidebar.classList.add('collapsed');
                sidebar.classList.remove('expanded');
            } else {
                sidebar.classList.remove('collapsed');
            }
        }

        mediaQuery.addListener(handleMobile);
        handleMobile(mediaQuery);

        // Active link detection
        const currentPath = window.location.pathname.split('/').pop();
        document.querySelectorAll('.nav-link').forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            }
        });

        // Auto-close on mobile when clicking outside
        document.addEventListener('click', (e) => {
            if (mediaQuery.matches &&
                !sidebar.contains(e.target) &&
                !e.target.closest('.mobile-menu-toggle')) {
                sidebar.classList.remove('expanded');
            }
        });


    });
</script>