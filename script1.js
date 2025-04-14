
document.addEventListener('DOMContentLoaded', function () {
    // Select all dropdown toggle links
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

    // Add click event listeners to dropdown toggles
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function (e) {
            e.preventDefault(); // Prevent default behavior only for dropdown toggles
            const parent = this.parentElement;
            parent.classList.toggle('active'); // Toggle the active class for dropdown menus
        });
    });
});