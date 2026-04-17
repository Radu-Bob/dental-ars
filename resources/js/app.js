import './bootstrap';
import '../css/app.css';
// Ensure flatpickr is installed via npm and imported:
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css"; 
import '@fortawesome/fontawesome-free/css/all.min.css';
import.meta.glob([
    '../fonts/**'
]);

// Function to format time as HH:MM
function formatTime(date) {
    const h = String(date.getHours()).padStart(2, '0');
    const m = String(date.getMinutes()).padStart(2, '0');
    return `${h}:${m}`;
}

document.addEventListener('DOMContentLoaded', function() {
    
    flatpickr(".flatpickr-date", {
        // Set the default display and submission format to YYYY-MM-DD
        dateFormat: "Y-m-d", 
        
        // This makes the picker user-friendly (we'll ignore the alt-input confusion)
        allowInput: true,
        
        // Ensures the visible input is styled correctly
        altInputClass: "form-control",
        
        // If you were using the altInput: true feature, you'd add it here, 
        // but for YYYY-MM-DD, the simple dateFormat setting should suffice.
    });

    // *** NEW: Flatpickr initialization for the Dashboard Calendar ***
    flatpickr("#dashboard-calendar", {
        inline: true, 
        static: true, 
        className: 'clinic-custom-calendar', // Changed to match our naming convention
        shorthandCurrentMonth: true,
        disableMobile: "true",
    });
//
    const clockDisplay = document.getElementById('clock-display');
    const clockToggle = document.getElementById('clock-toggle');
    const toggleIcon = document.getElementById('toggle-icon');
    
    // Safety check: exit if elements aren't found
    if (!clockDisplay || !clockToggle || !toggleIcon) {
        return; 
    }
    
    // Check local storage for initial state, defaults to ON (true)
    // NOTE: I've also slightly reduced the update interval to a full minute for better performance
    let isClockOn = localStorage.getItem('digitalClockState') !== 'off';
    let clockInterval;

    function updateClockDisplay() {
        const now = new Date(); // Define 'now' here so we can use it for both time and date
        
        if (isClockOn) {
            clockDisplay.textContent = formatTime(now);
            clockDisplay.classList.remove('clock-off');
            clockDisplay.classList.add('clock-on');
            
            // --- NEW: Update the hover date (title) ---
            const dateString = now.toLocaleDateString('en-GB', { 
                weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' 
            });
            // We target the parent 'digital-clock' div to ensure the hover works on the whole frame
            const clockFrame = document.getElementById('digital-clock');
            if (clockFrame) clockFrame.setAttribute('title', dateString);
            // ------------------------------------------

            toggleIcon.classList.remove('fa-toggle-off');
            toggleIcon.classList.add('fa-toggle-on'); 
            
        } else {
            clockDisplay.textContent = '00:00'; 
            clockDisplay.classList.remove('clock-on');
            clockDisplay.classList.add('clock-off');
            
            // Optional: Remove the title when the clock is off
            const clockFrame = document.getElementById('digital-clock');
            if (clockFrame) clockFrame.removeAttribute('title');

            toggleIcon.classList.remove('fa-toggle-on');
            toggleIcon.classList.add('fa-toggle-off'); 
        }
    }

    function toggleClock() {
        isClockOn = !isClockOn;
        localStorage.setItem('digitalClockState', isClockOn ? 'on' : 'off');
        
        if (isClockOn) {
            // Start interval only if it was not running
            if (!clockInterval) {
                // Update every minute (60,000 milliseconds)
                clockInterval = setInterval(updateClockDisplay, 60000); 
            }
        } else {
            // Stop the interval and clear display
            clearInterval(clockInterval);
            clockInterval = null;
        }
        updateClockDisplay();
    }

    // Initialize: set the starting state and start the interval if needed
    updateClockDisplay();
    // Start interval only if clock is meant to be on
    if (isClockOn) {
        clockInterval = setInterval(updateClockDisplay, 60000);
    }

    // Attach click listener to the button
    clockToggle.addEventListener('click', toggleClock);
    
    
    
});