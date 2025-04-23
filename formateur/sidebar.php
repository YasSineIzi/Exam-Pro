<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<style>
    /* Modern Sidebar Styling */
    :root {
        --sidebar-width: 260px;
        --collapsed-width: 80px;
        --primary-color: #6366f1;
        --hover-bg: #f8fafc;
        --transition-speed: 0.3s;
    }

    body {
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }

    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        width: var(--sidebar-width);
        background: #ffffff;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.05);
        transition: all var(--transition-speed) ease;
        display: flex;
        flex-direction: column;
        z-index: 1000;
        overflow-x: hidden;
    }

    .sidebar-header {
        padding: 0 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #f1f5f9;
        min-width: 0;
        position: relative;
        margin: auto;
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
        width: 100%;
    }

    .logo i {
        font-size: 1.5rem;
        color: var(--primary-color);
        transition: all var(--transition-speed) ease;
        width: 24px;
        text-align: center;
    }

    .logo-text {
        font-size: 1.5rem;
        font-weight: 600;
        color: #0f172a;
        transition: opacity var(--transition-speed);
        white-space: nowrap;
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
        flex-shrink: 0;
    }

    .toggle-btn:hover {
        background: #e2e8f0;
    }

    .toggle-btn i {
        transition: transform var(--transition-speed);
    }

    .nav {
        flex: 1;
        padding: 1rem;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .nav-item {
        margin-bottom: 0.5rem;
        list-style: none;
        min-width: 0;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        color: #64748b;
        text-decoration: none;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .nav-link:hover {
        background: var(--hover-bg);
        color: var(--primary-color);
    }

    .nav-link i {
        width: 24px;
        text-align: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .nav-link.active {
        background: #eef2ff;
        color: var(--primary-color);
        font-weight: 600;
    }

    /* Toggle button containers */
    .toggle-container-top,
    .toggle-container-bottom {
        padding: 1rem;
        display: flex;
        justify-content: flex-end;
        transition: opacity var(--transition-speed);
        min-width: 0;
    }

    .toggle-container-bottom {
        border-top: 1px solid #f1f5f9;
        opacity: 0;
        pointer-events: none;
    }

    /* Sidebar Collapse */
    .sidebar.collapsed {
        width: var(--collapsed-width);
    }

    .sidebar.collapsed .logo {
        justify-content: center;
    }

    .sidebar.collapsed .logo i {
        /* margin: 0; */
    }

    .sidebar.collapsed .logo-text,
    .sidebar.collapsed .nav-link span {
        display: none;
    }

    .sidebar.collapsed .nav-link {
        justify-content: center;
        padding: 0.75rem 0;
    }

    .sidebar.collapsed .toggle-container-top {
        opacity: 0;
        pointer-events: none;
    }

    .sidebar.collapsed .toggle-container-bottom {
        opacity: 1;
        pointer-events: auto;
        justify-content: center;
    }

    .sidebar.collapsed .toggle-btn i {
        transform: rotate(180deg);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .sidebar {
            width: var(--collapsed-width);
        }

        .sidebar .logo {
            justify-content: center;
        }

        .sidebar .logo i {
            margin: 0;
        }

        .sidebar .logo-text,
        .sidebar .nav-link span {
            display: none;
        }

        .sidebar .nav-link {
            justify-content: center;
        }

        .toggle-btn {
            display: none;
        }
    }
</style>

<nav id="sidebar" class="sidebar shadow-lg">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <span class="logo-text">ExamPro</span>
        </div>
        <div class="toggle-container-top">
            <button class="toggle-btn" id="sidebarToggleTop" aria-label="Toggle sidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="index.php" class="nav-link ">
                <i class="fas fa-home"></i>
                <span>Accueil</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="creerExam.php" class="nav-link">
                <i class="fas fa-plus-circle"></i>
                <span>Créer exam</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="lesExamCreé.php" class="nav-link">
                <i class="fas fa-file-alt"></i>
                <span>Examens Créés</span>
            </a>
        </li>
        <!-- <li class="nav-item">
    <a href="activities.php" class="nav-link">
        <i class="fas fa-exclamation-triangle"></i>
        <span>Activités Suspectes</span>
    </a>
</li> -->
        <li class="nav-item">
            <a href="students_who_took_exam.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Étudiants</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="results.php" class="nav-link">
                <i class="fas fa-chart-bar"></i>
                <span>Resultats</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="parametresProf.php" class="nav-link">
                <i class="fas fa-cog"></i>
                <span>Paramètres Prof</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="../" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </li>
    </ul>
    <div class="toggle-container-bottom">
        <button class="toggle-btn" id="sidebarToggleBottom" aria-label="Toggle sidebar">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const toggleBtnTop = document.getElementById('sidebarToggleTop');
        const toggleBtnBottom = document.getElementById('sidebarToggleBottom');

        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }

        toggleBtnTop.addEventListener('click', toggleSidebar);
        toggleBtnBottom.addEventListener('click', toggleSidebar);

        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            sidebar.classList.add('collapsed');
        }
    });
</script>