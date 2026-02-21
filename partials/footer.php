 <?php $base_url = getenv('APP_BASE_PATH') ?: '/index.php'; ?>
 <form id="logoutForm" method="POST" action="">
     <input type="hidden" name="logout" value="1">
 </form>
 <script>
     // Inactivity timeout in milliseconds (2 minutes = 120000 ms)
     let timeoutDuration = 120000;
     let timeoutId;



     // Reset the timer whenever the user interacts
     function resetTimer() {
         clearTimeout(timeoutId);
         timeoutId = setTimeout(logout, timeoutDuration);
     }

     // Function to logout by submitting the form
     function logout() {
         document.getElementById('logoutForm').submit();
     }

     // Listen to user activity events
     window.addEventListener('mousemove', resetTimer);
     window.addEventListener('keydown', resetTimer);
     window.addEventListener('mousedown', resetTimer);
     window.addEventListener('touchstart', resetTimer);
     window.addEventListener('scroll', resetTimer);

     // Start the timer initially
     resetTimer();
 </script>
 <script>
     lucide.createIcons();
 </script>
 <script src="<?php echo $base_url; ?>soliera.js"></script>
 <script src="<?php echo $base_url; ?>sidebar.js"></script>
 </body>

 </html>