<script>
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

    if (!sidebar || !sidebarLogo || !sonlyLogo) {
      return;
    }

    if (isMobileView()) {
      sidebar.classList.add('-translate-x-full');
      sidebarLogo.classList.remove('hidden');
      sonlyLogo.classList.add('hidden');
    } else {
      const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
      sidebar.classList.add(isCollapsed ? 'w-25' : 'w-64');

      document.querySelectorAll('.sidebar-text').forEach(text => {
        text.classList.toggle('hidden', isCollapsed);
      });

      if (isCollapsed) {
        sidebarLogo.classList.add('hidden');
        sonlyLogo.classList.remove('hidden');
      } else {
        sidebarLogo.classList.remove('hidden');
        sonlyLogo.classList.add('hidden');
      }
    }

    const collapses = sidebar.querySelectorAll('.collapse');
    collapses.forEach((collapse, index) => {
      const checkbox = collapse.querySelector('input[type="checkbox"]');
      if (!checkbox) return;
      const titleSpan = collapse.querySelector('.collapse-title .sidebar-text');
      const baseKey = titleSpan ?
        titleSpan.textContent.trim().toLowerCase().replace(/\s+/g, '_') :
        'section_' + index;
      const storageKey = 'sidebarSection_' + baseKey;
      collapse.dataset.sidebarSectionKey = storageKey;
      const storedValue = localStorage.getItem(storageKey);
      if (storedValue === 'true') {
        checkbox.checked = true;
      } else if (storedValue === 'false') {
        checkbox.checked = false;
      }
      checkbox.addEventListener('change', () => {
        localStorage.setItem(storageKey, checkbox.checked ? 'true' : 'false');
        updateDropdownIndicators();
      });
    });

    const scrollContainer = document.getElementById('sidebar-scroll') || sidebar;
    if (scrollContainer) {
      const savedScroll = parseInt(localStorage.getItem('sidebarScrollTop') || '0', 10);
      if (!Number.isNaN(savedScroll)) {
        scrollContainer.scrollTop = savedScroll;
      }
      scrollContainer.addEventListener('scroll', () => {
        localStorage.setItem('sidebarScrollTop', String(scrollContainer.scrollTop));
      });
    }

    setTimeout(() => {
      sidebar.classList.add('loaded');
    }, 50);

    window.addEventListener('resize', handleResize);
    updateDropdownIndicators();
  }

  function setActiveSidebarLink() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    const currentPath = window.location.pathname.replace(/\/+$/, '');
    const links = sidebar.querySelectorAll('a[href]');
    let bestMatch = null;
    let bestLength = 0;

    links.forEach(link => {
      const href = link.getAttribute('href');
      if (!href || href.startsWith('http') || href.startsWith('mailto:') || href.startsWith('#')) {
        return;
      }
      const linkUrl = new URL(href, window.location.origin);
      const linkPath = linkUrl.pathname.replace(/\/+$/, '');
      const container = link.querySelector('div');
      if (container) {
        container.classList.remove('bg-blue-600/80', 'bg-blue-700/60', 'shadow-lg');
      } else {
        link.classList.remove('bg-blue-600/80', 'bg-blue-700/60', 'shadow-lg');
      }
      if (currentPath === linkPath || currentPath.endsWith(linkPath)) {
        if (linkPath.length > bestLength) {
          bestMatch = link;
          bestLength = linkPath.length;
        }
      }
    });

    if (bestMatch) {
      const activeContainer = bestMatch.querySelector('div');
      if (activeContainer) {
        activeContainer.classList.add('bg-blue-600/80', 'shadow-lg');
      } else {
        bestMatch.classList.add('bg-blue-600/80', 'shadow-lg');
      }
      const collapse = bestMatch.closest('.collapse');
      if (collapse) {
        const checkbox = collapse.querySelector('input[type="checkbox"]');
        if (checkbox && !checkbox.checked) {
          checkbox.checked = true;
          const key = collapse.dataset.sidebarSectionKey;
          if (key) {
            localStorage.setItem(key, 'true');
          }
        }
      }
    }
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
    initSidebar();
    setActiveSidebarLink();
  });
</script>