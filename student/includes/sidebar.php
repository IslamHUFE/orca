<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;

    
    margin-left: 240px;

    transition: margin-left 0.3s ease;
}


/* ====================================================
   SIDEBAR
==================================================== */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;

    width: 240px;
    height: 100vh;

    background: #ffffff;

    border-right: 1px solid #e5e7eb;

    display: flex;
    flex-direction: column;

    z-index: 1000;

    transition: width 0.3s ease;

    box-shadow: 3px 0 15px rgba(0, 0, 0, 0.03);
}


/* ====================================================
   LOGO
==================================================== */

.sidebar-logo {
    height: 75px;

    display: flex;
    align-items: center;

    gap: 10px;

    padding: 0 25px;

    border-bottom: 1px solid #f0f0f0;

    font-size: 20px;
    font-weight: 700;

    color: #16803c;

    white-space: nowrap;

    overflow: hidden;
}

.logo-icon {
    min-width: 35px;

    width: 35px;
    height: 35px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #dcfce7;

    border-radius: 10px;

    font-size: 20px;
}


/* ====================================================
   SIDEBAR TOGGLE
==================================================== */

.sidebar-toggle {
    position: absolute;

    right: -17px;
    top: 82px;

    width: 34px;
    height: 34px;

    border-radius: 50%;

    border: 1px solid #d1fae5;

    background: #ffffff;

    color: #16803c;

    cursor: pointer;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 14px;
    font-weight: bold;

    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.10);

    transition: all 0.25s ease;
}

.sidebar-toggle:hover {
    background: #16803c;

    color: white;

    border-color: #16803c;

    transform: scale(1.08);
}


/* ====================================================
   NAVIGATION
==================================================== */

.sidebar-nav {
    flex: 1;

    padding: 20px 14px;

    overflow-y: auto;
}

.nav-link {
    display: flex;

    align-items: center;

    gap: 12px;

    width: 100%;

    padding: 11px 13px;

    margin-bottom: 5px;

    border-radius: 9px;

    text-decoration: none;

    color: #4b5563;

    font-size: 14px;

    white-space: nowrap;

    overflow: hidden;

    transition: all 0.2s ease;
}

.nav-link span {
    min-width: 22px;

    width: 22px;

    text-align: center;

    font-size: 17px;
}

.nav-link:hover {
    background: #f0fdf4;

    color: #16803c;

    transform: translateX(2px);
}


/* ====================================================
   ACTIVE
==================================================== */

.nav-link.active {
    background: #dcfce7;

    color: #16803c;

    font-weight: 600;
}


/* ====================================================
   BOTTOM
==================================================== */

.sidebar-bottom {
    padding: 15px 14px;

    border-top: 1px solid #f0f0f0;
}


/* ====================================================
   COLLAPSED SIDEBAR
==================================================== */

.sidebar.collapsed {
    width: 72px;
}


/* Logo */

.sidebar.collapsed .sidebar-logo {
    padding: 0;

    justify-content: center;
}

.sidebar.collapsed .sidebar-logo span {
    display: none;
}


/* Links */

.sidebar.collapsed .nav-link {
    justify-content: center;

    gap: 0;

    padding: 11px 0;
}

.sidebar.collapsed .nav-link span {
    margin: 0;
}


/* Button */

.sidebar.collapsed .sidebar-toggle {
    right: -17px;
}


/* ====================================================
   BODY WHEN SIDEBAR COLLAPSED
==================================================== */

body.sidebar-collapsed {
    margin-left: 72px;
}


/* ====================================================
   SCROLLBAR
==================================================== */

.sidebar-nav::-webkit-scrollbar {
    width: 5px;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: #d1fae5;

    border-radius: 10px;
}


/* ====================================================
   MOBILE
==================================================== */

@media (max-width: 800px) {

    body {
        margin-left: 200px;
    }

    .sidebar {
        width: 200px;
    }

    .sidebar-logo {
        padding: 0 18px;

        font-size: 18px;
    }

    .nav-link {
        font-size: 13px;

        padding: 10px;
    }

}

.sidebar.collapsed .nav-link {
    font-size: 0;
}

.sidebar.collapsed .nav-link span {
    font-size: 17px;
}
</style>


<aside class="sidebar" id="sidebar">

    <!-- TOGGLE BUTTON -->

    <button
        type="button"
        class="sidebar-toggle"
        id="sidebarToggle"
        title="Collapse sidebar"
    >
        ‹
    </button>


    <!-- LOGO -->

    <div class="sidebar-logo">

        <div class="logo-icon">
            ♻
        </div>

        <span>
            UniShare
        </span>

    </div>


    <!-- NAVIGATION -->

    <nav class="sidebar-nav">

        <a href="dashboard.php" class="nav-link">
            <span>🏠</span>
            Dashboard
        </a>

        <a href="browse_resources.php" class="nav-link">
            <span>🔍</span>
            Browse Resources
        </a>

        <a href="add_resource.php" class="nav-link">
            <span>➕</span>
            Add Resource
        </a>

        <a href="my_resources.php" class="nav-link">
            <span>📦</span>
            My Resources
        </a>

        <a href="my_requests.php" class="nav-link">
            <span>📋</span>
            My Requests
        </a>

        <a href="donations.php" class="nav-link">
            <span>🎁</span>
            Donations
        </a>

        <a href="exchanges.php" class="nav-link">
            <span>🔄</span>
            Exchanges
        </a>

        
        <a
    href="upcycling.php"
    class="nav-link <?= $current_page === 'upcycling.php' ? 'active' : '' ?>"
>
    <span>♻️</span>
    <span>Upcycling</span>
</a>

        <a href="messages.php" class="nav-link">
            <span>💬</span>
            Messages
        </a>

        <a href="impact.php" class="nav-link">
            <span>🌱</span>
            Impact
        </a>

    </nav>


    <!-- BOTTOM -->

    <div class="sidebar-bottom">

        <a href="settings.php" class="nav-link">
            <span>⚙</span>
            Settings
        </a>

        <a href="logout.php" class="nav-link">
            <span>🚪</span>
            Logout
        </a>

    </div>

</aside>


<script>

const sidebar = document.getElementById("sidebar");
const sidebarToggle = document.getElementById("sidebarToggle");

sidebarToggle.addEventListener("click", function () {

    const isCollapsed = sidebar.classList.toggle("collapsed");

    document.body.classList.toggle(
        "sidebar-collapsed",
        isCollapsed
    );

    if (isCollapsed) {

        sidebarToggle.innerHTML = "›";

        sidebarToggle.title = "Expand sidebar";

    } else {

        sidebarToggle.innerHTML = "‹";

        sidebarToggle.title = "Collapse sidebar";

    }

});

</script>