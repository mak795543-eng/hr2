  // Initialize lucide icons
  lucide.createIcons();
  
  // Check if mobile view
  function isMobileView() {
    return window.innerWidth < 768; // Tailwind's md breakpoint
  }

  // Toggle sidebar function
  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarLogo = document.getElementById('sidebar-logo');
    const sonlyLogo = document.getElementById('sonly');
    
    if (isMobileView()) {
      // Mobile behavior - toggle visibility
      if (sidebar.classList.contains('translate-x-0')) {
        // Closing sidebar on mobile
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
      } else {
        // Opening sidebar on mobile
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
      }
    } else {
      // Desktop behavior - toggle between expanded/collapsed
      const isCollapsed = sidebar.classList.toggle('w-64');
      sidebar.classList.toggle('w-25', !isCollapsed);
      localStorage.setItem('sidebarCollapsed', !isCollapsed);
      
      // Update text visibility based on collapsed state
      document.querySelectorAll('.sidebar-text').forEach(text => {
        text.classList.toggle('hidden', !isCollapsed);
      });
      
      // Toggle logos based on collapsed state
      if (sidebar.classList.contains('w-25')) {
        sidebarLogo.classList.add('hidden');
        sonlyLogo.classList.remove('hidden');
      } else {
        sidebarLogo.classList.remove('hidden');
        sonlyLogo.classList.add('hidden');
      }
    }
    
    // Update dropdown indicators
    updateDropdownIndicators();
  }

  // Update dropdown indicators
  function updateDropdownIndicators() {
    const sidebar = document.getElementById('sidebar');
    const isCollapsed = sidebar.classList.contains('w-25') && !isMobileView();
    const dropdownIcons = document.querySelectorAll('.dropdown-icon');
    
    dropdownIcons.forEach(icon => {
      if (isCollapsed) {
        const isOpen = icon.closest('.collapse').querySelector('input[type="checkbox"]').checked;
        icon.setAttribute('data-lucide', isOpen ? 'minus' : 'plus');
      } else {
        const isOpen = icon.closest('.collapse').querySelector('input[type="checkbox"]').checked;
        icon.setAttribute('data-lucide', isOpen ? 'chevron-down' : 'chevron-right');
      }
      lucide.createIcon(icon);
    });
  }

  // Handle window resize
  function handleResize() {
    const sidebar = document.getElementById('sidebar');
    const sidebarLogo = document.getElementById('sidebar-logo');
    const sonlyLogo = document.getElementById('sonly');
    
    if (isMobileView()) {
      // On mobile, ensure proper transform classes and show full logo
      if (!sidebar.classList.contains('translate-x-0')) {
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
      }
      sidebarLogo.classList.remove('hidden');
      sonlyLogo.classList.add('hidden');
    } else {
      // On desktop, apply the saved collapsed state
      const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
      sidebar.classList.remove('-translate-x-full', 'translate-x-0');
      sidebar.classList.toggle('w-64', !isCollapsed);
      sidebar.classList.toggle('w-25', isCollapsed);
      
      document.querySelectorAll('.sidebar-text').forEach(text => {
        text.classList.toggle('hidden', isCollapsed);
      });
      
      // Toggle logos based on collapsed state
      if (isCollapsed) {
        sidebarLogo.classList.add('hidden');
        sonlyLogo.classList.remove('hidden');
      } else {
        sidebarLogo.classList.remove('hidden');
        sonlyLogo.classList.add('hidden');
      }
    }
    
    updateDropdownIndicators();
  }

  // Initialize sidebar
  function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarLogo = document.getElementById('sidebar-logo');
    const sonlyLogo = document.getElementById('sonly');
    
    if (isMobileView()) {
      // Start hidden on mobile with full logo
      sidebar.classList.add('-translate-x-full');
      sidebarLogo.classList.remove('hidden');
      sonlyLogo.classList.add('hidden');
    } else {
      // Start with saved state on desktop
      const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
      sidebar.classList.add(isCollapsed ? 'w-25' : 'w-64');
      
      document.querySelectorAll('.sidebar-text').forEach(text => {
        text.classList.toggle('hidden', isCollapsed);
      });
      
      // Toggle logos based on collapsed state
      if (isCollapsed) {
        sidebarLogo.classList.add('hidden');
        sonlyLogo.classList.remove('hidden');
      } else {
        sidebarLogo.classList.remove('hidden');
        sonlyLogo.classList.add('hidden');
      }
    }
    
    setTimeout(() => {
      sidebar.classList.add('loaded');
    }, 50);
    
    // Set up event listeners
    document.querySelectorAll('.collapse input[type="checkbox"]').forEach(checkbox => {
      checkbox.addEventListener('change', updateDropdownIndicators);
    });
    
    window.addEventListener('resize', handleResize);
    updateDropdownIndicators();
  }

 function displayPhilippineTime() {
  // Create a date object for Philippine time (UTC+8)
  const options = {
    timeZone: 'Asia/Manila',
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true
  };

  // Get the formatted date and time string
  const philippineDateTime = new Date().toLocaleString('en-PH', options);
  
  // Update the element with the current time
  const timeElement = document.getElementById('philippineTime');
  if (timeElement) {
    timeElement.textContent = philippineDateTime;
  }
}

// Initial call to display the time
displayPhilippineTime();

// Update the time every second
setInterval(displayPhilippineTime, 1000);

// Add event listener to ensure the function runs after DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  displayPhilippineTime();
});

  
  // Initialize when DOM loads
  document.addEventListener('DOMContentLoaded', initSidebar);


  lucide.createIcons();

      // Helper: Map event type to classes
      function getEventClass(type) {
        switch(type) {
          case 'Meeting': return 'bg-green-100 text-green-800';
          case 'Training': return 'bg-pink-100 text-pink-800';
          case 'Shift': default: return 'bg-blue-100 text-blue-800';
        }
      }

      // Helper: Get start of week (Monday)
      function getMonday(d) {
        d = new Date(d);
        var day = d.getDay(),
            diff = d.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(d.setDate(diff));
      }

      // State for current week
      let currentDate = new Date('2025-08-11'); // Initial week as in your design

      function updateMonthLabel() {
        const monthLabel = document.getElementById('month-label');
        const options = { month: 'long', year: 'numeric' };
        monthLabel.textContent = currentDate.toLocaleDateString(undefined, options);
      }

      function renderCalendarDays() {
        const monday = getMonday(currentDate);
        let html = '';
        for(let i=0; i<7; i++) {
          const d = new Date(monday);
          d.setDate(monday.getDate() + i);
          const dayName = d.toLocaleDateString(undefined, { weekday: 'short' });
          const dayNum = d.getDate();
          html += `<div>${dayName}<br><span class="text-lg font-bold">${dayNum}</span></div>`;
        }
        document.getElementById('calendar-days').innerHTML = html;
      }

      async function loadEvents() {
        const res = await fetch('events.php');
        const events = await res.json();

        // ========== Calendar Grid ==========
        const calendarGrid = document.getElementById('calendar-grid');
        calendarGrid.innerHTML = '';
        const monday = getMonday(currentDate);

        for(let i=0; i<7; i++) {
          const d = new Date(monday);
          d.setDate(monday.getDate() + i);
          const dateStr = d.toISOString().slice(0,10);
          const dayEvents = events.filter(ev => ev.event_date === dateStr);

          if (dayEvents.length === 0) {
            calendarGrid.innerHTML += `<div></div>`;
            continue;
          }
          if (dayEvents.length === 1) {
            const ev = dayEvents[0];
            calendarGrid.innerHTML += `
              <div class="${getEventClass(ev.type)} rounded-lg p-2">
                <p class="font-semibold">${ev.title}</p>
                <p class="text-xs">${ev.start_time.substring(0,5)} - ${ev.end_time.substring(0,5)}</p>
              </div>
            `;
          } else {
            let stack = '';
            for (const ev of dayEvents) {
              stack += `
                <div class="${getEventClass(ev.type)} rounded-lg p-2 mb-1">
                  <p class="font-semibold">${ev.title}</p>
                  <p class="text-xs">${ev.start_time.substring(0,5)} - ${ev.end_time.substring(0,5)}</p>
                </div>
              `;
            }
            calendarGrid.innerHTML += `<div class="space-y-2">${stack}</div>`;
          }
        }

        // ========== Upcoming Shifts ==========
        const today = new Date();
        const upcoming = events.filter(ev => new Date(ev.event_date) > today && ev.type === 'Shift')
                               .sort((a, b) => new Date(a.event_date) - new Date(b.event_date))
                               .slice(0, 5);
        const upcomingDiv = document.getElementById('upcoming-shifts');
        upcomingDiv.innerHTML = '';
        for (const ev of upcoming) {
          const d = new Date(ev.event_date);
          const options = { weekday: 'short', day: 'numeric', month: 'short' };
          const dateStr = d.toLocaleDateString(undefined, options);
          upcomingDiv.innerHTML += `
            <div class="flex items-center space-x-4 border rounded-lg p-3">
              <i data-lucide="clock" class="text-theme w-5 h-5"></i>
              <div>
                <p class="font-semibold">${dateStr}</p>
                <p class="text-sm text-gray-500">${ev.start_time.substring(0,5)} - ${ev.end_time.substring(0,5)} · ${ev.title}</p>
              </div>
            </div>
          `;
        }
        lucide.createIcons();
      }

      function renderAll() {
        updateMonthLabel();
        renderCalendarDays();
        loadEvents();
      }

      function prevWeek() {
        currentDate.setDate(currentDate.getDate() - 7);
        renderAll();
      }

      function nextWeek() {
        currentDate.setDate(currentDate.getDate() + 7);
        renderAll();
      }

      function goToToday() {
        currentDate = getMonday(new Date());
        renderAll();
      }

      // Initial render
      renderAll();