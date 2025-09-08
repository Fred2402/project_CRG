function toggleMenu() {
    var menu = document.getElementById("menu-items");
    if (menu.classList.contains("hidden")) {
        menu.classList.remove("hidden");
    } else {
        menu.classList.add("hidden");
    }
}
