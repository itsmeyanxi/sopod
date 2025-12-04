// // 🔥 Session Timeout & Auto Logout Handler
// (function() {
//     let inactivityTimer;
//     const TIMEOUT_DURATION = 10 * 60 * 1000; // 10 minutes in milliseconds

//     // Function to reset the inactivity timer
//     function resetTimer() {
//         clearTimeout(inactivityTimer);
//         inactivityTimer = setTimeout(logoutUser, TIMEOUT_DURATION);
//     }

//     // Function to logout user
//     function logoutUser() {
//         // Show a message before redirecting
//         alert('Your session has expired due to inactivity. You will be logged out.');
        
//         // Create a form and submit logout
//         const form = document.createElement('form');
//         form.method = 'POST';
//         form.action = '/logout';
        
//         const csrfToken = document.querySelector('meta[name="csrf-token"]');
//         if (csrfToken) {
//             const input = document.createElement('input');
//             input.type = 'hidden';
//             input.name = '_token';
//             input.value = csrfToken.content;
//             form.appendChild(input);
//         }
        
//         document.body.appendChild(form);
//         form.submit();
//     }

//     // Events that indicate user activity
//     const activityEvents = [
//         'mousedown',
//         'mousemove',
//         'keypress',
//         'scroll',
//         'touchstart',
//         'click'
//     ];

//     // Add event listeners for user activity
//     activityEvents.forEach(event => {
//         document.addEventListener(event, resetTimer, true);
//     });

//     // Start the timer when page loads
//     resetTimer();

//     // 🔥 Handle tab/window close - logout immediately
//     window.addEventListener('beforeunload', function(e) {
//         // Send logout beacon when tab closes
//         const csrfToken = document.querySelector('meta[name="csrf-token"]');
//         if (csrfToken) {
//             const formData = new FormData();
//             formData.append('_token', csrfToken.content);
//             navigator.sendBeacon('/logout', formData);
//         }
//     });

//     // 🔥 Check session status every minute
//     setInterval(function() {
//         fetch('/check-session', {
//             method: 'GET',
//             headers: {
//                 'X-Requested-With': 'XMLHttpRequest',
//                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
//             }
//         })
//         .then(response => response.json())
//         .then(data => {
//             if (data.expired) {
//                 alert('Your session has expired. You will be redirected to login.');
//                 window.location.href = '/login';
//             }
//         })
//         .catch(error => {
//             console.error('Session check failed:', error);
//         });
//     }, 60000); // Check every 1 minute

// })();