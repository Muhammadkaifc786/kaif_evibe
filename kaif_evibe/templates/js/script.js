document.addEventListener("DOMContentLoaded", function () {
    const tabs = document.querySelectorAll(".tab-button");
    const forms = {
        attendee: document.getElementById("attendee-form"),
        organizer: document.getElementById("organizer-form"),
    };

    tabs.forEach(button => {
        button.addEventListener("click", function () {
            let selectedTab = this.getAttribute("data-tab");

            // Hide all forms
            Object.values(forms).forEach(form => form.classList.add("hidden"));

            // Show selected form
            forms[selectedTab].classList.remove("hidden");

            // Remove active class from all buttons
            tabs.forEach(btn => btn.classList.remove("active"));

            // Add active class to clicked button
            this.classList.add("active");
        });
    });
});
