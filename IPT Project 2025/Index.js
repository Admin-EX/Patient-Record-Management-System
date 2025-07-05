document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        // Fade out after 3 seconds
        setTimeout(function() {
            alert.style.opacity = '1';
            fadeOut(alert);
        }, 3000);
    });
});

function fadeOut(element) {
    let opacity = 1;
    const timer = setInterval(function() {
        if (opacity <= 0.1) {
            clearInterval(timer);
            element.style.display = 'none';
        }
        element.style.opacity = opacity;
        opacity -= opacity * 0.1;
    }, 50);
}

// Close button functionality
document.querySelectorAll('.closebtn').forEach(function(btn) {
    btn.onclick = function() {
        let div = this.parentElement;
        fadeOut(div);
    };
});