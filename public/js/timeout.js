let timeout;
const loginUrl = new URL('../login.php?timeout=1', document.currentScript.src).href;

// Function to reset timer
function resetTimer() {
    clearTimeout(timeout);

    timeout = setTimeout(function() {
        window.location.href = loginUrl;
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
