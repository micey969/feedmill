let timeout;

// Function to reset timer
function resetTimer() {
    clearTimeout(timeout);

    timeout = setTimeout(function() {
        window.location.href = "login.php?timeout=1";
    }, 600000); // 10 minutes in ms
}

// Detect user activity
window.onload = resetTimer;
window.addEventListener('mousemove', resetTimer, false);
window.addEventListener('keypress', resetTimer, false);
window.addEventListener('click', resetTimer, false);
window.addEventListener('scroll', resetTimer, false);
window.addEventListener('touchstart', resetTimer, false);
window.addEventListener('touchmove', resetTimer, false);
