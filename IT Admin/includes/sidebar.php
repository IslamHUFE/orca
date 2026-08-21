<?php

$current_page = basename($_SERVER["PHP_SELF"]);

?>

<style>

/* ====================================================
   GENERAL
==================================================== */

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f8faf9;
    color: #111827;
}


/* ====================================================
   SIDEBAR
==================================================== */

.dashboard-container {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 240px;
    min-height: 100vh;

    background: white;

    border-right: 1px solid #e5e7eb;

    display: flex;
    flex-direction: column;

    padding: 25px 15px;

    position: fixed;

    left: 0;
    top: 0;
    bottom: 0;

    z-index: 1000;
}


/* ====================================================
   LOGO
==================================================== */

.sidebar-logo {
    display: flex;
    align-items: center;

    gap: 10px;

    padding: 5px 10px 30px;

    font-size: 20px;

    font-weight: 700;

    color: #16803c;
}

.logo-icon {
    width: 35px;
    height: 35px;

    background: #eaf6ee;

    color: #16803c;

    border-radius: 10px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 22px;
}


/* ====================================================
   NAVIGATION
==================================================== */

.sidebar-nav {
    display: flex;

    flex-direction: column;

    gap: 5px;
}

.nav-link {
    display: flex;

    align-items: center;

    gap: 12px;

    padding: 11px 12px;

    color: #4b5563;

    text-decoration: none;

    border-radius: 8px;

    font-size: 14px;

    transition: 0.2s;
}

.nav-link:hover {
    background: #eaf6ee;

    color: #16803c;
}

.nav-link.active {
    background: #eaf6ee;

    color: #16803c;

    font-weight: 600;
}


/* ====================================================
   BOTTOM
==================================================== */

.sidebar-bottom {
    margin-top: auto;

    display: flex;

    flex-direction: column;

    gap: 5px;

    padding-top: 20px;

    border-top: 1px solid #e5e7eb;
}


/* ====================================================
   MAIN CONTENT
==================================================== */

.main-content {
    margin-left: 240px;

    width: calc(100% - 240px);

    min-height: 100vh;

    padding: 45px;
}


/* ====================================================
   TOGGLE BUTTON
==================================================== */

.sidebar-toggle {
    position: absolute;

    top: 20px;

    right: -14px;

    width: 28px;

    height: 28px;

    border-radius: 50%;

    border: 1px solid #e5e7eb;

    background: white;

    color: #16803c;

    font-size: 20px;

    cursor: pointer;

    display: flex;

    align-items: center;

    justify-content: center;

    z-index: 1001;
}

.sidebar-toggle:hover {
    background: #eaf6ee;
}


/* ====================================================
   RESPONSIVE
==================================================== */

@media (max-width: 800px) {

    .sidebar {
        width: 200px;
    }

    .main-content {
        margin-left: 200px;

        width: calc(100% - 200px);

        padding: 25px;
    }

}

@media (max-width: 600px) {

    .sidebar {
        position: relative;

        width: 100%;

        min-height: auto;
    }

    .dashboard-container {
        flex-direction: column;
    }

    .main-content {
        margin-left: 0;

        width: 100%;
    }

}


/* ====================================================
   COLLAPSED SIDEBAR
==================================================== */

.sidebar.collapsed {
    width: 75px;
}

.sidebar.collapsed .sidebar-logo span {
    display: none;
}

.sidebar.collapsed .nav-link {
    justify-content: center;

    padding-left: 0;

    padding-right: 0;
}

.sidebar.collapsed .nav-link span:last-child {
    display: none;
}

.sidebar.collapsed .sidebar-bottom .nav-link {
    justify-content: center;
}

body.sidebar-collapsed .main-content {
    margin-left: 75px;

    width: calc(100% - 75px);
}


/* ====================================================
   BOTTOM
==================================================== */

.sidebar-bottom {
    padding: 15px 14px;

    border-top: 1px solid #f0f0f0;
}

</style>


<!-- ====================================================
     SIDEBAR
==================================================== -->

<aside class="sidebar" id="sidebar">


    <!-- ====================================================
         TOGGLE BUTTON
    ==================================================== -->

    <button
        type="button"
        class="sidebar-toggle"
        id="sidebarToggle"
        title="Collapse sidebar"
    >
        ‹
    </button>


    <!-- ====================================================
         LOGO
    ==================================================== -->

    <div class="sidebar-logo">

        <div class="logo-icon">
            ♻
        </div>

        <span>
            UniShare
        </span>

    </div>


    <!-- ====================================================
         NAVIGATION
    ==================================================== -->

    <nav class="sidebar-nav">


        <!-- DASHBOARD -->

        <a
            href="admin_dashboard.php"
            class="nav-link
            <?= $current_page === 'admin_dashboard.php' ? 'active' : '' ?>"
        >

            <span>🏠</span>

            <span>
                Dashboard
            </span>

        </a>


        <!-- UPCYCLING -->

        <a
            href="upcycling.php"
            class="nav-link
            <?= (
                $current_page === 'upcycling.php' ||
                $current_page === 'upcycling_details.php'
            ) ? 'active' : '' ?>"
        >

            <span>♻️</span>

            <span>
                Upcycling
            </span>

        </a>


        <!-- COMPUTERS -->

        <a
            href="computers.php"
            class="nav-link
            <?= $current_page === 'computers.php' ? 'active' : '' ?>"
        >

            <span>🖥️</span>

            <span>
                Computers
            </span>

        </a>


        <!-- COMPONENTS -->

        <a
            href="components.php"
            class="nav-link
            <?= $current_page === 'components.php' ? 'active' : '' ?>"
        >

            <span>🧩</span>

            <span>
                Components
            </span>

        </a>


        


        <!-- USERS -->

        <a
            href="users.php"
            class="nav-link
            <?= $current_page === 'users.php' ? 'active' : '' ?>"
        >

            <span>👥</span>

            <span>
                Users
            </span>

        </a>


        <!-- EXCHANGES -->

        <a
            href="exchanges.php"
            class="nav-link
            <?= $current_page === 'exchanges.php' ? 'active' : '' ?>"
        >

            <span>🔄</span>

            <span>
                Exchanges
            </span>

        </a>


        <!-- DONATIONS -->

        <a
            href="donations.php"
            class="nav-link
            <?= $current_page === 'donations.php' ? 'active' : '' ?>"
        >

            <span>🎁</span>

            <span>
                Donations
            </span>

        </a>



       


 <a
            href="smart_matching.php"
            class="nav-link
            <?= $current_page === 'smart_matching.php' ? 'active' : '' ?>"
        >

            <span>🔄</span>
             <span>
               smart_matching
            </span>

        </a>


<a href="technician_repair_history.php"  class="nav-link
            <?= $current_page === 'technician_repair_history.php' ? 'active' : '' ?>">
    🔧
    <span>Repair History</span>
     
</a>
        

      
       

        <!-- REPORTS -->

        <a
            href="reports.php"
            class="nav-link
            <?= $current_page === 'reports.php' ? 'active' : '' ?>"
        >

            <span>📊</span>

            <span>
                Reports
            </span>

        </a>


    </nav>


    <!-- ====================================================
         BOTTOM
    ==================================================== -->

    <div class="sidebar-bottom">


        <!-- SETTINGS -->

        <a href="../student/settings.php" class="nav-link" 
          <?= $current_page === 'settings.php' ? 'active' : '' ?>">

            <span>⚙</span>
            Settings
        </a>

        <!-- LOGOUT -->

        <a
            href="../student/logout.php"
            class="nav-link"
        >


            <span>🚪</span>

            <span>
                Logout
            </span>

        </a>


    </div>


</aside>


<!-- ====================================================
     SIDEBAR SCRIPT
==================================================== -->

<script>

const sidebar = document.getElementById("sidebar");

const sidebarToggle =
    document.getElementById("sidebarToggle");


if (sidebar && sidebarToggle) {

    sidebarToggle.addEventListener(
        "click",
        function () {

            const isCollapsed =
                sidebar.classList.toggle("collapsed");


            document.body.classList.toggle(
                "sidebar-collapsed",
                isCollapsed
            );


            if (isCollapsed) {

                sidebarToggle.innerHTML = "›";

                sidebarToggle.title =
                    "Expand sidebar";

            } else {

                sidebarToggle.innerHTML = "‹";

                sidebarToggle.title =
                    "Collapse sidebar";

            }

        }
    );

}

</script>