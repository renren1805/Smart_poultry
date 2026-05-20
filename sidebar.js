document.addEventListener("DOMContentLoaded", () => {
    // Ensure sidebar overlay exists in the DOM
    let overlay = document.getElementById("sidebarOverlay") || document.querySelector(".sidebar-overlay");
    if (!overlay) {
        overlay = document.createElement("div");
        overlay.className = "sidebar-overlay";
        overlay.id = "sidebarOverlay";
        document.body.prepend(overlay);
    }

    // Add click event to overlay to close sidebar
    overlay.addEventListener("click", closeSidebar);
});

function toggleSidebar() {
    const sidebar = document.querySelector(".sidebar");
    const overlay = document.getElementById("sidebarOverlay") || document.querySelector(".sidebar-overlay");

    if (sidebar) sidebar.classList.toggle("active");
    if (overlay) overlay.classList.toggle("active");
}

function closeSidebar() {
    const sidebar = document.querySelector(".sidebar");
    const overlay = document.getElementById("sidebarOverlay") || document.querySelector(".sidebar-overlay");

    if (sidebar) sidebar.classList.remove("active");
    if (overlay) overlay.classList.remove("active");
}

// Close sidebar automatically on mobile when clicking outside
document.addEventListener("click", function (event) {
    const sidebar = document.querySelector(".sidebar");
    const toggleBtn = document.querySelector(".menu-toggle");

    if (
        window.innerWidth <= 1024 &&
        sidebar && sidebar.classList.contains("active") &&
        !sidebar.contains(event.target) &&
        (!toggleBtn || !toggleBtn.contains(event.target))
    ) {
        closeSidebar();
    }
});
